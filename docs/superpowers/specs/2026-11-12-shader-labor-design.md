# Shader-Labor – Design

Stand: 12.11.2026 · Auftraggeber: Ralph · Status: Entwurf zur Abnahme

## Wofür und warum

Ralph will eine **Sandbox für Hintergrund-Shader**: ein Ort für Spielereien und Experimente, ohne Contao, ohne Deploy, ohne Server. Erst wenn ein Shader überzeugt („wenn das Herzlein einen kleinen Hüpfer vor Freude macht“), wandert er ins RCT-Theme, ins Diorama, in den Proberaum oder wohin auch immer.

Vorbild ist das Dobi-Labor: Regler schieben, sofort hören bzw. hier sehen, Schritt für Schritt tüfteln. Ralph gibt Richtung und Urteil, Claude schreibt Shader und legt die passenden Regler an.

Anlass: Heute (12.11.) sind Bilder im Shader (Texturen) dazugekommen, damit öffnen sich viele neue Möglichkeiten. Gleichzeitig ist Mittwald-SSH abgeschaltet – ein rein lokales Werkzeug ist genau richtig.

## Ziele

1. **Durch die vorhandenen Shader klicken und sich inspirieren lassen**: Auswahl aller RCT-Theme-Presets (22) bzw. Shader-Modi (0–20), sofort sichtbar.
2. **Großes Canvas**, Größe und Hintergrund einstellbar.
3. **Rechte Spalte mit Reglern** für die Parameter, die heute in `rct-canvas-config.js` je Theme stehen (siehe „Parameter“). Jede Änderung wirkt sofort.
4. **Neue Shader**: Claude legt auf Ralphs Idee hin einen neuen Modus an und gibt ihm sinnvolle eigene Regler; dann wird gemeinsam getüftelt.
5. **Export**: Einstellungen als `<name>-shader-settings.json` speichern (Daten, aus denen Claude ein RCT-Preset baut oder den Shader woanders einsetzt).
6. Version-1-Komfort (von Ralph ausgewählt):
   - **Regler bleiben beim Neuladen erhalten** (F5 nach Code-Änderung verliert nichts).
   - **Vorher/Nachher-Knopf**: schaltet zwischen gespeichertem Stand und aktuellem Stand hin und her (kein Split-Screen).
   - **Favoriten**: Stände mit Namen speichern und per Klick zurückholen.
   - **Leistungsanzeige** (Bilder/s und Rechenzeit pro Bild) **mit Handy-Simulation** (halbe Auflösung, jedes zweite Bild).
   - **Vorschau-Rahmen umschaltbar**: pur · mit Inhalt davor (Text/Karte, damit man Lesbarkeit beurteilt) · Handygröße.

## Nicht-Ziele (Version 1)

- Kein Code-Editor im Labor – Shader-Code schreibt Claude in der Datei.
- Kein Split-Screen-Vergleich.
- Kein Würfel-Knopf, keine Aufnahme (später).
- Kein fertiges „Exportieren nach Diorama/Proberaum“ – Integration in andere Projekte macht Claude von Hand anhand der JSON (Ausbaustufe).

## Gleich danach (Version 2, erst wenn V1 läuft)

- Zeit-Steuerung: Pause, Zeitlupe, Zeit-Schieber.
- Bilder per Drag & Drop als Textur in den Shader (nutzt `loadTexture`).
- Musik-Reaktion: Shader pulsiert zum Song (für Proberaum/Waldbühne).

## Anti-Ziele (wäre ein Fehlschlag, selbst wenn es technisch läuft)

- **Eine Kopie der Shader-Engine.** Das Labor muss dieselbe `gl-bg-animation.js` nutzen wie das Theme – was im Labor gut aussieht, muss im Theme identisch aussehen.
- Ruckeln im Labor selbst (Regler-UI darf die Animation nicht ausbremsen).
- Verlust von Ralphs Einstellungen beim Neuladen.
- Ein Werkzeug, das einen Server, Build-Schritt oder Internet braucht.

## Rahmenbedingungen

- Läuft lokal als Datei im Browser (Chrome/Edge), ohne Server, ohne Build – wie die anderen Labore.
- WebGL1 wie das Theme (kein `fwidth`, keine WebGL2-Features im gemeinsamen Shader).
- Perf-First (Ralph: „wenn's ruckelt, stirbt's“).
- Code-Kommentare nur am echten Zeilenende (Ralphs Regel).
- Sprache der Oberfläche: Deutsch.

