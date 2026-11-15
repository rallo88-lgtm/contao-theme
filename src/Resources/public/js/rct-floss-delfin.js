// RCT – Delfine im Floß-Meer (Theme floss-meer, Modus 21; Ralph 15.11.2026, Sheet v2 von Astra 4×4, im Shader-Labor entwickelt: D:\shader-labor\contao\delfin.js).
// Oben: eine kleine Schule (1–3 Delfine) springt kurz nacheinander quer in der Ferne aus dem Wasser (ganze Breite, auch hinter Text und Sofa) – öfter.
// Unten: selten schwimmt einer vor dem Floß knapp unter der Oberfläche vorbei (dunkel, langsam).
// Die Delfine liegen IM Shader (Modus 21, Bild-Platz 3, bis zu 3 Plätze u_d0..u_d8) zwischen Meer und Floß → das schaukelnde Floß verdeckt sie von selbst, dazu ein Spiegelbild.
// Spritzer + Ringe malt eine kleine 2D-Leinwand über dem Shader – liegt der Platscher hinter dem Floß, entfällt er.
// Nur ab 1800 px Fensterbreite (Ralph 15.11.), nicht Handy/leise Seiten/„Bewegung reduzieren“; das Sheet (47 KB) lädt erst dann.
// Test: ?delfin=1 → alle 3–6 s ein Auftritt (jeder 8. schwimmt); window.rctDelfin.spring(anzahl) / .schwimm() sofort.
(function () {
  'use strict';
  const SHEET = (window.RCT_TEX_URLS && window.RCT_TEX_URLS['delfin-v2.avif']) || '/bundles/rct/img/floss-meer/delfin-v2.avif', TW = 1024;   // Sheet im Shader 1024² (256 px je Bild); RCT_TEX_URLS: Labor unter file:// (sonst „tainted“)
  const BOGEN = [[8, 41.6], [9, 29.9], [10, 23.4], [11, 14.9], [12, 4.6], [13, -5.8], [14, -21.5], [15, -33]];   // Sheet v2: [Bild, Neigung in Grad (Nase hoch +)] – gemessen (Hauptachse der Silhouette)
  const LAENGE_PX = 236, PLAETZE = 3;   // Delfinlänge im 256er-Bild (v2); Plätze im Shader
  const TEST = /[?&]delfin=1/.test(location.search);
  const CFG = Object.assign({
    pauseMin: 3, pauseMax: 6,      // s zwischen zwei Auftritten (Ralph 15.11. live: Takt wie im Testmodus der Vorschau) – unabhängig voneinander, dürfen sich überschneiden (Ralph 15.11.: hinten mehr los, nicht überbevölkert)
    erster: [1, 1.5],              // s bis zum ersten Auftritt
    anteilSchwimmen: 0.125,        // Anteil Vorbeischwimmen (wie Testmodus: etwa jeder 8. Auftritt)
    anteilSchule: 0.2,             // Anteil Schule (2–3 zusammen, „Dreierbande“), sonst ein einzelner Springer an eigener Stelle/Richtung
    schule: [0, 0.55, 0.45],       // Schule: Wahrscheinlichkeit für 1 / 2 / 3 Springer
    variation: { hoehe: [0.5, 1.0], weite: [1.05, 1.6], tempo: [0.95, 1.12] },   // Ralph 15.11.: Sprünge variieren; die langen, weiten wirken am besten → eher flach + weit; Dauer folgt Höhe (√) und Weite
    dauer: 1.35,                   // s in der Luft (inkl. Auf- und Abtauchen)
    laenge: 0.45,                  // Springer: Länge im Verhältnis zur Floß-Bildbreite (Welt) – Ralph 15.11.: noch etwas größer
    weiter: [8, 20],               // so viel weiter hinten als das Floß (Welt-Einheiten)
    hoehe: 0.75, weite: 2.6, tief: 0.35,   // Sprunghöhe, -weite, Starttiefe unter Wasser – je Delfinlänge
    versatz: [0.28, 0.5],          // s zwischen den Springern einer Schule
    laengeSchwimm: 0.36,           // Schwimmer vorne (näher → wirkt ohnehin größer)
    vorne: [10, 40],               // Schwimmer: Bahn so viele px UNTER der Floß-Wasserlinie (= etwas vor dem Floß)
    schwimmDauer: [9.6, 12.2], schwimmFps: 7, tauch: 0.3,   // s quer durchs Bild (Ralph 15.11.: 15 % schneller als 11–14), Bilder/s der Schwimm-Schleife (Bild 0–7), Tiefe je Delfinlänge
    minBreite: 1800,               // px Fensterbreite, ab der Delfine kommen (Ralph 15.11.)
  }, window.RCT_DELFIN || {});
  if (TEST) { CFG.pauseMin = 3; CFG.pauseMax = 6; CFG.erster = [1, 1]; }
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  let img = null, geladen = false;
  const laden = () => { if (img) return; img = new Image(); img.onload = () => { geladen = true; }; img.src = SHEET; };   // erst bei Bedarf (breites Fenster)
  let g = null, gTex = null, cv = null, c2 = null, akteure = [], spritzer = [], ringe = [], raf = 0, timer = 0, alt = 0, zaehler = 0;

  function textur() { if (gTex === g) return true; laden(); if (!geladen || !g.loadTextureUnit || !g.mesh.material.uniforms.u_d8) return false;   // Sheet einmal je Shader auf Bild-Platz 3
    g.loadTextureUnit(3, img, TW, TW); gTex = g; return true; }
  function leinwand() {
    if (cv && cv.isConnected) return true; if (!g || !g.el || !g.el.parentNode) return false;
    cv = document.createElement('canvas'); cv.id = 'rct-delfin'; const cs = getComputedStyle(g.el);
    Object.assign(cv.style, { position: 'fixed', left: '0', top: '0', width: '100vw', height: '100vh', pointerEvents: 'none', zIndex: cs.zIndex === 'auto' ? '' : cs.zIndex });
    g.el.after(cv); c2 = cv.getContext('2d'); groesse(); return true;
  }
  function groesse() { if (!cv) return; const d = Math.min(devicePixelRatio || 1, 2); cv.width = Math.round(innerWidth * d); cv.height = Math.round(innerHeight * d); }

  // Kamera wie im Shader / rct-floss-meer.js placed(): Neigung u_p0 + Scroll, Höhe u_p2 + Scroll, Fahrt u_p11 + Scroll/Intro, Seite camX
  function kamera() { const U = g.mesh.material.uniforms, x2 = U.u_x2.value, gl = g.minigl.gl, k = g.resScale || 1;
    return { p0: U.u_p0.value + x2[1], pit: Math.atan((U.u_p0.value + x2[1] - 0.5) / 1.1), h: Math.max(U.u_p2.value + x2[0], 0.3), f: (U.u_p11 ? U.u_p11.value : 0) + x2[2], x: x2[3],
      k, cw: gl.drawingBufferWidth * 1.02, mw: Math.max(U.u_moon.value[3] || innerHeight * k, 1), asp: gl.drawingBufferWidth / (gl.drawingBufferHeight / 1.28) }; }
  function bild(K, wx, wy, wz) {   // Weltpunkt → Bildschirm (CSS-px) + Maßstab (px je Welt-Einheit)
    const vx = wx - K.x, vy = wy - K.h, vz = wz - K.f, dy = vy * Math.cos(K.pit) + vz * Math.sin(K.pit), dz = -vy * Math.sin(K.pit) + vz * Math.cos(K.pit);
    if (dz < 0.1) return null;
    return { x: (0.5 + vx / dz * 1.1 / K.asp) * K.cw / K.k, y: (1 - (0.5 + dy / dz * 1.1)) * K.mw / K.k, s: 1.1 * K.mw / K.k / dz, dz };
  }
  const zuWx = (K, p, sx) => K.x + (sx * K.k / K.cw - 0.5) * p.dz * K.asp / 1.1;   // Bildschirm-x → Welt-x in der Tiefe von p
  function flossKasten(K, W) { const r = g._floss.proj(W, [K.p0, K.h, K.f, K.x]), h = r.w * 941 / 1672;   // Floß-Bild auf dem Bildschirm (für Spritzer hinter dem Floß)
    return { l: r.ax - 0.53 * r.w, r: r.ax + 0.47 * r.w, o: r.ay - 0.88 * h, u: r.ay + 0.12 * h }; }

  function aktiv() { const H = document.documentElement.classList; return g && g._floss && innerWidth >= CFG.minBreite && !H.contains('rct-floss-handy') && !H.contains('rct-floss-leise') && !document.hidden; }
  const frei = () => [0, 1, 2].filter(i => !akteure.some(a => a.platz === i));   // freie Shader-Plätze
  function bereit(n) { g = window.gradient || g; return aktiv() && frei().length >= n && textur() && leinwand(); }
  function starten(neu) { const f = frei(); neu.forEach((a, i) => { a.platz = f[i]; }); akteure = akteure.concat(neu); if (!raf) raf = requestAnimationFrame(malen); return true; }
  function auftritt() { zaehler++; const sw = TEST ? zaehler % 8 === 0 : Math.random() < CFG.anteilSchwimmen;
    if (sw) return schwimm() || spring(1); return spring(Math.random() < CFG.anteilSchule ? anzahlZiehen() : 1); }
  function planen(s) { clearTimeout(timer); timer = setTimeout(() => { auftritt(); planen(CFG.pauseMin + Math.random() * (CFG.pauseMax - CFG.pauseMin)); }, s * 1000); }
  function anzahlZiehen() { let r = Math.random(), n = 1; for (const w of CFG.schule) { if ((r -= w) < 0) break; n++; } return Math.min(n, PLAETZE); }

  function spring(anzahl, startX, richtung) {   // Schule aus 1–3 Springern, gleiche Richtung, kurz nacheinander; Test: anzahl/startX (CSS-px)/richtung (1/-1) vorgeben
    const n = Math.min(anzahl || 1, PLAETZE); if (!bereit(n)) return false;
    const F = g._floss, W = F.zuWelt(F.box()); if (!W) return false;
    const K = kamera(), L0 = W.s / 1.1 * CFG.laenge, wz0 = W.wz + CFG.weiter[0] + Math.random() * (CFG.weiter[1] - CFG.weiter[0]);
    for (let v = 0; v < 40; v++) {   // Startpunkt der ganzen Schule: ganze Breite (Ralph 15.11.: auch hinter Text und Sofa)
      const dir = richtung || (Math.random() < 0.5 ? 1 : -1), p = bild(K, 0, 0, wz0); if (!p) return false;
      const sx = startX != null ? startX : innerWidth * (0.04 + Math.random() * 0.92), wx0 = zuWx(K, p, sx), schule = [];
      let t = 0;
      const V = CFG.variation, zuf = r => r[0] + Math.random() * (r[1] - r[0]), basis = { h: zuf(V.hoehe), w: zuf(V.weite) };   // Schule springt ähnlich, Einzelne ganz eigen
      for (let i = 0; i < n; i++) {   // jeder etwas versetzt (Zeit, Tiefe, Größe, Lage entlang der Bahn)
        const L = L0 * (i ? 0.82 + Math.random() * 0.25 : 1) * (n === 1 ? 0.85 + Math.random() * 0.3 : 1), wz = wz0 + (i ? (Math.random() - 0.5) * 3 : 0);
        const hf = basis.h * (i ? 0.85 + Math.random() * 0.3 : 1), wf = basis.w * (i ? 0.9 + Math.random() * 0.2 : 1);
        schule.push({ art: 'sprung', t0: performance.now() + t * 1000, wx: wx0 - dir * i * L0 * (0.55 + Math.random() * 0.4), wz, dir, L, W, raus: false, rein: false,
          hoehe: CFG.hoehe * hf, weite: CFG.weite * wf, dauer: CFG.dauer * Math.sqrt(hf) * Math.sqrt(wf) * zuf(V.tempo) });
        t += CFG.versatz[0] + Math.random() * (CFG.versatz[1] - CFG.versatz[0]);
      }
      const passt = schule.every(s => { const a = bild(K, s.wx, 0, s.wz), b = bild(K, s.wx + dir * s.L * s.weite, 0, s.wz); if (!a || !b) return false;
        return Math.min(a.x, b.x) - 0.6 * s.L * a.s >= 0 && Math.max(a.x, b.x) + 0.6 * s.L * a.s <= innerWidth; });
      if (!passt) { if (startX != null) return false; continue; }
      return starten(schule);
    }
    return false;
  }

  function schwimm(richtung) {   // vor dem Floß knapp unter Wasser quer durchs ganze Bild (Ralph 15.11.: dunkel, langsam, selten)
    if (!bereit(1) || akteure.some(a => a.art === 'schwimm')) return false;
    const F = g._floss, W = F.zuWelt(F.box()); if (!W) return false;
    const K = kamera(), L = W.s / 1.1 * CFG.laengeSchwimm, zielY = F.proj(W, [K.p0, K.h, K.f, K.x]).ay + CFG.vorne[0] + Math.random() * (CFG.vorne[1] - CFG.vorne[0]);
    let lo = K.f + 1.5, hi = W.wz; for (let i = 0; i < 30; i++) { const m = (lo + hi) / 2, q = bild(K, W.wx, 0, m); if (q && q.y > zielY) lo = m; else hi = m; }   // Tiefe suchen, deren Wasserpunkt auf zielY liegt
    const wz = (lo + hi) / 2, p = bild(K, K.x, 0, wz); if (!p) return false;
    const dir = richtung || (Math.random() < 0.5 ? 1 : -1), rand = L * p.s * 0.7;   // Start/Ende knapp außerhalb des Bildes
    return starten([{ art: 'schwimm', t0: performance.now(), dauer: CFG.schwimmDauer[0] + Math.random() * (CFG.schwimmDauer[1] - CFG.schwimmDauer[0]),
      a: zuWx(K, p, dir > 0 ? -rand : innerWidth + rand), b: zuWx(K, p, dir > 0 ? innerWidth + rand : -rand), wz, dir, L, W }]);
  }

  function weltLage(s, t) { const y = (s.hoehe + CFG.tief) * s.L * 4 * t * (1 - t) - CFG.tief * s.L;   // Parabel je Sprung (eigene Höhe/Weite), beginnt und endet unter Wasser
    return [s.wx + s.dir * s.L * s.weite * t, y, s.wz]; }
  function platsch(K, s, wx, wz, staerke) {
    const p = bild(K, wx, 0, wz), f = flossKasten(K, s.W); if (!p || (p.x > f.l && p.x < f.r && p.y > f.o && p.y < f.u)) return;   // hinter dem Floß: kein Platscher (die Leinwand liegt über dem Floß)
    const L = s.L; ringe.push({ wx, wz, t0: performance.now(), r: L * 0.25, L });
    for (let i = 0; i < 70 * staerke; i++) {   // Ralph 15.11.: mehr Spritzer
      const a = Math.random() * Math.PI * 2, v = L * (0.4 + Math.random() * 0.9);
      spritzer.push({ x: wx, y: 0, z: wz, vx: Math.cos(a) * v * 0.5, vy: L * (1.2 + Math.random() * 1.6) * staerke, vz: Math.sin(a) * v * 0.3, r: L * (0.004 + Math.random() * 0.009), gv: L * 9 }); }   // gv: Schwerkraft je Delfinlänge (Tropfen fallen in ~0,5 s zurück)
  }

  function platz(U, i, d0, d1, d2) { U['u_d' + (i * 3)].value = d0; U['u_d' + (i * 3 + 1)].value = d1; U['u_d' + (i * 3 + 2)].value = d2; }
  function alleAus() { if (!g || !g.mesh || !g.mesh.material.uniforms.u_d8) return; const U = g.mesh.material.uniforms; for (let i = 0; i < PLAETZE; i++) U['u_d' + (i * 3)].value = [0, 0, 0, 0]; }
  function zeigen(K, U, i, s, now) {   // Akteur s auf Shader-Platz i; gibt false zurück, wenn er fertig ist
    const zeit = (now - s.t0) / 1000;
    if (zeit < 0) return true;   // Springer einer Schule wartet noch
    if (s.art === 'schwimm') {
      const t = zeit / s.dauer; if (t >= 1) { if (g._floss.schau) g._floss.schau(null); return false; }   // Missi schaut wieder geradeaus
      const wy = -CFG.tauch * s.L * (1 + 0.04 * Math.sin(zeit * 0.9)), p = bild(K, s.a + (s.b - s.a) * t, wy, s.wz);   // v2: Kopf steht im Sheet still, nur die Fluke schlägt – kaum Zusatz-Schaukeln
      if (p) { const nr = Math.floor(zeit * CFG.schwimmFps) % 8, ein = Math.min(t / 0.12, 1, (1 - t) / 0.12);   // weich ein-/ausblenden (taucht aus der Tiefe auf)
        if (g._floss.schau && t > 0.04 && t < 0.96) g._floss.schau(p.x, p.y);   // Ralph 15.11.: Missi verfolgt den Schwimmer vorne (nur wenn die Maus ruht)
        platz(U, i, [p.x * K.k, p.y * K.k, s.L * p.s / LAENGE_PX * K.k, 0.012 * Math.sin(zeit * 0.9 + 1) * s.dir], [nr % 4, Math.floor(nr / 4), s.dir, -1e5], [0.95, 0.85 * ein, 1, 0]); }
      return true;
    }
    const t = zeit / s.dauer; if (t >= 1) return false;
    const [wx, wy, wz] = weltLage(s, t), p = bild(K, wx, wy, wz), q = bild(K, ...weltLage(s, Math.min(t + 0.01, 1))), wasser = bild(K, wx, 0, wz);
    if (!s.raus && wy > 0) { s.raus = true; platsch(K, s, wx, wz, 1); }
    if (s.raus && !s.rein && wy < 0 && t > 0.5) { s.rein = true; platsch(K, s, wx, wz, 1.3); }
    if (wy > 0 && Math.random() < 0.7) { const f = flossKasten(K, s.W), pp = bild(K, wx, wy, wz);   // Tropfenspur: es tropft von Körper und Fluke ab (nicht vor dem Floß, wenn er dahinter ist)
      if (pp && !(pp.x > f.l && pp.x < f.r && pp.y > f.o && pp.y < f.u)) for (let k = 0; k < 2; k++) spritzer.push({ x: wx - s.dir * s.L * (0.1 + Math.random() * 0.45), y: wy - s.L * 0.05, z: wz + (Math.random() - 0.5) * s.L * 0.1,
        vx: s.dir * s.L * (Math.random() - 0.3) * 0.4, vy: s.L * Math.random() * 0.4, vz: 0, r: s.L * (0.003 + Math.random() * 0.006), gv: s.L * 9 }); }
    if (p && q && wasser) {
      const winkel = Math.atan2(-(q.y - p.y), (q.x - p.x) * s.dir) * 180 / Math.PI;   // Bahnneigung auf dem Bildschirm (Nase hoch +, in Blickrichtung des Delfins)
      let best = BOGEN[0]; for (const e of BOGEN) if (Math.abs(e[1] - winkel) < Math.abs(best[1] - winkel)) best = e;
      const nebel = Math.min(Math.max(1 - (p.dz - 6) / 40, 0.35), 1);
      platz(U, i, [p.x * K.k, p.y * K.k, s.L * p.s / LAENGE_PX * K.k, -(winkel - best[1]) * s.dir * Math.PI / 180],   // Drehung: in Blickrichtung gespiegelt
        [best[0] % 4, Math.floor(best[0] / 4), s.dir, wasser.y * K.k], [0.42 + 0.33 * nebel, 0.6 + 0.4 * nebel, 0, 0]);   // d2.z = 0: Sprung (über Wasser)
    }
    return true;
  }

  function malen(now) {
    const dt = Math.min((now - (alt || now)) / 1000, 0.05); alt = now;
    if (!aktiv()) { akteure = []; spritzer = []; ringe = []; }
    const d = Math.min(devicePixelRatio || 1, 2); c2.setTransform(d, 0, 0, d, 0, 0); c2.clearRect(0, 0, innerWidth, innerHeight);
    alleAus();
    if (!g || !akteure.length && !spritzer.length && !ringe.length) { raf = 0; alt = 0; return; }
    const K = kamera(), U = g.mesh.material.uniforms;
    akteure = akteure.filter(s => zeigen(K, U, s.platz, s, now));
    if (!akteure.length) alleAus();
    ringe = ringe.filter(rg => { const a = (now - rg.t0) / 1000; if (a > 1.6) return false; const r = rg.r + rg.L * 0.9 * a, al = 0.35 * (1 - a / 1.6);
      c2.beginPath(); for (let i = 0; i <= 28; i++) { const w = i / 28 * Math.PI * 2, s = bild(K, rg.wx + Math.cos(w) * r, 0, rg.wz + Math.sin(w) * r); if (s) i ? c2.lineTo(s.x, s.y) : c2.moveTo(s.x, s.y); }
      c2.strokeStyle = `rgba(190,225,255,${al.toFixed(3)})`; c2.lineWidth = 1.2; c2.stroke(); return true; });
    c2.fillStyle = 'rgba(205,232,255,0.55)';   // Ralph 15.11.: feine Gischt statt Kleckse
    spritzer = spritzer.filter(s => { s.vy -= s.gv * dt; s.x += s.vx * dt; s.y += s.vy * dt; s.z += s.vz * dt; if (s.y < 0) return false;
      const p = bild(K, s.x, s.y, s.z); if (p) { c2.beginPath(); c2.arc(p.x, p.y, Math.max(s.r * p.s, 0.5), 0, Math.PI * 2); c2.fill(); } return true; });
    raf = requestAnimationFrame(malen);
  }

  function warten() { g = window.gradient; if (g && g._floss) { if (innerWidth >= CFG.minBreite) laden(); planen(CFG.erster[0] + Math.random() * (CFG.erster[1] - CFG.erster[0])); return; } setTimeout(warten, 500); }
  addEventListener('resize', groesse);
  window.rctDelfin = { spring, schwimm, cfg: CFG, info: () => ({ geladen, g: !!g, aktiv: !!(g && aktiv()), akteure: akteure.length }) };
  warten();
})();
