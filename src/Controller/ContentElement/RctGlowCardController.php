<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\PageModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_glow_card', category: 'rct', template: 'content_element/rct_glow_card')]
class RctGlowCardController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $linkUrl = $this->resolveUrl((int) $model->rct_glow_card_link_page, (string) $model->rct_glow_card_link_url);

        $template->headline   = htmlspecialchars((string) $model->rct_glow_card_headline, ENT_QUOTES, 'UTF-8');
        $template->text       = $model->rct_glow_card_text
            ? nl2br(htmlspecialchars((string) $model->rct_glow_card_text, ENT_QUOTES, 'UTF-8'))
            : '';
        $template->glowStyle  = $model->rct_glow_card_style ?: 'rainbow';
        $template->glowSpeed  = $model->rct_glow_card_speed ?: 'normal';
        $template->linkUrl    = $linkUrl;
        $template->linkLabel  = $linkUrl
            ? htmlspecialchars((string) ($model->rct_glow_card_link_label ?: 'Mehr erfahren'), ENT_QUOTES, 'UTF-8')
            : '';
        $template->linkTarget = $model->rct_glow_card_link_target ? '_blank' : '_self';

        $cssId              = \Contao\StringUtil::deserialize($model->cssID, true);
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
