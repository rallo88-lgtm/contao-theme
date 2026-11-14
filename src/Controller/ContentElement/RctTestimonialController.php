<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RCT Kundenstimme (Ralph 14.11.2026): links Logo oder Icon + Name, Firma, Webseite – rechts das Zitat.
 * Anführungszeichen setzt das Theme (CSS), eingetippte am Anfang/Ende werden entfernt.
 */
#[AsContentElement(type: 'rct_testimonial', category: 'rct', template: 'content_element/rct_testimonial')]
class RctTestimonialController extends AbstractContentElementController
{
    private const QUOTES = '\s„“”"»«‚‘’\'';

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $name  = trim((string) $model->rct_ts_name);
        $quote = preg_replace('/^[' . self::QUOTES . ']+|[' . self::QUOTES . ']+$/u', '', (string) $model->rct_ts_quote) ?? '';

        $logo = '';
        if ($model->rct_itb_image) {
            $file = FilesModel::findByUuid($model->rct_itb_image);
            if ($file !== null) {
                $logo = '/' . $file->path;
            }
        }

        [$url, $urlLabel] = $this->website((string) $model->rct_ts_url);

        $cssId = StringUtil::deserialize($model->cssID, true);

        $template->name     = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $template->company  = htmlspecialchars(trim((string) $model->rct_ts_company), ENT_QUOTES, 'UTF-8');
        $template->quote    = nl2br(htmlspecialchars(trim($quote), ENT_QUOTES, 'UTF-8'));
        $template->logo     = $logo;
        $template->icon     = $logo === '' ? trim((string) $model->rct_itb_icon) : '';
        $template->url      = $url;
        $template->urlLabel = $urlLabel;
        $template->boxStyle = $model->rct_itb_style === 'dark' ? 'dark' : 'light';
        $template->htmlId   = trim($cssId[0] ?? '', '"\'');
        $template->cssClass = $cssId[1] ?? '';

        return $template->getResponse();
    }

    /** „begi.de“ oder „https://www.begi.de/“ → [Link, Anzeige ohne https:// und www.] */
    private function website(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['', ''];
        }
        if (!preg_match('~^https?://~i', $raw)) {
            $raw = 'https://' . $raw;
        }
        $host = parse_url($raw, PHP_URL_HOST);
        if (!\is_string($host) || $host === '') {
            return ['', ''];
        }
        $label = preg_replace('~^https?://(www\.)?~i', '', rtrim($raw, '/')) ?? $host;

        return [htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'), htmlspecialchars($label, ENT_QUOTES, 'UTF-8')];
    }
}