## Was die Engine heute erwartet (Ist-Stand, für den Builder)

- `src/Resources/public/js/gl-bg-animation.js`: Klasse `Gradient` (miniGL), ein Uber-Fragment-Shader mit Modi `if (u_line_mode > a && u_line_mode < b) { … }`; `rctShaderForMode` schneidet unbenutzte Modi weg (nur ein Modus wird kompiliert). Uniforms u. a. `u_line_mode`, `u_line_count`, `u_line_width`, `u_line_speed`, `u_line_color`, `u_bg_color`, `u_wave_strength`, `u_slime_mode`, `u_px_stretch`, `u_tex0`/`u_tex0_ready`/`u_moon` (neu 12.11.).
- `src/Resources/public/js/rct-canvas-config.js`: `RCT_CANVAS_CONFIG` mit 22 Presets; Felder `vertexShader_amp/seed/freqX/freqY/freqDelta/timeSpeed/density/activeColors/bgColor`, `fragmentShader_lineCount/lineWidth/lineSpeed/lineColor/waveStrength/slimeMode`, `shaderMode`, `fragmentWithVertex`, `frameSkip`, vereinzelt `mouseParallax`, `neuralLogo`, `bgImage`.
- `src/Resources/public/js/rct.js` `window.applyTheme(theme)`: baut aus dem Preset einen neuen `Gradient` (setzt die Felder oben, mit Umrechnung über `SPEED_SCALE` und `rctAuroraSpeed`).
- Die **vier Aurora-Farben** kommen aus CSS-Variablen `--gradient-color-1…4` (aus `--grad-1…4` je `[data-theme]` in `rct-layout.css`), nicht aus der JS-Konfiguration.
- Der Canvas wird intern fest 600 px hoch gerendert und per CSS gestreckt (`u_px_stretch` gleicht das für Datenmeer aus).

## Bedienung (Ralphs Bild)

- **Oben:** Auswahl Preset/Modus · Vorschau-Rahmen (pur / mit Inhalt / Handy) · Leistungsanzeige · Handy-Simulation an/aus · Vorher/Nachher · Favoriten (speichern, Liste) · Export JSON.
- **Mitte:** Canvas.
- **Rechts:** Regler-Spalte, gruppiert (Farben · Bewegung · Linien/Effekt · Aurora-Unterbau · Modus-spezifisch). Jeder Regler zeigt den Wert; Farben als Farbwähler.
- Beim Wechsel des Presets laden die Regler dessen Werte; die eigenen Änderungen gelten bis zum nächsten Wechsel (und überleben F5).

## Neue Shader

- Neue Modi entstehen wie bisher im Uber-Shader (`if (u_line_mode > N-0.5 && u_line_mode < N+0.5)`) – damit sind sie automatisch theme-fähig.
- Modus-eigene Regler: Ein neuer Modus kann eigene Uniforms mitbringen; das Labor zeigt für den aktiven Modus dessen Regler-Liste (Name, Bereich, Schritt, Startwert). Wo diese Liste steht, entscheidet der Builder (Vorschlag: eine kleine Beschreibung je Modus neben dem Preset).

## Export-Format

`<name>-shader-settings.json` enthält mindestens: Modus, alle Preset-Felder im Format von `RCT_CANVAS_CONFIG`, die vier Aurora-Farben, Modus-eigene Uniform-Werte, Labor-Version/Datum. Ziel: Claude kann daraus 1:1 ein neues Preset in `rct-canvas-config.js` + die Farb-Variablen in `rct-layout.css` erzeugen.

## Entscheidungen von Claude (Ralph hat sie nicht ausdrücklich getroffen)

