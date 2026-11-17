// Test: RCT läuft auch, wenn der Browser localStorage sperrt (privates Fenster, strenge Datenschutz-Einstellungen)
// Aufruf: node tools/floss-meer-test/server.js 8777 & PWC=<playwright-core> node tools/floss-meer-test/speicher-test.js
const { chromium } = require(process.env.PWC);
const fs = require('fs'), path = require('path');
const BASIS = 'http://localhost:' + (process.env.PORT || 8777);
const TPL = path.join(__dirname, '..', '..', 'src', 'Resources', 'contao', 'templates');
let fails = 0; const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) fails++; };
const SPERRE = () => Object.defineProperty(window, 'localStorage', { configurable: true, get() { throw new DOMException('The operation is insecure.', 'SecurityError'); } });
const kopfSkripte = () => fs.readdirSync(TPL).filter(f => /^fe_page.*\.twig$/.test(f)).flatMap(f => {
  const t = fs.readFileSync(path.join(TPL, f), 'utf8'), m = t.match(/\{% block head_init %\}([\s\S]*?)\{% endblock %\}/);
  return m ? [...m[1].matchAll(/<script>([\s\S]*?)<\/script>/g)].map(s => ({ f, js: s[1] })) : []; });
(async () => {
  const b = await chromium.launch({ channel: 'chrome' });
  { const c = await b.newContext({ viewport: { width: 1440, height: 860 } }), p = await c.newPage(), errs = [];
    await p.addInitScript(SPERRE); p.on('pageerror', e => errs.push(e.message));
    await p.goto(BASIS + '/?theme=floss-meer'); await p.waitForTimeout(3000);
    ok(errs.length === 0, 'rct.js mit gesperrtem Speicher ohne Absturz ' + JSON.stringify(errs.slice(0, 3)));
    ok(await p.evaluate(() => !!(window.gradient && window.gradient.conf && window.gradient.conf.playing)), 'Hintergrund-Shader läuft trotzdem');
    for (const k of kopfSkripte()) { const e = await p.evaluate(js => { try { (0, eval)(js); return null; } catch (x) { return x.message; } }, k.js);
      ok(e === null, `Kopf-Skript ${k.f} mit gesperrtem Speicher ohne Fehler ${e ? '(' + e + ')' : ''}`); }
    await c.close(); }
  { const c = await b.newContext({ viewport: { width: 1440, height: 860 } }), p = await c.newPage(), errs = [];   // Gegenprobe: normaler Speicher funktioniert wie bisher
    p.on('pageerror', e => errs.push(e.message));
    await p.goto(BASIS + '/?theme=floss-meer'); await p.waitForTimeout(2500);
    const r = await p.evaluate(() => { localStorage.setItem('user-theme', 'wave'); return localStorage.getItem('user-theme'); });
    ok(errs.length === 0 && r === 'wave', 'Normaler Speicher: keine Fehler, Speichern geht');
    for (const k of kopfSkripte()) { const e = await p.evaluate(js => { try { (0, eval)(js); return document.documentElement.getAttribute('data-theme'); } catch (x) { return 'FEHLER ' + x.message; } }, k.js);
      if (k.f === 'fe_page.html.twig') ok(e === 'wave', 'Kopf-Skript liest gespeichertes Theme weiterhin (' + e + ')'); else ok(!/^FEHLER/.test(String(e)), 'Kopf-Skript ' + k.f + ' normal ohne Fehler'); }
    await c.close(); }
  await b.close(); console.log(fails ? `${fails} FEHLER` : 'ALLES GRÜN'); process.exit(fails ? 1 : 0);
})();
