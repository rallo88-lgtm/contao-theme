<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_emitter', category: 'rct', template: 'content_element/rct_emitter')]
class RctEmitterController extends AbstractContentElementController
{
    private const PRESETS = [
        'snow' => [
            'particleColor'             => ['#ffffff', '#e0f2fe', '#bae6fd'],
            'particleShape'             => ['❄', '❅', '❆'],
            'particleDirection'         => 'down',
            'particleSpeed'             => 6,
            'particleRotation'          => true,
            'particleRotationSpeed'     => 8,
            'natuerlichesFallverhalten' => true,
            'fadeout'                   => true,
            'minSize'                   => 10,
            'maxSize'                   => 22,
            'newOn'                     => 250,
            'poolSize'                  => 70,
        ],
        'leaves' => [
            'particleColor'             => ['#FF8C00', '#CD853F', '#8B4513', '#A0522D', '#daa520'],
            'particleShape'             => ['🍂', '🍁', '🍃', '🌿'],
            'particleDirection'         => 'down',
            'particleSpeed'             => 10,
            'particleRotation'          => true,
            'particleRotationSpeed'     => 4,
            'natuerlichesFallverhalten' => true,
            'fadeout'                   => true,
            'minSize'                   => 20,
            'maxSize'                   => 40,
            'newOn'                     => 400,
            'poolSize'                  => 50,
        ],
        'petals' => [
            'particleColor'             => ['#fbcfe8', '#f9a8d4', '#f472b6', '#ec4899'],
            'particleShape'             => ['🌸', '🌺', '✿'],
            'particleDirection'         => 'down',
            'particleSpeed'             => 5,
            'particleRotation'          => true,
            'particleRotationSpeed'     => 5,
            'natuerlichesFallverhalten' => true,
            'fadeout'                   => true,
            'minSize'                   => 16,
            'maxSize'                   => 28,
            'newOn'                     => 500,
            'poolSize'                  => 40,
        ],
        'confetti' => [
            'particleColor'             => ['#ef4444', '#f59e0b', '#22c55e', '#3b82f6', '#a855f7', '#ec4899'],
            'particleShape'             => ['■', '●', '▲', '◆'],
            'particleDirection'         => 'down',
            'particleSpeed'             => 14,
            'particleRotation'          => true,
            'particleRotationSpeed'     => 2,
            'natuerlichesFallverhalten' => true,
            'fadeout'                   => false,
            'minSize'                   => 8,
            'maxSize'                   => 14,
            'newOn'                     => 150,
            'poolSize'                  => 100,
        ],
        'sparks' => [
            'particleColor'             => ['#fbbf24', '#fde047', '#fff8b6'],
            'particleShape'             => ['✨', '⭐', '·'],
            'particleDirection'         => 'up',
            'particleSpeed'             => 18,
            'particleRotation'          => true,
            'particleRotationSpeed'     => 1,
            'natuerlichesFallverhalten' => false,
            'fadeout'                   => true,
            'minSize'                   => 8,
            'maxSize'                   => 18,
            'newOn'                     => 200,
            'poolSize'                  => 60,
        ],
        'hearts' => [
            'particleColor'             => ['#ef4444', '#f43f5e', '#ec4899'],
            'particleShape'             => ['❤', '💕', '💖'],
            'particleDirection'         => 'up',
            'particleSpeed'             => 6,
            'particleRotation'          => false,
            'particleRotationSpeed'     => 0,
            'natuerlichesFallverhalten' => true,
            'fadeout'                   => true,
            'minSize'                   => 14,
            'maxSize'                   => 28,
            'newOn'                     => 400,
            'poolSize'                  => 40,
        ],
        'bubbles' => [
            'particleColor'             => ['rgba(255,255,255,0.6)', 'rgba(180,220,255,0.5)'],
            'particleShape'             => ['◯', '○', '°'],
            'particleDirection'         => 'up',
            'particleSpeed'             => 4,
            'particleRotation'          => false,
            'particleRotationSpeed'     => 0,
            'natuerlichesFallverhalten' => true,
            'fadeout'                   => true,
            'minSize'                   => 10,
            'maxSize'                   => 30,
            'newOn'                     => 350,
            'poolSize'                  => 35,
        ],
    ];

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $preset = (string) ($model->rct_emitter_preset ?: 'snow');
        $base   = self::PRESETS[$preset] ?? self::PRESETS['snow'];

