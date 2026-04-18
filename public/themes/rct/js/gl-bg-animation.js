// ============================================================
// RCT Vertex Shader — direkt editierbar
// ============================================================
const RCT_VERTEX_SHADER = `
varying vec3 v_color;

void main() {
  float time = u_time * u_global.noiseSpeed;
  vec2 noiseCoord = resolution * uvNorm * u_global.noiseFreq;

  // Tilting the plane
  float tilt    = resolution.y / 2.0 * uvNorm.y;
  float incline = resolution.x * uvNorm.x / 2.0 * u_vertDeform.incline;
  float offset  = resolution.x / 2.0 * u_vertDeform.incline
                  * mix(u_vertDeform.offsetBottom, u_vertDeform.offsetTop, uv.y);

  // Vertex noise
  float noise = snoise(vec3(
    noiseCoord.x * u_vertDeform.noiseFreq.x + time * u_vertDeform.noiseFlow,
    noiseCoord.y * u_vertDeform.noiseFreq.y,
    time * u_vertDeform.noiseSpeed + u_vertDeform.noiseSeed
  )) * u_vertDeform.noiseAmp;

  noise *= 1.0 - pow(abs(uvNorm.y), 2.0);
  noise  = max(0.0, noise);

  vec3 pos = vec3(
    position.x,
    position.y + tilt + incline + noise - offset,
    position.z
  );

  // Vertex color
  if (u_active_colors[0] == 1.) {
    v_color = u_baseColor;
  }

  for (int i = 0; i < u_waveLayers_length; i++) {
    if (u_active_colors[i + 1] == 1.) {
      WaveLayers layer = u_waveLayers[i];

      float noise = smoothstep(
        layer.noiseFloor,
        layer.noiseCeil,
        snoise(vec3(
          noiseCoord.x * layer.noiseFreq.x + time * layer.noiseFlow,
          noiseCoord.y * layer.noiseFreq.y,
          time * layer.noiseSpeed + layer.noiseSeed
        )) / 2.0 + 0.5
      );

      float bw;
      if (u_line_mode < 0.5) {
        bw = pow(noise, 4.);
      } else if (u_line_mode < 1.5) {
        float ln = fract(noise * u_line_count);
        float lm = 1.0 - smoothstep(0.0, u_line_width, ln)
                       * smoothstep(1.0, 1.0 - u_line_width, ln);
        bw = lm * noise;
      } else {
        bw = pow(noise, 4.);
      }
      v_color = blendNormal(v_color, layer.color, bw);
    }
  }

  gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
}
`;
// ============================================================
// RCT Vertex Shader FLAT (lineMode 3) — kein Aurora, nur Basisfarbe
// ============================================================
const RCT_VERTEX_SHADER_FLAT = `
varying vec3 v_color;

void main() {
  // Dieselbe Transformation wie der normale Shader — nur ohne Noise/Tilt
  float tilt    = resolution.y / 2.0 * uvNorm.y;
  float incline = resolution.x * uvNorm.x / 2.0 * u_vertDeform.incline;
  float offset  = resolution.x / 2.0 * u_vertDeform.incline
                  * mix(u_vertDeform.offsetBottom, u_vertDeform.offsetTop, uv.y);

  vec3 pos = vec3(
    position.x,
    position.y + tilt + incline - offset,
    position.z
  );

  // Hintergrundfarbe aus Theme — steuerbar per vertexShader_bgColor
  v_color = u_bg_color;

  gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
}
`;



