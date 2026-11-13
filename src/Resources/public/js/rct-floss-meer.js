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

    const heroEl = () => document.querySelector('.rct-floss');
    const hochformat = () => { const el = heroEl(); if (!el) return innerWidth < innerHeight * 0.8;   // „untereinander“ (Ralph 13.11.): nicht mehr nach Seitenverhältnis, sondern sobald neben dem Hero-Text kein Platz fürs Floß ist (folgt dem Layout der Seite)
      const c = spalte(), rg = document.createRange(), tw = document.createTreeWalker(el, NodeFilter.SHOW_TEXT); let rechts = c.left, n;
      while ((n = tw.nextNode())) { if (!n.nodeValue.trim()) continue; rg.selectNodeContents(n); for (const q of rg.getClientRects()) if (q.width > 0) rechts = Math.max(rechts, q.right); }   // nur echte Textstücke (Blockkästen wären so breit wie die Spalte)
      return c.left + c.width - rechts < Math.max(c.width * 0.35, 260); };   // weniger als 35 % der Spalte (bzw. 260 px) frei → Floß unter den Text
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
      if (hoch) { const c = spalte(), w = Math.min(c.width, innerWidth * 0.94), h = w * 941 / 1672;   // untereinander: Floß direkt unter dem Hero-Text, so breit wie die Spalte
        return { k, x: c.left + (c.width - w) / 2, y: unterkante(), w, h }; }
      const c = spalte(), platz = unterkante() - innerHeight * 0.12;   // Höhe zwischen Kopfzeile und Unterkante Hero-Text
      const w = Math.min(Math.max(c.width * 0.55, innerWidth * 0.355), Math.max(platz, innerHeight * 0.25) * 1672 / 941), h = w * 941 / 1672;   // Ralph 13.11.: 10 % größer + mit der Bildschirmbreite wachsen, aber nie höher als der Hero Platz hat (sonst rutscht es hinter den nächsten Artikel)
      const x = Math.min(c.left + c.width * 0.42, innerWidth * 0.97 - w);   // Ralph 13.11.: näher an den Text (war 0.48)   // darf rechts über die Inhaltsspalte ragen, nie über den Bildschirmrand
      return { k, x, y: wasserlinie(h) + hoehenVersatz(), w, h }; };
    const hoehenVersatz = () => { const r = cfg.hoehenRegel || [1920, 2560, -0.06, 0.065];   // Ralph 13.11.: 1920 und 2560 „treffen sich in der Mitte“ – Floß bei 1920 etwas höher, bei 2560 etwas tiefer (Anteil der Fensterhöhe)
      const k = Math.min(Math.max((innerWidth - r[0]) / (r[1] - r[0]), 0), 1); return (r[2] + (r[3] - r[2]) * k) * innerHeight; };
    const horizontZumFloss = b => {   // Ralph 13.11.: Horizont liegt bei 44 % der Fensterhöhe, der Hero-Text in festen Pixeln → auf hohen Bildschirmen (2560×1440) lag die Wasserlinie ÜBER dem Horizont („in der Luft“).
      if (leise || !U.u_p0) return;   // Ralph 13.11. (Responsive): gilt jetzt für alle Größen – Horizont knapp über der hinteren Teppichkante (55 % der Kastenhöhe), unabhängig von der Fensterhöhe
      const horizontY = b.y + 0.55 * b.h; U.u_p0.value = Math.min(Math.max(1 - horizontY / innerHeight, 0.4), 0.85); };   // 0,4–0,85: nie absurd viel Himmel oder Meer
    const unterkante = () => { const el = document.querySelector('.rct-floss'), se = document.scrollingElement || document.documentElement;
      return el ? el.getBoundingClientRect().bottom + se.scrollTop : innerHeight * 0.85; };
    const wasserlinie = h => {   // Ralph 13.11.: Floß nicht fest bei 40 %, sondern Unterkante des Floß-Kastens auf die Unterkante des .rct-floss-Elements (Hero-Text) – in Seitenkoordinaten bei Scroll 0, die Scroll-Kamera übernimmt den Rest
      const el = document.querySelector('.rct-floss'), se = document.scrollingElement || document.documentElement;
      const unten = el ? el.getBoundingClientRect().bottom + se.scrollTop : innerHeight * 0.4 + 0.88 * h;
      return Math.min(Math.max(unten - h, innerHeight * 0.12), innerHeight * 0.45); };   // ganzes Floß (Kasten) über der Unterkante – sonst rutscht der Bug hinter die Glasfläche des nächsten Artikels   // nie unter die Kopfzeile, nie tiefer als vorher

    let col = 1, row = 1, idle = 0;
    const platz = h => { const el = heroEl(); if (el) el.style.marginBottom = h ? Math.round(h * 1.06) + 'px' : ''; };   // untereinander: Hero wird um die Floßhöhe länger, die nächste Kachel rückt nach unten (nie mehr Floß hinter den Kacheln)
    const layout = () => { horizont(); platz(0); const hoch = hochformat(), b = box(); if (hoch) platz(b.h); U.u_x0.value = [b.x * b.k, b.y * b.k, b.w * b.k, b.h * b.k]; horizontZumFloss(b); U.u_x1.value = [col, row, 0.006, 0.006]; };   // Schaukeln-Reste (Shader nimmt die Wellen)
    const pick = (d, dz) => dz > Math.abs(d) ? 1 : (d < 0 ? 0 : 2);   // wie im Hero-CE: Totzone in der Mitte
    const placed = () => { const b = box(), k = b.k, gl = g.minigl.gl, res = [gl.drawingBufferWidth, gl.drawingBufferHeight], mw = Math.max(U.u_moon.value[3] || innerHeight * k, 1), cw = res[0] * 1.02;   // wie der Shader: Lage bei Standard-Kamera → Weltpunkt → aktuelle Kamera (für Missis Blick)
      const asp = res[0] / (res[1] / 1.28), p0 = Math.atan(0.22 / 1.1), x2 = U.u_x2.value, p1 = Math.atan((U.u_p0.value + x2[1] - 0.5) / 1.1), cam = Math.max(U.u_p2.value + x2[0], 0.3);
      const ax = (b.x + 0.53 * b.w) * k, ay = (b.y + 0.88 * b.h) * k, ux = ax / cw, uy = 1 - ay / mw;
      let x = (ux - 0.5) * asp, y = uy - 0.5, z = 1.1; const n = Math.hypot(x, y, z); x /= n; y /= n; z /= n;
      const ry = y * Math.cos(p0) - z * Math.sin(p0), rz = y * Math.sin(p0) + z * Math.cos(p0); if (ry > -0.001) return b;
      const t = 2.6 / -ry, wx = x * t, wy = ry * t, wz = rz * t, z0 = -wy * Math.sin(p0) + wz * Math.cos(p0), vy = wy + 2.6 - cam, vz = wz - ((U.u_p11 ? U.u_p11.value : 0) + x2[2]);   // vz: Kamera-Fahrt
      const dy = vy * Math.cos(p1) + vz * Math.sin(p1), dz = -vy * Math.sin(p1) + vz * Math.cos(p1); if (dz < 0.05) return b;
      const sx = 0.5 + wx / dz * 1.1 / asp, sy = 0.5 + dy / dz * 1.1, s = z0 / dz, w = b.w * s, h = b.h * s;
      return { k, w, h, x: sx * cw / k - 0.53 * w, y: (1 - sy) * mw / k - 0.88 * h }; };

    let hm = null, ht = null;   // Hover: Maus + Nachzügler in Bildschirm-uv (x über die Canvas-Breite, y = 1 oben)
    const hover = e => { const r = g.el.getBoundingClientRect();
      hm = (e.clientX >= r.left && e.clientX <= r.left + innerWidth && e.clientY >= 0 && e.clientY <= innerHeight) ? [(e.clientX - r.left) / r.width, 1 - e.clientY / innerHeight] : null; };
    const weg = () => { hm = null; };
    const look = e => { hover(e); if (leise) return; const b = placed(), hx = b.x + b.w * (0.7349 + 0.12045 / 2), hy = b.y + b.h * 0.10106 + b.w * 0.12045 * 0.45, dz = b.w * 0.12045 * 0.55;
      col = pick(e.clientX - hx, dz); row = pick(e.clientY - hy, dz); layout(); clearTimeout(idle); idle = setTimeout(() => { col = 1; row = 1; layout(); }, 4000); };
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => { if (window.gradient === g) layout(); });   // Webfont da → Text hat seine echte Höhe
    layout(); addEventListener('pointermove', look); addEventListener('resize', layout); document.documentElement.addEventListener('mouseleave', weg); addEventListener('blur', weg);

    // Scroll-Kamera in Stationen [Bildschirmhöhen, Höhe, Neigung, Fahrt] (Ralph 13.11.: oben Neigung + Anflug, danach Höhe) – dazwischen gleichmäßig, 0,1 s Nachgleiten
    const STATIONEN = leise ? (cfg.stationenLeise || [[0, 0, 0, 0], [2, -0.5, 0.3, 0]]) : (cfg.stationen || [[0, 0, 0, 0], [1, 0, 0.45, 0.7], [2, -0.8, 0.45, 0.7]]);
    const still = matchMedia('(prefers-reduced-motion: reduce)').matches;   // „Bewegung reduzieren“ → kein Intro, keine Scroll-Fahrt
    const alsStationen = z => Array.isArray(z[0]) ? z : [[0, 0, 0, 0], [2, 2 * (z[0] || 0), 2 * (z[1] || 0), 2 * (z[2] || 0)]];   // alte Form [Höhe, Neigung, Fahrt] pro Bildschirmhöhe → 2 Stationen
    const anStation = (st, pr) => { if (pr <= st[0][0]) return st[0].slice(1);
      for (let i = 1; i < st.length; i++) { const a = st[i - 1], b = st[i]; if (pr <= b[0]) { const k = (pr - a[0]) / Math.max(b[0] - a[0], 1e-6); return [1, 2, 3].map(j => a[j] + (b[j] - a[j]) * k); } }
      return st[st.length - 1].slice(1); };
    // Intro (Ralph 13.11.): Kamera fährt in 4 s von −4 auf ihr Ziel; Ziel-Fahrt hängt an der Bildschirmbreite (0 bis 1920 px, linear bis 3 bei 2560, gedeckelt); der Hero-Text blendet per CSS ein (rct-layout.css, kein Aufblitzen vor dem JS)
    const INTRO = still || leise || hochformat() ? 0 : (cfg.introSekunden ?? 4) * 1000, VON = cfg.introVon ?? -4, REGEL = cfg.fahrtRegel || [1920, 2560, 3];
    const zielFahrt = () => leise || hochformat() ? 0 : Math.min(Math.max((innerWidth - REGEL[0]) / (REGEL[1] - REGEL[0]) * REGEL[2], 0), REGEL[2]);
    const sanft = k => k < 0.5 ? 4 * k * k * k : 1 - Math.pow(-2 * k + 2, 3) / 2;   // weich an- und abbremsen
    const t0 = performance.now();
    let cur = [0, 0, 0], last = performance.now(), raf = 0, skip = null, dtAvg = 16;
    const fahrt = now => { raf = requestAnimationFrame(fahrt); const dt = Math.min((now - last) / 1000, 0.1); last = now; dtAvg += (dt * 1000 - dtAvg) * 0.1;
      const st = alsStationen((!leise && window.RCT_FLOSS_SCROLL) || STATIONEN), se = document.scrollingElement || document.documentElement;
      const pr = still ? 0 : Math.min(Math.max(se.scrollTop / Math.max(innerHeight, 1), 0), st[st.length - 1][0]), f = 1 - Math.exp(-dt * 10);
      const tgt = anStation(st, pr), ki = INTRO ? Math.min((now - t0) / INTRO, 1) : 1, intro = VON + (zielFahrt() - VON) * (INTRO ? sanft(ki) : 1);
      const moving = ki < 1 || tgt.some((v, i) => Math.abs(v - cur[i]) > 1e-4);
      cur = moving ? cur.map((v, i) => v + (tgt[i] - v) * f) : tgt; U.u_x2.value = [cur[0], cur[1], cur[2] + intro, 0];
      if (hm) { const fh = 1 - Math.exp(-dt / 0.45); ht = ht ? [ht[0] + (hm[0] - ht[0]) * fh, ht[1] + (hm[1] - ht[1]) * fh] : hm.slice(); U.u_x3.value = [hm[0], hm[1], ht[0], ht[1]]; }   // Nachzügler = Leuchtspur
      else { ht = null; U.u_x3.value = [-5, -5, -5, -5]; }
      if (moving && skip === null) { skip = g.frameSkip; g.frameSkip = dtAvg < 11 ? 2 : 1; } else if (!moving && skip !== null) { g.frameSkip = skip; skip = null; } };   // Fahrt: ~60 Shader-Bilder/s (120-Hz-Handy: jedes 2. Bild)
    U.u_x3.value = [-5, -5, -5, -5]; raf = requestAnimationFrame(fahrt);
    return () => { platz(0); document.documentElement.classList.remove('rct-floss-leise'); removeEventListener('pointermove', look); removeEventListener('resize', layout); document.documentElement.removeEventListener('mouseleave', weg); removeEventListener('blur', weg);
      clearTimeout(idle); cancelAnimationFrame(raf); if (skip !== null) g.frameSkip = skip; };
  }

  window.RCT_THEME_HOOKS = window.RCT_THEME_HOOKS || {};
  window.RCT_THEME_HOOKS['floss-meer'] = { start };
})();