        if ($preset === 'custom') {
            $base = self::PRESETS['snow']; // Fallback-Defaults für nicht gesetzte Felder
            $base = array_merge($base, $this->customOverrides($model));
        } else {
            // Erlaubt selektives Overriding einzelner Felder auch wenn Preset gewählt
            $base = array_merge($base, $this->customOverrides($model, true));
        }

        $template->emitterId      = 'rct-emitter-' . (int) $model->id;
        $template->emitterTarget  = trim((string) $model->rct_emitter_target);
        $template->emitterOptions = json_encode($base, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // Lazy-Load der Emitter-Lib — string-key dedupliziert über mehrere CEs auf einer Seite
        $GLOBALS['TL_BODY']['rct-emitter-lib'] = '<script src="bundles/rct/js/rallos-emitter.js" defer></script>';

        $cssId               = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId    = trim($cssId[0] ?? '', '"\'');
        $template->cssClass  = $cssId[1] ?? '';

        return $template->getResponse();
    }

    /**
     * Liest Custom-Overrides aus dem Model. Mit $onlyIfSet=true werden leere
     * Werte ignoriert (für Preset-Modus, wo nur explizit gefüllte Felder
     * Vorrang haben sollen).
     */
    private function customOverrides(ContentModel $model, bool $onlyIfSet = false): array
    {
        $out = [];

        $shapes = trim((string) $model->rct_emitter_shapes);
        if ($shapes !== '') {
            $out['particleShape'] = array_values(array_filter(array_map('trim', explode(',', $shapes)), 'strlen'));
        } elseif (!$onlyIfSet) {
            // Custom-Modus mit leerem Feld → Default belassen
        }

        $colors = trim((string) $model->rct_emitter_colors);
        if ($colors !== '') {
            $out['particleColor'] = array_values(array_filter(array_map('trim', explode(',', $colors)), 'strlen'));
        }

        $direction = (string) $model->rct_emitter_direction;
        if (in_array($direction, ['down', 'up', 'left', 'right'], true)) {
            $out['particleDirection'] = $direction;
        }

        // Numerische Felder — nur übernehmen wenn explizit gesetzt
        $numericMap = [
            'rct_emitter_min_size'        => ['minSize', 1, 200],
            'rct_emitter_max_size'        => ['maxSize', 1, 400],
            'rct_emitter_speed'           => ['particleSpeed', 1, 100],
            'rct_emitter_rotation_speed'  => ['particleRotationSpeed', 0.1, 30],
            'rct_emitter_new_on'          => ['newOn', 50, 5000],
            'rct_emitter_pool_size'       => ['poolSize', 5, 300],
        ];
        foreach ($numericMap as $field => [$key, $min, $max]) {
            $val = $model->{$field};
            if ($val === null || (string) $val === '') {
                continue;
            }
            $out[$key] = max($min, min($max, (float) $val));
        }

        // Booleans — checkboxen mappen direkt
        if ($model->rct_emitter_rotation !== null && $model->rct_emitter_rotation !== '') {
            $out['particleRotation'] = (bool) $model->rct_emitter_rotation;
        }
        if ($model->rct_emitter_natural_fall !== null && $model->rct_emitter_natural_fall !== '') {
            $out['natuerlichesFallverhalten'] = (bool) $model->rct_emitter_natural_fall;
        }
        if ($model->rct_emitter_natural_start !== null && $model->rct_emitter_natural_start !== '') {
            $out['natuerlichesStartverhalten'] = (bool) $model->rct_emitter_natural_start;
        }
        if ($model->rct_emitter_fadeout !== null && $model->rct_emitter_fadeout !== '') {
            $out['fadeout'] = (bool) $model->rct_emitter_fadeout;
        }

        return $out;
    }
}
