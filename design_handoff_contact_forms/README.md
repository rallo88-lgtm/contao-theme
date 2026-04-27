# Handoff: RCT Contact Forms (Light + Dark)

## Overview
Two design variants of a simple contact form for the RCT (Rallos Contao Toolbox) Design System, intended to replace/restyle the form at **https://rct.a-web-service.de/kontakt**:

1. **Dark variant** — sits on the RCT shell, soft aurora gradient background, accent-cyan controls.
2. **Light variant** — sits on the RCT light surface (`#fbf9f8`), dotted texture background, primary-blue controls.

Both variants share **the same markup and field semantics** — only theme tokens differ. Implement once, swap colors via a parent class.

## About the Design Files
The bundled files are **design references created in HTML** — a prototype showing intended look and behavior, not production code to ship as-is.

The task is to **recreate these forms inside the existing RCT Contao theme** (Twig templates + `rct.css` / `rct-components.css` token files), reusing Contao's standard `tl_form` markup so the existing form module, validation, captcha, honeypot, and CSRF infrastructure keeps working.

## Fidelity
**High-fidelity (hifi).** All measurements, colors, typography, letter-spacing, and spacing values are final and align with `colors_and_type.css`. Recreate pixel-perfect.

---

## Field list (mirrors the live form)

| # | Field | Type | Required | Notes |
|---|---|---|---|---|
| 01 | Name | `text` | yes | autocomplete="name" |
| 02 | E-Mail | `email` | yes | autocomplete="email" |
| 03 | Nachricht | `textarea` | yes | min-height 140px, vertical resize |
| 04 | Sicherheitsfrage | `text` (numeric) | yes | "6 + 2 = ?" pattern; rendered as boxed prompt + input pair |
| — | Datenschutz | `checkbox` | yes | links to privacy policy |
| — | Honeypot (`website`) | `text` | — | absolutely positioned offscreen, `tabindex="-1"`, `autocomplete="off"` |

Submit copy: **"Nachricht senden"** with right-arrow icon (Lucide `arrow-right`-style stroke).

---

## Layout

- Form is **560px max-width**, centered.
- Padding: `56px clamp(28px, 5vw, 64px)`.
- Two-column grid for Name + E-Mail; everything else spans full width.
- Sections separated by **36px gap** (header → meta → fields → actions → footer).
- Meta strip and footer use 1px dividers (top+bottom for meta, dashed top for footer).

### Header
- **Eyebrow** — `DM Mono 500`, `10px`, uppercase, `letter-spacing: 0.22em`, accent-coloured, with a 24px hairline before it. Text: "Kontakt · § 01"
- **H1** — `Space Grotesk 700`, `clamp(28px, 3.2vw, 40px)`, `letter-spacing: -0.025em`, `line-height: 1.05`. Text: "Schreiben Sie uns."
- **Intro** — `14.5px`, `line-height: 1.6`, muted text colour.

### Meta strip
Single row, mono caps, `10px / 0.16em`. Items separated by 1px × 12px vertical bars (use the `.rct-divider--vrow` from the dividers package). Items: `FORM /CONTACT` · `5 Felder` · `~ 60 Sek.` · `DSGVO`.

### Footer
Dashed top border (`1px dashed var(--border)`), mono caps `10px`. Left: "Antwort innerhalb 1 Werktag". Right: "› kontakt@a-web-service.de" (arrow accent-coloured).

---

## Components

### Field label
- Wrapper: `.field-label` — `DM Mono 500`, `10px`, uppercase, `letter-spacing: 0.18em`.
- Contains a faint **counter span** (`01`, `02`, …) at `9px / opacity 0.6`.
- Required marker: accent-coloured `*`.

### Text input / textarea
- Padding: `13px 14px`. Border: `1px solid var(--border)`. Background: subtle field tint.
- Border-radius: **`2px`** (RCT sharp default).
- Hover: border steps up to `--border-strong`.
- Focus: border switches to accent (`--accent` on dark, `--primary` on light) **plus** a 3px focus ring via `box-shadow` using `color-mix(... 18%, transparent)`. Background brightens on dark, stays solid white on light.
- Placeholder colour: `--txt-faint`.
- Transition: `.18s var(--ease)`.

### Captcha row
Side-by-side: a fixed-width **boxed prompt** with dashed border showing `6 + 2 =`, plus a numeric `text` input filling the rest. The operator characters (`+`, `=`) are accent-coloured; numerals stay neutral.

On mobile (`<540px`), the row stacks vertically.

### Checkbox
Custom 18×18 box. Tick implemented as a `::after` square that scales 0→1 on `:checked`. Box border switches to accent on check. Privacy link uses `border-bottom: 1px dotted` (not underline) in accent colour.

### Submit button
- `Space Grotesk 700`, `11px`, uppercase, `letter-spacing: 0.18em`.
- Padding: `15px 30px`. Border-radius: `2px`. No border.
- **Dark form:** `background: var(--accent)`, `color: #0e0e18`.
- **Light form:** `background: var(--primary)`, `color: #ffffff`.
- Hover: `opacity: 0.88` + `translateY(-1px)`.
- Inline arrow SVG slides right `4px` on hover.

### Status text
Right of submit button: `DM Mono`, `10px / 0.16em`, with a 6px dot + 3px box-shadow halo. Text: "Verschlüsselte Übertragung".

### Honeypot
```html
<input class="hp" tabindex="-1" autocomplete="off" name="website" type="text">
```
```css
.hp { position: absolute; left: -9999px; opacity: 0; pointer-events: none; }
```

---

## Design Tokens (existing in `colors_and_type.css`)

