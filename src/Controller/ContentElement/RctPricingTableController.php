<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_pricing_table', category: 'rct', template: 'content_element/rct_pricing_table')]
class RctPricingTableController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $raw     = html_entity_decode((string) $model->rct_pricing_data, ENT_QUOTES, 'UTF-8');
        $blocks  = preg_split('/\n\s*---\s*\n/', trim($raw));
        $plans   = [];

        foreach ($blocks as $block) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", trim($block)))));
            if (empty($lines)) continue;

            // First line: Name|Price|Period|highlight (highlight optional)
            $header    = array_map('trim', explode('|', array_shift($lines)));
            $name      = $header[0] ?? '';
            $price     = $header[1] ?? '';
            $period    = $header[2] ?? '';
            $highlight = isset($header[3]) && strtolower($header[3]) === 'highlight';

            // Last line starting with > is the button: Label|url
            $btnLine = '';
            if (!empty($lines) && str_starts_with(end($lines), '>')) {
                $btnLine = ltrim(array_pop($lines), '> ');
            }

            $btnParts = $btnLine ? array_map('trim', explode('|', $btnLine)) : [];
            $btn = ($btnParts[0] ?? '') ? [
                'label'  => htmlspecialchars($btnParts[0], ENT_QUOTES, 'UTF-8'),
                'url'    => htmlspecialchars($btnParts[1] ?? '#', ENT_QUOTES, 'UTF-8'),
                'target' => isset($btnParts[2]) && $btnParts[2] === '_blank' ? '_blank' : '_self',
            ] : [];

            // Remaining lines are features; leading + = included, - = excluded, plain = neutral
            $features = [];
            foreach ($lines as $line) {
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_starts_with($line, '+')) {
                    $features[] = ['text' => htmlspecialchars(ltrim($line, '+ '), ENT_QUOTES, 'UTF-8'), 'type' => 'yes'];
                } elseif (str_starts_with($line, '-')) {
                    $features[] = ['text' => htmlspecialchars(ltrim($line, '- '), ENT_QUOTES, 'UTF-8'), 'type' => 'no'];
                } else {
                    $features[] = ['text' => htmlspecialchars($line, ENT_QUOTES, 'UTF-8'), 'type' => 'neutral'];
                }
            }

            $plans[] = [
                'name'      => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                'price'     => htmlspecialchars($price, ENT_QUOTES, 'UTF-8'),
                'period'    => htmlspecialchars($period, ENT_QUOTES, 'UTF-8'),
                'highlight' => $highlight,
                'features'  => $features,
                'btn'       => $btn,
            ];
        }

        $cssId = \Contao\StringUtil::deserialize($model->cssID, true);

        $template->plans    = $plans;
        $template->style    = $model->rct_pricing_style ?: 'dark';
        $template->htmlId   = trim($cssId[0] ?? '', '"\'');
        $template->cssClass = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
