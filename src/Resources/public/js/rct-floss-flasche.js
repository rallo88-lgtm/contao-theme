// RCT – Flaschenpost im Floß-Meer (Theme floss-meer, Modus 21, leiser Modus; Ralph 16.11.2026, Bild von Astra).
// Auf Seiten mit einem Element der CSS-Klasse „rct-flasche“ (z. B. Kontakt-Formular) treibt ab und zu eine Flaschenpost langsam quer durchs Meer,
// knapp unterhalb dieses Elements. Die Flasche liegt IM Shader (Bild-Platz 4, u_f0/u_f1): schaukelt auf den echten Wellen, unter der Wasserlinie bläulich eingetaucht.
// Nur Desktop-artige Breite (ab 1024 px), nicht bei „Bewegung reduzieren“. Test: ?flasche=1 → sofort, ohne Pause; window.rctFlasche.los() startet eine Fahrt.
(function () {
  'use strict';
  const BILD = (window.RCT_TEX_URLS && window.RCT_TEX_URLS['flaschenpost.avif']) || '/bundles/rct/img/floss-meer/flaschenpost.avif';
  const TEST = /[?&]flasche=1/.test(location.search);
  const CFG = Object.assign({
    minBreite: 1024,            // px Fensterbreite
    dauer: [45, 60],            // s für eine Fahrt quer durchs Bild
    pause: [25, 45],            // s zwischen zwei Fahrten
    erster: [4, 8],             // s bis zur ersten Fahrt
    breitePx: 200,              // Flaschenbreite auf dem Bildschirm (px) – Ralph 16.11.: etwas größer
    abstand: 70,                // px unterhalb des .rct-flasche-Elements (Wasserlinie)
    helligkeit: 0.78, deckkraft: 0.95, seiten: 0.5,   // Bild 1024×512 → Höhe/Breite 0,5
  }, window.RCT_FLASCHE || {});
  if (TEST) { CFG.pause = [3, 3]; CFG.erster = [1, 1]; CFG.dauer = [18, 18]; }
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  let img = null, geladen = false, g = null, gTex = null, fahrt = null, raf = 0, timer = 0;
  const laden = () => { if (img) return; img = new Image(); img.onload = () => { geladen = true; }; img.src = BILD; };
  const ziel = () => document.querySelector('.rct-flasche');
  const zuf = r => r[0] + Math.random() * (r[1] - r[0]);

  function aktiv() { const H = document.documentElement.classList;
    return g && g.mesh && g.mesh.material.uniforms.u_f0 && innerWidth >= CFG.minBreite && H.contains('rct-floss-leise') && (ziel() || TEST) && !document.hidden; }
  function textur() { if (gTex === g) return true; laden(); if (!geladen || !g.loadTextureUnit) return false; g.loadTextureUnit(4, img, 1024, 512); gTex = g; return true; }

  // Kamera wie im Shader (Modus 21): Neigung u_p0 + Scroll, Höhe u_p2 + Scroll, Fahrt u_p11 + Scroll, Seite u_x2.w
  function kamera() { const U = g.mesh.material.uniforms, x2 = U.u_x2.value, gl = g.minigl.gl, k = g.resScale || 1;
    return { pit: Math.atan((U.u_p0.value + x2[1] - 0.5) / 1.1), h: Math.max(U.u_p2.value + x2[0], 0.3), f: (U.u_p11 ? U.u_p11.value : 0) + x2[2], x: x2[3],
      k, cw: gl.drawingBufferWidth * 1.02, mw: Math.max(U.u_moon.value[3] || innerHeight * k, 1), asp: gl.drawingBufferWidth / (gl.drawingBufferHeight / 1.28) }; }
  function bild(K, wx, wy, wz) {   // Weltpunkt → Bildschirm (CSS-px) + px je Welt-Einheit (waagerecht)
    const vx = wx - K.x, vy = wy - K.h, vz = wz - K.f, dy = vy * Math.cos(K.pit) + vz * Math.sin(K.pit), dz = -vy * Math.sin(K.pit) + vz * Math.cos(K.pit);
    if (dz < 0.1) return null;
    return { x: (0.5 + vx / dz * 1.1 / K.asp) * K.cw / K.k, y: (1 - (0.5 + dy / dz * 1.1)) * K.mw / K.k, sx: 1.1 / K.asp * K.cw / K.k / dz, dz };
  }

  function los() {
    g = window.gradient || g; if (!aktiv() || fahrt || !textur()) return false;
    const K = kamera(), el = ziel(), zielY = el ? Math.min(el.getBoundingClientRect().bottom + CFG.abstand, innerHeight * 0.92) : innerHeight * 0.8;
    let lo = K.f + 1, hi = K.f + 200; for (let i = 0; i < 40; i++) { const m = (lo + hi) / 2, q = bild(K, K.x, 0, m); if (q && q.y > zielY) lo = m; else hi = m; }   // Tiefe, deren Wasserpunkt auf zielY liegt
    const wz = (lo + hi) / 2, p = bild(K, K.x, 0, wz); if (!p || p.y < innerHeight * 0.3) return false;
    const L = CFG.breitePx / p.sx, rand = CFG.breitePx * 0.7, zuWx = sx => K.x + (sx * K.k / K.cw - 0.5) * p.dz * K.asp / 1.1;
    fahrt = { t0: performance.now(), dauer: zuf(CFG.dauer), a: zuWx(-rand), b: zuWx(innerWidth + rand), wz, L };
    cancelAnimationFrame(raf); raf = requestAnimationFrame(schritt); return true;
  }
  function aus() { if (g && g.mesh && g.mesh.material.uniforms.u_f1) g.mesh.material.uniforms.u_f1.value = [0, 0, 0, 0]; }
  function schritt(now) {
    if (!fahrt || !aktiv()) { fahrt = null; aus(); raf = 0; return; }
    const t = (now - fahrt.t0) / 1000 / fahrt.dauer; if (t >= 1) { fahrt = null; aus(); raf = 0; return; }
    const U = g.mesh.material.uniforms, ein = Math.min(t / 0.06, 1, (1 - t) / 0.06);
    U.u_f0.value = [fahrt.a + (fahrt.b - fahrt.a) * t, fahrt.wz, fahrt.L, 0.03 * Math.sin(now / 1000 * 0.7)];   // kaum Eigen-Schaukeln, die Wellen machen den Rest
    U.u_f1.value = [CFG.helligkeit, CFG.deckkraft * ein, 1, CFG.seiten];
    raf = requestAnimationFrame(schritt);
  }
  function planen(s) { clearTimeout(timer); timer = setTimeout(() => { if (!fahrt) los(); planen(fahrt ? (fahrt.dauer + zuf(CFG.pause)) : zuf(CFG.pause)); }, s * 1000); }
  function warten() { g = window.gradient; if (g && g.mesh) { if (aktiv()) laden(); planen(zuf(CFG.erster)); return; } setTimeout(warten, 500); }
  window.rctFlasche = { los, cfg: CFG, info: () => ({ geladen, aktiv: !!(g && aktiv()), fahrt: !!fahrt }) };
  warten();
})();
