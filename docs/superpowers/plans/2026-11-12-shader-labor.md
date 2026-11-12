# Shader-Labor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eine lokale Sandbox-Seite `tools/shader-labor/labor.html`, in der Ralph alle RCT-Hintergrund-Shader auswählt, per Regler live verändert, Stände speichert/vergleicht/exportiert und neue Shader-Experimente fährt – mit exakt derselben Engine wie das Theme.

**Architecture:** Das Labor lädt die echten Bundle-Dateien (`rct-canvas-config.js`, `gl-bg-animation.js`, `rct.js`, Theme-CSS) per relativem Pfad und benutzt `window.applyTheme()` unverändert – so sind Shader, CSS-Schichten und Punkte identisch zum Theme. Die Engine bekommt fünf kleine, rückwärtskompatible Haken (Regler-Plätze `u_p0…u_p7`, Zusatz-Modi, Breiten-Vorgabe, Auflösungs-Faktor, Textur-URL-Ersatz). Die Labor-Logik (Stände, Favoriten, Vergleich, Export) lebt testbar in einer reinen JS-Datei, die Oberfläche in einer zweiten.

**Tech Stack:** Vanilla JS (ES2019, kein Build), WebGL1, Node `node:test` für die reine Logik, `playwright-core` (Chrome) für Browser-Tests.

**Spec:** `docs/superpowers/specs/2026-11-12-shader-labor-design.md`

## Global Constraints

- Läuft lokal als Datei (`file://`) in Chrome/Edge – kein Server, kein Build-Schritt, kein Internet.
- WebGL1 im gemeinsamen Shader (kein `fwidth`, keine WebGL2-Features).
- Keine Kopie der Engine: das Labor lädt `src/Resources/public/js/gl-bg-animation.js` und `rct.js` direkt.
- Engine-Änderungen müssen das Theme unverändert lassen: alle Presets aus `RCT_CANVAS_CONFIG` rendern vorher/nachher gleich.
- Experimente (neue Modi) liegen NUR in `tools/shader-labor/experimente.js`, nie in der ausgelieferten Engine, bis Ralph abnimmt.
- `localStorage`-Schlüssel immer mit Präfix `shaderlabor:`.
- Code-Kommentare nur am echten Zeilenende (nie mitten in einer Codezeile).
- Oberflächentexte Deutsch.
- Perf-First: Regler-UI darf die Animation nicht ausbremsen (Regler-Updates gedrosselt, Neuaufbau entprellt ≥ 250 ms).
- Browser-Tests mit `playwright-core` aus `C:/Users/Administrator/AppData/Local/npm-cache/_npx/705bc6b22212b352/node_modules/playwright-core`, `chromium.launch({ channel: 'chrome' })`, Pfad über Umgebungsvariable `PWC`.

## Review Focus

1. **Schnelles Durchklicken der Presets** (z. B. 20 Wechsel in 5 s): es darf nur ein `Gradient` animieren, keine verwaisten Mouse-Handler, keine wachsende Frame-Zeit → Test in Task 3.
2. **Kaputter oder leerer `localStorage`** (ungültiges JSON, fremde Schlüssel, Speicher gesperrt): Labor startet mit Original-Werten statt abzustürzen → Test in Task 2.
3. **Import einer JSON aus anderer Labor-Version / mit unbekannten Feldern / fremdem Theme-Namen**: unbekannte Felder ignorieren, fehlende mit Original füllen, klare Meldung statt Absturz → Test in Task 2.
4. **No-GL-Themes** (`sparta`, `sparta2`, `baker-street`) und Themes mit `bgImage`/`neuralLogo`: Auswahl funktioniert ohne Fehler, Regler für nicht vorhandene Effekte sind ausgegraut → Test in Task 3.
5. **Fenstergröße ändern bzw. Handy-Rahmen an/aus**: Canvas, Mond (Datenmeer) und Leistungsanzeige folgen ohne Neuladen → Test in Task 4.

---

### Task 1: Engine-Haken (rückwärtskompatibel)

**Files:**
- Modify: `src/Resources/public/js/gl-bg-animation.js` (Uniforms in `initMaterial()` ~Z. 2140, `resize` ~Z. 1960, `setMoon()`/`init()` ~Z. 2290–2310, Fragment-Aufbau ~Z. 2106)
- Modify: `src/Resources/public/js/rct.js` (`applyTheme`, nach `window.gradient.frameSkip = …` ~Z. 607)
- Create: `tools/shader-labor/tests/engine-regression.js`, `tools/shader-labor/tests/engine-page.html`

