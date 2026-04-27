<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_form_header', category: 'rct', template: 'content_element/rct_form_header')]
class RctFormHeaderController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $raw   = (string) $model->rct_form_header_items;
        $lines = array_filter(array_map('trim', explode("\n", $raw)));
        $items = [];
        $i     = 0;
        foreach ($lines as $line) {
            if ($line === '' || str_starts_with($line, '#')) continue;
            $items[] = [
                'label'  => htmlspecialchars($line, ENT_QUOTES, 'UTF-8'),
                'accent' => $i === 0, // erste Zeile = Akzent
            ];
            $i++;
        }

        $template->items = $items;
        $template->style = $model->rct_form_header_style ?: 'light';

        $cssId              = StringUtil::deserialize($model->cssID, true);
        $template->htmlId   = trim($cssId[0] ?? '', '"\'');
        $template->cssClass = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
