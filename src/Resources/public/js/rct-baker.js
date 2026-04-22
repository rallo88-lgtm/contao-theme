/**
 * RCT Baker Street — fog overlay on #rct-dots-canvas
 */
(function () {
  'use strict';

  const FOG_N = 22;
  const fogs = Array.from({ length: FOG_N }, () => ({
    x:  Math.random(),
    y:  0.38 + Math.random() * 0.54,
    rx: 0.30 + Math.random() * 0.55,
    ry: 0.06 + Math.random() * 0.22,
    ph: Math.random() * Math.PI * 2,
    sp: (0.00035 + Math.random() * 0.00070) * (Math.random() < 0.5 ? 1 : -1),
    al: 0.13 + Math.random() * 0.15,
  }));

  let rafId = null;
  let W = 0, H = 0;
  let canvas, ctx;

  function resize() {
    if (!canvas) return;
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  function frame(ts) {
    const t = ts * 0.001;
    ctx.clearRect(0, 0, W, H);

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

    rafId = requestAnimationFrame(frame);
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
    if (theme === 'baker-street') start(); else stop();
  }

  document.addEventListener('DOMContentLoaded', function () {
    canvas = document.getElementById('rct-effects-canvas');
    if (!canvas) return;
    ctx = canvas.getContext('2d');

    window.addEventListener('resize', resize);
    check();

    new MutationObserver(check).observe(
      document.documentElement,
      { attributes: true, attributeFilter: ['data-theme'] }
    );
  });
})();
