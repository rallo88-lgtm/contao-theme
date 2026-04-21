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

  // ── Lamp positions as CANVAS fractions (fx = x/W, fy = y/H) ──
  // Calibrated from live browser output
  const LAMPS = [
    { fx: 0.449, fy: 0.368, ph: 0.00, sz: 1.00 },  // left foreground
    { fx: 0.625, fy: 0.358, ph: 3.07, sz: 0.88 },  // right foreground
    { fx: 0.505, fy: 0.441, ph: 2.14, sz: 0.52 },  // centre-left, mid
    { fx: 0.547, fy: 0.480, ph: 4.31, sz: 0.28 },  // centre, far
    { fx: 0.592, fy: 0.468, ph: 1.73, sz: 0.22 },  // centre-right, far
  ];

  // ── Fog layer descriptors ─────────────────────────────────────
  const FOG_N = 16;
  const fogs = Array.from({ length: FOG_N }, () => ({
    x:  Math.random(),
    y:  0.40 + Math.random() * 0.52,
    rx: 0.28 + Math.random() * 0.50,
    ry: 0.05 + Math.random() * 0.20,
    ph: Math.random() * Math.PI * 2,
    sp: (0.00010 + Math.random() * 0.00025) * (Math.random() < 0.5 ? 1 : -1),
    al: 0.07 + Math.random() * 0.08,   // much thinner — subtle atmosphere
  }));

  // ── Background image ──────────────────────────────────────────
  const img = new Image();
  img.src = '/bundles/rct/img/baker-street.webp';
  let imgLoaded = false;
  img.onload = () => { imgLoaded = true; };

  let W = 0, H = 0;

  // ── "cover" slice for background image ───────────────────────
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

  // Lamp canvas position — direct canvas fractions, no cover mapping
  function lampXY(lamp) {
    return { x: lamp.fx * W, y: lamp.fy * H };
  }

  // ── Flicker: slow overlapping sinusoids → gentle gas lamp feel ─
  function flicker(t, lamp) {
    return 0.80
      + 0.10 * Math.sin(t *  1.7  + lamp.ph)
      + 0.06 * Math.sin(t *  4.1  + lamp.ph * 1.41)
      + 0.04 * Math.sin(t *  8.7  + lamp.ph * 0.63);
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

    // 2 ─ Lamp halos (screen blend → brightens without washing out)
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

    // 3 ─ Vignette
    ctx.globalCompositeOperation = 'source-over';
    const vig = ctx.createRadialGradient(W * 0.5, H * 0.48, H * 0.08,
                                         W * 0.5, H * 0.48, H * 0.92);
    vig.addColorStop(0, 'rgba(0,0,0,0)');
    vig.addColorStop(1, 'rgba(0,0,0,0.60)');
    ctx.fillStyle = vig;
    ctx.fillRect(0, 0, W, H);

    // 4 ─ Fog layers AFTER vignette so white stays visible
    ctx.globalCompositeOperation = 'source-over';
    for (const fog of fogs) {
      const cx = ((fog.x + t * fog.sp) % 1.4 - 0.2) * W;
      const cy = fog.y * H;
      const rx = fog.rx * W;
      const ry = fog.ry * H;
      const a  = fog.al * (0.55 + 0.45 * Math.sin(t * 0.35 + fog.ph));

      ctx.save();
      ctx.translate(cx, cy);
      ctx.scale(1, ry / rx);
      const g = ctx.createRadialGradient(0, 0, 0, 0, 0, rx);
      g.addColorStop(0,    `rgba(210, 218, 228, ${a})`);
      g.addColorStop(0.55, `rgba(210, 218, 228, ${a * 0.45})`);
      g.addColorStop(1,    'rgba(210, 218, 228, 0)');
      ctx.fillStyle = g;
      ctx.beginPath();
      ctx.arc(0, 0, rx, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }

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
