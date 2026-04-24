<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_chart_bars', category: 'rct', template: 'content_element/rct_chart_bars')]
class RctChartBarsController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $raw   = (string) $model->rct_chart_bars_data;
        $lines = array_filter(array_map('trim', explode("\n", $raw)));
        $bars  = [];
        $i     = 0;

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#') || trim($line) === '') {
                continue;
            }

            if (str_contains($line, '|')) {
                // Format: Label|Wert  oder  Label|Wert|Farbe
                $parts = array_map('trim', explode('|', $line));
                $label = $parts[0];
                $value = min(100, max(0, (int) ($parts[1] ?? 0)));
                $color = $parts[2] ?? '';
            } else {
                // Nur eine Zahl → kein Label, Wert = die Zahl
                $label = '';
                $value = min(100, max(0, (int) trim($line)));
                $color = '';
            }

            // Farbe: benannter Key oder Hex → CSS-Variable oder direkter Wert
            $colorMap = [
                'accent'    => 'var(--rct-accent)',
                'primary'   => 'var(--rct-primary-light)',
                'dim'       => 'var(--rct-primary-dim)',
                'fixed'     => 'var(--rct-primary-fixed)',
                'secondary' => 'var(--rct-secondary-light)',
                'orange'    => '#f59e0b',
                'red'       => '#ef4444',
                'purple'    => 'var(--rct-secondary)',
            ];
            $colorCss = '';
            if ($color !== '') {
                $colorCss = $colorMap[$color] ?? (preg_match('/^#[0-9a-fA-F]{3,6}$/', $color) ? $color : '');
            }

            $bars[] = [
                'label'    => htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
                'value'    => $value,
                'index'    => $i,
                'delay'    => round($i * 0.12, 2),
                'colorCss' => $colorCss,
            ];
            $i++;
        }

        $raw = trim((string) $model->rct_content_color);
        if ($raw !== '') {
            $textColor = str_starts_with($raw, 'var(') ? $raw : '#' . ltrim($raw, '#');
        } else {
            $textColor = '';
        }

        $orientation = $model->rct_chart_orientation ?: 'vertical';

        // Pie: ein Kreis mit Wedges, Wert = direkter Prozentwert (0-100)
        if ($orientation === 'pie') {
            $radius = 25;
            $circ = 2 * M_PI * $radius; // ≈ 157.08
            $cumulative = 0;
            foreach ($bars as &$b) {
                $frac = min(1, $b['value'] / 100);
                $segLen = $frac * $circ;
                $b['pieSegLen']   = round($segLen, 3);
                $b['pieSegGap']   = round($circ - $segLen, 3);
                $b['pieRotation'] = round(-90 + ($cumulative / $circ) * 360, 3);
                $cumulative      += $segLen;
                if ($cumulative > $circ) $cumulative = $circ;
            }
            unset($b);
        }

        // Donut: eine Donut pro Reihe, jeder Wert als 0-100% Progress-Ring
        if ($orientation === 'donut') {
            $radius = 40;
            $circ = 2 * M_PI * $radius; // ≈ 251.33
            foreach ($bars as &$b) {
                $frac = $b['value'] / 100;
                $segLen = $frac * $circ;
                $b['pieSegLen'] = round($segLen, 3);
                $b['pieSegGap'] = round($circ - $segLen, 3);
            }
            unset($b);
        }

        $template->bars        = $bars;
        $template->orientation = $orientation;
        $template->color       = $model->rct_chart_color       ?: 'accent';
        $template->showValues  = (bool) $model->rct_chart_show_values;
        $template->textColor   = $textColor;
        $cssId                 = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId      = trim($cssId[0] ?? '', '"\'');
        $template->cssClass    = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
