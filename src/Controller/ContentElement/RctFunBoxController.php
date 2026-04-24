<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_fun_box', category: 'rct', template: 'content_element/rct_fun_box')]
class RctFunBoxController extends AbstractContentElementController
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
        $imgSrc = '';
        if ($model->rct_fb_image) {
            $file = FilesModel::findByUuid($model->rct_fb_image);
            if ($file !== null) {
                $imgSrc = '/' . $file->path;
            }
        }

        $template->imgSrc     = $imgSrc;
        $template->imgAlt     = htmlspecialchars((string) $model->rct_fb_image_alt, ENT_QUOTES, 'UTF-8');
        $template->icon       = trim((string) $model->rct_fb_icon);
        $template->headline   = htmlspecialchars((string) $model->rct_fb_headline, ENT_QUOTES, 'UTF-8');
        $template->text       = $model->rct_fb_text ? nl2br(htmlspecialchars((string) $model->rct_fb_text, ENT_QUOTES, 'UTF-8')) : '';
        $template->colorKey   = $model->rct_fb_color ?: 'accent';
        $template->colorCss   = self::COLOR_MAP[$template->colorKey] ?? self::COLOR_MAP['accent'];

        // Textfarbe aus DCA (optional)
        $raw = trim((string) $model->rct_content_color);
        if ($raw !== '') {
            $textColor = str_starts_with($raw, 'var(') ? $raw : '#' . ltrim($raw, '#');
        } else {
            $textColor = '';
        }
        $template->textColor = $textColor;

        $linkUrl = $this->resolveUrl((int) $model->rct_fb_link_page, (string) $model->rct_fb_link_url);
        $template->linkUrl    = $linkUrl;
        $template->linkLabel  = htmlspecialchars((string) ($model->rct_fb_link_label ?: 'Mehr erfahren'), ENT_QUOTES, 'UTF-8');
        $template->linkTarget = $model->rct_fb_link_target ? '_blank' : '_self';

        $cssId              = StringUtil::deserialize($model->cssID, true);
        $template->htmlId   = trim($cssId[0] ?? '', '"\'');
        $template->cssClass = $cssId[1] ?? '';

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
