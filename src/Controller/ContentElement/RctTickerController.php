<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RCT Laufband (Ralph 14.11.2026): Begriffe laufen endlos durchs Bild – reines CSS, kein JS.
 * Ein Begriff pro Zeile im Backend; Tempo, Richtung, Trenner, Höhe, Schrift, Farben, Rahmen, weiche Kanten.
 */
#[AsContentElement(type: 'rct_ticker', category: 'rct', template: 'content_element/rct_ticker')]
class RctTickerController extends AbstractContentElementController
{
    private const SPEED = ['slow' => 6, 'medium' => 3.5, 'fast' => 2];   // Sekunden pro Begriff (Laufzeit wächst mit der Liste, Tempo bleibt gleich)
    private const SEPARATORS = ['dot' => '·', 'bullet' => '•', 'bar' => '|', 'star' => '✦', 'none' => ''];

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $items = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $model->rct_ticker_items) ?: []), 'strlen'));

        $speed  = self::SPEED[$model->rct_ticker_speed ?: 'medium'] ?? self::SPEED['medium'];
        $height = max(24, min(400, (int) ($model->rct_ticker_height ?: 64)));
        $size   = max(10, min(120, (int) ($model->rct_ticker_size ?: 22)));

        $style  = '--rct-ticker-h:' . $height . 'px;--rct-ticker-size:' . $size . 'px;--rct-ticker-dur:' . max(8, round(\count($items) * $speed, 1)) . 's;';
        $color  = $this->hex($model->rct_ticker_color);
        if ($color) {
            $style .= '--rct-ticker-color:' . $color . ';';
        }
        $bg = $this->hex($model->rct_ticker_bg);
        if ($bg) {
            $a      = (string) $model->rct_ticker_bg_alpha;
            $alpha  = $a === '' ? 100 : max(0, min(100, (int) $a));
            $style .= '--rct-ticker-bg:' . $this->rgba($bg, $alpha / 100) . ';';
        }
        $border = $this->hex($model->rct_ticker_border);
        if ($border) {
            $style .= '--rct-ticker-border:' . $border . ';';
        }
        $font = trim((string) $model->rct_ticker_font);
        if ($font !== '' && preg_match('/^[\w\s\-]+$/u', $font)) {
            $style .= "--rct-ticker-font:'" . $font . "';";
        }

        $cssId = StringUtil::deserialize($model->cssID, true);

        $template->items     = array_map(static fn ($t) => htmlspecialchars($t, ENT_QUOTES, 'UTF-8'), $items);
        $template->separator = self::SEPARATORS[$model->rct_ticker_separator ?: 'dot'] ?? '·';
        $template->reverse   = $model->rct_ticker_direction === 'right';
        $template->pause     = $model->rct_ticker_pause === null || $model->rct_ticker_pause === '' ? true : (bool) $model->rct_ticker_pause;
        $template->fade      = (bool) $model->rct_ticker_fade;
        $template->styleCss  = $style;
        $template->htmlId    = trim($cssId[0] ?? '', '"\'');
        $template->cssClass  = $cssId[1] ?? '';

        return $template->getResponse();
    }

    private function hex(?string $value): string
    {
        $v = ltrim(trim((string) $value), '#');

        return preg_match('/^([0-9a-f]{3}|[0-9a-f]{6})$/i', $v) ? '#' . $v : '';
    }

    private function rgba(string $hex, float $alpha): string
    {
        $h = ltrim($hex, '#');
        if (\strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }

        return sprintf('rgba(%d,%d,%d,%s)', hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)), round($alpha, 2));
    }
}
