<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_map', category: 'rct', template: 'content_element/rct_map')]
class RctMapController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->mapId     = 'rct-map-' . $model->id;
        $template->address   = $model->rct_map_address;
        $template->zoom      = $model->rct_map_zoom ?: '14';
        $template->marker    = $model->rct_map_marker;
        $template->height    = $model->rct_map_height ?: '400px';
        $cssId               = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId    = trim($cssId[0] ?? '', '"\'');
        $template->cssClass  = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
