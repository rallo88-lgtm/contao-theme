<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\PageModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_cta', category: 'rct', template: 'content_element/rct_cta')]
class RctCtaController extends AbstractContentElementController
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
        $colorKey = $model->rct_cta_color ?: 'accent';
        $colorCss = self::COLOR_MAP[$colorKey] ?? self::COLOR_MAP['accent'];

        // Dunkle Farben brauchen weißen Button-Text
        $darkColors = ['purple', 'red'];
        $btnFg = in_array($colorKey, $darkColors) ? '#ffffff' : 'var(--rct-shell-bg, #171717)';

        // Button 1
        $btn1 = [];
        $btn1Url = $this->resolveUrl((int) $model->rct_cta_btn1_page, (string) $model->rct_cta_btn1_url);
        if ($model->rct_cta_btn1_label && $btn1Url) {
            $btn1 = [
                'label'  => htmlspecialchars((string) $model->rct_cta_btn1_label, ENT_QUOTES, 'UTF-8'),
                'url'    => $btn1Url,
                'style'  => $model->rct_cta_btn1_style ?: 'primary',
                'target' => $model->rct_cta_btn1_target ? '_blank' : '_self',
            ];
        }

        // Button 2
        $btn2 = [];
        $btn2Url = $this->resolveUrl((int) $model->rct_cta_btn2_page, (string) $model->rct_cta_btn2_url);
        if ($model->rct_cta_btn2_label && $btn2Url) {
            $btn2 = [
                'label'  => htmlspecialchars((string) $model->rct_cta_btn2_label, ENT_QUOTES, 'UTF-8'),
                'url'    => $btn2Url,
                'style'  => $model->rct_cta_btn2_style ?: 'outline',
                'target' => $model->rct_cta_btn2_target ? '_blank' : '_self',
            ];
        }

        $template->btnFg       = $btnFg;
        $template->ctaHeadline = htmlspecialchars((string) $model->rct_cta_headline, ENT_QUOTES, 'UTF-8');
        $template->ctaText     = $model->rct_cta_text ? nl2br(htmlspecialchars((string) $model->rct_cta_text, ENT_QUOTES, 'UTF-8')) : '';
        $template->icon        = (string) $model->rct_cta_icon;
        $template->colorCss    = $colorCss;
        $template->layout      = $model->rct_cta_layout ?: 'centered';
        $template->ctaStyle    = $model->rct_cta_style ?: 'light';
        $template->btn1        = $btn1;
        $template->btn2        = $btn2;
        $cssId                 = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId      = trim($cssId[0] ?? '', '"\'');
        $template->cssClass    = $cssId[1] ?? '';

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
