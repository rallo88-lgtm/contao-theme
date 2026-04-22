/**
 * RCT Baker Street — night sky animation on #rct-sky-canvas
 * Dark Victorian night sky with slowly drifting clouds.
 */
(function () {
  'use strict';

  const CLOUD_N = 8;
  const clouds = Array.from({ length: CLOUD_N }, (_, i) => ({
    x:   Math.random(),
    y:   0.05 + Math.random() * 0.50,
    rx:  0.15 + Math.random() * 0.30,
    ry:  0.04 + Math.random() * 0.10,
    ph:  Math.random() * Math.PI * 2,
    sp:  (0.00008 + Math.random() * 0.00015) * (i % 2 === 0 ? 1 : -1),
    al:  0.18 + Math.random() * 0.22,
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

    // Night sky gradient — deep indigo at top, near-black at bottom
    const sky = ctx.createLinearGradient(0, 0, 0, H * 0.65);
    sky.addColorStop(0,    '#06060f');
    sky.addColorStop(0.5,  '#0a0a1a');
    sky.addColorStop(1,    '#080810');
    ctx.fillStyle = sky;
    ctx.fillRect(0, 0, W, H);

    // Drifting cloud wisps
    for (const cloud of clouds) {
      const cx = ((cloud.x + t * cloud.sp) % 1.3 - 0.15) * W;
      const cy = cloud.y * H;
      const rx = cloud.rx * W;
      const ry = cloud.ry * H;
      const a  = cloud.al * (0.6 + 0.4 * Math.sin(t * 0.12 + cloud.ph));

      ctx.save();
      ctx.translate(cx, cy);
      ctx.scale(1, ry / rx);
      const g = ctx.createRadialGradient(0, 0, 0, 0, 0, rx);
      g.addColorStop(0,    `rgba(18, 20, 38, ${a})`);
      g.addColorStop(0.5,  `rgba(14, 16, 30, ${a * 0.6})`);
      g.addColorStop(1,    'rgba(8, 8, 16, 0)');
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
    canvas = document.getElementById('rct-sky-canvas');
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
