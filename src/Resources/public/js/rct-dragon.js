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
  const SW    = 40;    // scale width
  const SH    = 58;    // scale total height (incl. hidden overlap)
  const VSTEP = 27;    // vertical row step — controls visible height
  const HSTEP = 40;    // horizontal step (= SW, no horizontal gap)
  const HR    = 170;   // hover radius (px)
  const LMAX  = 0.65;  // max lift
  const BA    = 1.5;   // breathing amplitude px
  const BS    = 0.44;  // breathing speed rad/s

  // ── State ────────────────────────────────────────────────────
  let mx = -9999, my = -9999;
  let scales = [];
  let W = 0, H = 0;

  // ── Scale Shape ──────────────────────────────────────────────
  // Gothic-shield, top-center at (0,0), bottom tip at (0, h)
  function scaleShape(w, h) {
    const hw = w * 0.5;
    ctx.beginPath();
    ctx.moveTo(0, h * 0.06);
    ctx.bezierCurveTo(-hw * 0.30, 0,        -hw * 0.84, 0,        -hw, h * 0.24);
    ctx.bezierCurveTo(-hw,        h * 0.56,  -hw * 0.50, h * 0.88,  0,  h      );
    ctx.bezierCurveTo( hw * 0.50, h * 0.88,   hw,        h * 0.56,  hw, h * 0.24);
    ctx.bezierCurveTo( hw * 0.84, 0,           hw * 0.30, 0,          0, h * 0.06);
    ctx.closePath();
  }

  // ── Draw One Scale ───────────────────────────────────────────
  function drawScale(s, t) {
    const lift    = s.lift;
    const breathe = Math.sin(t * BS + s.phase) * BA;
    const shimmer = 0.5 + 0.5 * Math.sin(t * 0.55 + s.phase * 0.8);
    const bright  = shimmer * 10 + lift * 26;

    ctx.save();
    ctx.translate(s.cx, s.cy + breathe);

    // Lift: float upward + subtle foreshortening
    if (lift > 0.004) {
      ctx.translate(0, -lift * SH * 0.20);
      ctx.scale(1 + lift * 0.08, 1 - lift * 0.10);
    }

    // Drop shadow for lifted scales
    if (lift > 0.03) {
      ctx.shadowColor   = 'rgba(0, 0, 0, 0.65)';
      ctx.shadowBlur    = lift * 20;
      ctx.shadowOffsetY = lift * 8;
    }

    // ① Gold base fill
    const g = ctx.createLinearGradient(0, 0, 0, SH);
    g.addColorStop(0,    `hsl(${44 + s.hu}, 90%, ${64 + bright}%)`);
    g.addColorStop(0.28, `hsl(${41 + s.hu}, 84%, ${51 + bright * 0.55}%)`);
    g.addColorStop(0.62, `hsl(${38 + s.hu}, 79%, ${39 + bright * 0.28}%)`);
    g.addColorStop(1,    `hsl(${34 + s.hu}, 70%, 24%)`);
    scaleShape(SW, SH);
    ctx.fillStyle = g;
    ctx.fill();

    ctx.shadowColor = 'transparent';
    ctx.shadowBlur  = 0;
    ctx.shadowOffsetY = 0;

    // ② Dark outline — gives the dark-gap illusion between scales
    scaleShape(SW, SH);
    ctx.strokeStyle = `rgba(18, 7, 0, ${0.75 - lift * 0.25})`;
    ctx.lineWidth   = 1.4;
    ctx.stroke();

    // ③ Specular highlight arc (top-center catches light)
    const ha = 0.13 + shimmer * 0.12 + lift * 0.32;
    const hg = ctx.createLinearGradient(-SW * 0.33, 0, SW * 0.33, 0);
    hg.addColorStop(0,   'rgba(255, 248, 170, 0)');
    hg.addColorStop(0.5, `rgba(255, 248, 170, ${ha})`);
    hg.addColorStop(1,   'rgba(255, 248, 170, 0)');
    scaleShape(SW * 0.66, SH * 0.36);
    ctx.strokeStyle = hg;
    ctx.lineWidth   = 2.2;
    ctx.stroke();

    // ④ Iridescent inner edge — only when lifting
    if (lift > 0.05) {
      const hue = (165 + s.hu * 5 + t * 44) % 360;
      const a   = Math.min(1.0, lift * 1.6);
      const ig  = ctx.createLinearGradient(-SW * 0.5, 0, SW * 0.5, 0);
      ig.addColorStop(0,    `hsla(${hue},            100%, 68%, 0)`);
      ig.addColorStop(0.2,  `hsla(${hue},            100%, 80%, ${a})`);
      ig.addColorStop(0.5,  `hsla(${(hue + 115) % 360}, 100%, 88%, ${a})`);
      ig.addColorStop(0.8,  `hsla(${(hue + 225) % 360}, 100%, 80%, ${a})`);
      ig.addColorStop(1,    `hsla(${(hue + 335) % 360}, 100%, 68%, 0)`);
      scaleShape(SW - 4, SH - 5);
      ctx.strokeStyle = ig;
      ctx.lineWidth   = 7;
      ctx.stroke();
    }

    ctx.restore();
  }

  // ── Build Grid ───────────────────────────────────────────────
  function build() {
    scales = [];
    const rows = Math.ceil(H / VSTEP) + 4;
    const cols = Math.ceil(W / HSTEP) + 4;
    for (let r = 0; r < rows; r++) {
      for (let c = 0; c < cols; c++) {
        const xoff = (r & 1) ? HSTEP * 0.5 : 0;
        scales.push({
          cx:    (c - 1) * HSTEP + xoff,
          cy:    (r - 1) * VSTEP,
          lift:  0,
          phase: (r * 7 + c * 13) * 0.41,
          hu:    ((Math.random() * 14 - 7) | 0),  // ±7° hue jitter
        });
      }
    }
  }

  // ── Render Loop ──────────────────────────────────────────────
  function frame(ts) {
    const t = ts * 0.001;

    // Dark body-skin base
    ctx.fillStyle = '#1c0e02';
    ctx.fillRect(0, 0, W, H);

    // Scales drawn top-row-first so lower rows render on top (correct overlap)
    for (const s of scales) {
      const dx  = s.cx - mx;
      const dy  = (s.cy + VSTEP * 0.5) - my;
      const d   = Math.sqrt(dx * dx + dy * dy);
      const tgt = d < HR ? LMAX * Math.pow(1 - d / HR, 1.8) : 0;
      s.lift   += (tgt - s.lift) * 0.10;
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

  // Hook into applyTheme so dragon activates/deactivates on theme switch
  const _origApply = window.applyTheme;
  window.applyTheme = function (theme, withFade) {
    if (theme === 'dragon') {
      resize();
    }
    if (_origApply) _origApply(theme, withFade);
  };

  resize();
  requestAnimationFrame(frame);

  }); // DOMContentLoaded
})();