**Interfaces:**
- Produces (Engine, alle optional, ohne Angabe = heutiges Verhalten):
  - Uniforms `u_p0 … u_p7` (float, nur Fragment) ← `gradient.params: number[8]` (fehlend = 0). In `applyTheme`: `window.gradient.params = p.params || [];`
  - `window.RCT_EXTRA_MODES: string` – zusätzlicher GLSL-Text mit Modus-Blöcken im bekannten `if (u_line_mode > a && u_line_mode < b) { … }`-Muster; wird vor der letzten Zeile `gl_FragColor = vec4(color, 1.0);` des Uber-Shaders eingefügt, BEVOR `rctShaderForMode` zuschneidet.
  - `gradient.viewW: number` – ersetzt `window.innerWidth` in `resize`, `setMoon` und der Textur-Größenwahl.
  - `gradient.resScale: number` (Standard 1) – Canvas-Breite und -Höhe (600) werden damit multipliziert; CSS-Größe bleibt.
  - `window.RCT_TEX_URLS: { [dateiname]: url }` – `loadTexture` nimmt `RCT_TEX_URLS[basename(url)]`, falls vorhanden (für Data-URIs unter `file://`).

- [ ] **Step 1: Regressions-Test schreiben** – `engine-regression.js` lädt `engine-page.html` zweimal (Engine-Stand `git show HEAD:…` als Kopie in einem Temp-Ordner vs. Arbeitskopie), baut für jedes Preset aus `RCT_CANVAS_CONFIG` per `applyTheme(name)` den Hintergrund, friert die Zeit ein (`gradient.t` fest, ein Frame rendern) und vergleicht die Pixel: `assert(maxChannelDiff <= 2)` für jedes Preset. Zusätzlich: Seite ohne Konsolenfehler.
- [ ] **Step 2: Haken-Tests ergänzen** (laufen nur gegen die Arbeitskopie): (a) Preset mit `params: [0.5]` → `uniforms.u_p0.value === 0.5`; (b) `RCT_EXTRA_MODES` mit Modus 99, der `gl_FragColor = vec4(1.0,0.0,0.0,1.0)` setzt → Mittelpixel rot; (c) `viewW = 390` → `gradient.width === 390`; (d) `resScale = 0.5` → `canvas.height === 300`; (e) `RCT_TEX_URLS['theme-datenmeer-mond.avif'] = <Data-URI>` → nach Laden `u_tex0_ready === 1`.
- [ ] **Step 3: Tests laufen lassen – Haken-Tests schlagen fehl, Regression grün.** Run: `PWC=… node tools/shader-labor/tests/engine-regression.js` · Expected: Regression PASS, Haken (a)–(e) FAIL.
- [ ] **Step 4: Haken einbauen** (Signaturen oben; `params` als 8 einzelne `Uniform({ value, excludeFrom: "vertex" })`).
- [ ] **Step 5: Tests grün.** Expected: alle Presets `maxChannelDiff <= 2`, Haken (a)–(e) PASS, keine Konsolenfehler.
- [ ] **Step 6: Commit** `feat(webgl): Labor-Haken – u_p0..7/params, RCT_EXTRA_MODES, viewW, resScale, RCT_TEX_URLS (Theme unverändert)`

### Task 2: Labor-Logik (rein, ohne DOM)

**Files:**
- Create: `tools/shader-labor/labor-state.js` (UMD: `module.exports` in Node, `window.ShaderLabor` im Browser)
- Create: `tools/shader-labor/tests/labor-state.test.js`

**Interfaces:**
- Produces:
  - `createStore(storage, config)` → Store; `storage` = Objekt mit `getItem/setItem/removeItem` (im Browser `localStorage`, im Test eine Map-Attrappe); `config` = `RCT_CANVAS_CONFIG`.
  - `store.get(preset): Settings` – Original-Preset überlagert mit eigenen Änderungen; `Settings` = Preset-Felder wie in `RCT_CANVAS_CONFIG` + `colors: [hex×4]` + `params: number[8]`.
  - `store.set(preset, field, value)` · `store.reset(preset)` (zurück auf Original) · `store.changed(preset): boolean`
  - `store.remember(preset)` / `store.compareState(preset): Settings` (Standard = Original) · `store.toggleCompare(): boolean` (true = zeigt gemerkten Stand)
  - `store.favorites(): {name, preset, settings, date}[]` · `store.saveFavorite(name, preset)` · `store.loadFavorite(name)` · `store.deleteFavorite(name)`
  - `store.exportJSON(preset): string` – Felder: `labor: "shader-labor"`, `version: 1`, `date`, `theme`, `shaderMode`, alle Preset-Felder, `colors`, `params`
  - `store.importJSON(text): { ok: boolean, preset?: string, message: string }`
  - Alle Schlüssel mit Präfix `shaderlabor:`.

