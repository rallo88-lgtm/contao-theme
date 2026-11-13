# Test Theme „Floß im Datenmeer“ (Modus 21)
1. `node tools/floss-meer-test/server.js 8765` (liefert /bundles/rct/* aus src/Resources/public)
2. Regression gegen master: `git show master:src/Resources/public/js/gl-bg-animation.js > tools/floss-meer-test/gl-master.js` (ebenso rct.js → rct-master.js, rct-canvas-config.js → cfg-master.js) und `master.html` wie index.html mit diesen drei Dateien (ohne rct-floss-meer.js) anlegen
3. `PWC=<playwright-core> node tools/floss-meer-test/test.js` → erwartet „ALLES GRÜN“
