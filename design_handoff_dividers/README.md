# Handoff: RCT Dividers

## Overview
A set of **15 divider variants** for the RCT (Rallos Contao Toolbox) Design System, designed to match its "Clinical Architect / Tech Inspector" aesthetic — sharp corners, thin rules, monospaced labels, restrained accent usage.

The dividers are theme-aware: they automatically restyle for `data-theme="default" | "lime" | "purple" | "light"` using existing RCT CSS custom properties (`--accent`, `--border`, `--grad-1..4`, etc.).

## About the Design Files
The file `Dividers.html` in this bundle is a **design reference created in HTML** — a prototype gallery showing the intended look and behavior of each divider, not production code to drop in as-is.

The task is to **recreate these dividers in the target codebase's existing environment** (the RCT Contao theme — Twig templates + the existing `rct.css` / `rct-components.css` token files). Lift the markup patterns and CSS rules from `Dividers.html` and integrate them as a new section in `rct-components.css`, following the file's existing conventions (BEM-ish naming, custom-property-driven styling, no hard-coded colors).

## Fidelity
**High-fidelity (hifi).** All measurements, colors, fonts, letter-spacing values, and spacing tokens are final and align with `colors_and_type.css`. Recreate pixel-perfect.

## Where it lives in the codebase
Add to `src/Resources/public/css/rct-components.css` under a new section header, e.g.:
```css
/* ============================================================
   DIVIDERS
   ============================================================ */
```
All variants share the namespace `.rct-divider` (rename from the prototype's `.div-*`) so they don't collide with anything else.

---

## Divider Catalogue

Each variant uses RCT tokens only — no new color/spacing values are introduced.

| # | Class (suggested) | Purpose | Markup |
|---|---|---|---|
| 01 | `.rct-divider--hairline` | Baseline 1px rule. List/card content separation. | `<hr>` or `<div>` |
| 02 | `.rct-divider--fade` | Symmetric fade with accent core. Hero/section transitions. | `<div>` |
| 03 | `.rct-divider--ticks` | Hairline with evenly-spaced ticks. Tech/inspector feel. | `<div>` |
| 04 | `.rct-divider--labeled` | Rule with mono label between two line segments. | `<div>` w/ 3 children |
| 05 | `.rct-divider--section` | Left accent bar + title + rule + right counter. Mirrors RCT sidebar active-link pattern. | `<div>` w/ 4 children |
| 06 | `.rct-divider--coord` | Coordinate-style with end ticks. Surveying aesthetic. | `<div>` w/ start/line/end |
| 07 | `.rct-divider--diamond` | Centered accent diamond. Editorial. | `<div>` w/ line/mark/line |
| 08 | `.rct-divider--bracket` | `[ BEGIN … END ]` with dashed rule. Code/system blocks. | `<div>` |
| 09 | `.rct-divider--stepped` | Segmented progress-style bar. | `<div>` w/ N segs |
| 10 | `.rct-divider--caption` | Top: title + status dot. Bottom: split-color rule. | `<div>` w/ row+line |
| 11 | `.rct-divider--aurora` | Gradient using `--grad-1..4`. Reserved for hero breaks only. | `<div>` |
| 12 | `.rct-divider--ruler` | Skala with mono numerals (0/200/…/1200). | `<div>` w/ ticks+nums |
| 13 | `.rct-divider--counter` | `03 / 12` paginated rule. | `<div>` |
| 14 | `.rct-divider--dots` | Dotted rail, quieter than hairline. | `<div>` |
| 15 | `.rct-divider--vrow` | Vertical separators for toolbars/breadcrumbs (in-row, not section break). | `<div>` w/ items |

---

## Design Tokens (already in `colors_and_type.css`)

All dividers consume these existing tokens — do not invent new ones:

```
--accent           theme-switchable accent (default #27c4f4)
--primary          theme-switchable primary (default #2951c7)
--border           rgba(255,255,255,0.08)        // dark shell
--border-strong    rgba(255,255,255,0.16)
--muted            #737373
--font-mono        'DM Mono', monospace
--font-head        'Space Grotesk', sans-serif
--grad-1..4        theme aurora gradient stops
```

For **light shell** (`[data-theme="sparta2"]` or `[data-theme="light"]`), `--border` flips to `rgba(0,0,0,0.10)` and text colors invert. The prototype defines a `[data-theme="light"]` block; merge those values into RCT's existing `[data-theme="sparta2"]` rule rather than adding a new theme.

### Spacing
- Vertical breathing room around block dividers: **`var(--space-6)` to `var(--space-10)`** (24–40px). Don't bake margin into the divider class — let the parent control it.
- In-row dividers (#15): **`gap: 24px`**, vertical bar height **`14px`**.

### Typography (for labeled variants)
- Mono labels: `DM Mono 500`, `10px`, `text-transform: uppercase`, `letter-spacing: 0.16em–0.22em`, color `var(--muted)`.
- Numerals/counters: `DM Mono 500`, `10px`, accent value highlighted via `color: var(--accent)`.
- Section titles (#05): `Space Grotesk 700`, `11px`, uppercase, `letter-spacing: 0.18em`.

### Border radius
None. All dividers are square. The RCT system uses `--rct-radius` (2px) sparingly; divider edges stay sharp.

---

## Critical CSS rules per variant

Pull these directly from `Dividers.html` — they're tagged with comment blocks `/* ── 01 · Hairline ── */` etc. so they're easy to find. Below are the constraints to preserve when porting:

- **02 Fade** — gradient must use `--accent` at 50% (not at 0%/100%). Symmetry matters.
- **03 Ticks** — the tick layer is a `linear-gradient` background; tick spacing **`24px`**. Don't change without checking how it sits next to a `--space-6` grid.
- **05 Section** — the `3px` accent bar height must be `20px`. This matches the RCT sidebar active-link bar exactly.
- **07 Diamond** — outer border is `1px solid var(--accent)`; inner fill is `--accent` at `opacity: 0.55`. Both required.
- **09 Stepped** — segments are `flex: 1` with `4px` gap (margin-left). Off-state uses `--border`, on-state uses `--accent`. Total height **`6px`**.
- **10 Caption** — the bottom rule is split: first **18%** is `--accent`, remainder is `--border`. The percentage is intentional — it acts as a visual "you are here" pin.
- **11 Aurora** — `box-shadow: 0 0 24px color-mix(in oklab, var(--grad-1) 30%, transparent)` is part of the look. Don't drop it.
- **12 Ruler** — two stacked tick layers: major every `80px`, minor every `16px`. Numerals laid out via `flex: justify-content: space-between`.
- **15 V-Row** — vertical bar is `1px × 14px`, color `--border-strong`. Used purely inline; do not center vertically with margin auto — flex `align-items: center` only.

---

## Interactions & Behavior

Dividers are **static decorative elements** — no hover, focus, or click states required. They re-tint via CSS when the theme attribute changes; no JS needed.

The prototype's Tweaks panel (theme switcher) is **prototype-only** — do not port.

---

## Responsive

All dividers are width-fluid (`width: 100%`). Below `720px`:
- The variants stay legible at any width.
- For #04 / #05 / #06, consider a min-width on the inner label/title — if you wrap, the rule looks broken. Test in tight cards.
- #15 (in-row) should switch to `flex-wrap: wrap` with `row-gap: 8px` on small toolbars.

---

## Accessibility

- Decorative dividers should use `<hr role="separator" aria-orientation="horizontal">` or `<div role="separator">`.
- For #04 / #05 / #10 (labeled variants) the visible text is the section heading — wrap accordingly so screen readers announce the section, not just a separator.
- Color contrast: the accent is only used at >=2px stroke or as a tint on top of a 1px hairline; never as the sole carrier of meaning.

---

## Files in this bundle

- `Dividers.html` — full gallery with all 15 variants. Each variant block is fenced in `/* ── NN · Name ── */` comments so you can extract just the CSS you need.
- `README.md` — this file.

The HTML expects fonts at `../fonts/space-grotesk-*.woff2` and `../fonts/dm-mono-*.woff2`. In RCT they're already loaded globally; you can drop those `@font-face` declarations when porting.

---

## Suggested commit shape

1. Add `.rct-divider` block to `rct-components.css` (variants 01–15, in order).
2. Add a new entry to the design-system preview folder (`preview/comp-dividers.html`) mirroring the gallery.
3. Register the preview card in the manifest under group **Components**.

No new tokens, no new fonts, no new assets.