- [ ] **Step 1: Tests schreiben** (`node:test`, `assert`): Änderung an `wave` überlebt neuen Store mit gleichem Storage (F5); Wechsel `wave`→`lotus`→`wave` behält Änderung; `reset` stellt Original her und `changed()` wird false; `remember`+`toggleCompare` liefert abwechselnd gemerkten/aktuellen Stand, ohne `remember` = Original; Favorit speichern/laden/löschen; Export enthält `labor`, `version: 1`, `theme`, `shaderMode`, `colors`, `params`; Import des eigenen Exports → `ok: true` und identische `get()`-Werte; **Review Focus 2:** Storage mit kaputtem JSON / `getItem` wirft → Store liefert Original, kein Throw; **Review Focus 3:** Import mit unbekanntem Feld `foo`, fehlendem `colors` und unbekanntem Theme → `ok: false` bzw. Felder ignoriert/aufgefüllt, `message` nicht leer.
- [ ] **Step 2: Laufen lassen, fehlschlagen sehen.** Run: `node --test tools/shader-labor/tests/` · Expected: FAIL (`createStore is not a function`)
- [ ] **Step 3: `labor-state.js` implementieren.**
- [ ] **Step 4: Grün.** Expected: alle Tests PASS.
- [ ] **Step 5: Commit** `feat(shader-labor): Labor-Logik – Stände pro Preset, Vergleich, Favoriten, Export/Import`

### Task 3: Labor-Seite mit Preset-Auswahl und Reglern

**Files:**
- Create: `tools/shader-labor/labor.html`, `tools/shader-labor/labor-ui.js`, `tools/shader-labor/labor.css`
- Create: `tools/shader-labor/experimente.js` (setzt `window.RCT_EXTRA_MODES = ''` und `window.SHADER_LAB_MODES = {}`; Platz für neue Modi)
- Create: `tools/shader-labor/make-assets.js` → erzeugt `tools/shader-labor/assets.js` (`window.RCT_TEX_URLS = { 'theme-datenmeer-mond.avif': 'data:image/avif;base64,…' }`)
- Create: `tools/shader-labor/tests/labor-smoke.js`

**Interfaces:**
- Consumes: Task 1 (`params`, `viewW`, `resScale`, `RCT_TEX_URLS`, `RCT_EXTRA_MODES`), Task 2 (`ShaderLabor.createStore`).
- Produces: `window.SHADER_LAB_MODES[mode] = [{ key: 'p0'…'p7', label, min, max, step, value }]` – Regler-Beschreibung je Modus (für Task 5 und alle späteren Experimente).
- Seite setzt vor `rct.js`: `rctCanvasEnabled = rctDotsEnabled = true`, `rctAuroraSpeed = 1`; enthält `#wrapper`, `#main`, `#gradient-canvas`, `#rct-dots-canvas`, wie `fe_page.html.twig` sie anlegt.
- Anwenden: Regler, die direkt einem Uniform entsprechen (`u_line_count/width/speed/color`, `u_bg_color`, `u_wave_strength`, `u_slime_mode`, `u_p*`), wirken sofort; alles andere (Modus, Aurora-Felder, Farben, `fragmentWithVertex`, `density`) über Neuaufbau: Kopie von `RCT_CANVAS_CONFIG[preset]` mit eigenen Werten überschreiben, Farben als CSS-Variablen `--grad-1…4` auf `documentElement` setzen, `applyTheme(preset, false)` – entprellt 250 ms.
- Regler-Bereiche: aus den Min/Max-Werten aller Presets abgeleitet (Spanne ×2), Farben als `<input type=color>`; Regler, die der aktive Modus nicht nutzt, ausgegraut.

- [ ] **Step 1: Smoke-Test schreiben** (`labor-smoke.js`): Seite per `file://` öffnen → keine Konsolenfehler; jedes Preset wählen → `gradient.lineMode === preset.shaderMode` (No-GL-Themes: kein Fehler, Canvas ausgeblendet – **Review Focus 4**); Regler `fragmentShader_lineCount` auf 33 → `uniforms.u_line_count.value === 33` binnen 100 ms; F5 → Regler zeigt 33; **Review Focus 1:** 20 Preset-Wechsel in 5 s → danach genau ein laufender `Gradient`, Median-Frame-Zeit ≤ 1,2 × Ausgangswert; Datenmeer → `u_tex0_ready === 1` (Mond über `assets.js`).
- [ ] **Step 2: Laufen lassen, fehlschlagen sehen.** Run: `node tools/shader-labor/make-assets.js && PWC=… node tools/shader-labor/tests/labor-smoke.js` · Expected: FAIL (Seite fehlt)
- [ ] **Step 3: Seite, UI und Asset-Skript bauen.**
- [ ] **Step 4: Grün.** Expected: alle Smoke-Prüfungen PASS.
- [ ] **Step 5: Commit** `feat(shader-labor): Labor-Seite – Preset-Auswahl, Regler-Spalte, Live- und Neuaufbau-Weg`

