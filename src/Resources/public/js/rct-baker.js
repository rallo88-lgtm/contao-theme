/**
 * RCT Baker Street Background
 * Canvas 2D — Victorian fog scene with flickering gas lamps
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

  const canvas = document.getElementById('rct-baker-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  // ── Lamp positions (fractions of source image w/h) ───────────
  // Measured from the baker-street.webp original
  const LAMPS = [
    { fx: 0.190, fy: 0.410, ph: 0.00, sz: 1.00 },  // left, tall, closest
    { fx: 0.680, fy: 0.418, ph: 3.07, sz: 0.88 },  // right, tall, foreground
    { fx: 0.430, fy: 0.488, ph: 2.14, sz: 0.52 },  // centre-left, mid
    { fx: 0.545, fy: 0.552, ph: 4.31, sz: 0.35 },  // centre, far
    { fx: 0.600, fy: 0.568, ph: 1.73, sz: 0.22 },  // furthest
  ];

  // ── Fog layer descriptors ─────────────────────────────────────
  const FOG_N = 18;
  const fogs = Array.from({ length: FOG_N }, () => ({
    x:  Math.random(),
    y:  0.45 + Math.random() * 0.45,   // street level and below
    rx: 0.28 + Math.random() * 0.48,
    ry: 0.06 + Math.random() * 0.22,
    ph: Math.random() * Math.PI * 2,
    sp: (0.00015 + Math.random() * 0.00040) * (Math.random() < 0.5 ? 1 : -1),
    al: 0.10 + Math.random() * 0.18,   // much higher alpha → thick
  }));

  // ── Background image ──────────────────────────────────────────
  const img = new Image();
  img.src = '/bundles/rct/img/baker-street.webp';
  let imgLoaded = false;
  img.onload = () => { imgLoaded = true; };

  let W = 0, H = 0;

  // ── "cover" slice: which region of img fills the canvas ───────
  function coverSlice() {
    const iw = img.naturalWidth, ih = img.naturalHeight;
    const ia = iw / ih, ca = W / H;
    let sw, sh, sx, sy;
    if (ca > ia) {
      sw = iw; sh = iw / ca;
      sx = 0;  sy = (ih - sh) * 0.5;
    } else {
      sh = ih; sw = ih * ca;
      sx = (iw - sw) * 0.5; sy = 0;
    }
    return { sx, sy, sw, sh };
  }

  // Image-fraction → canvas pixel (accounting for cover crop)
  function lampXY(lamp) {
    if (!imgLoaded) return { x: lamp.fx * W, y: lamp.fy * H };
    const { sx, sy, sw, sh } = coverSlice();
    return {
      x: (lamp.fx * img.naturalWidth  - sx) / sw * W,
      y: (lamp.fy * img.naturalHeight - sy) / sh * H,
    };
  }

  // ── Flicker: three overlapping sinusoids → natural flame feel ─
  function flicker(t, lamp) {
    return 0.72
      + 0.16 * Math.sin(t *  7.3  + lamp.ph)
      + 0.08 * Math.sin(t * 17.11 + lamp.ph * 1.41)
      + 0.04 * Math.sin(t * 31.97 + lamp.ph * 0.63);
  }

  // ── Render ────────────────────────────────────────────────────
  function frame(ts) {
    const t = ts * 0.001;

    ctx.clearRect(0, 0, W, H);

    // 1 ─ Background image
    if (imgLoaded) {
      const { sx, sy, sw, sh } = coverSlice();
      ctx.drawImage(img, sx, sy, sw, sh, 0, 0, W, H);
    } else {
      ctx.fillStyle = '#080608';
      ctx.fillRect(0, 0, W, H);
    }

    // 2 ─ Fog layers (source-over, very low alpha each)
    ctx.globalCompositeOperation = 'source-over';
    for (const fog of fogs) {
      const cx = ((fog.x + t * fog.sp) % 1.4 - 0.2) * W;
      const cy = fog.y * H;
      const rx = fog.rx * W;
      const ry = fog.ry * H;
      const a  = fog.al * (0.55 + 0.45 * Math.sin(t * 0.38 + fog.ph));

      ctx.save();
      ctx.translate(cx, cy);
      ctx.scale(1, ry / rx);                          // circle → ellipse
      const g = ctx.createRadialGradient(0, 0, 0, 0, 0, rx);
      g.addColorStop(0,    `rgba(235, 228, 210, ${a})`);         // warm white centre
      g.addColorStop(0.55, `rgba(215, 205, 180, ${a * 0.55})`);  // soft cream mid
      g.addColorStop(1,    'rgba(200, 190, 160, 0)');
      ctx.fillStyle = g;
      ctx.beginPath();
      ctx.arc(0, 0, rx, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }

    // 3 ─ Lamp halos (screen blend → brightens without washing out)
    ctx.globalCompositeOperation = 'screen';
    for (const lamp of LAMPS) {
      const { x, y } = lampXY(lamp);
      const fl = flicker(t, lamp);
      const r  = Math.min(W, H) * 0.19 * lamp.sz * fl;
      const a  = 0.52 * fl * lamp.sz;

      const g = ctx.createRadialGradient(x, y, 0, x, y, r);
      g.addColorStop(0,    `rgba(255, 210,  70, ${a})`);
      g.addColorStop(0.22, `rgba(240, 150,  28, ${a * 0.55})`);
      g.addColorStop(0.55, `rgba(180,  75,   8, ${a * 0.16})`);
      g.addColorStop(1,    'rgba(0, 0, 0, 0)');
      ctx.fillStyle = g;
      ctx.fillRect(0, 0, W, H);
    }

    // 4 ─ Vignette (source-over)
    ctx.globalCompositeOperation = 'source-over';
    const vig = ctx.createRadialGradient(W * 0.5, H * 0.48, H * 0.08,
                                         W * 0.5, H * 0.48, H * 0.92);
    vig.addColorStop(0, 'rgba(0,0,0,0)');
    vig.addColorStop(1, 'rgba(0,0,0,0.68)');
    ctx.fillStyle = vig;
    ctx.fillRect(0, 0, W, H);

    requestAnimationFrame(frame);
  }

  // ── Resize ────────────────────────────────────────────────────
  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  const _origApply = window.applyTheme;
  window.applyTheme = function (theme, withFade) {
    if (theme === 'baker-street') resize();
    if (_origApply) _origApply(theme, withFade);
  };

  window.addEventListener('resize', resize);
  resize();
  requestAnimationFrame(frame);

  }); // DOMContentLoaded
})();
