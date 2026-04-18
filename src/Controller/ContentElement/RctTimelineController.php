<?php

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_timeline', category: 'rct')]
class RctTimelineController extends AbstractContentElementController
{
    private const COLOR_MAP = [
        'accent'    => 'var(--rct-accent)',
        'primary'   => 'var(--rct-primary-light)',
        'dim'       => 'var(--rct-primary-dim)',
        'fixed'     => 'var(--rct-primary-fixed)',
        'secondary' => 'var(--rct-secondary-light)',
        'purple'    => 'var(--rct-secondary)',
        'orange'    => '#f59e0b',
        'red'       => '#ef4444',
    ];

    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $raw   = (string) $model->rct_timeline_data;
        $blocks = preg_split('/\n---+\s*\n/', trim($raw));
        $items  = [];
        $defaultColor = $model->rct_timeline_color ?: 'accent';

        foreach ($blocks as $i => $block) {
            $block = trim($block);
            if ($block === '') continue;

            $lines = explode("\n", $block);
            $first = trim(array_shift($lines));

            // Erste Zeile: Datum|Titel  oder  Datum|Titel|Farbe  oder  Datum|Titel|Farbe|Icon
            $parts = array_map('trim', explode('|', $first));
            $date  = $parts[0] ?? '';
            $title = $parts[1] ?? '';
            $color = $parts[2] ?? $defaultColor;
            $icon  = $parts[3] ?? '';

            // Restliche Zeilen = Body (Leerzeilen werden zu <br><br>)
            $bodyLines = array_map('trim', $lines);
            $body = implode("\n", $bodyLines);
            $body = nl2br(htmlspecialchars(trim($body), ENT_QUOTES, 'UTF-8'));

            $colorCss = self::COLOR_MAP[$color]
                ?? (preg_match('/^#[0-9a-fA-F]{3,6}$/', $color) ? $color : self::COLOR_MAP[$defaultColor]);

            $items[] = [
                'date'     => htmlspecialchars($date,  ENT_QUOTES, 'UTF-8'),
                'title'    => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'body'     => $body,
                'colorCss' => $colorCss,
                'icon'     => $icon,
                'index'    => $i,
                'side'     => ($i % 2 === 0) ? 'left' : 'right',
            ];
        }

        $template->items          = $items;
        $template->variant        = $model->rct_timeline_variant ?: 'alternate';
        $template->showLine       = (bool) ($model->rct_timeline_show_line ?? true);
        $template->timelineStyle  = $model->rct_timeline_style ?: 'dark';

        return $template->getResponse();
    }
}
