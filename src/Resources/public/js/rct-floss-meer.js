// RCT-Theme „Floß im Datenmeer“ (Shader-Modus 21, aus Shader-Labor Exp. 32, Ralph 12.11.2026)
// Floß + Sofa + Missis Kopf liegen als Bilder IM Shader auf dem Datenmeer: in der Welt verankert, reiten auf den Wellen, Spiegelbild im Wasser.
// Dieses JS liefert dem Shader die Lage (u_x0), Missis Blick (u_x1), die Scroll-Kamera (u_x2) und das Hover-Leuchten (u_x3).
// rct.js ruft RCT_THEME_HOOKS[theme].start(gradient) nach dem Aufbau auf und die zurückgegebene Funktion beim Theme-Wechsel.
(function () {
  'use strict';
  const IMG = '/bundles/rct/img/floss-meer/';
  const src = name => (window.RCT_TEX_URLS && window.RCT_TEX_URLS[name]) || IMG + name;   // RCT_TEX_URLS: Ersatz-URL je Dateiname (Testseiten unter file://)
  const loadImg = url => new Promise((res, rej) => { const i = new Image(); i.onload = () => res(i); i.onerror = rej; i.src = url; });

  function start(g) {
    const U = g.mesh.material.uniforms, cfg = g.themeConfig || {};
    const leise = !document.querySelector('.rct-floss');   // Floß nur auf Seiten mit Klasse „rct-floss“ (Ralph 12.11.: Home = Bühne); sonst leises Meer: selber Shader, andere Kamera
    document.documentElement.classList.toggle('rct-floss-leise', leise);
    if (leise) { U.u_tex1_ready.value = 0; U.u_tex2_ready.value = 0; }
    if (!leise) Promise.all([loadImg(src('teppich-insel.avif')), loadImg(src('aws-hero-bg.avif')), loadImg(src('aws-hero-missi-sheet.avif'))]).then(([insel, couch, missi]) => {
      if (window.gradient !== g) return;
      const W = 1300, H = Math.round(W * 941 / 1672), cv = document.createElement('canvas'); cv.width = W; cv.height = H;   // Kasten wie .aws-sofa (1672 : 941)
      const x = cv.getContext('2d'); x.drawImage(insel, 0, 0, W, H); x.drawImage(couch, W * 0.25, H * 0.09883, W * 0.7082, W * 0.7082 * couch.height / couch.width);   // Lage wie .aws-insel / .aws-couch im Hero-CE
      g.loadTextureUnit(1, cv, W, H); g.loadTextureUnit(2, missi, 768, 768);
    }).catch(() => {});   // Bilder fehlen → Meer ohne Floß

    const hochformat = () => innerWidth < innerHeight * 0.8;
    const horizont = () => {   // Handy hochkant: eigene Horizont-Neigung (p0) + Kamerahöhe (p2) – der Horizont liegt auf Kamera-Augenhöhe, Halshöhe also über p2; leise Seiten: paramsLeise (+ paramsLeisePortrait)
      const lagen = leise ? [cfg.paramsLeise, hochformat() && cfg.paramsLeisePortrait] : [hochformat() && cfg.paramsPortrait], pp = Object.assign({}, ...lagen.map(o => o || {}));
      const idx = new Set([0, 2, ...Object.keys(cfg.paramsLeise || {}).map(Number)]);
      for (const i of idx) { const u = U['u_p' + i]; if (u) u.value = pp[i] != null ? pp[i] : (cfg.params || [])[i]; } };
    const spalte = () => {   // Inhaltsspalte des Themes (ohne Innenabstand); ohne Spalte: ganzes Fenster
      const el = document.querySelector('.rct-content-inner') || document.querySelector('#main'); if (!el) return { left: 0, width: innerWidth };
      const r = el.getBoundingClientRect(), cs = getComputedStyle(el), pl = parseFloat(cs.paddingLeft) || 0, pr = parseFloat(cs.paddingRight) || 0;
      return r.width > 200 ? { left: r.left + pl, width: r.width - pl - pr } : { left: 0, width: innerWidth };
    };
    const box = () => { const k = g.resScale || 1, hoch = hochformat();   // Desktop: rechte Hälfte der Inhaltsspalte (Meer bleibt volle Breite); Hochformat: fast volle Breite unter der Mitte
      if (hoch) { const w = innerWidth * 0.94; return { k, x: innerWidth * 0.03, y: innerHeight * 0.5, w, h: w * 941 / 1672 }; }
      const c = spalte(), w = c.width * 0.5, h = w * 941 / 1672;
      return { k, x: c.left + c.width * 0.48, y: wasserlinie(h), w, h }; };
    const wasserlinie = h => {   // Ralph 13.11.: Floß nicht fest bei 40 %, sondern Unterkante des Floß-Kastens auf die Unterkante des .rct-floss-Elements (Hero-Text) – in Seitenkoordinaten bei Scroll 0, die Scroll-Kamera übernimmt den Rest
      const el = document.querySelector('.rct-floss'), se = document.scrollingElement || document.documentElement;
      const unten = el ? el.getBoundingClientRect().bottom + se.scrollTop : innerHeight * 0.4 + 0.88 * h;
      return Math.min(Math.max(unten - h, innerHeight * 0.12), innerHeight * 0.45); };   // ganzes Floß (Kasten) über der Unterkante – sonst rutscht der Bug hinter die Glasfläche des nächsten Artikels   // nie unter die Kopfzeile, nie tiefer als vorher

    let col = 1, row = 1, idle = 0;
    const layout = () => { horizont(); const b = box(); U.u_x0.value = [b.x * b.k, b.y * b.k, b.w * b.k, b.h * b.k]; U.u_x1.value = [col, row, 0.006, 0.006]; };   // Schaukeln-Reste (Shader nimmt die Wellen)
    const pick = (d, dz) => dz > Math.abs(d) ? 1 : (d < 0 ? 0 : 2);   // wie im Hero-CE: Totzone in der Mitte
    const placed = () => { const b = box(), k = b.k, gl = g.minigl.gl, res = [gl.drawingBufferWidth, gl.drawingBufferHeight], mw = Math.max(U.u_moon.value[3] || innerHeight * k, 1), cw = res[0] * 1.02;   // wie der Shader: Lage bei Standard-Kamera → Weltpunkt → aktuelle Kamera (für Missis Blick)
      const asp = res[0] / (res[1] / 1.28), p0 = Math.atan(0.22 / 1.1), x2 = U.u_x2.value, p1 = Math.atan((U.u_p0.value + x2[1] - 0.5) / 1.1), cam = Math.max(U.u_p2.value + x2[0], 0.3);
      const ax = (b.x + 0.53 * b.w) * k, ay = (b.y + 0.88 * b.h) * k, ux = ax / cw, uy = 1 - ay / mw;
      let x = (ux - 0.5) * asp, y = uy - 0.5, z = 1.1; const n = Math.hypot(x, y, z); x /= n; y /= n; z /= n;
      const ry = y * Math.cos(p0) - z * Math.sin(p0), rz = y * Math.sin(p0) + z * Math.cos(p0); if (ry > -0.001) return b;
      const t = 2.6 / -ry, wx = x * t, wy = ry * t, wz = rz * t, z0 = -wy * Math.sin(p0) + wz * Math.cos(p0), vy = wy + 2.6 - cam;
      const dy = vy * Math.cos(p1) + wz * Math.sin(p1), dz = -vy * Math.sin(p1) + wz * Math.cos(p1); if (dz < 0.05) return b;
      const sx = 0.5 + wx / dz * 1.1 / asp, sy = 0.5 + dy / dz * 1.1, s = z0 / dz, w = b.w * s, h = b.h * s;
      return { k, w, h, x: sx * cw / k - 0.53 * w, y: (1 - sy) * mw / k - 0.88 * h }; };

    let hm = null, ht = null;   // Hover: Maus + Nachzügler in Bildschirm-uv (x über die Canvas-Breite, y = 1 oben)
    const hover = e => { const r = g.el.getBoundingClientRect();
      hm = (e.clientX >= r.left && e.clientX <= r.left + innerWidth && e.clientY >= 0 && e.clientY <= innerHeight) ? [(e.clientX - r.left) / r.width, 1 - e.clientY / innerHeight] : null; };
    const weg = () => { hm = null; };
    const look = e => { hover(e); if (leise) return; const b = placed(), hx = b.x + b.w * (0.7349 + 0.12045 / 2), hy = b.y + b.h * 0.10106 + b.w * 0.12045 * 0.45, dz = b.w * 0.12045 * 0.55;
      col = pick(e.clientX - hx, dz); row = pick(e.clientY - hy, dz); layout(); clearTimeout(idle); idle = setTimeout(() => { col = 1; row = 1; layout(); }, 4000); };
    layout(); addEventListener('pointermove', look); addEventListener('resize', layout); document.documentElement.addEventListener('mouseleave', weg); addEventListener('blur', weg);

    // Scroll-Kamera „Sinkflug“: pro gescrollter Bildschirmhöhe sinkt die Kamera und neigt sich (Horizont wandert mit dem Inhalt), max. 2 Bildschirmhöhen, 0,1 s Nachgleiten
    const ZIEL = leise ? (cfg.scrollLeise || [-0.25, 0.15]) : [-0.7, 0.45], MAX_BILDER = 2, still = matchMedia('(prefers-reduced-motion: reduce)').matches;   // „Bewegung reduzieren“ → Kamera bleibt stehen
    let cur = [0, 0], last = performance.now(), raf = 0, skip = null, dtAvg = 16;
    const fahrt = now => { raf = requestAnimationFrame(fahrt); const dt = Math.min((now - last) / 1000, 0.1); last = now; dtAvg += (dt * 1000 - dtAvg) * 0.1;
      const se = document.scrollingElement || document.documentElement, pr = still ? 0 : Math.min(Math.max(se.scrollTop / Math.max(innerHeight, 1), 0), MAX_BILDER), f = 1 - Math.exp(-dt * 10);
      const tgt = [ZIEL[0] * pr, ZIEL[1] * pr], moving = Math.abs(tgt[0] - cur[0]) + Math.abs(tgt[1] - cur[1]) > 1e-4;
      cur = moving ? [cur[0] + (tgt[0] - cur[0]) * f, cur[1] + (tgt[1] - cur[1]) * f] : tgt; U.u_x2.value = [cur[0], cur[1], 0, 0];
      if (hm) { const fh = 1 - Math.exp(-dt / 0.45); ht = ht ? [ht[0] + (hm[0] - ht[0]) * fh, ht[1] + (hm[1] - ht[1]) * fh] : hm.slice(); U.u_x3.value = [hm[0], hm[1], ht[0], ht[1]]; }   // Nachzügler = Leuchtspur
      else { ht = null; U.u_x3.value = [-5, -5, -5, -5]; }
      if (moving && skip === null) { skip = g.frameSkip; g.frameSkip = dtAvg < 11 ? 2 : 1; } else if (!moving && skip !== null) { g.frameSkip = skip; skip = null; } };   // Fahrt: ~60 Shader-Bilder/s (120-Hz-Handy: jedes 2. Bild)
    U.u_x3.value = [-5, -5, -5, -5]; raf = requestAnimationFrame(fahrt);
    return () => { document.documentElement.classList.remove('rct-floss-leise'); removeEventListener('pointermove', look); removeEventListener('resize', layout); document.documentElement.removeEventListener('mouseleave', weg); removeEventListener('blur', weg);
      clearTimeout(idle); cancelAnimationFrame(raf); if (skip !== null) g.frameSkip = skip; };
  }

  window.RCT_THEME_HOOKS = window.RCT_THEME_HOOKS || {};
  window.RCT_THEME_HOOKS['floss-meer'] = { start };
})();