// ============================================================
// RCT Fragment Shader — direkt editierbar
// ============================================================
const RCT_FRAGMENT_SHADER = `
varying vec3 v_color;

// Hash-basiertes Value Noise — kein snoise nötig
float rct_hash(vec2 p) {
  p = fract(p * vec2(127.1, 311.7));
  p += dot(p, p + 45.32);
  return fract(p.x * p.y);
}
float rct_vnoise(vec2 p) {
  vec2 i = floor(p);
  vec2 f = fract(p);
  vec2 u = f * f * (3.0 - 2.0 * f);
  return mix(mix(rct_hash(i), rct_hash(i + vec2(1,0)), u.x),
             mix(rct_hash(i + vec2(0,1)), rct_hash(i + vec2(1,1)), u.x), u.y);
}
float rct_fbm(vec2 p) {
  float v = 0.0; float a = 0.52;
  for (int i = 0; i < 4; i++) {
    v += a * rct_vnoise(p);
    p  = p * 2.13 + vec2(1.3, 1.7);
    a *= 0.50;
  }
  return v;
}

// 3×5 Pixel-Digit Bitmap (bit = row*3+col, row0=top)
// 0=31599 1=29850 2=29671 3=31207 4=18925 5=31183 6=31695 7=9511 8=31727 9=31215
float rct_tachoBit(float d, float bit) {
    float b;
    if      (d < 0.5) b = 31599.0;
    else if (d < 1.5) b = 29850.0;
    else if (d < 2.5) b = 29671.0;
    else if (d < 3.5) b = 31207.0;
    else if (d < 4.5) b = 18925.0;
    else if (d < 5.5) b = 31183.0;
    else if (d < 6.5) b = 31695.0;
    else if (d < 7.5) b = 9511.0;
    else if (d < 8.5) b = 31727.0;
    else              b = 31215.0;
    return mod(floor(b / pow(2.0, bit)), 2.0);
}

// Entgegenkommendes Auto (perspektivisch skaliert, von vorne)
void rct_traffic(vec2 uv, float tX, float tZ, float horiz, float ar, float camX, inout vec3 color) {
    if (tZ <= 0.02) return;
    float vF    = tZ * 0.42;
    float relZ  = 1.3 / vF;
    float tScrX = tX / (relZ * ar * 1.1) + 0.5 - camX;
    float tScrY = horiz - vF;
    float tSc   = 0.106 * vF;

    vec2  cp    = (uv - vec2(tScrX, tScrY + tSc * 0.45)) / tSc;
    cp          = floor(cp / 0.125 + 0.5) * 0.125;

    float fBody = step(cp.y, 0.52) * step(0.0,  cp.y) * step(-1.0, cp.x) * step(cp.x, 1.0);
    float fCab  = step(cp.y, 0.94) * step(0.52, cp.y) * step(-0.6, cp.x) * step(cp.x, 0.6);
    float fCar  = max(fBody, fCab);
    float fW1   = step(length(cp - vec2(-0.70, 0.0)), 0.30);
    float fW2   = step(length(cp - vec2( 0.70, 0.0)), 0.30);
    float fWhl  = max(fW1, fW2);
    float fWind = step(cp.y, 0.90) * step(0.54, cp.y) * step(-0.52, cp.x) * step(cp.x, 0.52);
    float fHL1  = step(length(cp - vec2(-0.55, 0.38)), 0.16);
    float fHL2  = step(length(cp - vec2( 0.55, 0.38)), 0.16);
    float fHL   = max(fHL1, fHL2);

    float fog   = smoothstep(0.0, 0.05, vF);
    vec3  cFinal = vec3(0.0);
    cFinal = mix(cFinal, vec3(0.03, 0.01, 0.01), fWhl);
    cFinal = mix(cFinal, vec3(0.65, 0.12, 0.05), fCar);
    cFinal = mix(cFinal, vec3(0.06, 0.18, 0.40) * 0.5, fWind);
    cFinal = mix(cFinal, vec3(1.0, 0.97, 0.80),  fHL);
    color  = mix(color, cFinal, max(fCar, fWhl) * fog);

    float glow = smoothstep(tSc * 2.2, 0.0, length((uv - vec2(tScrX, tScrY + tSc * 0.25)) * vec2(1.0, 1.4)));
    color += vec3(1.0, 0.97, 0.80) * glow * 0.35 * fog;
}

// Glasblase/Tropfen: Fresnel-Rim + Highlight + Caustic
// stretch > 1.0 = vertikal gestreckt (Tropfenform)
vec3 rct_bubble(vec2 p, vec2 c, float r, float stretch, vec3 col) {
  vec2 dp  = p - c;
  float d  = length(vec2(dp.x, dp.y / stretch));
  float rim   = smoothstep(r*1.14, r*0.93, d) - smoothstep(r*0.93, r*0.70, d);
  float inner = smoothstep(r*0.88, r*0.44, d) * 0.10;
  float hl    = smoothstep(r*0.21, 0.0, length(p - c - vec2(-r*0.27,  r*stretch*0.33))) * 0.98;
  float cst   = smoothstep(r*0.15, 0.0, length(p - c - vec2( r*0.09, -r*stretch*0.37))) * 0.40;
  return col * (rim + inner + cst) + vec3(hl * 0.9, hl * 1.0, hl * 0.85);
}

void main() {
  vec3 color = v_color;

  if (u_darken_top == 1.0) {
    vec2 st = gl_FragCoord.xy / resolution.xy;
    color.g -= pow(st.y + sin(-12.0) * st.x, u_shadow_power) * 0.4;
  }

  // Modus 2 + 3: horizontale Sinuswellen
  if (u_line_mode > 1.5 && u_line_mode < 3.5) {
    float y           = gl_FragCoord.y;
    float x           = gl_FragCoord.x / resolution.x;
    float spacing     = resolution.y / u_line_count;
    float mouseDist   = distance(gl_FragCoord.xy / resolution.xy, u_mouse);
    float mouseEffect = 0.3 / (0.05 + mouseDist);
    float wave        = sin(x * 18.85 - u_time * u_line_speed * 0.05 + mouseEffect)
                        * spacing * u_line_width;
    float t           = mod(y + wave, spacing);
    float dist        = abs(t - spacing * 0.5);
    float line        = 1.0 - smoothstep(0.5, 1.5, dist);

    vec3 lineColor;
    if (u_line_mode > 2.5) {
      lineColor = u_line_color;
    } else {
      lineColor = clamp(v_color * 2.0 + 0.2, 0.0, 1.0);
    }
    color = mix(color, lineColor, line);
  }

  // Modus 4: radiale Strahlen vom Zentrum — Sonnenwind / Pulsar
  if (u_line_mode > 3.5 && u_line_mode < 4.5) {
    vec2 uv     = gl_FragCoord.xy / resolution.xy;
    vec2 center = vec2(0.5, 0.5);
    vec2 delta  = uv - center;

    // Winkel + langsame Rotation über Zeit
    float angle  = atan(delta.y, delta.x);
    float rot    = angle + u_time * u_line_speed * 0.02;

    // Radius — für Intensitätsverlauf
    float radius = length(delta * vec2(resolution.x / resolution.y, 1.0));

    // Strahlen: u_line_count Perioden über 2*PI
    float period = 6.2832 / u_line_count;
    float t      = mod(rot, period);
    float dist   = abs(t - period * 0.5);

    // Breite wächst nach außen — natürlicher Strahlenverlauf
    float hw     = period * u_line_width * 0.5 * (0.2 + radius) ;
    float ray    = 1.0 - smoothstep(0.0, hw, dist);

    // Maus zieht Strahlen leicht an
    float mouseDist   = distance(uv, u_mouse);
    float mouseEffect = 0.2 / (0.08 + mouseDist);
    float t2     = mod(rot + mouseEffect, period);
    float dist2  = abs(t2 - period * 0.5);
    float ray2   = 1.0 - smoothstep(0.0, hw, dist2);
    ray = max(ray, ray2 * 0.6);

    // Intensität nimmt nach außen ab — kommt aus dem Loch
    float intensity = ray * (1.25 - smoothstep(0.0, 0.75, radius));

    color = mix(color, u_line_color, intensity);
  }

  // Modus 5: Strahlen mit Lemniskate-Zentrum (liegende Acht) + Maus
  if (u_line_mode > 4.5 && u_line_mode < 5.5) {
    vec2 uv = gl_FragCoord.xy / resolution.xy;

    // Lemniskate (Bernoulli) — liegende Acht als Zentrum-Pfad
    float lemT     = u_time * u_line_speed * 0.15;
    float lemX     = 0.5 + sin(lemT) * u_line_width * 0.35;
    float lemY     = 0.5 + sin(lemT * 2.0) * u_line_width * 0.18;
    vec2 lemCenter = vec2(lemX, lemY);

    // Maus-Einfluss
    float mousePull = 0.0;
    vec2 center = mix(lemCenter, u_mouse, mousePull *
                  (1.0 - smoothstep(0.0, 0.15, distance(u_mouse, vec2(0.5, 0.5)))));

    vec2 delta  = uv - center;
    float angle  = atan(delta.y, delta.x);
    float rot    = angle + u_time * u_line_speed * 0.02;
    float radius = length(delta * vec2(resolution.x / resolution.y, 1.0));
    float period = 6.2832 / u_line_count;
    float t      = mod(rot, period);
    float dist   = abs(t - period * 0.5);
    float hw     = period * 0.3 * (0.15 + radius);
    float ray    = 1.0 - smoothstep(0.0, hw, dist);
    float intensity = ray * (1.3 - smoothstep(0.0, 0.8, radius));
    color = mix(color, u_line_color, intensity);
  }

  // Modus 6: Honig-Tropfen — von oben nach unten, versetzt, mit Lifecycle
  if (u_line_mode > 5.5 && u_line_mode < 6.5) {
    vec2 uv   = gl_FragCoord.xy / resolution.xy;
    float y   = 1.0 - uv.y;  // Y invertieren: 0=oben, 1=unten
    float ar  = resolution.x / resolution.y;
    float spd = u_line_speed;

    float honey     = 0.0;
    float alpha     = 0.0;
    float highlight = 0.0;
    float N      = u_line_count + 2.0;  // +2 Fäden

    // Cycle: Laufen (1.0) + Stehen (1s) + Fadeout (2.5s)
    // Bei spd=0.02 und cycleDur=12 → ca. 10s laufen, 1s stehen, 2.5s faden
    float cycleDur  = 17.5;  // Gesamtdauer in u_time Einheiten
    float runEnd    = 10.0 / cycleDur;   // Faden unten angekommen
    float holdEnd   = 11.0 / cycleDur;   // 1s stehen
    float fadeEnd   = 1.0;               // 2.5s faden bis Ende

    for (int i = 0; i < 14; i++) {
      if (float(i) >= N) break;
      float fi    = float(i);
      float phase = fi * 1.618;
      float delay = mod(phase, 5.0) / 5.0;

      float localT   = mod(u_time * spd + delay * cycleDur, cycleDur);
      float t        = localT / cycleDur;  // 0..1 über gesamten Cycle

      // Progress: nur während der Lauf-Phase 0→1
      float progress = clamp(t / runEnd, 0.0, 1.0);

      // Opacity: fadeIn am Start, Halten, dann 2.5s Fadeout
      float fadeIn   = smoothstep(0.0, 0.05, t);
      float fadeOut  = 1.0 - smoothstep(holdEnd, fadeEnd, t);
      float opacity  = fadeIn * fadeOut;

      // X-Position
      float cx = (fi + 0.5) / N
               + sin(u_time * spd * 0.04 + phase) * 0.025
               + sin(u_time * spd * 0.09 + phase * 1.7) * 0.012;

      // Welligkeit — skaliert mit u_wave_strength (Honig=0.2, Toxic=1.0)
      float indSpd  = u_slime_mode > 0.5 ? spd * (0.6 + mod(fi * 0.7391, 0.9)) : spd;
      float waveX = (sin(y * 8.0  + u_time * indSpd * 0.30 + phase) * 0.022
                  +  sin(y * 3.5  + u_time * indSpd * 0.14 + phase * 2.1) * 0.030
                  +  sin(y * 18.0 + u_time * indSpd * 0.52 + phase * 0.7) * 0.008
                  +  sin(y * 1.2  + u_time * indSpd * 0.04 + phase * 3.1) * 0.015)
                  * u_wave_strength;
      float dx = (uv.x - cx - waveX) * ar;

      // Fadenbreite
      float widthBase = u_line_width * 0.013;
      float waveAmp = u_slime_mode > 0.5 ? 1.8 : 1.0;
      float widthWave = 1.0
                      + 0.22 * sin(y * 5.0  + u_time * indSpd * 0.20 + phase) * waveAmp
                      + 0.13 * sin(y * 11.0 + u_time * indSpd * 0.38 + phase * 2.7) * waveAmp
                      + 0.09 * sin(y * 2.0  + u_time * indSpd * 0.06 + phase * 1.3) * waveAmp
                      + 0.05 * sin(y * 22.0 + u_time * indSpd * 0.60 + phase * 0.5) * waveAmp;
      float width = widthBase * widthWave * (1.0 + (1.0 - y) * 0.8);

      // Hauptfaden — Slime: ~80% solid+Glanz, ~20% Outline/Röhre
      float thinFade   = smoothstep(0.82, 1.18, widthWave);
      float outlineMode = u_slime_mode > 0.5 ? step(0.80, mod(phase, 1.0)) : 0.0;

      float solidStrand   = smoothstep(width, width * 0.2, abs(dx));
      float outerStrand   = smoothstep(width, width * 0.15, abs(dx));
      float innerCut      = mix(1.0, smoothstep(0.0, width * 0.18, abs(dx)), thinFade);
      float outlineStrand = outerStrand * innerCut;
      float strand        = mix(solidStrand, outlineStrand, outlineMode);

      float strandVisible = strand * smoothstep(progress + 0.01, progress - 0.005, y);

      // Glanzstreifen nur bei solid-Fäden
      if (u_slime_mode > 0.5) {
        float specLine = smoothstep(width * 0.12, 0.0, abs(dx))
                       * smoothstep(progress + 0.01, progress - 0.005, y)
                       * opacity * thinFade * (1.0 - outlineMode);
        highlight = max(highlight, specLine);
      }

      // Verzweigungen — 2 Abzweigungen pro Faden an fixen Y-Positionen
      float branchStr = 0.0;

      // Verzweigung 1: bei y=0.3, läuft schräg rechts
      float b1Y     = 0.30;
      float b1Active = smoothstep(b1Y + 0.01, b1Y - 0.005, y)   // nur unter b1Y
                     * smoothstep(b1Y - 0.25, b1Y, y);           // max 25% lang
      float b1X     = cx + waveX + (y - b1Y) * 0.18             // schräg rechts
                     + sin(y * 12.0 + phase * 3.1) * 0.008;     // leicht gewellt
      float b1dx    = (uv.x - b1X) * ar;
      float b1w     = width * 0.55;
      float b1strand = smoothstep(b1w, b1w * 0.2, abs(b1dx));
      branchStr = max(branchStr, b1strand * b1Active * opacity
                    * smoothstep(b1Y, b1Y - 0.02, y));           // fadeIn an Abzweigung

      // Verzweigung 2: bei y=0.62, läuft schräg links
      float b2Y     = 0.62;
      float b2Active = smoothstep(b2Y + 0.01, b2Y - 0.005, y)
                     * smoothstep(b2Y - 0.20, b2Y, y);
      float b2X     = cx + waveX - (y - b2Y) * 0.14
                     + sin(y * 9.0 + phase * 2.3) * 0.007;
      float b2dx    = (uv.x - b2X) * ar;
      float b2w     = width * 0.45;
      float b2strand = smoothstep(b2w, b2w * 0.2, abs(b2dx));
      branchStr = max(branchStr, b2strand * b2Active * opacity
                    * smoothstep(b2Y, b2Y - 0.02, y));

      // Tropfen-Spitze — oben rund (geht in Faden über), unten spitz
      float dropY    = progress;
      float dropDir  = step(0.0, y - dropY); // 0=oberhalb Spitze, 1=unterhalb
      float yScale   = mix(1.2, 5.0, dropDir); // oben weich, unten sehr spitz
      float dropDist = length(vec2(dx * 3.2, (y - dropY) * yScale));
      float drop     = smoothstep(width * 2.8, width * 0.5, dropDist) * opacity;

      // Tropfen an Verzweigungsenden
      float db1Dist = length(vec2(b1dx * 2.0, (y - (b1Y - 0.22)) * 1.2));
      float db1     = smoothstep(b1w * 2.5, b1w * 0.5, db1Dist) * b1Active * opacity;
      float db2Dist = length(vec2(b2dx * 2.0, (y - (b2Y - 0.18)) * 1.2));
      float db2     = smoothstep(b2w * 2.5, b2w * 0.5, db2Dist) * b2Active * opacity;

      // Branches entfernt — nur Hauptfaden + Tropfen
      float total = max(strandVisible * opacity, drop);
      honey = max(honey, total);
      alpha = max(alpha, total);
    }

    // Rand-Vignette: Mitte voll sichtbar, Ränder etwas transparenter
    float distCenter = abs(uv.x - 0.5) * 2.0;
    float vignette   = 1.0 - smoothstep(0.4, 1.0, distCenter) * 0.5;
    alpha *= vignette;

    float gloss      = honey * honey * 1.4;
    float innerDepth = smoothstep(0.6, 1.0, honey);

    vec3 strandColor;

    if (u_slime_mode > 0.5) {
      // Satter Slime: volle Deckkraft, nur 5-8% Dip im Kern
      vec3 slimeDark  = u_line_color * vec3(0.10, 0.45, 0.04);
      vec3 slimeMid   = u_line_color * vec3(0.22, 1.30, 0.08);
      vec3 slimeGlow  = u_line_color * vec3(0.50, 1.70, 0.25) + vec3(0.0, 0.05, 0.0);
      strandColor = mix(slimeDark, slimeMid, smoothstep(0.0, 0.5, honey));
      strandColor = mix(strandColor, slimeGlow, gloss * innerDepth * 0.6);

      // Glanzstreifen einmischen
      vec3 specColor = u_line_color * vec3(0.25, 1.40, 0.12) + vec3(0.0, 0.04, 0.0);
      strandColor = mix(strandColor, specColor, highlight * 0.65);

      float ap = 0.92 + 0.05 * sin(u_time * u_line_speed * 0.3);
      gl_FragColor = vec4(strandColor, alpha * ap);
    } else {
      // Honey Moon: unverändert
      vec3 honeyDark   = u_line_color * vec3(0.55, 0.30, 0.02);
      vec3 honeyMid    = u_line_color * vec3(0.90, 0.65, 0.10);
      vec3 honeyBright = u_line_color * vec3(1.60, 1.20, 0.60) + 0.05;
      strandColor = mix(honeyDark, honeyMid, honey);
      strandColor = mix(strandColor, honeyBright, gloss * innerDepth * 0.5);
      gl_FragColor = vec4(strandColor, alpha * (0.85 + 0.05 * sin(u_time * u_line_speed * 0.3)));
    }
    return;

  }

  // Modus 7: Rosenblätter — Rhodonea + Fibonacci-Spiralversatz, geschlossene Blüte
  if (u_line_mode > 6.5 && u_line_mode < 7.5) {
    // Elliptische Normierung — Rose füllt 16:9 natürlich ohne zu clippen
    vec2 pn      = (gl_FragCoord.xy - resolution.xy * 0.5) / resolution.y;
    float ax     = 0.92 * u_line_width;  // X-Halbachse (Breite)
    float ay     = 0.39 * u_line_width;  // Y-Halbachse (kein Clip auf großen Screens)
    vec2 ps      = vec2(pn.x * (ay / ax), pn.y);  // in Ellipsen-Raum
    float r      = length(ps);
    float theta  = atan(ps.y, ps.x);
    float k      = u_line_count;
    float maxR   = ay;
    float GA     = 2.39996323;           // Echter Goldener Winkel: 2π·(1−1/φ) = 137.5°
    float PHI    = 1.6180339887;

    float petals  = 0.0;
    float glow    = 0.0;
    float maxOpac = 0.0;

    for (int i = 0; i < 12; i++) {
      float fi     = float(i);
      float angOff = fi * GA;            // Fibonacci-Spiralversatz
      float tOff   = fi * PHI * 1.1;

      float cycLen  = 4.5;
      float tBase   = u_time * u_line_speed * 0.10 + tOff;
      float t       = mod(tBase, cycLen) / cycLen;
      float cycleN  = floor(tBase / cycLen);   // wievielter Zyklus dieser Welle
      float progress = smoothstep(0.0, 0.55, t);
      float opacity  = smoothstep(0.0, 0.04, t) * (1.0 - smoothstep(0.65, 1.0, t));
      maxOpac = max(maxOpac, opacity);

      // Spawnpunkt dreht pro Zyklus ~8° im Uhrzeigersinn weiter
      float rotPerCycle = -0.14;             // negativ = Uhrzeigersinn
      float angRot = angOff + cycleN * rotPerCycle;

      // Rhodonea — Mindestradius 15% damit Mitte nie komplett leer
      float roseR = max(abs(cos(k * (theta + angRot))), 0.15) * maxR * progress;

      // Blatt von innen nach außen füllen (kein offenes Zentrum)
      float inside  = smoothstep(roseR + 0.009, roseR - 0.003, r);
      // Helligkeitsverlauf: innen dunkel, Rand satt
      float radial  = smoothstep(0.0, roseR * 0.9, r);
      float layer   = inside * (0.25 + radial * 0.75) * opacity;
      petals = max(petals, layer);

      // Leuchtkontur
      float edge = smoothstep(0.014, 0.0, abs(r - roseR)) * step(0.02, maxR * 0.15) * opacity;
      glow = max(glow, edge);
    }

    // Immer geschlossener dunkler Kern — verhindert Lücken zur Mitte
    float core = smoothstep(maxR * 0.18, 0.0, r) * maxOpac;

    vec3 petalDark  = u_line_color * 0.08;
    vec3 petalMid   = u_line_color * 0.78;
    vec3 petalGlow  = u_line_color * 1.9 + vec3(0.18, 0.0, 0.06);
    vec3 coreColor  = vec3(0.015, 0.0, 0.005);

    vec3 petalColor = mix(petalDark, petalMid, petals);
    petalColor      = mix(petalColor, petalGlow, glow * 0.9);
    color = mix(color, petalColor, max(petals * 0.92, glow));
    color = mix(color, coreColor, core * 0.88);
  }

  // Modus 8: Claudy Sky — FBM-Wolken, honoris causa
  if (u_line_mode > 7.5 && u_line_mode < 8.5) {
    vec2 uv = gl_FragCoord.xy / resolution.xy;
    float t  = u_time * u_line_speed * 0.018;

    // Himmel-Gradient: tiefblau oben → warmem Cyan am Horizont
    vec3 skyTop     = vec3(0.10, 0.22, 0.58);
    vec3 skyHorizon = vec3(0.52, 0.78, 0.96);
    vec3 sky        = mix(skyHorizon, skyTop, pow(uv.y, 0.7));

    // Horizon-Dunst
    sky = mix(sky, vec3(0.82, 0.90, 0.98), smoothstep(0.35, 0.0, uv.y) * 0.45);

    // Mouse-Parallax: Maus von Mitte abweichend → Wolken verschieben sich (3D-Tiefe)
    vec2 mouse  = u_mouse - vec2(0.5, 0.5);  // -0.5..+0.5, zentriert
    vec2 pxFar  = mouse * vec2(-0.06, 0.04); // ferne Schicht: wenig Verschiebung
    vec2 pxNear = mouse * vec2(-0.14, 0.09); // nahe Schicht: mehr Verschiebung

    // Zwei Wolken-Schichten (nah + fern) für Tiefe
    vec2 pFar  = uv * vec2(3.5, 2.5) + vec2(t * 0.6, 0.0) + pxFar;
    vec2 pNear = uv * vec2(2.2, 1.8) + vec2(t, 0.12)       + pxNear;

    float cFar  = smoothstep(0.46, 0.72, rct_fbm(pFar));
    float cNear = smoothstep(0.50, 0.80, rct_fbm(pNear));

    // Wolken nur in oberer Bildhälfte → natürlicher Horizont
    float skyMask = smoothstep(0.0, 0.45, uv.y);
    cFar  *= skyMask;
    cNear *= skyMask;

    // Wolkenfarben: weiß mit blau-grauem Schatten
    vec3 cloudWhite  = vec3(0.97, 0.97, 1.00);
    vec3 cloudShadow = vec3(0.62, 0.70, 0.84);
    vec3 cloudFar    = mix(cloudShadow, cloudWhite, smoothstep(0.0, 1.0, cFar));
    vec3 cloudNear   = mix(cloudShadow * 0.95, cloudWhite, smoothstep(0.0, 1.0, cNear));

    // Sonne: dezentes Glühen oben rechts, leicht mouse-reaktiv
    vec2 sunPos   = vec2(0.78, 0.88) + mouse * 0.025;
    float sunDist = length(uv - sunPos);
    vec3 sunColor = vec3(1.0, 0.97, 0.88);
    float sunHalo = smoothstep(0.40, 0.0, sunDist) * 0.18;
    float sunDisc = smoothstep(0.025, 0.012, sunDist) * 0.55;

    color = sky;
    color = mix(color, cloudFar,  cFar  * 0.75);
    color = mix(color, cloudNear, cNear * 0.90);
    color += sunColor * sunHalo;
    color  = mix(color, sunColor, sunDisc);
    color  = clamp(color, 0.0, 1.0);

    gl_FragColor = vec4(color, 1.0);
    return;
  }

  // Modus 9: Glass Tank — Glascontainer mit aufsteigenden Blasen + Caustics
  if (u_line_mode > 8.5 && u_line_mode < 9.5) {
    vec2 uv = gl_FragCoord.xy / resolution.xy;
    float sc = resolution.y;                      // normalisiert auf Höhe
    vec2 p   = gl_FragCoord.xy / sc;              // aspect-korrekte Koordinaten
    float ax = resolution.x / sc;                 // aspect ratio
    float t  = u_time * u_line_speed * 0.005;

    // Flüssigkeit: tiefdunkles Teal, heller nach oben
    vec3 deep    = vec3(0.0, 0.018, 0.012);
    vec3 shallow = u_line_color * 0.07;
    color = mix(deep, shallow, pow(uv.y, 0.6));

    // Caustic-Lichtmuster am Boden (animiert)
    vec2 cUV  = vec2(p.x * 3.2, p.y * 2.4);
    float caust = rct_fbm(cUV + vec2(t * 0.5, t * 0.35))
                + rct_fbm(cUV * 1.4 + vec2(-t * 0.3, t * 0.6)) * 0.5;
    caust = smoothstep(0.78, 1.05, caust);
    float floorFade = smoothstep(0.32, 0.0, uv.y);
    color += u_line_color * caust * floorFade * 0.35;

    // Glaswände: Fresnel-Schein links + rechts
    float wallL = smoothstep(0.10, 0.0, uv.x) * smoothstep(0.0, 0.15, uv.y);
    float wallR = smoothstep(0.90, 1.0, uv.x) * smoothstep(0.0, 0.15, uv.y);
    color += u_line_color * (wallL + wallR) * 0.22;

    // Oberfläche: Lichtreflexion oben
    float surf = smoothstep(0.96, 1.0, uv.y);
    color += u_line_color * surf * 0.55 + vec3(0.05) * surf;

    // Aufsteigende Glasblasen — 20 Stück
    for (int i = 0; i < 20; i++) {
      float fi    = float(i);
      float s1    = rct_hash(vec2(fi, 0.13));
      float s2    = rct_hash(vec2(fi, 2.77));
      float s3    = rct_hash(vec2(fi, 5.49));
      float s4    = rct_hash(vec2(fi, 8.31));

      float xBase = s1 * ax * 0.84 + ax * 0.08;  // im Behälter bleiben
      float speed = 0.10 + s2 * 0.25;
      float by    = fract(s3 - t * speed);         // 0=unten, 1=oben, wrapped
      float bSize = 0.009 + s4 * 0.020;

      // Horizontales Taumeln
      float wobble = sin(t * (1.8 + s1 * 2.5) + fi * 1.93) * bSize * 0.9;
      vec2 bc = vec2(xBase + wobble, by);

      if (by > 0.02 && by < 0.97) {
        color += rct_bubble(p, bc, bSize, 1.0, u_line_color);
      }
    }

    color = clamp(color, 0.0, 1.0);
    gl_FragColor = vec4(color, 1.0);
    return;
  }

  // Modus 10: Magnetfeld — Dipol-Feldlinien + Lemniskaten-Pole, Maus-reaktiv
  if (u_line_mode > 9.5 && u_line_mode < 10.5) {
    vec2 uv  = gl_FragCoord.xy / resolution.xy;
    float ar = resolution.x / resolution.y;
    // Aspect-korrigierte Koordinaten, zentriert
    vec2 p   = (uv - 0.5) * vec2(ar, 1.0);
    float t  = u_time * u_line_speed * 0.04;

    // Pole tracen eine Lemniskate (liegende Acht), Maus kippt die Achse
    vec2 mouse  = (u_mouse - 0.5) * 0.55;
    float lx    = sin(t) * 0.26;
    float ly    = sin(t * 2.0) * 0.13;
    vec2 posN   = vec2(lx + mouse.x * 0.35,  ly + mouse.y * 0.35);
    vec2 posS   = vec2(-lx - mouse.x * 0.35, -ly - mouse.y * 0.35);

    // Stream-Funktion des 2D-Dipols: Feldlinien = Iso-Konturen von psi
    float psiN  = atan(p.y - posN.y, p.x - posN.x);
    float psiS  = atan(p.y - posS.y, p.x - posS.x);
    float psi   = psiN - psiS;

    // Feldlinien: u_line_count Konturlinien (sin-Streifen)
    float stripe = sin(psi * u_line_count * 0.5);
    float lw     = clamp(0.055 / u_line_width, 0.01, 0.3);
    float line   = 1.0 - smoothstep(0.0, lw, abs(stripe));

    // Lokale Feldstärke — treibt Helligkeit (stärker an den Polen)
    float rN    = length(p - posN);
    float rS    = length(p - posS);
    float B     = clamp(0.004 / (rN * rN + 0.004) + 0.004 / (rS * rS + 0.004), 0.0, 1.0);

    // Richtungsfeld für Farbkodierung: 0=Südpol-Seite, 1=Nordpol-Seite
    vec2 fieldN = (p - posN) / (rN * rN + 0.002);
    vec2 fieldS = -(p - posS) / (rS * rS + 0.002);
    vec2 field  = fieldN + fieldS;
    float len   = length(field);
    float dir   = len > 0.0001
                  ? dot(field / len, normalize(posN - posS)) * 0.5 + 0.5
                  : 0.5;

    // Farben: Nordpol = Theme-Accent, Südpol = Komplementär (warm-rot)
    vec3 colN    = u_line_color;
    vec3 colS    = vec3(u_line_color.b * 0.7 + 0.25,
                        u_line_color.g * 0.20,
                        u_line_color.r * 0.35);
    vec3 lineCol = mix(colS, colN, dir) * (0.55 + B * 2.2);

    // Pol-Glühen + Disc
    float haloN = smoothstep(0.12, 0.0, rN) * 1.8;
    float haloS = smoothstep(0.12, 0.0, rS) * 1.8;
    float discN = smoothstep(0.022, 0.008, rN);
    float discS = smoothstep(0.022, 0.008, rS);

    // Hintergrund: fast schwarz + schwacher Feldfarben-Schimmer
    vec3 bg = vec3(0.018, 0.018, 0.030) + u_line_color * B * 0.06;

    color  = bg;
    color  = mix(color, lineCol, line * 0.88);
    color += colN * haloN;
    color += colS * haloS;
    color  = mix(color, colN * 1.8, discN);
    color  = mix(color, colS * 1.8, discS);
    color  = clamp(color, 0.0, 1.0);

    gl_FragColor = vec4(color, 1.0);
    return;
  }

  // Modus 11: Neon Grid — Retro-Perspektivgitter (Synthwave), Maus-reaktiv
  if (u_line_mode > 10.5 && u_line_mode < 11.5) {
    vec2 uv  = gl_FragCoord.xy / resolution.xy;
    float ar = resolution.x / resolution.y;
    float t  = (u_car_active > 0.5 ? u_car_offset : u_time) * u_line_speed * 0.035;

    // Maus: Horizont + Fluchtpunkt
    vec2 mouse    = u_mouse - 0.5;
    float boostN  = clamp(u_car_boost / 4.0, 0.0, 1.0) * step(0.5, u_car_active);
    float horiz   = 0.50 + mouse.y * 0.10 - boostN * 0.08;
    float camX    = mouse.x * 0.35 + u_car_tilt * 0.07;

    // ── HIMMEL ──────────────────────────────────────────────
    float skyT  = smoothstep(horiz, horiz + 0.45, uv.y);
    vec3 skyTop = vec3(0.012, 0.005, 0.035);
    vec3 skyMid = u_line_color * 0.25 + vec3(0.04, 0.0, 0.07);
    vec3 sky    = mix(skyMid, skyTop, skyT);

    // Sterne: pro Rasterzelle ein Punkt (nicht die ganze Zelle leuchten)
    vec2 starGrid = uv * vec2(90.0 * ar, 45.0);
    vec2 starCell = floor(starGrid);
    vec2 starFrac = fract(starGrid);
    float sHash   = rct_hash(starCell);
    // Zufällige Position des Sterns innerhalb der Zelle
    vec2 starPos  = vec2(rct_hash(starCell + 7.3), rct_hash(starCell + 13.1));
    float starDist = length(starFrac - starPos);
    float sTwink  = 0.4 + 0.6 * sin(u_time * u_line_speed * 0.25 + sHash * 23.7);
    sky += vec3(0.7, 0.85, 1.0)
         * step(0.972, sHash)
         * smoothstep(0.13, 0.0, starDist)
         * sTwink
         * step(horiz + 0.01, uv.y);

    // Sonne — halbe Ellipse (breiter als hoch), Synthwave-Style
    float sunCY = horiz + 0.002;
    float ax    = 0.33;   // X-Halbachse (breit, in res.y-Einheiten)
    float ay    = 0.085;  // Y-Halbachse (flach) → Verhältnis ~2.6:1
    float above = step(horiz, uv.y);
    // Ellipsen-Distanz: ex²+ey²<1 → halbe Ellipse über Horizont
    float ex    = (uv.x - 0.5 + camX * 0.25) * ar / ax;
    float ey    = (uv.y - sunCY) / ay;
    float eDist = sqrt(ex * ex + ey * ey);

    // Stripes: gleichmäßig in Y, ~5 Bänder, unten hell
    float fy    = clamp((uv.y - sunCY) / ay, 0.0, 1.0);
    float sStrp = step(0.45, fract(fy * 5.0 + 0.5));

    float sDisc = step(eDist, 1.0) * above * sStrp;
    float sEdge = smoothstep(0.09, 0.0, abs(eDist - 1.0)) * above;  // Ellipsen-Kontur
    float sHalo = smoothstep(2.6, 0.0, eDist) * above * 0.40;
    vec3 sunCol = u_line_color * 1.6 + vec3(0.15, 0.0, 0.05);
    sky  = mix(sky, sunCol * 0.85, sDisc);
    sky += sunCol * 1.8 * sEdge;
    sky += sunCol * sHalo;

    // Horizont-Glow
    sky += u_line_color * smoothstep(0.14, 0.0, abs(uv.y - horiz)) * 0.55;

    // ── BODEN-GRID ──────────────────────────────────────────
    vec3 floorColor = vec3(0.0);

    if (uv.y < horiz) {
      float vF   = max(horiz - uv.y, 0.0001);
      // Kamera-relativ (kein t-Offset) → Vertikallinien driften nicht seitwärts
      float relZ = min(1.3 / vF, 60.0);

      // ── Vertikale Linien (constant X, konvergieren zum Fluchtpunkt, statisch)
      float worldX = (uv.x - 0.5 + camX) * relZ * ar * 1.1;
      float fx     = min(fract(worldX), 1.0 - fract(worldX));
      float lw     = u_line_width * 0.026;
      float lineX  = 1.0 - smoothstep(0.0, lw,        fx);
      float glowX  = (1.0 - smoothstep(0.0, lw * 5.0, fx)) * 0.28;

      // ── Horizontale Linien: Screen-Space-Berechnung — kein Aliasing, kein Blitzen
      // Jede Linie i liegt bei vF_i = 1.3 / (i - scrollFrac)
      // → Position ist analytisch exakt, bewegt sich glatt durch den Screen
      float scrollFrac = fract(t * 3.5);
      float hw   = 0.0022 * u_line_width;  // feste Screen-Space-Breite (~1.5px)
      float hLine = 0.0;
      float hGlow = 0.0;

      for (int i = 1; i <= 26; i++) {
        float D    = float(i) - scrollFrac;
        float vF_l = 1.3 / max(D, 0.05);        // Screen-Position dieser Linie
        float ok   = step(vF_l, 0.54);           // nur innerhalb des sichtbaren Bodens
        float dist = abs(vF - vF_l);
        hLine = max(hLine, (1.0 - smoothstep(0.0, hw,       dist)) * ok);
        hGlow = max(hGlow, (1.0 - smoothstep(0.0, hw * 7.0, dist)) * 0.35 * ok);
      }

      vec3 colZ = u_line_color;
      vec3 colX = vec3(u_line_color.b * 0.6 + 0.35,
                       u_line_color.r * 0.10,
                       u_line_color.r * 0.90);

      // Fog: nur ~10px Einblendung am Horizont
      float fog    = smoothstep(0.0, 0.018, vF);
      float bright = clamp(vF * 3.5, 0.0, 1.0);

      vec3 ground  = vec3(0.008, 0.003, 0.018) + u_line_color * 0.022 * fog;
      floorColor   = ground;
      floorColor  += colZ * (hLine + hGlow) * fog * (0.65 + bright * 0.55);
      floorColor  += colX * (lineX  + glowX) * fog * (0.65 + bright * 0.55);
      // Horizont-Glow auf Boden-Seite — überbrückt den Schnitt zu Himmel
      floorColor  += u_line_color * smoothstep(0.07, 0.0, vF) * 0.30;
    }

    // Compositing
    color = uv.y >= horiz ? sky : floorColor;
    // Horizont-Naht überbrücken: heller Glow-Streifen über beide Seiten
    color += u_line_color * smoothstep(0.012, 0.0, abs(uv.y - horiz)) * 1.4;

    // ── EASTER EGG: 8-Bit Auto ──────────────────────────────────────────────
    if (u_car_active > 0.5) {
      float carDepth = 0.34;
      float carSX    = u_car_x * carDepth / (1.3 * ar * 1.1) + 0.5 - mouse.x * 0.35;
      float n        = clamp(u_car_boost / 4.0, 0.0, 1.0);
      float boostFwd = 1.0 - cos(n * 1.5708);  // Sinus Ease-In: flach → dramatisch
      float carSY    = horiz - carDepth + boostFwd * 0.110;
      float carScale = 0.036;  // halbe Karosserie-Breite im Screen (~50% kleiner)

      vec2  cp = uv - vec2(carSX, carSY + carScale * 0.45);
      cp.x    -= u_car_tilt * cp.y * 0.09;   // Lean beim Lenken
      cp       = cp / carScale;              // normiert: x∈[-1,1]

      // Pixel-Snap: 1/8 Einheit = 1 Pixel → 16px breit
      float px = 0.125;
      cp       = floor(cp / px + 0.5) * px;

      // Shapes (harte Kanten = Pixel-Art)
      float fBody = step(cp.y, 0.52) * step(0.0,  cp.y) * step(-1.0, cp.x) * step(cp.x, 1.0);
      float fCab  = step(cp.y, 0.94) * step(0.52, cp.y) * step(-0.6, cp.x) * step(cp.x, 0.6);
      float fCar  = max(fBody, fCab);
      float fW1   = step(length(cp - vec2(-0.70, 0.0)), 0.30);
      float fW2   = step(length(cp - vec2( 0.70, 0.0)), 0.30);
      float fWhl  = max(fW1, fW2);
      float fWind = step(cp.y, 0.90) * step(0.54, cp.y) * step(-0.52, cp.x) * step(cp.x, 0.52);
      // Rücklichter: beide Seiten (von hinten gesehen)
      float fHL1  = step(length(cp - vec2( 1.04, 0.17)), 0.15);
      float fHL2  = step(length(cp - vec2( 1.04, 0.40)), 0.15);
      float fHL3  = step(length(cp - vec2(-1.04, 0.17)), 0.15);
      float fHL4  = step(length(cp - vec2(-1.04, 0.40)), 0.15);
      float fHL   = max(max(fHL1, fHL2), max(fHL3, fHL4));

      vec3 cBody  = vec3(0.06, 0.18, 0.92);  // blau
      vec3 cWhl   = vec3(0.02, 0.03, 0.06);
      vec3 cWind  = vec3(0.10, 0.40, 0.85) * 0.55;
      vec3 cRL    = mix(vec3(0.28, 0.01, 0.01), vec3(1.0, 0.03, 0.03), u_car_brake);

      vec3 cFinal = vec3(0.0);
      cFinal      = mix(cFinal, cWhl,  fWhl);
      cFinal      = mix(cFinal, cBody, fCar);
      cFinal      = mix(cFinal, cWind, fWind);
      cFinal      = mix(cFinal, cRL,   fHL);
      color       = mix(color, cFinal, max(fCar, fWhl));

      // Rücklicht-Glow (immer leicht rot, beim Bremsen intensiv)
      float glowBase  = 0.10 + u_car_brake * 0.50;
      vec2  glPR = vec2(carSX + carScale * 1.12, carSY + carScale * 0.26);
      vec2  glPL = vec2(carSX - carScale * 1.12, carSY + carScale * 0.26);
      float glowR = smoothstep(0.08, 0.0, length((uv - glPR) * vec2(1.0, 1.3)));
      float glowL = smoothstep(0.08, 0.0, length((uv - glPL) * vec2(1.0, 1.3)));
      color      += vec3(1.0, 0.03, 0.03) * (glowR + glowL) * glowBase;

      // Glow bei Höchstgeschwindigkeit (erst ab 95%)
      color += u_line_color * smoothstep(3.8, 4.0, u_car_boost) * 0.22;
    }

    // ── GEGENVERKEHR ─────────────────────────────────────────────────────────
    if (u_car_active > 0.5) {
      rct_traffic(uv, u_traffic_x.x, u_traffic_z.x, horiz, ar, camX, color);
      rct_traffic(uv, u_traffic_x.y, u_traffic_z.y, horiz, ar, camX, color);
      rct_traffic(uv, u_traffic_x.z, u_traffic_z.z, horiz, ar, camX, color);
      rct_traffic(uv, u_traffic_x.w, u_traffic_z.w, horiz, ar, camX, color);
    }

    // ── TACHO ────────────────────────────────────────────────────────────────
    if (u_car_active > 0.5) {
      float spd = floor(clamp(u_car_boost / 4.0, 0.0, 1.0) * 199.0);
      float dH  = floor(spd / 100.0);
      float dT  = floor(mod(spd, 100.0) / 10.0);
      float dO  = mod(spd, 10.0);

      float dpx  = 0.007;
      float dpy  = dpx * ar;
      float dWid = 4.0 * dpx;
      vec2  tb   = vec2(0.82, 0.058);  // rechts unten

      float tach = 0.0;
      // Y-Flip: lp.y=0 = Boden der Zelle, Bitmap row0=top
      vec2 lpH = (uv - tb)                       / vec2(dpx, dpy);
      if (lpH.x >= 0.0 && lpH.x < 3.0 && lpH.y >= 0.0 && lpH.y < 5.0)
          tach = max(tach, rct_tachoBit(dH, (4.0 - floor(lpH.y)) * 3.0 + floor(lpH.x)));
      vec2 lpT = (uv - (tb + vec2(dWid, 0.0)))   / vec2(dpx, dpy);
      if (lpT.x >= 0.0 && lpT.x < 3.0 && lpT.y >= 0.0 && lpT.y < 5.0)
          tach = max(tach, rct_tachoBit(dT, (4.0 - floor(lpT.y)) * 3.0 + floor(lpT.x)));
      vec2 lpO = (uv - (tb + vec2(2.0*dWid, 0.0)))/ vec2(dpx, dpy);
      if (lpO.x >= 0.0 && lpO.x < 3.0 && lpO.y >= 0.0 && lpO.y < 5.0)
          tach = max(tach, rct_tachoBit(dO, (4.0 - floor(lpO.y)) * 3.0 + floor(lpO.x)));

      vec3  tCol = u_line_color * 2.2 + vec3(0.08, 0.0, 0.12);
      color = mix(color, tCol, tach);
      // Hintergrund-Halo um die Digits
      float halo = step(tb.x - dpx*1.5, uv.x) * step(uv.x, tb.x + 3.0*dWid + dpx*1.5)
                 * step(tb.y - dpy,      uv.y) * step(uv.y, tb.y + 5.5*dpy);
      color += u_line_color * halo * 0.07;

      // Speed-Bar unter den Digits
      float barW = 3.0 * dWid - dpx;
      float barH = dpy * 0.65;
      vec2  blp  = uv - (tb - vec2(0.0, dpy * 1.8));
      if (blp.x >= 0.0 && blp.x <= barW && blp.y >= 0.0 && blp.y <= barH) {
          float fill    = abs(clamp(u_car_boost, -0.3, 4.0)) / 4.0;
          float onBar   = step(blp.x / barW, fill);
          vec3  barCol  = u_car_boost >= 0.0 ? u_line_color * 2.0 : vec3(0.9, 0.08, 0.08);
          color = mix(color, barCol, onBar * 0.9);
          // Bar-Rand (immer sichtbar)
          float barEdge = step(barW - dpx * 0.5, blp.x) + step(blp.y, dpy * 0.08);
          color += u_line_color * barEdge * 0.15;
      }
    }

    // Crash-Flash
    color = mix(color, vec3(1.0, 0.04, 0.04), u_car_crash * 0.60);

    // CRT Scanlines
    color *= 0.90 + 0.10 * step(1.5, mod(gl_FragCoord.y, 3.0));

    // Vignette
    vec2 vUV  = (uv - 0.5) * 2.0;
    color    *= 1.0 - dot(vUV * vec2(0.65, 0.45), vUV * vec2(0.65, 0.45)) * 0.22;

    color = clamp(color, 0.0, 1.0);
    gl_FragColor = vec4(color, 1.0);
    return;
  }

  gl_FragColor = vec4(color, 1.0);
}
`;

