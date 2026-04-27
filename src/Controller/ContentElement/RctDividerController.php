<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_divider', category: 'rct', template: 'content_element/rct_divider')]
class RctDividerController extends AbstractContentElementController
{
    private const VALID_VARIANTS = [
        'fade', 'ticks', 'labeled', 'section', 'coord', 'marker',
        'bracket', 'stepped', 'caption', 'aurora', 'ruler', 'counter', 'dots',
    ];

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $variant = (string) $model->rct_divider_variant ?: 'fade';
        if (!in_array($variant, self::VALID_VARIANTS, true)) {
            $variant = 'fade';
        }

        // Höhe — nur fade + aurora nutzen --rd-h
        $height = (int) $model->rct_divider_height;
        $bgStyle = ($height > 0 && in_array($variant, ['fade', 'aurora'], true))
            ? '--rd-h:' . $height . 'px;'
            : '';

        // Stepped: Segments (Total) + Progress (wieviele "on")
        $segments = (int) ($model->rct_divider_segments ?: 0);
        if ($segments < 2) {
            $segments = 6; // Default wenn leer / 0 / 1 (1-Segment-Stepper macht keinen Sinn)
        }
        $segments = min(20, $segments);
        $progress = max(0, min($segments, (int) $model->rct_divider_progress));
        $stepArr  = [];
        for ($i = 0; $i < $segments; $i++) {
            $stepArr[] = $i < $progress; // true = "on"
        }

        // Ruler: Maximum (Default 1200), 7 Schritte
        $rulerMax = max(1, (int) ($model->rct_divider_ruler_max ?: 1200));
        $rulerNums = [];
        for ($i = 0; $i <= 6; $i++) {
            $rulerNums[] = (int) round($rulerMax * $i / 6);
        }

        // Bracket-Defaults
        $start = (string) $model->rct_divider_start;
        $end   = (string) $model->rct_divider_end;
        if ($variant === 'bracket') {
            if ($start === '') $start = 'BEGIN';
            if ($end   === '') $end   = 'END';
        }

        $template->variant       = $variant;
        $template->bgStyle       = $bgStyle;
        $template->label         = htmlspecialchars((string) $model->rct_divider_label, ENT_QUOTES, 'UTF-8');
        $template->idx           = htmlspecialchars((string) $model->rct_divider_index, ENT_QUOTES, 'UTF-8');
        $template->total         = htmlspecialchars((string) $model->rct_divider_total, ENT_QUOTES, 'UTF-8');
        $template->segments      = $segments;
        $template->progress      = $progress;
        $template->stepArr       = $stepArr;
        $template->start         = htmlspecialchars($start, ENT_QUOTES, 'UTF-8');
        $template->end           = htmlspecialchars($end,   ENT_QUOTES, 'UTF-8');
        $template->status        = htmlspecialchars((string) $model->rct_divider_status, ENT_QUOTES, 'UTF-8');
        $template->statusDot     = (bool) $model->rct_divider_status_dot;
        $template->rulerNums     = $rulerNums;
        $template->icon          = (string) $model->rct_divider_icon;

        $cssId                   = StringUtil::deserialize($model->cssID, true);
        $template->htmlId        = trim($cssId[0] ?? '', '"\'');
        $template->cssClass      = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
