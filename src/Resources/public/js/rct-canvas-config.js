/**
 * RCT Canvas Config
 * Alle Background-Animations-Presets pro Theme.
 *
 * Farben: Hex-Strings "#RRGGBB" oder "rgb(r,g,b)"
 * Speed:  1.0 = normale Geschwindigkeit (intern normalisiert)
 *
 * vertexShader_*     → Aurora/Blob Vertex-Shader Parameter
 * fragmentShader_*   → Fragment-Shader Effekt Parameter
 * shaderMode         → 0=Aurora Blobs  | 1=Isolinien    | 2=Linien+Aurora
 *                      3=Linien flat   | 4=Strahlen     | 5=Lemniskate+Maus
 *                      6=Honig-Tropfen | 7=Rosenblätter (Rhodonea+Fibonacci)
 *                      8=Claudy Sky    (FBM-Wolken, honoris causa)
 *                      10=Magnetfeld   (Dipol-Feldlinien + Lemniskaten-Pole)
 *                      11=Neon Grid    (Synthwave Perspektivgitter, Maus-reaktiv)
 * fragmentWithVertex → true=Aurora im Hintergrund | false=flacher BG
 */
const RCT_CANVAS_CONFIG = {

  default: {
    vertexShader_amp:          320,
    vertexShader_seed:         5,
    vertexShader_freqX:        14e-5,
    vertexShader_freqY:        29e-5,
    vertexShader_freqDelta:    1e-5,
    vertexShader_timeSpeed:    1.0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#0F0F14',
    fragmentWithVertex:        true,
    shaderMode:                0,
  },

  lime: {
    vertexShader_amp:          280,
    vertexShader_seed:         3,
    vertexShader_freqX:        12e-5,
    vertexShader_freqY:        24e-5,
    vertexShader_freqDelta:    1e-5,
    vertexShader_timeSpeed:    0.8,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#0F1408',
    fragmentWithVertex:        true,
    shaderMode:                0,
  },

  purple: {
    vertexShader_amp:          350,
    vertexShader_seed:         8,
    vertexShader_freqX:        16e-5,
    vertexShader_freqY:        32e-5,
    vertexShader_freqDelta:    2e-5,
    vertexShader_timeSpeed:    0.7,
    vertexShader_density:      [0.06, 0.18],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#0A0612',
    fragmentWithVertex:        true,
    shaderMode:                4,
    fragmentShader_lineCount:  16,
    fragmentShader_lineWidth:  0.125,
    fragmentShader_lineSpeed:  0.7,
    fragmentShader_lineColor:  '#A832F8',
  },

  'dark-cherry': {
    vertexShader_amp:          260,
    vertexShader_seed:         12,
    vertexShader_freqX:        10e-5,
    vertexShader_freqY:        20e-5,
    vertexShader_freqDelta:    0.5e-5,
    vertexShader_timeSpeed:    0.9,
    vertexShader_density:      [0.05, 0.14],
    vertexShader_activeColors: [1, 1, 1, 0],
    vertexShader_bgColor:      '#0F0305',
    fragmentWithVertex:        true,
    shaderMode:                2,
    fragmentShader_lineCount:  30,
    fragmentShader_lineWidth:  1.5,
    fragmentShader_lineSpeed:  0.6,
    fragmentShader_lineColor:  '#CC1A33',
  },

  'honey-moon': {
    bgImage:               '../img/honeycombs.jpg',
    vertexShader_amp:          0,
    vertexShader_seed:         7,
    vertexShader_freqX:        11e-5,
    vertexShader_freqY:        22e-5,
    vertexShader_freqDelta:    1e-5,
    vertexShader_timeSpeed:    0.0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#080401',
    fragmentWithVertex:        false,
    shaderMode:                6,
    fragmentShader_lineCount:  12,
    fragmentShader_lineWidth:  4.0,
    fragmentShader_lineSpeed:  0.025,
    fragmentShader_lineColor:  '#F2AE0D',
    fragmentShader_waveStrength: 0.2,
  },

  'candy-chaos': {
    vertexShader_amp:          420,
    vertexShader_seed:         2,
    vertexShader_freqX:        20e-5,
    vertexShader_freqY:        38e-5,
    vertexShader_freqDelta:    3e-5,
    vertexShader_timeSpeed:    3.0,
    vertexShader_density:      [0.08, 0.20],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#0F0F14',
    fragmentWithVertex:        true,
    shaderMode:                0,
    fragmentShader_lineCount:  6,
    fragmentShader_lineWidth:  0.4,
    fragmentShader_lineSpeed:  0.0,
    fragmentShader_lineColor:  '#CCBB66',
  },

  sparta: {
    vertexShader_amp:          0,
    vertexShader_seed:         0,
    vertexShader_freqX:        0,
    vertexShader_freqY:        0,
    vertexShader_freqDelta:    0,
    vertexShader_timeSpeed:    0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#0A0A0A',
    fragmentWithVertex:        false,
    shaderMode:                0,
    neuralLogo:                false,
  },

  'sparta2': {
    vertexShader_amp:          0,
    vertexShader_seed:         0,
    vertexShader_freqX:        0,
    vertexShader_freqY:        0,
    vertexShader_freqDelta:    0,
    vertexShader_timeSpeed:    0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#ffffff',
    fragmentWithVertex:        false,
    shaderMode:                0,
    neuralLogo:                false,
  },


  'dark-cherry-bloom': {
    vertexShader_amp:          160,
    vertexShader_seed:         12,
    vertexShader_freqX:        8e-5,
    vertexShader_freqY:        16e-5,
    vertexShader_freqDelta:    0.3e-5,
    vertexShader_timeSpeed:    0.4,
    vertexShader_density:      [0.03, 0.10],
    vertexShader_activeColors: [1, 1, 0, 0],
    vertexShader_bgColor:      '#060001',
    fragmentWithVertex:        true,
    shaderMode:                7,
    fragmentShader_lineCount:  5,      // Fibonacci: 5 Blätter — Baccara-Rose
    fragmentShader_lineWidth:  1.0,    // Größe: ax=0.78, ay=0.43 → 88% Breite, kein Clip
    fragmentShader_lineSpeed:  0.125,
    fragmentShader_lineColor:  '#8B0020', // Baccara: fast schwarz-rot, samtig
    frameSkip:                 3,         // 20fps — Rose dreht zu langsam für 30fps-Overhead
  },

  'claudy-sky': {
    vertexShader_amp:          0,
    vertexShader_seed:         0,
    vertexShader_freqX:        0,
    vertexShader_freqY:        0,
    vertexShader_freqDelta:    0,
    vertexShader_timeSpeed:    0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#1a3870',
    fragmentWithVertex:        false,
    shaderMode:                8,
    fragmentShader_lineCount:  2,
    fragmentShader_lineWidth:  1.0,
    fragmentShader_lineSpeed:  0.2,
    fragmentShader_lineColor:  '#87CEEB',
    frameSkip:                 2,   // 15fps — Wolken brauchen kein 30fps
    mouseParallax:             true,
  },

  'glass-tank': {
    vertexShader_amp:          0,
    vertexShader_seed:         0,
    vertexShader_freqX:        0,
    vertexShader_freqY:        0,
    vertexShader_freqDelta:    0,
    vertexShader_timeSpeed:    0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#08090a',
    fragmentWithVertex:        false,
    shaderMode:                9,
    fragmentShader_lineCount:  20,
    fragmentShader_lineWidth:  1.0,
    fragmentShader_lineSpeed:  1.0,
    fragmentShader_lineColor:  '#aab4be',
    frameSkip:                 2,
  },

  'neon-grid': {
    vertexShader_amp:          0,
    vertexShader_seed:         0,
    vertexShader_freqX:        0,
    vertexShader_freqY:        0,
    vertexShader_freqDelta:    0,
    vertexShader_timeSpeed:    0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#07020f',
    fragmentWithVertex:        false,
    shaderMode:                11,
    fragmentShader_lineCount:  1,      // reserviert
    fragmentShader_lineWidth:  1.0,    // Liniendicke (>1 = dicker)
    fragmentShader_lineSpeed:  1.25,   // Scroll-Geschwindigkeit
    fragmentShader_lineColor:  '#ff2d78', // Hot Pink → Querlinien; Tiefenlinien = Komplement
    frameSkip:                 1,      // 30fps — pure Mathe
    mouseParallax:             true,
  },

  'magnetic-field': {
    vertexShader_amp:          0,
    vertexShader_seed:         0,
    vertexShader_freqX:        0,
    vertexShader_freqY:        0,
    vertexShader_freqDelta:    0,
    vertexShader_timeSpeed:    0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#03040a',
    fragmentWithVertex:        false,
    shaderMode:                10,
    fragmentShader_lineCount:  14,
    fragmentShader_lineWidth:  1.0,
    fragmentShader_lineSpeed:  0.18,
    fragmentShader_lineColor:  '#27c4f4',
    frameSkip:                 1,         // 30fps — reine Mathe, kein Loop
    mouseParallax:             true,
  },

  'baker-street': {
    bgImage:                   '../img/baker-street.webp',
    vertexShader_amp:          0,
    vertexShader_seed:         0,
    vertexShader_freqX:        0,
    vertexShader_freqY:        0,
    vertexShader_freqDelta:    0,
    vertexShader_timeSpeed:    0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#0a080c',
    fragmentWithVertex:        false,
    shaderMode:                0,
  },

  'toxic-green': {
    vertexShader_amp:          0,
    vertexShader_seed:         0,
    vertexShader_freqX:        0,
    vertexShader_freqY:        0,
    vertexShader_freqDelta:    0,
    vertexShader_timeSpeed:    0,
    vertexShader_density:      [0.06, 0.16],
    vertexShader_activeColors: [1, 1, 1, 1],
    vertexShader_bgColor:      '#000000',
    bgImage:                   '../img/theme-bg-toxic-green.webp',
    fragmentWithVertex:        false,
    shaderMode:                6,
    fragmentShader_lineCount:  11,
    fragmentShader_lineWidth:  1.95,
    fragmentShader_lineSpeed:  0.035,
    fragmentShader_lineColor:  '#22FF44',
    fragmentShader_waveStrength: 0.6,
    fragmentShader_slimeMode:  1.0,
  },

};