- **Ort:** `D:\kunden\rct-bundle\tools\shader-labor.html` – neben der Engine, damit das Labor immer die aktuelle `gl-bg-animation.js` lädt (per relativem Pfad, ohne Kopie). `tools/` liegt nicht in `src/Resources/public` und wird nicht ausgeliefert.
- **Speichern** von Reglerstand und Favoriten im `localStorage` des Labors (file://-Seiten haben eigenen Speicher; reicht für ein lokales Werkzeug). Export/Import über JSON-Datei als Sicherung.
- **Leistungsmessung** über `requestAnimationFrame`-Zeitabstände (Median + 95 %), wie bei den Tests heute.

### Nach dem Builder-Check entschieden (Claude, 12.11.)

1. **„Identisch wie im Theme“ heißt inkl. der Theme-CSS-Schichten.** Das Labor lädt `rct-layout.css` (+ `rct.css`, `rct-customize.css`, soweit für den Canvas relevant) und setzt `data-theme` des aktiven Presets, damit `mask-image`, `opacity`, `background-image`, Datenmeer-128vh und – wo das Theme ihn nutzt – der Punkte-Canvas genauso erscheinen. Regler gibt es in V1 nur für die JS-Parameter und die vier Aurora-Farben; die CSS-Schichten werden angezeigt, aber nicht geregelt. Der Export enthält den Theme-Namen, aus dem die CSS-Schichten stammen.
2. **Modus-eigene Regler über acht allgemeine Uniforms** `u_p0 … u_p7` (float) in der Engine, im Preset als Feld `params: [ … ]`. Neue Modi lesen ihre Regler aus diesen Uniforms. `applyTheme` setzt `params` zusätzlich – das ist der einzige Eingriff in `rct.js`, rückwärtskompatibel (Presets ohne `params` = alles 0, bestehende Modi lesen `u_p*` nicht). Damit sind neue Modi automatisch theme-fähig, ohne Schema-Wildwuchs. Regressionstest: alle 22 Presets im Theme vorher/nachher gleich.
3. **Experimente leben NICHT in der ausgelieferten Engine.** Neue Modi entstehen in `tools/shader-labor-experimente.js`; die Engine bekommt einen kleinen Haken (z. B. `window.RCT_EXTRA_MODES` = zusätzlicher Fragment-Code, wird vor dem Modus-Zuschnitt eingefügt). Erst bei Abnahme zieht Claude den Modus in `gl-bg-animation.js` um.
4. **Änderungen werden pro Preset gemerkt.** Wechsel des Presets verliert nichts; zurück zum Preset = eigener Stand. Knopf „Original“ setzt ein Preset auf die Werte aus `rct-canvas-config.js` zurück.
5. **Vorher/Nachher:** Knopf „Stand merken“ legt den Vergleichsstand fest (Standard = Original-Preset); der Umschalter zeigt abwechselnd gemerkten und aktuellen Stand.
6. **Handy-Vorschau = echtes Handy-Verhalten der Engine, nur im Labor:** Die Engine bekommt eine optionale Breiten-Vorgabe (`gradient.viewW`, Standard `window.innerWidth`), das Labor setzt sie im Handy-Rahmen auf z. B. 390 px – so greifen `lineCount`-Halbierung, kleiner Mond usw. Das Theme bleibt unverändert (ohne Vorgabe wie bisher). **Handy-Simulation** halbiert zusätzlich die Render-Auflösung und verdoppelt `frameSkip` gegenüber dem Preset.
7. **file://-Fallen:** `loadTexture` akzeptiert auch Data-URIs; das Labor reicht den Datenmeer-Mond als Data-URI bzw. per einstellbarer URL herein (sonst „tainted canvas“). `localStorage`-Schlüssel bekommen das Präfix `shaderlabor:` (file://-Seiten teilen sich den Speicher mit den anderen Laboren).

## Dem Builder überlassen

- Aufbau der Regler-UI, Gruppierung im Detail, Bereichsgrenzen der Regler (sinnvoll aus den vorhandenen Preset-Werten ableiten).
- Wie das Labor einen `Gradient` erzeugt (Logik aus `applyTheme` wiederverwenden oder schlank nachbauen – ohne die Engine zu kopieren).
- Ob kleine Änderungen an `gl-bg-animation.js` nötig sind (z. B. Uniforms nachträglich setzen ohne Neuaufbau) – erlaubt, solange das Theme unverändert funktioniert.

## Erfolg

Ralph klickt sich durch die vorhandenen Shader, verändert Regler, drückt F5 und nichts ist weg, speichert einen Favoriten, sieht die Bilder/s, schaltet auf „Handy“ – und bekommt Lust auf den ersten neuen Shader.
