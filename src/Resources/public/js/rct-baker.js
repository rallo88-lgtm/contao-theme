/**
 * RCT Baker Street — lamp halos, moon glow and fog on #rct-effects-canvas
 * Canvas renders with mix-blend-mode: screen (set via CSS) against the bg image.
 */
(function () {
  'use strict';

  // Lamp positions as canvas fractions, calibrated from 2560×1440 screenshot
  const LAMPS = [
    { fx: 0.437, fy: 0.376, ph: 0.00, sz: 1.00 },
    { fx: 0.530, fy: 0.472, ph: 3.07, sz: 0.45 },
    { fx: 0.566, fy: 0.517, ph: 2.14, sz: 0.33 },
    { fx: 0.644, fy: 0.492, ph: 1.73, sz: 0.41 },
    { fx: 0.673, fy: 0.391, ph: 4.31, sz: 0.89 },
  ];

  const MOON = { fx: 0.611, fy: 0.322 };

  const FOG_N = 14;
  const fogs = Array.from({ length: FOG_N }, () => ({
    x:  Math.random(),
    y:  0.55 + Math.random() * 0.38,
    rx: 0.30 + Math.random() * 0.55,
    ry: 0.06 + Math.random() * 0.22,
    ph: Math.random() * Math.PI * 2,
    sp: (0.00035 + Math.random() * 0.00070) * (Math.random() < 0.5 ? 1 : -1),
    al: 0.13 + Math.random() * 0.15,
  }));

  function flicker(t, lamp) {
    return 0.80
      + 0.10 * Math.sin(t * 1.7  + lamp.ph)
      + 0.06 * Math.sin(t * 4.1  + lamp.ph * 1.41)
      + 0.04 * Math.sin(t * 8.7  + lamp.ph * 0.63);
  }

  const FRAME_INTERVAL = 1000 / 30; // 30fps cap
  let lastTs  = 0;
  let rafId   = null;
  let W = 0, H = 0;
  let canvas, ctx;

  function resize() {
    if (!canvas) return;
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  function frame(ts) {
    rafId = requestAnimationFrame(frame);
    if (ts - lastTs < FRAME_INTERVAL) return;
    lastTs = ts;

    const t = ts * 0.001;
    ctx.clearRect(0, 0, W, H);

    // 1 — Lamp halos (screen-blended via CSS mix-blend-mode)
    for (const lamp of LAMPS) {
      const x  = lamp.fx * W;
      const y  = lamp.fy * H;
      const fl = flicker(t, lamp);
      const r  = Math.min(W, H) * 0.18 * lamp.sz * fl;
      const a  = 0.55 * fl * lamp.sz;

      const g = ctx.createRadialGradient(x, y, 0, x, y, r);
      g.addColorStop(0,    `rgba(255, 210,  70, ${a})`);
      g.addColorStop(0.22, `rgba(240, 150,  28, ${a * 0.55})`);
      g.addColorStop(0.55, `rgba(180,  75,   8, ${a * 0.16})`);
      g.addColorStop(1,    'rgba(0, 0, 0, 0)');
      ctx.fillStyle = g;
      ctx.fillRect(0, 0, W, H);
    }

    // 2 — Moon glow (subtle silver, no flicker)
    const mx = MOON.fx * W;
    const my = MOON.fy * H;
    const mr = Math.min(W, H) * 0.07;
    const mg = ctx.createRadialGradient(mx, my, 0, mx, my, mr);
    mg.addColorStop(0,    'rgba(220, 230, 255, 0.30)');
    mg.addColorStop(0.40, 'rgba(180, 200, 240, 0.12)');
    mg.addColorStop(1,    'rgba(0, 0, 0, 0)');
    ctx.fillStyle = mg;
    ctx.fillRect(0, 0, W, H);

    // 3 — Fog layers
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
  }

  function start() {
    if (rafId) return;
    resize();
    rafId = requestAnimationFrame(frame);
  }

  function stop() {
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    if (ctx) ctx.clearRect(0, 0, W, H);
  }

  function check() {
    const theme = document.documentElement.getAttribute('data-theme');
    if (theme === 'baker-street' && !document.hidden) start(); else stop();
  }

  document.addEventListener('DOMContentLoaded', function () {
    canvas = document.getElementById('rct-effects-canvas');
    if (!canvas) return;
    ctx = canvas.getContext('2d');

    window.addEventListener('resize', resize);
    document.addEventListener('visibilitychange', check);
    check();

    new MutationObserver(check).observe(
      document.documentElement,
      { attributes: true, attributeFilter: ['data-theme'] }
    );
  });
})();
