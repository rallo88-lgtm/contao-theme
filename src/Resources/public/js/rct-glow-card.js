/**
 * RCT Glow Card — Per-CE WebGL Distance-Field Border
 *
 * Shader-Port: rectangle outline distance field × procedural value-noise.
 * Distanzen werden in Pixeln gerechnet (pixel-symmetrischer Border auf
 * jedem Card-Aspect). IntersectionObserver pausiert Render-Loop wenn
 * Card ausserhalb des Viewports liegt.
 */
(function () {
  'use strict';

  var VS = 'attribute vec2 a_pos; void main() { gl_Position = vec4(a_pos, 0.0, 1.0); }';

  var FS = [
    'precision mediump float;',
    'uniform vec2  u_resolution;',
    'uniform float u_time;',
    'uniform float u_inset;',
    'uniform float u_glowW;',
    'uniform float u_noiseAmp;',
    'uniform vec3  u_color;',
    '',
    'float rct_glow_hash(vec2 p) {',
    '  return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453);',
    '}',
    '',
    'float rct_glow_noise(vec2 p) {',
    '  vec2 i = floor(p);',
    '  vec2 f = fract(p);',
    '  f = f * f * (3.0 - 2.0 * f);',
    '  float a = rct_glow_hash(i);',
    '  float b = rct_glow_hash(i + vec2(1.0, 0.0));',
    '  float c = rct_glow_hash(i + vec2(0.0, 1.0));',
    '  float d = rct_glow_hash(i + vec2(1.0, 1.0));',
    '  return mix(mix(a, b, f.x), mix(c, d, f.x), f.y);',
    '}',
    '',
    'void main() {',
    '  vec2 fc  = gl_FragCoord.xy;',
    '  vec2 res = u_resolution.xy;',
    '  float ins = u_inset;',
    '',
    '  // 4 Rechteck-Ecken in Pixeln (symmetrischer Inset)',
    '  vec2 p1 = vec2(ins, ins);',
    '  vec2 p2 = vec2(res.x - ins, res.y - ins);',
    '  vec2 p3 = vec2(ins, res.y - ins);',
    '  vec2 p4 = vec2(res.x - ins, ins);',
    '',
    '  // Noise in Pixel-Space (Tile ~80 Pixel)',
    '  vec2 nuv = fc / 80.0 + vec2(u_time * 4.0, u_time * 6.0);',
    '  float n = rct_glow_noise(nuv);',
    '',
    '  // Distance-to-Outline in Pixeln',
    '  float d1 = step(p1.x, fc.x) * step(fc.x, p4.x) * abs(fc.y - p1.y) +',
    '             step(fc.x, p1.x) * distance(fc, p1) +',
    '             step(p4.x, fc.x) * distance(fc, p4);',
    '  d1 = min(step(p3.x, fc.x) * step(fc.x, p2.x) * abs(fc.y - p2.y) +',
    '           step(fc.x, p3.x) * distance(fc, p3) +',
    '           step(p2.x, fc.x) * distance(fc, p2), d1);',
    '  d1 = min(step(p1.y, fc.y) * step(fc.y, p3.y) * abs(fc.x - p1.x) +',
    '           step(fc.y, p1.y) * distance(fc, p1) +',
    '           step(p3.y, fc.y) * distance(fc, p3), d1);',
    '  d1 = min(step(p4.y, fc.y) * step(fc.y, p2.y) * abs(fc.x - p2.x) +',
    '           step(fc.y, p4.y) * distance(fc, p4) +',
    '           step(p2.y, fc.y) * distance(fc, p2), d1);',
    '',
    '  // Glow-Halo: hell auf der Outline, faellt zu beiden Seiten ab',
    '  float f1 = u_glowW / abs(d1 + n * u_noiseAmp);',
    '  gl_FragColor = vec4(f1 * u_color, clamp(f1, 0.0, 1.0));',
    '}'
  ].join('\n');

  function compile(gl, type, src) {
    var s = gl.createShader(type);
    gl.shaderSource(s, src);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
      console.error('[rct-glow-card] shader error:', gl.getShaderInfoLog(s));
      gl.deleteShader(s);
      return null;
    }
    return s;
  }

  function hexToRgb(hex) {
    hex = (hex || '').replace('#', '').trim();
    if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
    var n = parseInt(hex, 16);
    if (isNaN(n)) return null;
    return [((n >> 16) & 255) / 255, ((n >> 8) & 255) / 255, (n & 255) / 255];
  }

  function parseColor(card) {
    // Priority: data-glow-color (DCA-Preset) > --rct-accent (Theme) > Fallback
    var explicit = hexToRgb(card.dataset.glowColor);
    if (explicit) return explicit;
    var v = getComputedStyle(card).getPropertyValue('--rct-accent').trim();
    var themed = hexToRgb(v);
    return themed || [0.15, 0.77, 0.96];
  }

  function speedMul(card) {
    var s = card.dataset.glowSpeed;
    if (s === 'slow') return 1.0;
    if (s === 'fast') return 60.0;
    return 20.0;
  }

  function widthPreset(card) {
    var w = card.dataset.glowWidth;
    if (w === 'thin') return { glow: 3.0, noise: 1.0 };
    if (w === 'bold') return { glow: 12.0, noise: 4.0 };
    return { glow: 6.0, noise: 2.0 };
  }

  function initCard(card) {
    var canvas = card.querySelector('.rct-glow-card__canvas');
    if (!canvas) return;

    var gl = canvas.getContext('webgl', {
      antialias: true,
      alpha: true,
      premultipliedAlpha: false
    });
    if (!gl) return;

    var vs = compile(gl, gl.VERTEX_SHADER, VS);
    var fs = compile(gl, gl.FRAGMENT_SHADER, FS);
    if (!vs || !fs) return;

    var prog = gl.createProgram();
    gl.attachShader(prog, vs);
    gl.attachShader(prog, fs);
    gl.linkProgram(prog);
    if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
      console.error('[rct-glow-card] link error:', gl.getProgramInfoLog(prog));
      return;
    }
    gl.useProgram(prog);

    var buf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    var posLoc = gl.getAttribLocation(prog, 'a_pos');
    gl.enableVertexAttribArray(posLoc);
    gl.vertexAttribPointer(posLoc, 2, gl.FLOAT, false, 0, 0);

    var uRes      = gl.getUniformLocation(prog, 'u_resolution');
    var uTime     = gl.getUniformLocation(prog, 'u_time');
    var uInset    = gl.getUniformLocation(prog, 'u_inset');
    var uGlowW    = gl.getUniformLocation(prog, 'u_glowW');
    var uNoiseAmp = gl.getUniformLocation(prog, 'u_noiseAmp');
    var uColor    = gl.getUniformLocation(prog, 'u_color');

    gl.enable(gl.BLEND);
    gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);

    var color = parseColor(card);
    var mul   = speedMul(card);
    var width = widthPreset(card);

    // Bei Theme-Switch die Accent-Farbe nachladen — aber NUR wenn die Card
    // keinen expliziten Color-Preset hat (sonst bleibt Cyan auch bei Theme-Wechsel).
    if (!card.dataset.glowColor && 'MutationObserver' in window) {
      new MutationObserver(function () { color = parseColor(card); })
        .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }
    var dpr   = Math.min(window.devicePixelRatio || 1, 1.5);
    var inset = 14.0 * dpr;  // Pixel-Inset im Buffer-Space
    var glowW = width.glow * dpr;
    var noiseAmp = width.noise * dpr;

    function resize() {
      var w = Math.max(1, Math.round(card.clientWidth  * dpr));
      var h = Math.max(1, Math.round(card.clientHeight * dpr));
      if (canvas.width !== w || canvas.height !== h) {
        canvas.width  = w;
        canvas.height = h;
        gl.viewport(0, 0, w, h);
      }
    }

    var visible = true;
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) visible = entries[i].isIntersecting;
      }).observe(card);
    }
    if ('ResizeObserver' in window) {
      new ResizeObserver(resize).observe(card);
    }
    resize();

    var t0 = performance.now();
    function render() {
      if (visible) {
        resize();
        var t = ((performance.now() - t0) / 1000) * mul;
        gl.uniform2f(uRes, canvas.width, canvas.height);
        gl.uniform1f(uTime, t);
        gl.uniform1f(uInset, inset);
        gl.uniform1f(uGlowW, glowW);
        gl.uniform1f(uNoiseAmp, noiseAmp);
        gl.uniform3f(uColor, color[0], color[1], color[2]);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
      }
      requestAnimationFrame(render);
    }
    render();
  }

  function init() {
    var cards = document.querySelectorAll('[data-rct-glow-card]');
    for (var i = 0; i < cards.length; i++) initCard(cards[i]);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
