/**
 * RCT Dragon Scale Background
 * Canvas 2D — animated dragon skin with iridescent hover effect
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

  const canvas = document.getElementById('rct-dragon-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  // ── Tuning ───────────────────────────────────────────────────
  const SW    = 40;    // base scale width
  const SH    = 58;    // base scale total height
  const VSTEP = 27;    // vertical row step
  const HSTEP = 40;    // horizontal step
  const HR    = 260;   // hover radius (px)
  const LMAX  = 1.4;   // max lift
  const BA    = 1.5;   // breathing amplitude px
  const BS    = 0.44;  // breathing speed rad/s

  // ── State ────────────────────────────────────────────────────
  let mx = -9999, my = -9999;
  let scales = [];
  let W = 0, H = 0;

  // ── Scale Shape ──────────────────────────────────────────────
  function scaleShape(w, h) {
    const hw = w * 0.5;
    ctx.beginPath();
    ctx.moveTo(0, h * 0.06);
    ctx.bezierCurveTo(-hw * 0.30, 0,         -hw * 0.84, 0,         -hw, h * 0.24);
    ctx.bezierCurveTo(-hw,        h * 0.56,   -hw * 0.50, h * 0.88,   0,  h      );
    ctx.bezierCurveTo( hw * 0.50, h * 0.88,    hw,        h * 0.56,   hw, h * 0.24);
    ctx.bezierCurveTo( hw * 0.84, 0,            hw * 0.30, 0,           0, h * 0.06);
    ctx.closePath();
  }

  // ── Draw One Scale ───────────────────────────────────────────
  function drawScale(s, t) {
    const lift    = s.lift;
    const sw      = SW * s.sz;
    const sh      = SH * s.sz;
    const breathe = Math.sin(t * BS + s.phase) * BA;
    const shimmer = 0.5 + 0.5 * Math.sin(t * 0.55 + s.phase * 0.8);
    const bright  = shimmer * 10 + lift * 45 + s.bodyLight;

    ctx.save();
    ctx.translate(s.cx + s.jx, s.cy + s.jy + breathe);
    ctx.rotate(s.rot);

    // Lift: float upward + foreshortening
    if (lift > 0.004) {
      ctx.translate(0, -lift * sh * 0.42);
      ctx.scale(1 + lift * 0.18, 1 - lift * 0.22);
    }

    // ① Shadow — drawn as oversized dark fill, no blur (fast)
    if (lift > 0.04) {
      const shadowAlpha = Math.min(0.7, lift * 0.55);
      scaleShape(sw + lift * 10, sh + lift * 12);
      ctx.fillStyle = `rgba(0, 0, 0, ${shadowAlpha})`;
      ctx.translate(0, lift * 10);
      scaleShape(sw + lift * 10, sh + lift * 12);
      ctx.fillStyle = `rgba(0, 0, 0, ${shadowAlpha})`;
      ctx.fill();
      ctx.translate(0, -lift * 10);
    }

    // ② Gold base fill
    const g = ctx.createLinearGradient(0, 0, 0, sh);
    g.addColorStop(0,    `hsl(${44 + s.hu}, 90%, ${Math.min(88, 64 + bright)}%)`);
    g.addColorStop(0.28, `hsl(${41 + s.hu}, 84%, ${Math.min(75, 51 + bright * 0.55)}%)`);
    g.addColorStop(0.62, `hsl(${38 + s.hu}, 79%, ${Math.min(60, 39 + bright * 0.28)}%)`);
    g.addColorStop(1,    `hsl(${34 + s.hu}, 70%, 22%)`);
    scaleShape(sw, sh);
    ctx.fillStyle = g;
    ctx.fill();

    // ③ Dark outline
    scaleShape(sw, sh);
    ctx.strokeStyle = `rgba(18, 7, 0, ${0.75 - lift * 0.25})`;
    ctx.lineWidth   = 1.4;
    ctx.stroke();

    // ④ Specular highlight arc
    const ha = 0.13 + shimmer * 0.12 + lift * 0.32;
    const hg = ctx.createLinearGradient(-sw * 0.33, 0, sw * 0.33, 0);
    hg.addColorStop(0,   'rgba(255, 248, 170, 0)');
    hg.addColorStop(0.5, `rgba(255, 248, 170, ${ha})`);
    hg.addColorStop(1,   'rgba(255, 248, 170, 0)');
    scaleShape(sw * 0.66, sh * 0.36);
    ctx.strokeStyle = hg;
    ctx.lineWidth   = 2.2;
    ctx.stroke();

    // ⑤ Iridescent edge when lifting
    if (lift > 0.05) {
      const hue = (165 + s.hu * 5 + t * 44) % 360;
      const a   = Math.min(1.0, lift);
      const ig  = ctx.createLinearGradient(-sw * 0.5, 0, sw * 0.5, 0);
      ig.addColorStop(0,    `hsla(${hue},              100%, 68%, 0)`);
      ig.addColorStop(0.2,  `hsla(${hue},              100%, 80%, ${a})`);
      ig.addColorStop(0.5,  `hsla(${(hue + 115) % 360}, 100%, 88%, ${a})`);
      ig.addColorStop(0.8,  `hsla(${(hue + 225) % 360}, 100%, 80%, ${a})`);
      ig.addColorStop(1,    `hsla(${(hue + 335) % 360}, 100%, 68%, 0)`);
      scaleShape(sw - 3, sh - 4);
      ctx.strokeStyle = ig;
      ctx.lineWidth   = 10;
      ctx.stroke();
    }

    ctx.restore();
  }

  // ── Build Grid ───────────────────────────────────────────────
  function build() {
    scales = [];
    const rows = Math.ceil(H / VSTEP) + 4;
    const cols = Math.ceil(W / HSTEP) + 4;
    const cx = W * 0.5, cy = H * 0.4;   // body "light source" center

    for (let r = 0; r < rows; r++) {
      for (let c = 0; c < cols; c++) {
        const xoff = (r & 1) ? HSTEP * 0.5 : 0;
        const bx = (c - 1) * HSTEP + xoff;
        const by = (r - 1) * VSTEP;

        // Body curvature: distance from center → darker at edges
        const dx = (bx - cx) / (W * 0.6);
        const dy = (by - cy) / (H * 0.6);
        const bodyLight = Math.max(-18, 12 - (dx*dx + dy*dy) * 28);

        scales.push({
          cx:        bx,
          cy:        by,
          jx:        (Math.random() - 0.5) * 5,   // position jitter
          jy:        (Math.random() - 0.5) * 4,
          rot:       (Math.random() - 0.5) * 0.12, // slight tilt ±~7°
          sz:        0.82 + Math.random() * 0.36,   // size 82–118%
          lift:      0,
          phase:     (r * 7 + c * 13) * 0.41,
          hu:        ((Math.random() * 16 - 8) | 0),
          bodyLight: bodyLight,
        });
      }
    }
  }

  // ── Render Loop ──────────────────────────────────────────────
  function frame(ts) {
    const t = ts * 0.001;

    ctx.fillStyle = '#1c0e02';
    ctx.fillRect(0, 0, W, H);

    for (const s of scales) {
      const dx  = (s.cx + s.jx) - mx;
      const dy  = (s.cy + s.jy + VSTEP * 0.5) - my;
      const d   = Math.sqrt(dx * dx + dy * dy);
      const tgt = d < HR ? LMAX * Math.pow(1 - d / HR, 1.8) : 0;
      s.lift   += (tgt - s.lift) * 0.18;
      drawScale(s, t);
    }

    requestAnimationFrame(frame);
  }

  // ── Resize ───────────────────────────────────────────────────
  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
    build();
  }

  window.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; });
  window.addEventListener('resize', resize);

  const _origApply = window.applyTheme;
  window.applyTheme = function (theme, withFade) {
    if (theme === 'dragon') resize();
    if (_origApply) _origApply(theme, withFade);
  };

  resize();
  requestAnimationFrame(frame);

  }); // DOMContentLoaded
})();
