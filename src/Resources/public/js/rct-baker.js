/**
 * RCT Baker Street — fog overlay on #rct-dots-canvas
 */
(function () {
  'use strict';

  const FOG_N = 16;
  const fogs = Array.from({ length: FOG_N }, () => ({
    x:  Math.random(),
    y:  0.40 + Math.random() * 0.52,
    rx: 0.28 + Math.random() * 0.50,
    ry: 0.05 + Math.random() * 0.20,
    ph: Math.random() * Math.PI * 2,
    sp: (0.00010 + Math.random() * 0.00025) * (Math.random() < 0.5 ? 1 : -1),
    al: 0.07 + Math.random() * 0.08,
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
    canvas = document.getElementById('rct-dots-canvas');
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
