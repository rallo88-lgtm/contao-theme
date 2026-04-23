<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_grid_start', category: 'rct', template: 'content_element/rct_grid_start')]
class RctGridStartController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($request->attributes->get('_scope') === 'backend') {
            return new Response('');
        }

        $template->columns   = $model->rct_columns ?: '3';
        $template->gap       = $model->rct_gap;
        $template->align     = $model->rct_align;
        $cssId               = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId    = trim($cssId[0] ?? '', '"\'');
        $template->cssClass  = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
