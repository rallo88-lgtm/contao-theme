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

#[AsContentElement(type: 'rct_hero', category: 'rct', template: 'content_element/rct_hero')]
class RctHeroController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->overline = htmlspecialchars((string) $model->rct_hero_overline, ENT_QUOTES, 'UTF-8');
        $template->headline = strip_tags((string) $model->rct_hero_headline, '<em>');
        $template->body     = $model->rct_hero_body ? nl2br(htmlspecialchars((string) $model->rct_hero_body, ENT_QUOTES, 'UTF-8')) : '';
        $template->layout   = $model->rct_hero_layout ?: 'centered';

        // Überschrift-Farbe (direkt auf <h1> gesetzt, ContentColorListener überspringt rct_hero)
        $hlColor = trim((string) $model->rct_content_color);
        if ($hlColor !== '' && !str_starts_with($hlColor, 'var(')) {
            $hlColor = '#' . ltrim($hlColor, '#');
        }
        $template->hlColor = $hlColor;
        $template->hlFont  = trim((string) $model->rct_hl_font);

        // Bilder (multi-select, serialisiert)
        $images = [];
        $imgAlt = htmlspecialchars((string) $model->rct_hero_image_alt, ENT_QUOTES, 'UTF-8');
        if ($model->rct_hero_image) {
            $uuids = StringUtil::deserialize($model->rct_hero_image, true);
            foreach ($uuids as $uuid) {
                if (!$uuid) {
                    continue;
                }
                $file = FilesModel::findByUuid($uuid);
                if ($file !== null) {
                    $images[] = '/' . $file->path;
                }
            }
        }
        $template->images     = $images;
        $template->imgAlt     = $imgAlt;
        $template->slideSpeed = (int) ($model->rct_hero_slide_speed ?: 5);

        // Button 1
        $btn1    = [];
        $btn1Url = $this->resolveUrl((int) $model->rct_hero_btn1_page, (string) $model->rct_hero_btn1_url);
        if ($model->rct_hero_btn1_label && $btn1Url) {
            $btn1 = [
                'label'  => htmlspecialchars((string) $model->rct_hero_btn1_label, ENT_QUOTES, 'UTF-8'),
                'url'    => $btn1Url,
                'style'  => $model->rct_hero_btn1_style ?: 'primary',
                'target' => $model->rct_hero_btn1_target ? '_blank' : '_self',
            ];
        }

        // Button 2
        $btn2    = [];
        $btn2Url = $this->resolveUrl((int) $model->rct_hero_btn2_page, (string) $model->rct_hero_btn2_url);
        if ($model->rct_hero_btn2_label && $btn2Url) {
            $btn2 = [
                'label'  => htmlspecialchars((string) $model->rct_hero_btn2_label, ENT_QUOTES, 'UTF-8'),
                'url'    => $btn2Url,
                'style'  => $model->rct_hero_btn2_style ?: 'outline',
                'target' => $model->rct_hero_btn2_target ? '_blank' : '_self',
            ];
        }

        $template->btn1 = $btn1;
        $template->btn2 = $btn2;

        // Stats (Format: Wert|Label, eine Zeile pro Stat, max. 3)
        $stats = [];
        if ($model->rct_hero_stats) {
            $lines = array_filter(array_map('trim', explode("\n", (string) $model->rct_hero_stats)));
            foreach (array_slice($lines, 0, 3) as $line) {
                $parts = explode('|', $line, 2);
                if (\count($parts) === 2 && $parts[0] !== '') {
                    $stats[] = [
                        'value' => htmlspecialchars(trim($parts[0]), ENT_QUOTES, 'UTF-8'),
                        'label' => htmlspecialchars(trim($parts[1]), ENT_QUOTES, 'UTF-8'),
                    ];
                }
            }
        }
        $template->stats = $stats;

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
