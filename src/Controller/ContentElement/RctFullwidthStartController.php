<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\LayoutModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_fullwidth_start', category: 'rct', template: 'content_element/rct_fullwidth_start')]
class RctFullwidthStartController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($request->attributes->get('_scope') === 'backend') {
            return new Response('');
        }

        $cssId              = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId   = trim($cssId[0] ?? '', '"\'');
        $template->cssClass = $cssId[1] ?? '';
        $template->isClassic = $this->isClassicLayout($request);

        return $template->getResponse();
    }

    private function isClassicLayout(Request $request): bool
    {
        $page = $request->attributes->get('pageModel');
        if (!$page instanceof PageModel) {
            return false;
        }
        $layout = LayoutModel::findById($page->layout);
        return $layout && $layout->template === 'fe_page_classic';
    }
}
