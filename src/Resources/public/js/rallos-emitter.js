/**
 * Rallo's Emitter 2.0 — Vanilla JS Particle-Emitter
 *
 * API:
 *   const e = new RalloEmitter('#container', { ... });
 *   e.start();    // läuft per Default schon
 *   e.stop();
 *   e.update({ particleSpeed: 20 });
 *   e.destroy();
 *
 * Performance-Notiz:
 *   Bewegung läuft über CSS-Transition auf transform (GPU-beschleunigt),
 *   nicht über animierte top/left wie im jQuery-Original. Spart Layout-Reflows.
 */
(function (global) {
  'use strict';

  const DEFAULTS = {
    particleColor:               ['#ffffff'],
    particleShape:               ['❄', '❅', '❆'],
    particleSpeed:               10,
    particleRotation:            false,
    particleRotationSpeed:       2,
    natuerlichesFallverhalten:   false,
    natuerlichesStartverhalten:  false,
    fadeout:                     false,
    particleDirection:           'down',  // down | up | left | right
    minSize:                     10,
    maxSize:                     20,
    newOn:                       500,     // Spawn-Intervall in ms
    poolSize:                    50,
    controlPanel:                false,
  };

  function injectStyles() {
    if (document.getElementById('rallos-emitter-styles')) return;
    const style = document.createElement('style');
    style.id = 'rallos-emitter-styles';
    style.textContent = `
      @keyframes rallos-emitter-rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
      @keyframes rallos-emitter-sway     { 0% { transform: translateX(0); } 50% { transform: translateX(60px); } 100% { transform: translateX(0); } }
      .rallos-emitter-particle .rallos-emitter-inner { display: inline-block; }
      .rallos-emitter-sway     { display: inline-block; }
    `;
    document.head.appendChild(style);
  }

  function buildPanel(opts) {
    if (document.getElementById('rallos-emitter-panel')) return;
    const wrap = document.createElement('div');
    wrap.id = 'rallos-emitter-panel';
    wrap.style.cssText = 'position:fixed;top:10px;right:10px;width:280px;padding:10px;background:rgba(0,0,0,0.85);color:#fff;z-index:20000;font:13px/1.4 sans-serif;border-radius:6px;';
    wrap.innerHTML = `
      <h4 style="margin:0 0 8px 0;font-size:14px;">Emitter Control Panel</h4>
      <label>Direction:
        <select data-ep="direction" style="width:100%;margin-bottom:5px;">
          <option value="down">Down</option><option value="up">Up</option>
          <option value="left">Left</option><option value="right">Right</option>
        </select>
      </label>
      <label>Colors (comma): <input data-ep="colors" type="text" style="width:100%;margin-bottom:5px;" value="${opts.particleColor.join(',')}"></label>
      <label>Shapes (comma): <input data-ep="shapes" type="text" style="width:100%;margin-bottom:5px;" value="${opts.particleShape.join(',')}"></label>
      <label>Min Size: <input data-ep="minSize" type="range" min="5" max="100" value="${opts.minSize}"><span data-ep-val="minSize">${opts.minSize}</span></label><br>
      <label>Max Size: <input data-ep="maxSize" type="range" min="5" max="200" value="${opts.maxSize}"><span data-ep-val="maxSize">${opts.maxSize}</span></label><br>
      <label>Speed: <input data-ep="speed" type="range" min="1" max="50" value="${opts.particleSpeed}"><span data-ep-val="speed">${opts.particleSpeed}</span></label><br>
      <label>Rotation: <input data-ep="rotation" type="checkbox" ${opts.particleRotation ? 'checked' : ''}></label>
      <label>Rotation Speed: <input data-ep="rotSpeed" type="range" min="0.5" max="20" step="0.5" value="${opts.particleRotationSpeed}"><span data-ep-val="rotSpeed">${opts.particleRotationSpeed}</span></label><br>
      <label>Nat. Fall: <input data-ep="natFall" type="checkbox" ${opts.natuerlichesFallverhalten ? 'checked' : ''}></label><br>
      <label>Nat. Start: <input data-ep="natStart" type="checkbox" ${opts.natuerlichesStartverhalten ? 'checked' : ''}></label><br>
      <label>Fadeout: <input data-ep="fade" type="checkbox" ${opts.fadeout ? 'checked' : ''}></label><br>
      <label>New On (ms): <input data-ep="newOn" type="range" min="50" max="2000" value="${opts.newOn}"><span data-ep-val="newOn">${opts.newOn}</span></label><br>
      <label>Pool Size: <input data-ep="pool" type="range" min="10" max="200" value="${opts.poolSize}"><span data-ep-val="pool">${opts.poolSize}</span></label>
    `;
    document.body.appendChild(wrap);

    wrap.querySelectorAll('input[type="range"]').forEach(input => {
      const valOut = wrap.querySelector(`[data-ep-val="${input.dataset.ep}"]`);
      input.addEventListener('input', () => { valOut.textContent = input.value; });
    });

    wrap.querySelector('[data-ep="direction"]').value = opts.particleDirection;
  }

  function readPanel(opts) {
    const wrap = document.getElementById('rallos-emitter-panel');
    if (!wrap) return;
    const get = sel => wrap.querySelector(`[data-ep="${sel}"]`);
    opts.particleDirection          = get('direction').value;
    opts.particleColor              = get('colors').value.split(',');
    opts.particleShape              = get('shapes').value.split(',');
    opts.minSize                    = parseFloat(get('minSize').value);
    opts.maxSize                    = parseFloat(get('maxSize').value);
    opts.particleSpeed              = parseFloat(get('speed').value);
    opts.particleRotation           = get('rotation').checked;
    opts.particleRotationSpeed      = parseFloat(get('rotSpeed').value);
    opts.natuerlichesFallverhalten  = get('natFall').checked;
    opts.natuerlichesStartverhalten = get('natStart').checked;
    opts.fadeout                    = get('fade').checked;
    opts.newOn                      = parseInt(get('newOn').value, 10);
    opts.poolSize                   = parseInt(get('pool').value, 10);
  }

  function rnd(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

  function RalloEmitter(target, userOptions) {
    if (!(this instanceof RalloEmitter)) return new RalloEmitter(target, userOptions);

    const container = typeof target === 'string' ? document.querySelector(target) : target;
    if (!container) {
      console.warn('[RalloEmitter] Target nicht gefunden:', target);
      return;
    }

    const opts = Object.assign({}, DEFAULTS, userOptions || {});

    const cs = getComputedStyle(container);
    if (!['relative', 'absolute', 'fixed', 'sticky'].includes(cs.position)) {
      container.style.position = 'relative';
    }

    injectStyles();
    if (opts.controlPanel) buildPanel(opts);

    // Pool aufbauen
    const pool = [];
    for (let i = 0; i < opts.poolSize; i++) {
      const el = document.createElement('div');
      el.className = 'rallos-emitter-particle';
      el.innerHTML = '<div class="rallos-emitter-sway"><div class="rallos-emitter-inner"></div></div>';
      el.style.cssText = 'position:absolute;top:0;left:0;pointer-events:none;z-index:11000;display:none;will-change:transform,opacity;';
      el._active = false;
      container.appendChild(el);
      pool.push(el);
    }

    function getParticle() {
      for (let i = 0; i < pool.length; i++) {
        if (!pool[i]._active) {
          pool[i]._active = true;
          pool[i].style.display = 'block';
          return pool[i];
        }
      }
      return null;
    }

    function spawn() {
      if (opts.controlPanel) readPanel(opts);

      const el = getParticle();
      if (!el) return;

      const inner = el.querySelector('.rallos-emitter-inner');
      const sway  = el.querySelector('.rallos-emitter-sway');

      const w = container.offsetWidth;
      const h = container.offsetHeight;

      const size  = opts.minSize + Math.random() * (opts.maxSize - opts.minSize);
      const shape = rnd(opts.particleShape);
      const color = rnd(opts.particleColor);

      inner.textContent = shape;
      inner.style.fontSize = size + 'px';
      inner.style.color = color;

      const ph = el.offsetHeight;
      const pw = el.offsetWidth;

      let startTop, startLeft, targetTop, targetLeft;
      switch (opts.particleDirection) {
        case 'down':
          startTop   = opts.natuerlichesFallverhalten ? Math.random() * h / 3 : -ph;
          startLeft  = Math.random() * w;
          targetTop  = h;
          targetLeft = startLeft + (opts.natuerlichesFallverhalten ? Math.random() * 100 - 50 : 0);
          break;
        case 'up':
          startTop   = opts.natuerlichesFallverhalten ? h - Math.random() * h / 3 : h + ph;
          startLeft  = Math.random() * w;
          targetTop  = -ph;
          targetLeft = startLeft + (opts.natuerlichesFallverhalten ? Math.random() * 100 - 50 : 0);
          break;
        case 'right':
          startLeft  = opts.natuerlichesFallverhalten ? Math.random() * w / 3 : -pw;
          startTop   = Math.random() * h;
          targetLeft = w;
          targetTop  = startTop + (opts.natuerlichesFallverhalten ? Math.random() * 100 - 50 : 0);
          break;
        case 'left':
          startLeft  = opts.natuerlichesFallverhalten ? w - Math.random() * w / 3 : w + pw;
          startTop   = Math.random() * h;
          targetLeft = -pw;
          targetTop  = startTop + (opts.natuerlichesFallverhalten ? Math.random() * 100 - 50 : 0);
          break;
        default:
          startTop   = -ph;
          startLeft  = Math.random() * w;
          targetTop  = h;
          targetLeft = startLeft;
      }

      if (opts.natuerlichesStartverhalten) {
        startLeft += (Math.random() - 0.5) * w * 0.2;
        startTop  += (Math.random() - 0.5) * h * 0.2;
      }

      // Reset → Anfangs-Position via top/left, transform/opacity neutral
      el.style.transition = 'none';
      el.style.top        = startTop + 'px';
      el.style.left       = startLeft + 'px';
      el.style.transform  = 'translate(0,0)';
      el.style.opacity    = '1';

      // Rotation auf .inner
      inner.style.animation = opts.particleRotation
        ? `rallos-emitter-rotation ${opts.particleRotationSpeed}s linear infinite ${Math.random() > 0.5 ? 'normal' : 'reverse'}`
        : 'none';

      // Sway auf Wrapper (additiv zur Hauptbewegung)
      if (opts.natuerlichesFallverhalten) {
        const speed = Math.max(0.5, 3 + Math.random() * 2 - opts.particleSpeed * 0.05);
        sway.style.animation = `rallos-emitter-sway ${speed}s ease-in-out infinite`;
      } else {
        sway.style.animation = 'none';
      }

      const isVertical = opts.particleDirection === 'up' || opts.particleDirection === 'down';
      const duration   = (isVertical ? h : w) / 100 * 10 / opts.particleSpeed;

      const dx = targetLeft - startLeft;
      const dy = targetTop  - startTop;

      // Reflow erzwingen, damit transition mit neuen Werten greift
      void el.offsetHeight;

      el.style.transition = `transform ${duration}s linear${opts.fadeout ? ', opacity ' + duration + 's linear' : ''}`;
      el.style.transform  = `translate(${dx}px, ${dy}px)`;
      if (opts.fadeout) el.style.opacity = '0';

      const onEnd = function (e) {
        if (e.propertyName !== 'transform') return;
        el.removeEventListener('transitionend', onEnd);
        el._active = false;
        el.style.display = 'none';
        el.style.transition = 'none';
      };
      el.addEventListener('transitionend', onEnd);
    }

    let intervalId = null;

    function start() {
      if (intervalId) return;
      intervalId = setInterval(spawn, opts.newOn);
    }

    function stop() {
      if (!intervalId) return;
      clearInterval(intervalId);
      intervalId = null;
    }

    function update(newOptions) {
      Object.assign(opts, newOptions || {});
      if (newOptions && newOptions.newOn && intervalId) {
        stop();
        start();
      }
    }

    function destroy() {
      stop();
      pool.forEach(el => el.remove());
      pool.length = 0;
    }

    // Pause wenn Tab nicht sichtbar (spart CPU)
    function onVisibilityChange() {
      if (document.hidden) stop();
      else start();
    }
    document.addEventListener('visibilitychange', onVisibilityChange);

    start();

    return {
      start: start,
      stop: stop,
      update: update,
      destroy: function () {
        document.removeEventListener('visibilitychange', onVisibilityChange);
        destroy();
      },
      options: opts,
    };
  }

  global.RalloEmitter = RalloEmitter;
})(window);