/*   Stripe WebGl Gradient Animation
*   All Credits to Stripe.com
*   ScrollObserver functionality to disable animation when not scrolled into view has been disabled and 
*   commented out for now.
*   https://kevinhufnagl.com
*/


//Converting colors to proper format
function normalizeColor(hexCode) {
  return [(hexCode >> 16 & 255) / 255, (hexCode >> 8 & 255) / 255, (255 & hexCode) / 255]
} ["SCREEN", "LINEAR_LIGHT"].reduce((hexCode, t, n) => Object.assign(hexCode, {
  [t]: n
}), {});

//Essential functionality of WebGl
//t = width
//n = height
class MiniGl {
  constructor(canvas, width, height, debug = false) {
      const _miniGl = this,
          debug_output = -1 !== document.location.search.toLowerCase().indexOf("debug=webgl");
      _miniGl.canvas = canvas, _miniGl.gl = _miniGl.canvas.getContext("webgl", {
          antialias: true,
          alpha: true,
          premultipliedAlpha: false
      }), _miniGl.meshes = [];
      const context = _miniGl.gl;
      width && height && this.setSize(width, height), _miniGl.lastDebugMsg, _miniGl.debug = debug && debug_output ? function(e) {
          const t = new Date;
          t - _miniGl.lastDebugMsg > 1e3 && console.log("---"), console.log(t.toLocaleTimeString() + Array(Math.max(0, 32 - e.length)).join(" ") + e + ": ", ...Array.from(arguments).slice(1)), _miniGl.lastDebugMsg = t
      } : () => {}, Object.defineProperties(_miniGl, {
          Material: {
              enumerable: false,
              value: class {
                  constructor(vertexShaders, fragments, uniforms = {}) {
                      const material = this;
                      function getShaderByType(type, source) {
                          const shader = context.createShader(type);
                          return context.shaderSource(shader, source), context.compileShader(shader), context.getShaderParameter(shader, context.COMPILE_STATUS) || console.error(context.getShaderInfoLog(shader)), _miniGl.debug("Material.compileShaderSource", {
                              source: source
                          }), shader
                      }
                      function getUniformVariableDeclarations(uniforms, type) {
                          return Object.entries(uniforms).map(([uniform, value]) => value.getDeclaration(uniform, type)).join("\n")
                      }
                      material.uniforms = uniforms, material.uniformInstances = [];

                      const prefix = "\n              precision highp float;\n            ";
                      material.vertexSource = `\n              ${prefix}\n              attribute vec4 position;\n              attribute vec2 uv;\n              attribute vec2 uvNorm;\n              ${getUniformVariableDeclarations(_miniGl.commonUniforms,"vertex")}\n              ${getUniformVariableDeclarations(uniforms,"vertex")}\n              ${vertexShaders}\n            `,
                      material.Source = `\n              ${prefix}\n              ${getUniformVariableDeclarations(_miniGl.commonUniforms,"fragment")}\n              ${getUniformVariableDeclarations(uniforms,"fragment")}\n              ${fragments}\n            `,
                      material.vertexShader = getShaderByType(context.VERTEX_SHADER, material.vertexSource),
                      material.fragmentShader = getShaderByType(context.FRAGMENT_SHADER, material.Source),
                      material.program = context.createProgram(),
                      context.attachShader(material.program, material.vertexShader),
                      context.attachShader(material.program, material.fragmentShader),
                      context.linkProgram(material.program),
                      context.getProgramParameter(material.program, context.LINK_STATUS) || console.error(context.getProgramInfoLog(material.program)),
                      context.useProgram(material.program),
                      material.attachUniforms(void 0, _miniGl.commonUniforms),
                      material.attachUniforms(void 0, material.uniforms)
                  }
                  //t = uniform
                  attachUniforms(name, uniforms) {
                      //n  = material
                      const material = this;
                      void 0 === name ? Object.entries(uniforms).forEach(([name, uniform]) => {
                          material.attachUniforms(name, uniform)
                      }) : "array" == uniforms.type ? uniforms.value.forEach((uniform, i) => material.attachUniforms(`${name}[${i}]`, uniform)) : "struct" == uniforms.type ? Object.entries(uniforms.value).forEach(([uniform, i]) => material.attachUniforms(`${name}.${uniform}`, i)) : (_miniGl.debug("Material.attachUniforms", {
                          name: name,
                          uniform: uniforms
                      }), material.uniformInstances.push({
                          uniform: uniforms,
                          location: context.getUniformLocation(material.program, name)
                      }))
                  }
              }
          },
          Uniform: {
              enumerable: !1,
              value: class {
                  constructor(e) {
                      this.type = "float", Object.assign(this, e);
                      this.typeFn = {
                          float: "1f",
                          int: "1i",
                          vec2: "2fv",
                          vec3: "3fv",
                          vec4: "4fv",
                          mat4: "Matrix4fv"
                      } [this.type] || "1f", this.update()
                  }
                  update(value) {
                      void 0 !== this.value && context[`uniform${this.typeFn}`](value, 0 === this.typeFn.indexOf("Matrix") ? this.transpose : this.value, 0 === this.typeFn.indexOf("Matrix") ? this.value : null)
                  }
                  //e - name
                  //t - type
                  //n - length
                  getDeclaration(name, type, length) {
                      const uniform = this;
                      if (uniform.excludeFrom !== type) {
                          if ("array" === uniform.type) return uniform.value[0].getDeclaration(name, type, uniform.value.length) + `\nconst int ${name}_length = ${uniform.value.length};`;
                          if ("struct" === uniform.type) {
                              let name_no_prefix = name.replace("u_", "");
                              return name_no_prefix = 
                                name_no_prefix.charAt(0).toUpperCase() + 
                                name_no_prefix.slice(1), 
                                `uniform struct ${name_no_prefix} 
                                {\n` + 
                                Object.entries(uniform.value).map(([name, uniform]) => 
                                uniform.getDeclaration(name, type)
                                .replace(/^uniform/, ""))
                                .join("") 
                                + `\n} ${name}${length>0?`[${length}]`:""};`
                          }
                          return `uniform ${uniform.type} ${name}${length>0?`[${length}]`:""};`
                      }
                  }
              }
          },
          PlaneGeometry: {
              enumerable: !1,
              value: class {
                  constructor(width, height, n, i, orientation) {
                    context.createBuffer(), this.attributes = {
                          position: new _miniGl.Attribute({
                              target: context.ARRAY_BUFFER,
                              size: 3
                          }),
                          uv: new _miniGl.Attribute({
                              target: context.ARRAY_BUFFER,
                              size: 2
                          }),
                          uvNorm: new _miniGl.Attribute({
                              target: context.ARRAY_BUFFER,
                              size: 2
                          }),
                          index: new _miniGl.Attribute({
                              target: context.ELEMENT_ARRAY_BUFFER,
                              size: 3,
                              type: context.UNSIGNED_SHORT
                          })
                      }, this.setTopology(n, i), this.setSize(width, height, orientation)
                  }
                  setTopology(e = 1, t = 1) {
                      const n = this;
                      n.xSegCount = e, n.ySegCount = t, n.vertexCount = (n.xSegCount + 1) * (n.ySegCount + 1), n.quadCount = n.xSegCount * n.ySegCount * 2, n.attributes.uv.values = new Float32Array(2 * n.vertexCount), n.attributes.uvNorm.values = new Float32Array(2 * n.vertexCount), n.attributes.index.values = new Uint16Array(3 * n.quadCount);
                      for (let e = 0; e <= n.ySegCount; e++)
                          for (let t = 0; t <= n.xSegCount; t++) {
                              const i = e * (n.xSegCount + 1) + t;
                              if (n.attributes.uv.values[2 * i] = t / n.xSegCount, n.attributes.uv.values[2 * i + 1] = 1 - e / n.ySegCount, n.attributes.uvNorm.values[2 * i] = t / n.xSegCount * 2 - 1, n.attributes.uvNorm.values[2 * i + 1] = 1 - e / n.ySegCount * 2, t < n.xSegCount && e < n.ySegCount) {
                                  const s = e * n.xSegCount + t;
                                  n.attributes.index.values[6 * s] = i, n.attributes.index.values[6 * s + 1] = i + 1 + n.xSegCount, n.attributes.index.values[6 * s + 2] = i + 1, n.attributes.index.values[6 * s + 3] = i + 1, n.attributes.index.values[6 * s + 4] = i + 1 + n.xSegCount, n.attributes.index.values[6 * s + 5] = i + 2 + n.xSegCount
                              }
                          }
                      n.attributes.uv.update(), n.attributes.uvNorm.update(), n.attributes.index.update(), _miniGl.debug("Geometry.setTopology", {
                          uv: n.attributes.uv,
                          uvNorm: n.attributes.uvNorm,
                          index: n.attributes.index
                      })
                  }
                  setSize(width = 1, height = 1, orientation = "xz") {
                      const geometry = this;
                      geometry.width = width,
                      geometry.height = height,
                      geometry.orientation = orientation,
                      geometry.attributes.position.values && geometry.attributes.position.values.length === 3 * geometry.vertexCount 
                      || (geometry.attributes.position.values = new Float32Array(3 * geometry.vertexCount));
                      const o = width / -2,
                          r = height / -2,
                          segment_width = width / geometry.xSegCount,
                          segment_height = height / geometry.ySegCount;
                      for (let yIndex= 0; yIndex <= geometry.ySegCount; yIndex++) {
                          const t = r + yIndex * segment_height;
                          for (let xIndex = 0; xIndex <= geometry.xSegCount; xIndex++) {
                              const r = o + xIndex * segment_width,
                                  l = yIndex * (geometry.xSegCount + 1) + xIndex;
                              geometry.attributes.position.values[3 * l + "xyz".indexOf(orientation[0])] = r, 
                              geometry.attributes.position.values[3 * l + "xyz".indexOf(orientation[1])] = -t
                          }
                      }
                      geometry.attributes.position.update(), _miniGl.debug("Geometry.setSize", {
                          position: geometry.attributes.position
                      })
                  }
              }
          },
          Mesh: {
              enumerable: !1,
              value: class {
                  constructor(geometry, material) {
                      const mesh = this;
                      mesh.geometry = geometry, mesh.material = material, mesh.wireframe = !1, mesh.attributeInstances = [], Object.entries(mesh.geometry.attributes).forEach(([e, attribute]) => {
                          mesh.attributeInstances.push({
                              attribute: attribute,
                              location: attribute.attach(e, mesh.material.program)
                          })
                      }), _miniGl.meshes.push(mesh), _miniGl.debug("Mesh.constructor", {
                          mesh: mesh
                      })
                  }
                  draw() {
                    context.useProgram(this.material.program), this.material.uniformInstances.forEach(({
                          uniform: e,
                          location: t
                      }) => e.update(t)), this.attributeInstances.forEach(({
                          attribute: e,
                          location: t
                      }) => e.use(t)), context.drawElements(this.wireframe ? context.LINES : context.TRIANGLES, this.geometry.attributes.index.values.length, context.UNSIGNED_SHORT, 0)
                  }
                  remove() {
                      _miniGl.meshes = _miniGl.meshes.filter(e => e != this)
                  }
              }
          },
          Attribute: {
              enumerable: !1,
              value: class {
                  constructor(e) {
                      this.type = context.FLOAT, this.normalized = !1, this.buffer = context.createBuffer(), Object.assign(this, e), this.update()
                  }
                  update() {
                      void 0 !== this.values && (context.bindBuffer(this.target, this.buffer), context.bufferData(this.target, this.values, context.STATIC_DRAW))
                  }
                  attach(e, t) {
                      const n = context.getAttribLocation(t, e);
                      return this.target === context.ARRAY_BUFFER && (context.enableVertexAttribArray(n), context.vertexAttribPointer(n, this.size, this.type, this.normalized, 0, 0)), n
                  }
                  use(e) {
                    context.bindBuffer(this.target, this.buffer), this.target === context.ARRAY_BUFFER && (context.enableVertexAttribArray(e), context.vertexAttribPointer(e, this.size, this.type, this.normalized, 0, 0))
                  }
              }
          }
      });
      const a = [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1];
      _miniGl.commonUniforms = {
          projectionMatrix: new _miniGl.Uniform({
              type: "mat4",
              value: a
          }),
          modelViewMatrix: new _miniGl.Uniform({
              type: "mat4",
              value: a
          }),
          resolution: new _miniGl.Uniform({
              type: "vec2",
              value: [1, 1]
          }),
          aspectRatio: new _miniGl.Uniform({
              type: "float",
              value: 1
          })
      }
  }
  setSize(e = 480, t = 270) {
      this.width = e, this.height = t, this.canvas.width = e, this.canvas.height = t, this.gl.viewport(0, 0, e, t), this.commonUniforms.resolution.value = [e, t], this.commonUniforms.aspectRatio.value = e / t, this.debug("MiniGL.setSize", {
          width: e,
          height: t
      })
  }
  //left, right, top, bottom, near, far
  setOrthographicCamera(e = 0, t = 0, n = 0, i = -2e3, s = 2e3) {
      this.commonUniforms.projectionMatrix.value = [2 / this.width, 0, 0, 0, 0, 2 / this.height, 0, 0, 0, 0, 2 / (i - s), 0, e, t, n, 1], this.debug("setOrthographicCamera", this.commonUniforms.projectionMatrix.value)
  }
  render() {
      this.gl.clearColor(0, 0, 0, 0), this.gl.clearDepth(1), this.meshes.forEach(e => e.draw())
  }
}