The form introduces **no new tokens**. It defines a small per-form scope with locally-named vars that all resolve to existing RCT tokens:

### Dark (`.rct-form`)
| Local var | Resolves to |
|---|---|
| `--bg` | `#0e0e18` (RCT main content bg) |
| `--field-bg` | `rgba(255,255,255,0.04)` |
| `--field-bg-focus` | `rgba(255,255,255,0.06)` |
| `--border` | `rgba(255,255,255,0.10)` |
| `--border-strong` | `rgba(255,255,255,0.20)` |
| `--border-focus` | `var(--accent)` |
| `--txt` | `#ffffff` |
| `--txt-muted` | `rgba(255,255,255,0.6)` |
| `--label-mono` | `rgba(255,255,255,0.55)` |

### Light (`.rct-form.is-light`)
| Local var | Resolves to |
|---|---|
| `--bg` | `var(--rct-bg)` (`#fbf9f8`) |
| `--field-bg` | `#ffffff` |
| `--border` | `rgba(27,28,28,0.12)` |
| `--border-strong` | `rgba(27,28,28,0.28)` |
| `--border-focus` | `var(--primary)` |
| `--txt` | `var(--rct-text)` (`#1b1c1c`) |
| `--txt-muted` | `var(--rct-text-muted)` (`#434936`) |
| `--label-mono` | `var(--rct-text-faint)` (`#747a64`) |

When porting into `rct-components.css`, replace the local var aliases with direct token references where possible — keep them only if it makes theme switching easier in your Twig partial.

---

## Background treatment

### Dark
Two soft radial gradients via `--grad-1` / `--grad-3` (theme-driven):
- top-right: `radial-gradient(ellipse 70% 60% at 85% 20%, color-mix(in oklab, var(--grad-1) 22%, transparent), transparent 60%)`
- bottom-left: `radial-gradient(ellipse 60% 70% at 10% 90%, color-mix(in oklab, var(--grad-3) 18%, transparent), transparent 65%)`

This doubles as the existing RCT aurora canvas — if your page already has the global aurora, omit this layer to avoid double-up.

### Light
Subtle dot grid with radial mask (faded at edges):
```css
background-image: radial-gradient(circle, rgba(27,28,28,0.06) 1px, transparent 1.2px);
background-size: 22px 22px;
mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
```

---

## States to implement

The prototype shows the **default** state. Implement these additional states in the codebase:

- **`:hover`** — border `--border-strong`. (Already in CSS.)
- **`:focus`** — border `--border-focus` + 3px focus ring via `box-shadow`. (Already in CSS.)
- **`[aria-invalid="true"]`** — border `var(--error)` (`#ff6b6b` dark / `#ba1a1a` light) + 3px ring in same colour at 18%. Add a small mono error message below the field at `11px / 0.05em`, error colour, with leading `! ` glyph.
- **`:disabled`** — `opacity: 0.5`, `cursor: not-allowed`.
- **Success screen** — replace form with mono "✓ NACHRICHT GESENDET" + same eyebrow/title pattern; no animation needed beyond the existing `0.4s var(--ease)`.

Contao's form module emits server-validated errors as a sibling div — wire those into the `[aria-invalid]` styling.

---

## Responsive

- ≥541px: two-column field grid (Name + E-Mail).
- <541px: single column; captcha row stacks (boxed prompt above input).
- Submit row uses `flex-wrap: wrap` so the status caption drops below on narrow widths.

---

## Accessibility

- Every input has a real `<label for>` (the prototype uses `htmlFor`).
- Required fields use both `required` and visible `*` marker (the asterisk is decorative — required state must also be machine-readable).
- The captcha input must announce its prompt; in the Contao port, render the full question as the input's `aria-label` (e.g. `aria-label="Sechs plus zwei"`) so screen readers don't have to parse the visual boxed numerals.
- Honeypot has `tabindex="-1"` and `autocomplete="off"` — keep these.
- Focus ring is a 3px tinted `box-shadow`, not just border colour — visible against any background.

---

## Where it lives in the codebase

**CSS** — add a new section to `src/Resources/public/css/rct-components.css`:
```
/* ============================================================
   FORMS — CONTACT
   ============================================================ */
```
Wrap all rules under a single root class, e.g. `.rct-contact-form` (and `.rct-contact-form.is-light` for the light variant).

**Twig** — extend Contao's default form template (`form_default.html5` / `form_textfield.html5` / `form_textarea.html5` / `form_captcha.html5` / `form_checkbox.html5`) so the existing form module produces this markup. Don't replace the form module — restyle it.

**Preview card** — add `preview/comp-forms-contact.html` to the design-system gallery and register under group **Components**.

---

## Files in this bundle

- `Contact Forms.html` — full prototype, **both variants side-by-side** on a Design Canvas. Variant CSS is fenced under `.rct-form` / `.rct-form.is-light`.
- `design-canvas.jsx` — only used by the prototype to lay the two artboards out; **do not port**.
- `README.md` — this file.

The HTML expects fonts at `../fonts/space-grotesk-*.woff2` and `../fonts/dm-mono-*.woff2`. In RCT they're already loaded globally — drop those `@font-face` declarations when porting.

The prototype's Tweaks panel (theme switcher) is **prototype-only** — do not port.

---

## Suggested commit shape

1. Add `.rct-contact-form` block to `rct-components.css` (variants share markup; only the local var scope differs).
2. Override Contao form widget templates to emit the markup shown in `Contact Forms.html`.
3. Add `preview/comp-forms-contact.html` to the gallery and register the asset.
4. Verify the live `/kontakt` route picks up the new styles without breaking the existing form module's validation, captcha, or honeypot logic.

No new tokens, no new fonts, no new assets.
