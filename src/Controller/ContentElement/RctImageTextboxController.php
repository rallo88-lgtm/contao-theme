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

#[AsContentElement(type: 'rct_image_textbox', category: 'rct', template: 'content_element/rct_image_textbox')]
class RctImageTextboxController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // Bild
        $imgSrc = '';
        if ($model->rct_itb_image) {
            $file = FilesModel::findByUuid($model->rct_itb_image);
            if ($file !== null) {
                $imgSrc = '/' . $file->path;
            }
        }

        $template->imgSrc     = $imgSrc;
        $template->imgAlt     = htmlspecialchars((string) $model->rct_itb_image_alt, ENT_QUOTES, 'UTF-8');
        $template->headline   = htmlspecialchars((string) $model->rct_itb_headline, ENT_QUOTES, 'UTF-8');
        $template->text       = $model->rct_itb_text ? nl2br(htmlspecialchars((string) $model->rct_itb_text, ENT_QUOTES, 'UTF-8')) : '';
        $template->boxStyle   = $model->rct_itb_style ?: 'light';

        // Layout-Direction: top (Default, Bild oben) | left | right
        $layout = $model->rct_itb_layout ?: 'top';
        $template->boxLayout = in_array($layout, ['top', 'left', 'right'], true) ? $layout : 'top';

        $linkUrl = $this->resolveUrl((int) $model->rct_itb_link_page, (string) $model->rct_itb_link_url);
        $template->linkUrl    = $linkUrl;
        $template->linkLabel  = htmlspecialchars((string) ($model->rct_itb_link_label ?: 'Mehr erfahren'), ENT_QUOTES, 'UTF-8');
        $template->linkTarget = $model->rct_itb_link_target ? '_blank' : '_self';

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
