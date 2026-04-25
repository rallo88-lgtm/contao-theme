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

#[AsContentElement(type: 'rct_slider_box', category: 'rct', template: 'content_element/rct_slider_box')]
class RctSliderBoxController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $imgSrc = '';
        if ($model->rct_sb_image) {
            $file = FilesModel::findByUuid($model->rct_sb_image);
            if ($file !== null) {
                $imgSrc = '/' . $file->path;
            }
        }

        $template->imgSrc         = $imgSrc;
        $template->imgAlt         = htmlspecialchars((string) $model->rct_sb_image_alt, ENT_QUOTES, 'UTF-8');
        $template->bgPosition     = $model->rct_sb_bg_position ?: 'center';
        $template->overlayOpacity = $model->rct_sb_overlay !== '' ? (int) $model->rct_sb_overlay : 0;
        $template->minHeight      = $model->rct_sb_min_height ?: '500px';
        $template->overline       = htmlspecialchars((string) $model->rct_sb_overline, ENT_QUOTES, 'UTF-8');
        $template->headline       = htmlspecialchars((string) $model->rct_sb_headline, ENT_QUOTES, 'UTF-8');
        $template->text           = $model->rct_sb_text ? nl2br(htmlspecialchars((string) $model->rct_sb_text, ENT_QUOTES, 'UTF-8')) : '';
        $template->align          = $model->rct_sb_align ?: 'center';
        $template->contentColor   = trim((string) $model->rct_content_color);

        $template->linkUrl    = $this->resolveUrl((int) $model->rct_sb_link_page, (string) $model->rct_sb_link_url);
        $template->linkLabel  = htmlspecialchars((string) ($model->rct_sb_link_label ?: 'Mehr erfahren'), ENT_QUOTES, 'UTF-8');
        $template->linkTarget = $model->rct_sb_link_target ? '_blank' : '_self';
        $template->linkStyle  = $model->rct_sb_link_style ?: 'primary';

        $template->link2Url    = $this->resolveUrl((int) $model->rct_sb_link2_page, (string) $model->rct_sb_link2_url);
        $template->link2Label  = htmlspecialchars((string) $model->rct_sb_link2_label, ENT_QUOTES, 'UTF-8');
        $template->link2Target = $model->rct_sb_link2_target ? '_blank' : '_self';
        $template->link2Style  = $model->rct_sb_link2_style ?: 'outline';

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
