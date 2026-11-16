// RCT – Geisterschiff im Floß-Meer (Theme floss-meer, Modus 21, leiser Modus; Ralph 16.11.2026, Sheet von Astra 4×2, 543×362 je Bild).
// Auf Seiten mit einem Element der CSS-Klasse „rct-geisterschiff“ (gedacht für die 404-Seite „Hier ist nur Wasser.“) zieht weit hinten im Nebel
// langsam ein Geisterschiff vorbei: halb durchsichtig, grünlich schimmernd, Fenster und Laternen glühen, Segel flattern, Spiegelbild im Wasser.
// Das Schiff liegt IM Shader (Bild-Platz 5, u_g0/u_g1). Ab 800 px Breite, nicht bei „Bewegung reduzieren“. Test: ?geist=1 → sofort; window.rctGeist.los().
(function () {
  'use strict';
  const BILD = (window.RCT_TEX_URLS && window.RCT_TEX_URLS['geisterschiff.avif']) || '/bundles/rct/img/floss-meer/geisterschiff.avif';
  const TEST = /[?&]geist=1/.test(location.search);
  const CFG = Object.assign({
    minBreite: 800,
    dauer: [130, 160],          // s für eine Fahrt quer durchs Bild (Ralph 16.11.: langsamer, gemächlich)
    pause: [40, 80],            // s zwischen zwei Fahrten
    erster: [2, 4],             // s bis zur ersten Fahrt (auf der 404 bleibt keiner lange)
    breiteAnteil: 0.2,          // Schiffsbreite als Anteil der Fensterbreite
    horizontAbstand: 0.045,     // Wasserlinie so weit unter dem Horizont (Anteil der Fensterhöhe) – weit hinten im Nebel
    fps: 4, deckkraft: 0.9, gluehen: 0,   // Ralph 16.11.: Leuchten (Fenster-Glühen + Lichthof) erst mal aus; Segel ruhiger
  }, window.RCT_GEIST || {});
  if (TEST) { CFG.pause = [3, 3]; CFG.erster = [0.5, 0.5]; CFG.dauer = [70, 70]; }
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  let img = null, geladen = false, g = null, gTex = null, fahrt = null, raf = 0, timer = 0;
  const laden = () => { if (img) return; img = new Image(); img.onload = () => { geladen = true; }; img.src = BILD; };
  const zuf = r => r[0] + Math.random() * (r[1] - r[0]);

  function aktiv() { const H = document.documentElement.classList;
    return g && g.mesh && g.mesh.material.uniforms.u_g0 && innerWidth >= CFG.minBreite && H.contains('rct-floss-leise') && (document.querySelector('.rct-geisterschiff') || TEST) && !document.hidden; }
  function textur() { if (gTex === g) return true; laden(); if (!geladen || !g.loadTextureUnit) return false; g.loadTextureUnit(5, img, 2172, 724); gTex = g; return true; }

  function kamera() { const U = g.mesh.material.uniforms, x2 = U.u_x2.value, gl = g.minigl.gl, k = g.resScale || 1;
    return { p0: U.u_p0.value + x2[1], pit: Math.atan((U.u_p0.value + x2[1] - 0.5) / 1.1), h: Math.max(U.u_p2.value + x2[0], 0.3), f: (U.u_p11 ? U.u_p11.value : 0) + x2[2], x: x2[3],
      k, cw: gl.drawingBufferWidth * 1.02, mw: Math.max(U.u_moon.value[3] || innerHeight * k, 1), asp: gl.drawingBufferWidth / (gl.drawingBufferHeight / 1.28) }; }
  function bild(K, wx, wy, wz) {
    const vx = wx - K.x, vy = wy - K.h, vz = wz - K.f, dy = vy * Math.cos(K.pit) + vz * Math.sin(K.pit), dz = -vy * Math.sin(K.pit) + vz * Math.cos(K.pit);
    if (dz < 0.1) return null;
    return { x: (0.5 + vx / dz * 1.1 / K.asp) * K.cw / K.k, y: (1 - (0.5 + dy / dz * 1.1)) * K.mw / K.k, dz };
  }

  function los() {
    g = window.gradient || g; if (!aktiv() || fahrt || !textur()) return false;
    const K = kamera(), horizontY = (1 - K.p0) * innerHeight, zielY = horizontY + CFG.horizontAbstand * innerHeight;   // Horizont liegt bei (1 − Neigung) der Fensterhöhe
    let lo = K.f + 1, hi = K.f + 400; for (let i = 0; i < 40; i++) { const m = (lo + hi) / 2, q = bild(K, K.x, 0, m); if (q && q.y > zielY) lo = m; else hi = m; }
    const wz = (lo + hi) / 2, p = bild(K, K.x, 0, wz); if (!p) return false;
    const breite = innerWidth * CFG.breiteAnteil, rand = breite * 0.6, zuWx = sx => K.x + (sx * K.k / K.cw - 0.5) * p.dz * K.asp / 1.1;
    fahrt = { t0: performance.now(), dauer: zuf(CFG.dauer), a: zuWx(-rand), b: zuWx(innerWidth + rand), wz, breite };
    cancelAnimationFrame(raf); raf = requestAnimationFrame(schritt); return true;
  }
  function aus() { if (g && g.mesh && g.mesh.material.uniforms.u_g0) g.mesh.material.uniforms.u_g0.value = [0, 0, 0, 0]; }
  function schritt(now) {
    if (!fahrt || !aktiv()) { fahrt = null; aus(); raf = 0; return; }
    const t = (now - fahrt.t0) / 1000 / fahrt.dauer; if (t >= 1) { fahrt = null; aus(); raf = 0; return; }
    const K = kamera(), p = bild(K, fahrt.a + (fahrt.b - fahrt.a) * t, 0, fahrt.wz); if (!p) { raf = requestAnimationFrame(schritt); return; }
    const U = g.mesh.material.uniforms, zeit = now / 1000, nr = Math.floor(zeit * CFG.fps) % 8;
    const ein = Math.min(t / 0.08, 1, (1 - t) / 0.08), flacker = 0.88 + 0.12 * Math.sin(zeit * 1.3) * Math.sin(zeit * 0.47 + 1);   // taucht aus dem Nebel auf, flackert geisterhaft
    const hub = Math.sin(zeit * 0.6) * 2.5;   // sachtes Heben und Senken auf der Dünung (px)
    U.u_g0.value = [p.x * K.k, (p.y + hub) * K.k, fahrt.breite / 543 * K.k, 0];
    U.u_g1.value = [nr % 4, Math.floor(nr / 4), CFG.deckkraft * ein * flacker, CFG.gluehen * (0.8 + 0.2 * Math.sin(zeit * 2.1))];
    raf = requestAnimationFrame(schritt);
  }
  function planen(s) { clearTimeout(timer); timer = setTimeout(() => { if (!fahrt) los(); planen(fahrt ? (fahrt.dauer + zuf(CFG.pause)) : zuf(CFG.pause)); }, s * 1000); }
  function warten() { g = window.gradient; if (g && g.mesh) { if (aktiv()) laden(); planen(zuf(CFG.erster)); return; } setTimeout(warten, 500); }
  window.rctGeist = { los, cfg: CFG, info: () => ({ geladen, aktiv: !!(g && aktiv()), fahrt: !!fahrt }) };
  warten();
})();
