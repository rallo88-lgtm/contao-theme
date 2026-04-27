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

#[AsContentElement(type: 'rct_productbox', category: 'rct', template: 'content_element/rct_productbox')]
class RctProductboxController extends AbstractContentElementController
{
    private const COLOR_MAP = [
        'accent'    => 'var(--rct-accent)',
        'primary'   => 'var(--rct-primary-light)',
        'secondary' => 'var(--rct-secondary-light)',
        'orange'    => '#f59e0b',
        'red'       => '#ef4444',
        'green'     => '#22c55e',
        'purple'    => 'var(--rct-secondary)',
    ];

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $colorKey = $model->rct_productbox_color ?: 'accent';
        $colorCss = self::COLOR_MAP[$colorKey] ?? self::COLOR_MAP['accent'];

        // Bild
        $imgSrc = '';
        if ($model->rct_productbox_image) {
            $file = FilesModel::findByUuid($model->rct_productbox_image);
            if ($file !== null) {
                $imgSrc = '/' . $file->path;
            }
        }

        // Button (optional)
        $btn    = [];
        $btnUrl = $this->resolveUrl((int) $model->rct_productbox_btn_page, (string) $model->rct_productbox_btn_url);
        if ($model->rct_productbox_btn_label && $btnUrl) {
            $btn = [
                'label'  => htmlspecialchars((string) $model->rct_productbox_btn_label, ENT_QUOTES, 'UTF-8'),
                'url'    => $btnUrl,
                'style'  => $model->rct_productbox_btn_style ?: 'primary',
                'target' => $model->rct_productbox_btn_target ? '_blank' : '_self',
            ];
        }

        $template->bannerText  = htmlspecialchars((string) $model->rct_productbox_banner, ENT_QUOTES, 'UTF-8');
        $template->colorCss    = $colorCss;
        $template->imgSrc      = $imgSrc;
        $template->imgAlt      = htmlspecialchars((string) $model->rct_productbox_image_alt, ENT_QUOTES, 'UTF-8');
        $template->headline    = htmlspecialchars((string) $model->rct_productbox_headline, ENT_QUOTES, 'UTF-8');
        $template->subheadline = htmlspecialchars((string) $model->rct_productbox_subheadline, ENT_QUOTES, 'UTF-8');
        $template->text        = $model->rct_productbox_text ? nl2br(htmlspecialchars((string) $model->rct_productbox_text, ENT_QUOTES, 'UTF-8')) : '';
        $template->priceExtra  = htmlspecialchars((string) $model->rct_productbox_price_extra, ENT_QUOTES, 'UTF-8');
        $template->price       = htmlspecialchars((string) $model->rct_productbox_price, ENT_QUOTES, 'UTF-8');
        $template->priceNote   = htmlspecialchars((string) $model->rct_productbox_price_note, ENT_QUOTES, 'UTF-8');
        $template->boxStyle    = $model->rct_productbox_style ?: 'light';
        $template->btn         = $btn;

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