### Task 4: Werkzeugleiste – Vergleich, Favoriten, Export, Leistung, Vorschau-Rahmen

**Files:**
- Modify: `tools/shader-labor/labor.html`, `labor-ui.js`, `labor.css`
- Create: `tools/shader-labor/tests/labor-toolbar.js`

**Interfaces:**
- Consumes: Task 2 Store (`remember`, `toggleCompare`, Favoriten, `exportJSON/importJSON`), Task 1 (`viewW`, `resScale`, `frameSkip`).
- Leistungsanzeige: Median und 95 %-Wert der `requestAnimationFrame`-Abstände über die letzten 120 Bilder, als „fps · ms“; aktualisiert ≤ 2×/s.
- Handy-Simulation: `resScale = 0.5`, `frameSkip = 2 × Preset-frameSkip`.
- Vorschau-Rahmen: `pur` (nur Canvas) · `inhalt` (halbtransparente Karte mit Überschrift + Fließtext davor, wie ein RCT-Artikel) · `handy` (Rahmen 390 × 844 CSS-px, `gradient.viewW = 390`).
- Export: Download `<preset>-shader-settings.json`; Import: Datei-Auswahl → `importJSON`, Meldung anzeigen.

- [ ] **Step 1: Tests schreiben** (`labor-toolbar.js`): „Stand merken“ → Regler ändern → Vorher/Nachher zeigt abwechselnd alten/neuen Uniform-Wert; Favorit „Test“ speichern, Preset wechseln, Favorit laden → Werte zurück; Export-Download enthält `"labor":"shader-labor"`; Import derselben Datei → Meldung „übernommen“; Leistungsanzeige zeigt Zahl > 0; Handy-Simulation an → `canvas.height === 300`; **Review Focus 5:** Rahmen `handy` → `gradient.width === 390`, zurück auf `pur` und Fenster auf 1280 px verkleinern → `gradient.width === 1280` und (Datenmeer) `u_moon.value[2]` passt zur neuen Breite, ohne Neuladen.
- [ ] **Step 2: Laufen lassen, fehlschlagen sehen.** Run: `PWC=… node tools/shader-labor/tests/labor-toolbar.js` · Expected: FAIL
- [ ] **Step 3: Werkzeugleiste bauen.**
- [ ] **Step 4: Grün.** Expected: alle Prüfungen PASS; Task-3-Smoke weiterhin grün.
- [ ] **Step 5: Commit** `feat(shader-labor): Vorher/Nachher, Favoriten, Export/Import, Leistungsanzeige, Handy-Simulation, Vorschau-Rahmen`

### Task 5: Erster Experiment-Modus als Durchstich

**Files:**
- Modify: `tools/shader-labor/experimente.js`
- Create: `tools/shader-labor/LIES-MICH.md` (kurz: Labor öffnen, `make-assets.js`, neuen Modus anlegen, Export → Preset)
- Modify: `tools/shader-labor/tests/labor-smoke.js`

**Interfaces:**
- Consumes: `RCT_EXTRA_MODES`, `SHADER_LAB_MODES`, `u_p0…u_p7`.
- Produces: Modus **30 „Testmuster“** – weiche Farbringe um die Bildmitte; Regler `p0` Ringzahl (1–40, Start 8), `p1` Tempo (0–2, Start 0.5), `p2` Weichheit (0–1, Start 0.3). Erscheint in der Auswahl als „🧪 Experiment 30 – Testmuster“.

- [ ] **Step 1: Test ergänzen**: „🧪 Experiment 30“ wählen → `gradient.lineMode === 30`, keine Shader-Fehler; Regler `p0` auf 20 → `u_p0.value === 20`; Export enthält `"shaderMode":30` und `"params":[20,…]`; das ausgelieferte `gl-bg-animation.js` enthält die Zeichenfolge `u_line_mode > 29.5` NICHT.
- [ ] **Step 2: Fehlschlagen sehen.** Run: `PWC=… node tools/shader-labor/tests/labor-smoke.js` · Expected: FAIL (Modus 30 fehlt)
- [ ] **Step 3: Modus 30 + Regler-Beschreibung + LIES-MICH schreiben.**
- [ ] **Step 4: Grün** – alle Tests aus Task 1–5 PASS.
- [ ] **Step 5: Commit** `feat(shader-labor): erster Experiment-Modus 30 (Testmuster) als Durchstich + LIES-MICH`
