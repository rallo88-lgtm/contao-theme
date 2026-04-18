<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_stat_box', category: 'rct')]
class RctStatBoxController extends AbstractContentElementController
{
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $raw = trim((string) $model->rct_stat_value);

        // Zahl parsen: Komma → Punkt, Tausenderpunkte entfernen
        $normalized = str_replace(['.', ','], ['', '.'], $raw);
        if (substr_count($normalized, '.') > 1) {
            $normalized = str_replace('.', '', $raw);
        }
        $isFloat   = str_contains($normalized, '.');
        $numValue  = $isFloat ? (float) $normalized : (int) $normalized;
        $decimals  = $isFloat ? strlen(substr(strrchr($normalized, '.'), 1)) : 0;

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

        $colorKey = $model->rct_stat_color ?: 'accent';
        $colorCss = $colorMap[$colorKey] ?? $colorMap['accent'];

        $template->numValue  = $numValue;
        $template->decimals  = $decimals;
        $template->unit      = htmlspecialchars((string) $model->rct_stat_unit,   ENT_QUOTES, 'UTF-8');
        $template->prefix    = htmlspecialchars((string) $model->rct_stat_prefix, ENT_QUOTES, 'UTF-8');
        $template->label     = htmlspecialchars((string) $model->rct_stat_label,  ENT_QUOTES, 'UTF-8');
        $template->subLabel  = htmlspecialchars((string) $model->rct_stat_sublabel, ENT_QUOTES, 'UTF-8');
        $template->icon      = (string) $model->rct_stat_icon;
        $template->colorCss  = $colorCss;
        $template->size      = $model->rct_stat_size ?: 'md';

        return $template->getResponse();
    }
}
