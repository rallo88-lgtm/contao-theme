<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_tabs', category: 'rct')]
class RctTabsController extends AbstractContentElementController
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
        $colorKey = $model->rct_tabs_color ?: 'accent';
        $colorCss = self::COLOR_MAP[$colorKey] ?? self::COLOR_MAP['accent'];

        $template->tabs      = $this->parseTabs((string) $model->rct_tabs_data);
        $template->colorCss  = $colorCss;
        $template->tabsStyle = $model->rct_tabs_style ?: 'dark';
        $cssId               = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId    = trim($cssId[0] ?? '', '"\'');
        $template->cssClass  = $cssId[1] ?? '';

        return $template->getResponse();
    }

    private function parseTabs(string $raw): array
    {
        $blocks = preg_split('/^---\s*$/m', $raw);
        $tabs   = [];

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));
            if (empty($lines)) {
                continue;
            }

            $title = htmlspecialchars(trim(array_shift($lines)), ENT_QUOTES, 'UTF-8');
            if ($title === '') {
                continue;
            }

            $content = nl2br(htmlspecialchars(trim(implode("\n", $lines)), ENT_QUOTES, 'UTF-8'));

            $tabs[] = [
                'title'   => $title,
                'content' => $content,
            ];
        }

        return $tabs;
    }
}
