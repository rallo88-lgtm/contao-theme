/**
 * RCT Baker Street — night sky, dynamically injected before #gradient-canvas
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
    al:  0.20 + Math.random() * 0.25,
  }));

  const FRAME_INTERVAL = 1000 / 30; // 30fps cap
  let lastTs  = 0;
  let rafId   = null;
  let W = 0, H = 0;
  let canvas = null, ctx = null;

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

    // Night sky — dark blue, lighter towards upper middle
    const sky = ctx.createLinearGradient(0, 0, 0, H * 0.65);
    sky.addColorStop(0,   '#0b1428');
    sky.addColorStop(0.4, '#0a1222');
    sky.addColorStop(1,   '#070b18');
    ctx.fillStyle = sky;
    ctx.fillRect(0, 0, W, H);

    // Drifting cloud wisps — blue-grey tones
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
      g.addColorStop(0,    `rgba(20, 32, 58, ${a})`);
      g.addColorStop(0.5,  `rgba(14, 24, 44, ${a * 0.55})`);
      g.addColorStop(1,    'rgba(8, 12, 24, 0)');
      ctx.fillStyle = g;
      ctx.beginPath();
      ctx.arc(0, 0, rx, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }
  }

  function inject() {
    const gradCanvas = document.getElementById('gradient-canvas');
    if (!document.getElementById('rct-sky-canvas')) {
      const c = document.createElement('canvas');
      c.id = 'rct-sky-canvas';
      gradCanvas.parentNode.insertBefore(c, gradCanvas);
      canvas = c;
      ctx = c.getContext('2d');
    }
    if (!document.getElementById('rct-baker-bg')) {
      const bg = document.createElement('div');
      bg.id = 'rct-baker-bg';
      gradCanvas.parentNode.insertBefore(bg, gradCanvas);
    }
  }

  function remove() {
    ['rct-sky-canvas', 'rct-baker-bg'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.remove();
    });
    canvas = null;
    ctx = null;
  }

  function start() {
    if (rafId) return;
    inject();
    resize();
    rafId = requestAnimationFrame(frame);
  }

  function stop() {
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    remove();
  }

  function check() {
    const theme = document.documentElement.getAttribute('data-theme');
    if (theme === 'baker-street' && !document.hidden) start(); else stop();
  }

  document.addEventListener('DOMContentLoaded', function () {
    window.addEventListener('resize', resize);
    document.addEventListener('visibilitychange', check);
    check();
    new MutationObserver(check).observe(
      document.documentElement,
      { attributes: true, attributeFilter: ['data-theme'] }
    );
  });
})();