//Sets initial properties
function e(object, propertyName, val) {
  return propertyName in object ? Object.defineProperty(object, propertyName, {
      value: val,
      enumerable: !0,
      configurable: !0,
      writable: !0
  }) : object[propertyName] = val, object
}

//Gradient object
class Gradient {
  constructor(...t) {
      e(this, "el", void 0), e(this, "cssVarRetries", 0), e(this, "maxCssVarRetries", 200), e(this, "angle", 0), e(this, "isLoadedClass", !1), e(this, "isScrolling", !1), /*e(this, "isStatic", o.disableAmbientAnimations()),*/ e(this, "scrollingTimeout", void 0), e(this, "scrollingRefreshDelay", 200), e(this, "isIntersecting", !1), e(this, "shaderFiles", void 0), e(this, "vertexShader", void 0), e(this, "sectionColors", void 0), e(this, "computedCanvasStyle", void 0), e(this, "conf", void 0), e(this, "uniforms", void 0), e(this, "t", 1253106), e(this, "last", 0), e(this, "width", void 0), e(this, "minWidth", 1111), e(this, "height", 600), e(this, "xSegCount", void 0), e(this, "ySegCount", void 0), e(this, "mesh", void 0), e(this, "material", void 0), e(this, "geometry", void 0), e(this, "minigl", void 0), e(this, "scrollObserver", void 0), e(this, "amp", 320), e(this, "seed", 5), e(this, "freqX", 14e-5), e(this, "freqY", 29e-5), e(this, "freqDelta", 1e-5), e(this, "activeColors", [1, 1, 1, 1]), e(this, "isMetaKey", !1), e(this, "isGradientLegendVisible", !1), e(this, "isMouseDown", !1), e(this, "handleScroll", () => {
          clearTimeout(this.scrollingTimeout), this.scrollingTimeout = setTimeout(this.handleScrollEnd, this.scrollingRefreshDelay), this.isGradientLegendVisible && this.hideGradientLegend(), this.conf.playing && (this.isScrolling = !0, this.pause())
      }), 
      
      // Im constructor bei den anderen e(this, "handle...", ...) hinzufügen:
e(this, "handleMouseMove", (event) => {
    if (this.mesh && this.mesh.material) {
        // Normalisierte Koordinaten (0.0 bis 1.0)
        const rect = this.el.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width;
        const y = 1.0 - (event.clientY - rect.top) / rect.height; // Y ist in WebGL umgedreht
        this.mesh.material.uniforms.u_mouse.value = [x, y];
    }
}),
      e(this, "handleScrollEnd", () => {
          this.isScrolling = !1, this.isIntersecting && this.play()
      }), e(this, "resize", () => {
          this.width = window.innerWidth, this.minigl.setSize(this.width, this.height), this.minigl.setOrthographicCamera(), this.xSegCount = Math.ceil(this.width * this.conf.density[0]), this.ySegCount = Math.ceil(this.height * this.conf.density[1]), this.mesh.geometry.setTopology(this.xSegCount, this.ySegCount), this.mesh.geometry.setSize(this.width, this.height), this.mesh.material.uniforms.u_shadow_power.value = this.width < 600 ? 5 : 6,
        // Responsive: auf Mobile Tropfen/Linien halbieren
        this.lineCount !== undefined && (
          this.mesh.material.uniforms.u_line_count.value = this.width < 768
            ? Math.ceil(this.lineCount / 2)
            : this.lineCount
        )
      }),
      // Easter Egg: 8-Bit Auto State
      e(this, "_carX", 0.0),
      e(this, "_carBoost", 1.0),
      e(this, "_carTilt", 0.0),
      e(this, "_carActive", 0.0),
      e(this, "_carKeys", {}),
      e(this, "_carOffset", 0.0),
      e(this, "_carLastT", 0.0),
      e(this, "_carCrash", 0.0),
      e(this, "_traffic", [
          { worldX: -2.8, phase:  0.05 },
          { worldX:  3.1, phase: -1.5 },
          { worldX:  0.0, phase: -2.5 },
          { worldX:  0.0, phase: -3.2 },
      ]),
      e(this, "handleKeyDown", (ev) => {
          if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(ev.key)) return;
          if (this.lineMode !== 11) return;
          this._carKeys[ev.key] = true;
          if (!this._carActive) {
              this._carActive  = 1.0;
              this._carBoost   = 0.0;
              this._carOffset  = this.t;   // nahtloser Übergang: Grid steht still
              this._carLastT   = this.t;
              if (this.mesh && this.mesh.material) {
                  this.mesh.material.uniforms.u_car_active.value = 1.0;
                  this.mesh.material.uniforms.u_car_boost.value  = 0.0;
                  this.mesh.material.uniforms.u_car_offset.value = this.t;
              }
          }
          ev.preventDefault();
      }),
      e(this, "handleKeyUp", (ev) => {
          delete this._carKeys[ev.key];
      }),
      e(this, "handleMouseDown", e => {
          this.isGradientLegendVisible && (this.isMetaKey = e.metaKey, this.isMouseDown = !0, !1 === this.conf.playing && requestAnimationFrame(this.animate))
      }), e(this, "handleMouseUp", () => {
          this.isMouseDown = !1
      }), e(this, "animate", e => {
          if (!this.shouldSkipFrame(e) || this.isMouseDown) {
              if (this.t += Math.min(e - this.last, 1e3 / 15), this.last = e, this.isMouseDown) {
                  let e = 160;
                  this.isMetaKey && (e = -160), this.t += e
              }
              //this.mesh.material.uniforms.u_time.value = this.t, this.minigl.render()
              //this.mesh.material.uniforms.u_time.value = this.t * 10, this.minigl.render()
              this.mesh.material.uniforms.u_time.value = this.t * (this.timeSpeed || 1);
              // Auto-Physics (nur wenn aktiv)
              if (this._carActive && this.mesh && this.mesh.material) {
                  const steer = 0.055 + Math.abs(this._carBoost) * 0.007;
                  if (this._carKeys['ArrowLeft'])  this._carX -= steer;
                  if (this._carKeys['ArrowRight']) this._carX += steer;
                  this._carX = Math.max(-3.2, Math.min(3.2, this._carX));
                  if (this._carKeys['ArrowUp']) {
                      this._carBoost = Math.min(4.0, this._carBoost + 0.022);
                  } else if (this._carKeys['ArrowDown']) {
                      if (this._carBoost > 0) this._carBoost = Math.max(0,    this._carBoost - 0.040); // schnell bremsen
                      else                    this._carBoost = Math.max(-0.3, this._carBoost - 0.006); // langsam rückwärts
                  } else if (this._carBoost > 0) {
                      this._carBoost = Math.max(0, this._carBoost - 0.010);
                  } else if (this._carBoost < 0) {
                      this._carBoost = Math.min(0, this._carBoost + 0.006); // ausrollen rückwärts
                  }
                  const tgt = this._carKeys['ArrowLeft'] ? -1.0 : this._carKeys['ArrowRight'] ? 1.0 : 0.0;
                  this._carTilt += (tgt - this._carTilt) * 0.12;
                  // Offset akkumulieren: dt = Zuwachs von this.t seit letztem Frame
                  const dt = this.t - this._carLastT;
                  this._carLastT   = this.t;
                  this._carOffset += this._carBoost * dt;
                  this.mesh.material.uniforms.u_car_x.value      = this._carX;
                  this.mesh.material.uniforms.u_car_boost.value   = this._carBoost;
                  this.mesh.material.uniforms.u_car_tilt.value    = this._carTilt;
                  this.mesh.material.uniforms.u_car_brake.value   = this._carKeys['ArrowDown'] ? 1.0 : 0.0;
                  this.mesh.material.uniforms.u_car_offset.value  = this._carOffset;

                  // ── Gegenverkehr ─────────────────────────────────────────
                  const AR     = (this.el.width / this.el.height) || 1.78;
                  const TSCALE = 0.00011;
                  this._traffic.forEach(car => {
                      car.phase += (Math.max(this._carBoost, 0) + 0.8) * dt * TSCALE;
                      if (car.phase > 1.05) {
                          car.phase  = -(1.2 + Math.random() * 1.8);
                          car.worldX = (Math.random() * 2 - 1) * 4.5;
                      }
                  });
                  this.mesh.material.uniforms.u_traffic_x.value = this._traffic.map(c => c.worldX);
                  this.mesh.material.uniforms.u_traffic_z.value = this._traffic.map(c => Math.max(c.phase, 0));

                  // ── Collision Detection ──────────────────────────────────
                  const CAR_DEPTH = 0.34, CAR_SCALE = 0.036;
                  const carRelZ   = 1.3 / CAR_DEPTH;
                  const carScrX   = this._carX / (carRelZ * AR * 1.1) + 0.5;
                  this._traffic.forEach(car => {
                      if (car.phase < 0.05 || car.phase > 0.98) return;
                      const vF    = car.phase * 0.42;
                      const relZ  = 1.3 / vF;
                      const tScrX = car.worldX / (relZ * AR * 1.1) + 0.5;
                      const tSc   = 0.106 * vF;
                      if (Math.abs(tScrX - carScrX) < (tSc + CAR_SCALE) * 0.85
                       && Math.abs(vF - CAR_DEPTH)  < (tSc + CAR_SCALE) * 0.65
                       && this._carCrash < 0.1) {
                          this._carCrash = 1.0;
                          this._carBoost *= 0.15;
                          car.phase  = -(1.2 + Math.random() * 1.8);
                          car.worldX = (Math.random() * 2 - 1) * 4.5;
                      }
                  });
                  if (this._carCrash > 0) this._carCrash = Math.max(0, this._carCrash - 0.025);
                  this.mesh.material.uniforms.u_car_crash.value = this._carCrash;
              }
              this.minigl.render();

          }
          if (0 !== this.last && this.isStatic) return this.minigl.render(), void this.disconnect();
          (/*this.isIntersecting && */this.conf.playing || this.isMouseDown) && requestAnimationFrame(this.animate)
      }), e(this, "addIsLoadedClass", () => {
          /*this.isIntersecting && */!this.isLoadedClass && (this.isLoadedClass = !0, this.el.classList.add("isLoaded"), setTimeout(() => {
              this.el.parentElement.classList.add("isLoaded")
          }, 3e3))
      }), e(this, "pause", () => {
          this.conf.playing = false
      }), e(this, "play", () => {
          requestAnimationFrame(this.animate), this.conf.playing = true
      }), e(this,"initGradient", (selector) => {
        this.el = document.querySelector(selector);
        this.connect();
        return this;
      })
  }
  async connect() {
    window.addEventListener("mousemove", this.handleMouseMove);
    window.addEventListener("keydown", this.handleKeyDown);
    window.addEventListener("keyup",   this.handleKeyUp);
      this.shaderFiles = {
          vertex: this.fragmentWithVertex ? RCT_VERTEX_SHADER : RCT_VERTEX_SHADER_FLAT,
          noise: "//\n// Description : Array and textureless GLSL 2D/3D/4D simplex\n//               noise functions.\n//      Author : Ian McEwan, Ashima Arts.\n//  Maintainer : stegu\n//     Lastmod : 20110822 (ijm)\n//     License : Copyright (C) 2011 Ashima Arts. All rights reserved.\n//               Distributed under the MIT License. See LICENSE file.\n//               https://github.com/ashima/webgl-noise\n//               https://github.com/stegu/webgl-noise\n//\n\nvec3 mod289(vec3 x) {\n  return x - floor(x * (1.0 / 289.0)) * 289.0;\n}\n\nvec4 mod289(vec4 x) {\n  return x - floor(x * (1.0 / 289.0)) * 289.0;\n}\n\nvec4 permute(vec4 x) {\n    return mod289(((x*34.0)+1.0)*x);\n}\n\nvec4 taylorInvSqrt(vec4 r)\n{\n  return 1.79284291400159 - 0.85373472095314 * r;\n}\n\nfloat snoise(vec3 v)\n{\n  const vec2  C = vec2(1.0/6.0, 1.0/3.0) ;\n  const vec4  D = vec4(0.0, 0.5, 1.0, 2.0);\n\n// First corner\n  vec3 i  = floor(v + dot(v, C.yyy) );\n  vec3 x0 =   v - i + dot(i, C.xxx) ;\n\n// Other corners\n  vec3 g = step(x0.yzx, x0.xyz);\n  vec3 l = 1.0 - g;\n  vec3 i1 = min( g.xyz, l.zxy );\n  vec3 i2 = max( g.xyz, l.zxy );\n\n  //   x0 = x0 - 0.0 + 0.0 * C.xxx;\n  //   x1 = x0 - i1  + 1.0 * C.xxx;\n  //   x2 = x0 - i2  + 2.0 * C.xxx;\n  //   x3 = x0 - 1.0 + 3.0 * C.xxx;\n  vec3 x1 = x0 - i1 + C.xxx;\n  vec3 x2 = x0 - i2 + C.yyy; // 2.0*C.x = 1/3 = C.y\n  vec3 x3 = x0 - D.yyy;      // -1.0+3.0*C.x = -0.5 = -D.y\n\n// Permutations\n  i = mod289(i);\n  vec4 p = permute( permute( permute(\n            i.z + vec4(0.0, i1.z, i2.z, 1.0 ))\n          + i.y + vec4(0.0, i1.y, i2.y, 1.0 ))\n          + i.x + vec4(0.0, i1.x, i2.x, 1.0 ));\n\n// Gradients: 7x7 points over a square, mapped onto an octahedron.\n// The ring size 17*17 = 289 is close to a multiple of 49 (49*6 = 294)\n  float n_ = 0.142857142857; // 1.0/7.0\n  vec3  ns = n_ * D.wyz - D.xzx;\n\n  vec4 j = p - 49.0 * floor(p * ns.z * ns.z);  //  mod(p,7*7)\n\n  vec4 x_ = floor(j * ns.z);\n  vec4 y_ = floor(j - 7.0 * x_ );    // mod(j,N)\n\n  vec4 x = x_ *ns.x + ns.yyyy;\n  vec4 y = y_ *ns.x + ns.yyyy;\n  vec4 h = 1.0 - abs(x) - abs(y);\n\n  vec4 b0 = vec4( x.xy, y.xy );\n  vec4 b1 = vec4( x.zw, y.zw );\n\n  //vec4 s0 = vec4(lessThan(b0,0.0))*2.0 - 1.0;\n  //vec4 s1 = vec4(lessThan(b1,0.0))*2.0 - 1.0;\n  vec4 s0 = floor(b0)*2.0 + 1.0;\n  vec4 s1 = floor(b1)*2.0 + 1.0;\n  vec4 sh = -step(h, vec4(0.0));\n\n  vec4 a0 = b0.xzyw + s0.xzyw*sh.xxyy ;\n  vec4 a1 = b1.xzyw + s1.xzyw*sh.zzww ;\n\n  vec3 p0 = vec3(a0.xy,h.x);\n  vec3 p1 = vec3(a0.zw,h.y);\n  vec3 p2 = vec3(a1.xy,h.z);\n  vec3 p3 = vec3(a1.zw,h.w);\n\n//Normalise gradients\n  vec4 norm = taylorInvSqrt(vec4(dot(p0,p0), dot(p1,p1), dot(p2, p2), dot(p3,p3)));\n  p0 *= norm.x;\n  p1 *= norm.y;\n  p2 *= norm.z;\n  p3 *= norm.w;\n\n// Mix final noise value\n  vec4 m = max(0.6 - vec4(dot(x0,x0), dot(x1,x1), dot(x2,x2), dot(x3,x3)), 0.0);\n  m = m * m;\n  return 42.0 * dot( m*m, vec4( dot(p0,x0), dot(p1,x1),\n                                dot(p2,x2), dot(p3,x3) ) );\n}",
          blend: "//\n// https://github.com/jamieowen/glsl-blend\n//\n\n// Normal\n\nvec3 blendNormal(vec3 base, vec3 blend) {\n\treturn blend;\n}\n\nvec3 blendNormal(vec3 base, vec3 blend, float opacity) {\n\treturn (blendNormal(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Screen\n\nfloat blendScreen(float base, float blend) {\n\treturn 1.0-((1.0-base)*(1.0-blend));\n}\n\nvec3 blendScreen(vec3 base, vec3 blend) {\n\treturn vec3(blendScreen(base.r,blend.r),blendScreen(base.g,blend.g),blendScreen(base.b,blend.b));\n}\n\nvec3 blendScreen(vec3 base, vec3 blend, float opacity) {\n\treturn (blendScreen(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Multiply\n\nvec3 blendMultiply(vec3 base, vec3 blend) {\n\treturn base*blend;\n}\n\nvec3 blendMultiply(vec3 base, vec3 blend, float opacity) {\n\treturn (blendMultiply(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Overlay\n\nfloat blendOverlay(float base, float blend) {\n\treturn base<0.5?(2.0*base*blend):(1.0-2.0*(1.0-base)*(1.0-blend));\n}\n\nvec3 blendOverlay(vec3 base, vec3 blend) {\n\treturn vec3(blendOverlay(base.r,blend.r),blendOverlay(base.g,blend.g),blendOverlay(base.b,blend.b));\n}\n\nvec3 blendOverlay(vec3 base, vec3 blend, float opacity) {\n\treturn (blendOverlay(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Hard light\n\nvec3 blendHardLight(vec3 base, vec3 blend) {\n\treturn blendOverlay(blend,base);\n}\n\nvec3 blendHardLight(vec3 base, vec3 blend, float opacity) {\n\treturn (blendHardLight(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Soft light\n\nfloat blendSoftLight(float base, float blend) {\n\treturn (blend<0.5)?(2.0*base*blend+base*base*(1.0-2.0*blend)):(sqrt(base)*(2.0*blend-1.0)+2.0*base*(1.0-blend));\n}\n\nvec3 blendSoftLight(vec3 base, vec3 blend) {\n\treturn vec3(blendSoftLight(base.r,blend.r),blendSoftLight(base.g,blend.g),blendSoftLight(base.b,blend.b));\n}\n\nvec3 blendSoftLight(vec3 base, vec3 blend, float opacity) {\n\treturn (blendSoftLight(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Color dodge\n\nfloat blendColorDodge(float base, float blend) {\n\treturn (blend==1.0)?blend:min(base/(1.0-blend),1.0);\n}\n\nvec3 blendColorDodge(vec3 base, vec3 blend) {\n\treturn vec3(blendColorDodge(base.r,blend.r),blendColorDodge(base.g,blend.g),blendColorDodge(base.b,blend.b));\n}\n\nvec3 blendColorDodge(vec3 base, vec3 blend, float opacity) {\n\treturn (blendColorDodge(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Color burn\n\nfloat blendColorBurn(float base, float blend) {\n\treturn (blend==0.0)?blend:max((1.0-((1.0-base)/blend)),0.0);\n}\n\nvec3 blendColorBurn(vec3 base, vec3 blend) {\n\treturn vec3(blendColorBurn(base.r,blend.r),blendColorBurn(base.g,blend.g),blendColorBurn(base.b,blend.b));\n}\n\nvec3 blendColorBurn(vec3 base, vec3 blend, float opacity) {\n\treturn (blendColorBurn(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Vivid Light\n\nfloat blendVividLight(float base, float blend) {\n\treturn (blend<0.5)?blendColorBurn(base,(2.0*blend)):blendColorDodge(base,(2.0*(blend-0.5)));\n}\n\nvec3 blendVividLight(vec3 base, vec3 blend) {\n\treturn vec3(blendVividLight(base.r,blend.r),blendVividLight(base.g,blend.g),blendVividLight(base.b,blend.b));\n}\n\nvec3 blendVividLight(vec3 base, vec3 blend, float opacity) {\n\treturn (blendVividLight(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Lighten\n\nfloat blendLighten(float base, float blend) {\n\treturn max(blend,base);\n}\n\nvec3 blendLighten(vec3 base, vec3 blend) {\n\treturn vec3(blendLighten(base.r,blend.r),blendLighten(base.g,blend.g),blendLighten(base.b,blend.b));\n}\n\nvec3 blendLighten(vec3 base, vec3 blend, float opacity) {\n\treturn (blendLighten(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Linear burn\n\nfloat blendLinearBurn(float base, float blend) {\n\t// Note : Same implementation as BlendSubtractf\n\treturn max(base+blend-1.0,0.0);\n}\n\nvec3 blendLinearBurn(vec3 base, vec3 blend) {\n\t// Note : Same implementation as BlendSubtract\n\treturn max(base+blend-vec3(1.0),vec3(0.0));\n}\n\nvec3 blendLinearBurn(vec3 base, vec3 blend, float opacity) {\n\treturn (blendLinearBurn(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Linear dodge\n\nfloat blendLinearDodge(float base, float blend) {\n\t// Note : Same implementation as BlendAddf\n\treturn min(base+blend,1.0);\n}\n\nvec3 blendLinearDodge(vec3 base, vec3 blend) {\n\t// Note : Same implementation as BlendAdd\n\treturn min(base+blend,vec3(1.0));\n}\n\nvec3 blendLinearDodge(vec3 base, vec3 blend, float opacity) {\n\treturn (blendLinearDodge(base, blend) * opacity + base * (1.0 - opacity));\n}\n\n// Linear light\n\nfloat blendLinearLight(float base, float blend) {\n\treturn blend<0.5?blendLinearBurn(base,(2.0*blend)):blendLinearDodge(base,(2.0*(blend-0.5)));\n}\n\nvec3 blendLinearLight(vec3 base, vec3 blend) {\n\treturn vec3(blendLinearLight(base.r,blend.r),blendLinearLight(base.g,blend.g),blendLinearLight(base.b,blend.b));\n}\n\nvec3 blendLinearLight(vec3 base, vec3 blend, float opacity) {\n\treturn (blendLinearLight(base, blend) * opacity + base * (1.0 - opacity));\n}",
          fragment: RCT_FRAGMENT_SHADER
      },
      this.conf = {
          presetName: "",
          wireframe: false,
          density: [.06, .16],
          zoom: 1,
          rotation: 0,
          playing: true
      }, 
      document.querySelectorAll("canvas").length < 1 ? console.log("DID NOT LOAD HERO STRIPE CANVAS") : (
        
        this.minigl = new MiniGl(this.el, null, null, !0), 
        requestAnimationFrame(() => {
            this.el && (this.computedCanvasStyle = getComputedStyle(this.el), this.waitForCssVars())
        })
        /*
        this.scrollObserver = await s.create(.1, !1),
        this.scrollObserver.observe(this.el),
        this.scrollObserver.onSeparate(() => {
            window.removeEventListener("scroll", this.handleScroll), window.removeEventListener("mousedown", this.handleMouseDown), window.removeEventListener("mouseup", this.handleMouseUp), window.removeEventListener("keydown", this.handleKeyDown), this.isIntersecting = !1, this.conf.playing && this.pause()
        }), 
        this.scrollObserver.onIntersect(() => {
            window.addEventListener("scroll", this.handleScroll), window.addEventListener("mousedown", this.handleMouseDown), window.addEventListener("mouseup", this.handleMouseUp), window.addEventListener("keydown", this.handleKeyDown), this.isIntersecting = !0, this.addIsLoadedClass(), this.play()
        })*/

      )
  }
  disconnect() {
      this.scrollObserver && (window.removeEventListener("scroll", this.handleScroll), window.removeEventListener("mousedown", this.handleMouseDown), window.removeEventListener("mouseup", this.handleMouseUp), window.removeEventListener("keydown", this.handleKeyDown), this.scrollObserver.disconnect()), window.removeEventListener("resize", this.resize), window.removeEventListener("keydown", this.handleKeyDown), window.removeEventListener("keyup", this.handleKeyUp)
  }
  initMaterial() {
      this.uniforms = {
          u_time: new this.minigl.Uniform({
              value: 0
          }),
// In initMaterial() unter u_line_speed hinzufügen:
u_mouse: new this.minigl.Uniform({ 
  type: "vec2", 
  value: [0, 0] 
}),
          u_shadow_power: new this.minigl.Uniform({
              value: 5
          }),
          u_darken_top: new this.minigl.Uniform({
              value: "" === this.el.dataset.jsDarkenTop ? 1 : 0
          }),
          u_line_mode: new this.minigl.Uniform({ value: this.lineMode || 0.0 }),
          u_line_count: new this.minigl.Uniform({ value: this.lineCount || 20.0 }),
          u_line_width: new this.minigl.Uniform({ value: this.lineWidth || 0.15 }),
          u_line_speed: new this.minigl.Uniform({ value: this.lineSpeed || 0.0 }),
          u_line_color: new this.minigl.Uniform({ value: this.lineColor || [0.8, 0.7, 0.4], type: "vec3" }),
          u_bg_color:   new this.minigl.Uniform({ value: this.bgColor   || [0.06, 0.06, 0.08], type: "vec3" }),
          u_wave_strength: new this.minigl.Uniform({ value: this.waveStrength || 1.0 }),
          u_slime_mode:   new this.minigl.Uniform({ value: this.slimeMode   || 0.0 }),
          u_car_x:        new this.minigl.Uniform({ value: 0.0 }),
          u_car_boost:    new this.minigl.Uniform({ value: 1.0 }),
          u_car_tilt:     new this.minigl.Uniform({ value: 0.0 }),
          u_car_active:   new this.minigl.Uniform({ value: 0.0 }),
          u_car_brake:    new this.minigl.Uniform({ value: 0.0 }),
          u_car_offset:   new this.minigl.Uniform({ value: 0.0 }),
          u_traffic_x:    new this.minigl.Uniform({ value: [0.0, 0.0, 0.0, 0.0], type: "vec4" }),
          u_traffic_z:    new this.minigl.Uniform({ value: [0.0, 0.0, 0.0, 0.0], type: "vec4" }),
          u_car_crash:    new this.minigl.Uniform({ value: 0.0 }),
          u_active_colors: new this.minigl.Uniform({
              value: this.activeColors,
              type: "vec4"
          }),
          u_global: new this.minigl.Uniform({
              value: {
                  noiseFreq: new this.minigl.Uniform({
                      value: [this.freqX, this.freqY],
                      type: "vec2"
                  }),
                  noiseSpeed: new this.minigl.Uniform({
                      value: 5e-6
                  })
              },
              type: "struct"
          }),
          u_vertDeform: new this.minigl.Uniform({
              value: {
                  incline: new this.minigl.Uniform({
                      value: Math.sin(this.angle) / Math.cos(this.angle)
                  }),
                  offsetTop: new this.minigl.Uniform({
                      value: -.5
                  }),
                  offsetBottom: new this.minigl.Uniform({
                      value: -.5
                  }),
                  noiseFreq: new this.minigl.Uniform({
                      value: [3, 4],
                      type: "vec2"
                  }),
                  noiseAmp: new this.minigl.Uniform({
                      value: this.amp
                  }),
                  noiseSpeed: new this.minigl.Uniform({
                      value: 10
                  }),
                  noiseFlow: new this.minigl.Uniform({
                      value: 3
                  }),
                  noiseSeed: new this.minigl.Uniform({
                      value: this.seed
                  })
              },
              type: "struct",
              excludeFrom: "fragment"
          }),
          u_baseColor: new this.minigl.Uniform({
              value: this.sectionColors[0],
              type: "vec3",
              excludeFrom: "fragment"
          }),
          u_waveLayers: new this.minigl.Uniform({
              value: [],
              excludeFrom: "fragment",
              type: "array"
          })
      };
      for (let e = 1; e < this.sectionColors.length; e += 1) this.uniforms.u_waveLayers.value.push(new this.minigl.Uniform({
          value: {
              color: new this.minigl.Uniform({
                  value: this.sectionColors[e],
                  type: "vec3"
              }),
              noiseFreq: new this.minigl.Uniform({
                  value: [2 + e / this.sectionColors.length, 3 + e / this.sectionColors.length],
                  type: "vec2"
              }),
              noiseSpeed: new this.minigl.Uniform({
                  value: 11 + .3 * e
              }),
              noiseFlow: new this.minigl.Uniform({
                  value: 6.5 + .3 * e
              }),
              noiseSeed: new this.minigl.Uniform({
                  value: this.seed + 10 * e
              }),
              noiseFloor: new this.minigl.Uniform({
                  value: .1
              }),
              noiseCeil: new this.minigl.Uniform({
                  value: .63 + .07 * e
              })
          },
          type: "struct"
      }));
      return this.vertexShader = [this.shaderFiles.noise, this.shaderFiles.blend, this.shaderFiles.vertex].join("\n\n"), new this.minigl.Material(this.vertexShader, this.shaderFiles.fragment, this.uniforms)
  }
  initMesh() {
      this.material = this.initMaterial(), this.geometry = new this.minigl.PlaneGeometry, this.mesh = new this.minigl.Mesh(this.geometry, this.material)
  }
  shouldSkipFrame(e) {
      if (!!window.document.hidden || !this.conf.playing) return true;
      this._fc = ((this._fc || 0) + 1) % (this.frameSkip || 2);
      return this._fc !== 0;
  }
  updateFrequency(e) {
      this.freqX += e, this.freqY += e
  }
  toggleColor(index) {
      this.activeColors[index] = 0 === this.activeColors[index] ? 1 : 0
  }
  showGradientLegend() {
      this.width > this.minWidth && (this.isGradientLegendVisible = !0, document.body.classList.add("isGradientLegendVisible"))
  }
  hideGradientLegend() {
      this.isGradientLegendVisible = !1, document.body.classList.remove("isGradientLegendVisible")
  }
  init() {
      this.initGradientColors(), this.initMesh(), this.resize(), requestAnimationFrame(this.animate), window.addEventListener("resize", this.resize)
  }
  /*
  * Waiting for the css variables to become available, usually on page load before we can continue.
  * Using default colors assigned below if no variables have been found after maxCssVarRetries
  */
  waitForCssVars() {
      if (this.computedCanvasStyle && -1 !== this.computedCanvasStyle.getPropertyValue("--gradient-color-1").indexOf("#")) this.init(), this.addIsLoadedClass();
      else {
          if (this.cssVarRetries += 1, this.cssVarRetries > this.maxCssVarRetries) {
              return this.sectionColors = [16711680, 16711680, 16711935, 65280, 255],void this.init();
          }
          requestAnimationFrame(() => this.waitForCssVars())
      }
  }
  /*
  * Initializes the four section colors by retrieving them from css variables.
  */
  initGradientColors() {
      this.sectionColors = ["--gradient-color-1", "--gradient-color-2", "--gradient-color-3", "--gradient-color-4"].map(cssPropertyName => {
          let hex = this.computedCanvasStyle.getPropertyValue(cssPropertyName).trim();
          //Check if shorthand hex value was used and double the length so the conversion in normalizeColor will work.
          if (4 === hex.length) {
              const hexTemp = hex.substr(1).split("").map(hexTemp => hexTemp + hexTemp).join("");
              hex = `#${hexTemp}`
          }
          return hex && `0x${hex.substr(1)}`
      }).filter(Boolean).map(normalizeColor)
  }
}