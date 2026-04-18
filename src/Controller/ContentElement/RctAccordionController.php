<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_accordion', category: 'rct', template: 'content_element/rct_accordion')]
class RctAccordionController extends AbstractContentElementController
{
    private const COLOR_MAP = [
        'accent'    => 'var(--rct-accent)',
        'primary'   => 'var(--rct-primary-light)',
        'dim'       => 'var(--rct-primary-dim)',
        'secondary' => 'var(--rct-secondary-light)',
        'purple'    => 'var(--rct-secondary)',
        'orange'    => '#f59e0b',
        'red'       => '#ef4444',
        'white'     => '#ffffff',
    ];

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $raw    = (string) $model->rct_accordion_data;
        $blocks = preg_split('/\n---+\s*\n/', trim($raw));
        $items  = [];

        foreach ($blocks as $i => $block) {
            $block = trim($block);
            if ($block === '') continue;

            $lines = explode("\n", $block);
            $title = htmlspecialchars(trim(array_shift($lines)), ENT_QUOTES, 'UTF-8');
            $body  = nl2br(htmlspecialchars(trim(implode("\n", $lines)), ENT_QUOTES, 'UTF-8'));

            $items[] = [
                'title' => $title,
                'body'  => $body,
                'index' => $i,
                'open'  => ($i === 0 && (bool) $model->rct_accordion_first_open),
            ];
        }

        $colorKey = $model->rct_accordion_color ?: 'accent';
        $colorCss = self::COLOR_MAP[$colorKey]
            ?? (preg_match('/^#[0-9a-fA-F]{3,6}$/', $colorKey) ? $colorKey : self::COLOR_MAP['accent']);

        $template->items          = $items;
        $template->accordionStyle = $model->rct_accordion_style ?: 'dark';
        $template->colorCss       = $colorCss;

        return $template->getResponse();
    }
}
