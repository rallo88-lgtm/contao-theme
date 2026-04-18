<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\PageModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_icon_box', category: 'rct')]
class RctIconBoxController extends AbstractContentElementController
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

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $colorKey = $model->rct_icon_box_color ?: 'accent';
        $colorCss = self::COLOR_MAP[$colorKey] ?? self::COLOR_MAP['accent'];

        $linkUrl = $this->resolveUrl((int) $model->rct_icon_box_link_page, (string) $model->rct_icon_box_link_url);

        $template->icon      = (string) $model->rct_icon_box_icon;
        $template->headline  = htmlspecialchars((string) $model->rct_icon_box_headline, ENT_QUOTES, 'UTF-8');
        $template->text      = $model->rct_icon_box_text ? nl2br(htmlspecialchars((string) $model->rct_icon_box_text, ENT_QUOTES, 'UTF-8')) : '';
        $template->colorCss  = $colorCss;
        $template->align     = $model->rct_icon_box_align ?: 'centered';
        $template->boxStyle  = $model->rct_icon_box_style ?: 'dark';
        $template->linkUrl   = $linkUrl;
        $template->linkLabel = $linkUrl ? htmlspecialchars((string) ($model->rct_icon_box_link_label ?: 'Mehr erfahren'), ENT_QUOTES, 'UTF-8') : '';
        $template->linkTarget = $model->rct_icon_box_link_target ? '_blank' : '_self';
        $cssId               = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId    = trim($cssId[0] ?? '', '"\'');
        $template->cssClass  = $cssId[1] ?? '';

        return $template->getResponse();
    }

    private function resolveUrl(int $pageId, string $manualUrl): string
    {
        if ($pageId > 0) {
            $page = PageModel::findById($pageId);
            if ($page !== null) {
                return htmlspecialchars($page->getFrontendUrl(), ENT_QUOTES, 'UTF-8');
            }
        }

        return $manualUrl ? htmlspecialchars($manualUrl, ENT_QUOTES, 'UTF-8') : '';
    }
}
