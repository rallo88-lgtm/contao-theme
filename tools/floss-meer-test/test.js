// Tests Zweig floss-meer: Regression (master vs. Zweig, pixelgleich) + Theme floss-meer
const { chromium } = require(process.env.PWC);
let fails = 0; const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) fails++; };
const shot = () => { const g = window.gradient, gl = g.minigl.gl; g.pause(); g.mesh.material.uniforms.u_time.value = 5000; g.minigl.render();
  const W = gl.drawingBufferWidth, H = gl.drawingBufferHeight, b = new Uint8Array(W * H * 4); gl.readPixels(0, 0, W, H, gl.RGBA, gl.UNSIGNED_BYTE, b); return { W, H, px: Array.from(b) }; };
(async () => {
  const b = await chromium.launch({ channel: 'chrome' });
  const page = async (url, vp = { width: 1280, height: 800 }, extra = {}) => { const c = await b.newContext(Object.assign({ viewport: vp }, extra)), p = await c.newPage(), errs = [];
    p.on('pageerror', e => errs.push(e.message)); p.on('console', m => m.type() === 'error' && errs.push(m.text().slice(0, 200)));
    await p.goto(url); await p.waitForFunction(() => window.gradient && window.gradient.mesh && window.gradient.conf.playing, null, { timeout: 8000 }); await p.waitForTimeout(1500); return { p, c, errs }; };
  for (const th of ['datenmeer', 'default', 'wave', 'neon-grid', 'honey-moon']) {
    const A = await page('http://localhost:8765/master.html?theme=' + th), B = await page('http://localhost:8765/?theme=' + th);
    const a = await A.p.evaluate(shot), z = await B.p.evaluate(shot); let max = 0; if (a.W === z.W && a.H === z.H) for (let i = 0; i < a.px.length; i++) max = Math.max(max, Math.abs(a.px[i] - z.px[i])); else max = 999;
    ok(max <= 1, `Regression ${th}: Zweig = master (max. Abweichung ${max}, ${a.W}×${a.H})`); await A.c.close(); await B.c.close();
  }
  { const { p, c, errs } = await page('http://localhost:8765/?theme=floss-meer', { width: 1440, height: 860 });
    const r = await p.evaluate(() => { const g = window.gradient, u = g.mesh.material.uniforms, el = document.querySelector('.rct-content-inner').getBoundingClientRect();
      return { mode: g.lineMode, h: g.height, t: [u.u_tex0_ready.value, u.u_tex1_ready.value, u.u_tex2_ready.value], x0: [...u.u_x0.value], right: el.right - 20, left: el.left + 20 }; });
    ok(r.mode === 21 && r.h === 1024, `floss-meer: Modus 21, Desktop Render-Höhe ${r.h}`);
    ok(r.t.every(v => v === 1), 'Mond, Floß und Missi geladen ' + JSON.stringify(r.t));
    ok(await p.evaluate(() => !document.documentElement.classList.contains('rct-floss-leise')), 'Home (Klasse rct-floss): volle Bühne, kein Glas');
    ok(r.x0[0] >= r.left && r.x0[0] + r.x0[2] <= r.right + 1, `Floß in der Inhaltsspalte (${Math.round(r.x0[0])}…${Math.round(r.x0[0] + r.x0[2])} innerhalb ${Math.round(r.left)}…${Math.round(r.right)})`);
    await p.evaluate(() => scrollTo(0, innerHeight)); await p.waitForTimeout(1500);
    const x2 = await p.evaluate(() => [...window.gradient.mesh.material.uniforms.u_x2.value]);
    ok(Math.abs(x2[0]) < 0.02 && Math.abs(x2[1] - 0.45) < 0.02, 'Scroll 1 Bildschirmhöhe → Station 1: Neigung + Anflug, Höhe noch 0 ' + JSON.stringify(x2.slice(0, 2).map(v => +v.toFixed(3))));
    await p.evaluate(() => scrollTo(0, innerHeight * 2)); await p.waitForTimeout(1500);
    const x2b = await p.evaluate(() => [...window.gradient.mesh.material.uniforms.u_x2.value]);
    ok(Math.abs(x2b[0] + 0.8) < 0.03 && Math.abs(x2b[1] - 0.45) < 0.02, 'Scroll 2 Bildschirmhöhen → Station 2: jetzt sinkt die Höhe ' + JSON.stringify(x2b.slice(0, 2).map(v => +v.toFixed(3))));
    await p.mouse.move(300, 700); await p.waitForTimeout(600);
    ok(await p.evaluate(() => window.gradient.mesh.material.uniforms.u_x3.value[0] > -1), 'Hover: Maus über dem Meer kommt im Shader an');
    await p.evaluate(() => window.applyTheme('datenmeer', false)); await p.waitForTimeout(2500);
    ok(await p.evaluate(() => window._rctThemeHookStop === null && window.gradient.lineMode === 20), 'Theme-Wechsel → Floß-JS abgemeldet, Datenmeer läuft');
    ok(errs.filter(e => !/favicon/.test(e)).length === 0, 'Desktop ohne JS-/Shader-Fehler ' + JSON.stringify(errs.slice(0, 3))); await c.close(); }
  { const { p, c, errs } = await page('http://localhost:8765/?theme=floss-meer&leise', { width: 1440, height: 860 });   // Unterseite ohne Klasse rct-floss: leises Meer
    await p.waitForTimeout(1000);
    const r = await p.evaluate(() => { const g = window.gradient, u = g.mesh.material.uniforms, cf = g.themeConfig.paramsLeise;
      return { mode: g.lineMode, t: [u.u_tex1_ready.value, u.u_tex2_ready.value], p: Object.keys(cf).map(i => +u['u_p' + i].value.toFixed(4)), soll: Object.values(cf), kl: document.documentElement.classList.contains('rct-floss-leise'),
        alpha: getComputedStyle(document.documentElement).getPropertyValue('--rct-main-alpha').trim() }; });
    ok(r.mode === 21 && r.t.every(v => v === 0), 'Leise: Modus 21, kein Floß/Missi geladen ' + JSON.stringify(r.t));
    ok(JSON.stringify(r.p) === JSON.stringify(r.soll), 'Leise: andere Kamera (paramsLeise) ' + JSON.stringify(r.p));
    ok(r.kl && r.alpha === '0.35', 'Leise: Glas hinter dem Inhalt (--rct-main-alpha ' + r.alpha + ')');
    await p.evaluate(() => scrollTo(0, innerHeight)); await p.waitForTimeout(1500);
    const x2 = await p.evaluate(() => [...window.gradient.mesh.material.uniforms.u_x2.value]);
    ok(Math.abs(x2[0] + 0.25) < 0.02 && Math.abs(x2[1] - 0.15) < 0.02, 'Leise: sanfte Scroll-Kamera ' + JSON.stringify(x2.slice(0, 2).map(v => +v.toFixed(3))));
    await p.mouse.move(300, 700); await p.waitForTimeout(600);
    ok(await p.evaluate(() => window.gradient.mesh.material.uniforms.u_x3.value[0] > -1), 'Leise: Hover-Leuchten bleibt an');
    await p.evaluate(() => window.applyTheme('datenmeer', false)); await p.waitForTimeout(2500);
    ok(await p.evaluate(() => !document.documentElement.classList.contains('rct-floss-leise')), 'Leise: Theme-Wechsel räumt die Glas-Klasse weg');
    ok(errs.filter(e => !/favicon/.test(e)).length === 0, 'Leise ohne Fehler ' + JSON.stringify(errs.slice(0, 3))); await c.close(); }
  for (let [vw, ziel] of [[1920, 0], [2560, 3]]) {   // Intro: Kamera fährt in 4 s von −4 auf das Ziel je Bildschirmbreite, Text blendet ein
    const c = await b.newContext({ viewport: { width: vw, height: Math.round(vw * 9 / 16) } }), p = await c.newPage(); await p.goto('http://localhost:8765/?theme=floss-meer');
    await p.waitForFunction(() => window.gradient && window.gradient.mesh && window.gradient.mesh.material.uniforms.u_x2, null, { timeout: 8000 }); await p.waitForTimeout(400);
    const op = () => p.evaluate(() => { const e = document.querySelector('.rct-floss'); return e ? +getComputedStyle(e).opacity : 1; });
    const frueh = await p.evaluate(() => ({ z: window.gradient.mesh.material.uniforms.u_x2.value[2] })); frueh.intro = (await op()) < 0.05;
    await p.waitForTimeout(4600); const spaet = await p.evaluate(() => ({ z: window.gradient.mesh.material.uniforms.u_x2.value[2], soll: window.gradient._floss.kamera()[2] })); spaet.intro = (await op()) < 0.99; ziel = spaet.soll;   // Ziel = Kamera-Rechnung (Ralphs Korrekturen)
    ok(frueh.z < -2.5 && frueh.intro && Math.abs(spaet.z - ziel) < 0.05 && !spaet.intro, `Intro ${vw} px: Fahrt ${frueh.z.toFixed(2)} → ${spaet.z.toFixed(2)} (Ziel ${ziel}), Text erst danach`); await c.close(); }
  { const { p, c, errs } = await page('http://localhost:8765/?theme=floss-meer', { width: 390, height: 844 }, { isMobile: true, hasTouch: true });
    const r = await p.evaluate(() => { const g = window.gradient, u = g.mesh.material.uniforms, k = g.resScale || 1, x0 = u.u_x0.value, mw = u.u_moon.value[3] / k;
      const hero = document.querySelector('.rct-floss').getBoundingClientRect(), naechst = document.querySelector('.rct-floss').nextElementSibling;
      return { h: g.height, p2: u.u_p2.value, horizontY: (1 - u.u_p0.value) * mw, hinten: (x0[1] + 0.55 * x0[3]) / k, flossOben: x0[1] / k, flossUnten: (x0[1] + x0[3]) / k, textUnten: hero.bottom, naechstOben: naechst ? naechst.getBoundingClientRect().top : 1e9 }; });
    ok(r.h === 600 && r.p2 === 1.0, `Handy: Render-Höhe ${r.h}, Kamera ${r.p2}`);
    ok(r.flossOben >= r.textUnten - 2 && r.naechstOben >= r.flossUnten - 2, `Handy untereinander: Floß unter dem Text, nächster Inhalt darunter (Text ${Math.round(r.textUnten)}, Floß ${Math.round(r.flossOben)}–${Math.round(r.flossUnten)}, nächster ${Math.round(r.naechstOben)})`);
    ok(r.hinten >= r.horizontY - 2, `Handy: Floß ragt nicht über den Horizont (hintere Kante ${Math.round(r.hinten)}, Horizont ${Math.round(r.horizontY)})`);
    await p.screenshot({ path: 'D:/tmp/rct-floss-handy.png' });
    ok(errs.length === 0, 'Handy ohne Fehler ' + JSON.stringify(errs.slice(0, 3))); await c.close(); }
  { const { p, c } = await page('http://localhost:8765/?theme=floss-meer', { width: 1280, height: 800 }, { reducedMotion: 'reduce' });
    await p.evaluate(() => scrollTo(0, innerHeight)); await p.waitForTimeout(1200);
    ok(await p.evaluate(() => window.gradient.mesh.material.uniforms.u_x2.value[0] === 0), '„Bewegung reduzieren“ → Kamera bleibt stehen'); await c.close(); }
  await b.close(); console.log(fails ? `\n${fails} FEHLGESCHLAGEN` : '\nALLES GRÜN'); process.exit(fails ? 1 : 0);
})();
