# RCT Kundenstimme (rct_testimonial) – Kurz-Design

Ralph 14.11.2026, freigegeben im Chat („passt so“).

## Warum
Kundenstimmen auf aws sind aus Icon-Textboxen gebaut: Breite schrumpft mit kurzem Text (Ginsberg 766 px statt 1152 px),
Tippfehler im Namen, fehlende/falsche Anführungszeichen. Ein eigenes Element macht das konsistent und fehlerfest.

## Ziele
- Neues CE „RCT Kundenstimme“: links Logo **oder** Icon, darunter Name, Firma/Zusatz, Webseite (Link); rechts Zitat, senkrecht mittig.
- Stil **hell** (wie helle Icon-Textbox) / **dunkel** (wie dunkle Icon-Textbox: Clip-Path-Ecken oben rechts + unten links,
  Akzent-Ecke oben rechts, Scanlines, Akzent-Rahmen, Mono-Schrift).
- Anführungszeichen „…“ setzt das Theme; eingetippte am Anfang/Ende werden entfernt.
- Linke Spalte feste Breite, Element immer volle Breite; im Swiper volle Höhe der Folie + seitlicher Abstand für die Pfeile.
- Handy (≤ 768 px): untereinander.
- Dazu Slider-Fix: Text-Elemente in einer Swiper-Folie nehmen die volle Breite ein (Bilder bleiben zentriert).
- Release v1.7.18, lokal testen, Deploy nur nach Ralphs „ja“ pro Instanz.

## Nicht-Ziele
Sterne-Bewertung, Personenfoto zusätzlich zum Logo, eigenes Slider-JS.

## Ansatz
Eigenes CE nach Muster rct_ticker/rct_icon_textbox (einzige sinnvolle Variante – Icon-Textbox weiter verbiegen behält die Stolperfallen).
- Felder wiederverwendet: `rct_itb_image` (Spalte vorhanden), `rct_itb_icon`, `rct_itb_style`.
- Neu (jsonData, ohne sql): `rct_ts_name`, `rct_ts_company`, `rct_ts_url`, `rct_ts_quote`.
- Markup: `<figure class="rct-ts style-…">` mit `<blockquote>` + `<figcaption>`; Logo-Alt = Name.
- Website-Anzeige ohne `https://` und `www.`, Link `target=_blank rel=noopener`.

## Dem Bauenden überlassen
Konkrete Abstände/Größen, solange es zu den Icon-Textboxen passt.
