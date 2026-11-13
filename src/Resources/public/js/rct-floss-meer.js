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

    // Kamera folgt dem Layout (Ralph 13.11.): WO das Floß hin soll, bestimmt der Hero-Text (box), WIE es dahin kommt, die Kamera – Ralphs Feinkorrektur je Bildschirmbreite
    // verschiebt das Ziel (px) und der Rechenkern stellt Höhe, Fahrt und Seite der Kamera passend ein (Horizont = Neigung bleibt). Gilt bei jeder Fensterhöhe.
    const STD_P = Math.atan(0.22 / 1.1), STD_H = 2.6;   // Standard-Kamera, mit der u_x0 (Bildkasten) im Shader in die Welt umgerechnet wird
    const mass = () => { const gl = g.minigl.gl, k = g.resScale || 1; return { k, cw: gl.drawingBufferWidth * 1.02, mw: Math.max(U.u_moon.value[3] || innerHeight * k, 1), asp: gl.drawingBufferWidth / (gl.drawingBufferHeight / 1.28) }; };
    const zuWelt = b => { const m = mass(), ux = (b.x + 0.53 * b.w) * m.k / m.cw, uy = 1 - (b.y + 0.88 * b.h) * m.k / m.mw;   // Bildkasten (Standard-Kamera) → Weltpunkt
      let x = (ux - 0.5) * m.asp, y = uy - 0.5, z = 1.1; const n = Math.hypot(x, y, z); x /= n; y /= n; z /= n;
      const ry = y * Math.cos(STD_P) - z * Math.sin(STD_P), rz = y * Math.sin(STD_P) + z * Math.cos(STD_P); if (ry > -0.001) return null;
      const t = STD_H / -ry, wz = rz * t, z0 = STD_H * Math.sin(STD_P) + wz * Math.cos(STD_P); return { wx: x * t, wz, s: b.w * m.k * z0 / m.mw }; };
    const proj = (W, cam) => { const m = mass(), pit = Math.atan((cam[0] - 0.5) / 1.1), vx = W.wx - cam[3], vy = -Math.max(cam[1], 0.3), vz = W.wz - cam[2];   // wie der Shader: Weltpunkt + Kamera [p0, p2, fahrt, seitlich] → Ankerpunkt + Breite (CSS-px)
      const dy = vy * Math.cos(pit) + vz * Math.sin(pit), dz = -vy * Math.sin(pit) + vz * Math.cos(pit);
      return { ax: (0.5 + vx / dz * 1.1 / m.asp) * m.cw / m.k, ay: (1 - (0.5 + dy / dz * 1.1)) * m.mw / m.k, w: W.s * m.mw / dz / m.k }; };
    const loese = (W, ziel, p0, start) => { let u = start.slice();   // Höhe, Fahrt, Seite so, dass das Floß bei festem Horizont genau auf ziel landet (Newton 3 × 3)
      const f = v => { const r = proj(W, [p0, v[0], v[1], v[2]]); return [r.ax - ziel.ax, r.ay - ziel.ay, r.w - ziel.w]; };
      const det = M => M[0][0] * (M[1][1] * M[2][2] - M[1][2] * M[2][1]) - M[0][1] * (M[1][0] * M[2][2] - M[1][2] * M[2][0]) + M[0][2] * (M[1][0] * M[2][1] - M[1][1] * M[2][0]);
      for (let it = 0; it < 60; it++) { const f0 = f(u); if (Math.hypot(...f0) < 0.2) break;
        const J = [0, 1, 2].map(j => { const v = u.slice(); v[j] += 1e-3; return f(v).map((x, i) => (x - f0[i]) / 1e-3); }), A = [0, 1, 2].map(i => [J[0][i], J[1][i], J[2][i]]), D = det(A);
        if (Math.abs(D) < 1e-12) break;
        const dx = [0, 1, 2].map(c => det(A.map((row, i) => row.map((x, j) => j === c ? -f0[i] : x))) / D);
        u = u.map((x, j) => x + 0.8 * dx[j]); u[0] = Math.min(Math.max(u[0], 0.4), 6); u[1] = Math.min(Math.max(u[1], -8), 6); }
      return [p0, u[0], u[1], u[2]]; };
    const korrektur = () => { const st = window.RCT_FLOSS_KORREKTUR || cfg.flossKorrektur || [], w = innerWidth;   // [[breite, dx px, dy px, größe ±, horizont ±], …] – weiche Kurve durch alle Punkte (Ralph 13.11.)
      if (!st.length) return [0, 0, 0, 0]; if (w <= st[0][0]) return [1, 2, 3, 4].map(j => st[0][j] || 0); if (w >= st[st.length - 1][0]) return [1, 2, 3, 4].map(j => st[st.length - 1][j] || 0);
      return [1, 2, 3, 4].map(j => kurve(st.map(x => x[0]), st.map(x => x[j] || 0), w)); };
    const kurve = (xs, ys, x) => {   // monotone kubische Kurve (Fritsch-Carlson): weich, schießt zwischen zwei Punkten nie über deren Werte hinaus
      const n = xs.length, d = [], m = []; for (let i = 0; i < n - 1; i++) d.push((ys[i + 1] - ys[i]) / (xs[i + 1] - xs[i]));
      for (let i = 0; i < n; i++) m.push(i === 0 ? d[0] : i === n - 1 ? d[n - 2] : (d[i - 1] * d[i] <= 0 ? 0 : (d[i - 1] + d[i]) / 2));
      for (let i = 0; i < n - 1; i++) { if (d[i] === 0) { m[i] = 0; m[i + 1] = 0; continue; } const a = m[i] / d[i], b = m[i + 1] / d[i], q = a * a + b * b; if (q > 9) { const t = 3 / Math.sqrt(q); m[i] = t * a * d[i]; m[i + 1] = t * b * d[i]; } }
      let k = 0; while (k < n - 2 && x > xs[k + 1]) k++;
      const h = xs[k + 1] - xs[k], t = (x - xs[k]) / h, t2 = t * t, t3 = t2 * t;
      return (2 * t3 - 3 * t2 + 1) * ys[k] + (t3 - 2 * t2 + t) * h * m[k] + (-2 * t3 + 3 * t2) * ys[k + 1] + (t3 - t2) * h * m[k + 1]; };
    let flossFahrt = null;
    let camX = 0;
    const fahrtNachRegel = () => { const R = cfg.fahrtRegel || [1920, 2560, 3]; return Math.min(Math.max((innerWidth - R[0]) / (R[1] - R[0]) * R[2], 0), R[2]); };   // Grund-Fahrt je Breite (0 bis 1920, 3 bei 2560) – hier oben, weil layout() vor dem Intro-Teil läuft
    const kameraRechnen = (b, hoch) => {   // nebeneinander: Kamera so, dass das Floß (Layout-Ziel + Ralphs Korrektur) genau sitzt – Horizont (p0) aus horizontZumFloss + Korrektur
      flossFahrt = null; camX = 0; if (leise || !U.u_p0) return; const W = zuWelt(b); if (!W) return;   // auch untereinander (Ralph 13.11.: unter 800 sollen die Korrekturen auch greifen)
      const stK = window.RCT_FLOSS_KORREKTUR || cfg.flossKorrektur || [], kr = hoch && !(stK.length && stK[0][0] <= innerWidth) && !stK.some(x => x[0] < 800) ? [0, 0, 0, 0] : korrektur(), p0 = U.u_p0.value,   // untereinander nur mit eigener Korrektur unter 800 (sonst würde die 800er aufs Handy durchschlagen)
        p2 = U.u_p2.value, f0 = fahrtNachRegel(), ist = proj(W, [p0, p2, f0, 0]), p0n = Math.min(Math.max(p0 + (kr[3] || 0), 0.3), 0.9);
      const kam = kr.some(v => v) ? loese(W, { ax: ist.ax + kr[0], ay: ist.ay + kr[1], w: ist.w * (1 + kr[2]) }, p0n, [p2, f0, 0]) : [p0, p2, f0, 0];
      U.u_p0.value = kam[0]; U.u_p2.value = kam[1]; flossFahrt = kam[2]; camX = kam[3]; };
    let col = 1, row = 1, idle = 0;
    const platz = h => { const el = heroEl(); if (el) el.style.marginBottom = h ? Math.round(h * 1.06) + 'px' : ''; };   // untereinander: Hero wird um die Floßhöhe länger, die nächste Kachel rückt nach unten (nie mehr Floß hinter den Kacheln)
    const layout = () => { horizont(); platz(0); const hoch = hochformat(), b = box(); if (hoch) platz(b.h); U.u_x0.value = [b.x * b.k, b.y * b.k, b.w * b.k, b.h * b.k]; horizontZumFloss(b); kameraRechnen(b, hoch); U.u_x1.value = [col, row, 0.006, 0.006]; };   // Schaukeln-Reste (Shader nimmt die Wellen)
    const pick = (d, dz) => dz > Math.abs(d) ? 1 : (d < 0 ? 0 : 2);   // wie im Hero-CE: Totzone in der Mitte
    const placed = () => { const b = box(), k = b.k, gl = g.minigl.gl, res = [gl.drawingBufferWidth, gl.drawingBufferHeight], mw = Math.max(U.u_moon.value[3] || innerHeight * k, 1), cw = res[0] * 1.02;   // wie der Shader: Lage bei Standard-Kamera → Weltpunkt → aktuelle Kamera (für Missis Blick)
      const asp = res[0] / (res[1] / 1.28), p0 = Math.atan(0.22 / 1.1), x2 = U.u_x2.value, p1 = Math.atan((U.u_p0.value + x2[1] - 0.5) / 1.1), cam = Math.max(U.u_p2.value + x2[0], 0.3);
      const ax = (b.x + 0.53 * b.w) * k, ay = (b.y + 0.88 * b.h) * k, ux = ax / cw, uy = 1 - ay / mw;
      let x = (ux - 0.5) * asp, y = uy - 0.5, z = 1.1; const n = Math.hypot(x, y, z); x /= n; y /= n; z /= n;
      const ry = y * Math.cos(p0) - z * Math.sin(p0), rz = y * Math.sin(p0) + z * Math.cos(p0); if (ry > -0.001) return b;
      const t = 2.6 / -ry, wx = x * t - camX, wy = ry * t, wz = rz * t, z0 = -wy * Math.sin(p0) + wz * Math.cos(p0), vy = wy + 2.6 - cam, vz = wz - ((U.u_p11 ? U.u_p11.value : 0) + x2[2]);   // vz: Kamera-Fahrt
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
    const INTRO = still || leise || hochformat() || window.RCT_FLOSS_OHNE_INTRO ? 0 : (cfg.introSekunden ?? 4) * 1000, VON = cfg.introVon ?? -4, REGEL = cfg.fahrtRegel || [1920, 2560, 3];
    const regelFahrt = () => leise || hochformat() ? 0 : Math.min(Math.max((innerWidth - REGEL[0]) / (REGEL[1] - REGEL[0]) * REGEL[2], 0), REGEL[2]);
    const zielFahrt = () => flossFahrt != null ? flossFahrt : regelFahrt();   // Intro-Ziel: Fahrt aus der Kamera-Rechnung (nebeneinander) bzw. Regel
    const sanft = k => k < 0.5 ? 4 * k * k * k : 1 - Math.pow(-2 * k + 2, 3) / 2;   // weich an- und abbremsen
    const t0 = performance.now();
    let cur = [0, 0, 0], last = performance.now(), raf = 0, skip = null, dtAvg = 16;
    const fahrt = now => { raf = requestAnimationFrame(fahrt); const dt = Math.min((now - last) / 1000, 0.1); last = now; dtAvg += (dt * 1000 - dtAvg) * 0.1;
      const st = alsStationen((!leise && window.RCT_FLOSS_SCROLL) || STATIONEN), se = document.scrollingElement || document.documentElement;
      const pr = still ? 0 : Math.min(Math.max(se.scrollTop / Math.max(innerHeight, 1), 0), st[st.length - 1][0]), f = 1 - Math.exp(-dt * 10);
      const tgt = anStation(st, pr), ki = INTRO ? Math.min((now - t0) / INTRO, 1) : 1, zf = zielFahrt(), von = Math.min(VON, zf - 2), intro = von + (zf - von) * (INTRO ? sanft(ki) : 1);
      const moving = ki < 1 || tgt.some((v, i) => Math.abs(v - cur[i]) > 1e-4);
      cur = moving ? cur.map((v, i) => v + (tgt[i] - v) * f) : tgt; U.u_x2.value = [cur[0], cur[1], cur[2] + intro, camX];
      if (hm) { const fh = 1 - Math.exp(-dt / 0.45); ht = ht ? [ht[0] + (hm[0] - ht[0]) * fh, ht[1] + (hm[1] - ht[1]) * fh] : hm.slice(); U.u_x3.value = [hm[0], hm[1], ht[0], ht[1]]; }   // Nachzügler = Leuchtspur
      else { ht = null; U.u_x3.value = [-5, -5, -5, -5]; }
      if (moving && skip === null) { skip = g.frameSkip; g.frameSkip = dtAvg < 11 ? 2 : 1; } else if (!moving && skip !== null) { g.frameSkip = skip; skip = null; } };   // Fahrt: ~60 Shader-Bilder/s (120-Hz-Handy: jedes 2. Bild)
    g._floss = { zuWelt, proj, korrektur, layout, box, kamera: () => [U.u_p0.value, U.u_p2.value, zielFahrt(), camX] };   // Contao-Vorschau: Korrektur-Werkzeug
    U.u_x3.value = [-5, -5, -5, -5]; raf = requestAnimationFrame(fahrt);
    return () => { platz(0); document.documentElement.classList.remove('rct-floss-leise'); removeEventListener('pointermove', look); removeEventListener('resize', layout); document.documentElement.removeEventListener('mouseleave', weg); removeEventListener('blur', weg);
      clearTimeout(idle); cancelAnimationFrame(raf); if (skip !== null) g.frameSkip = skip; };
  }

  window.RCT_THEME_HOOKS = window.RCT_THEME_HOOKS || {};
  window.RCT_THEME_HOOKS['floss-meer'] = { start };   // start() gibt die Aufräum-Funktion zurück; g._floss = Helfer für das Korrektur-Werkzeug und Tests
})();
