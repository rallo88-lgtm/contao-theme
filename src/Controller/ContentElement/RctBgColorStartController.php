<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_bgcolor_start', category: 'rct', template: 'content_element/rct_bgcolor_start')]
class RctBgColorStartController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($request->attributes->get('_scope') === 'backend') {
            return new Response('');
        }

        $bg      = trim((string) $model->rct_bgcolor_bg);
        $padding = $model->rct_bgcolor_padding ?: 'md';

        $template->bg      = preg_match('/^#[0-9a-fA-F]{3,8}$/', $bg) ? $bg : '';
        $template->padding = in_array($padding, ['none', 'sm', 'md', 'lg'], true) ? $padding : 'md';

        // Optionales Hintergrundbild
        $template->image = null;
        if ($model->rct_bgcolor_image) {
            $file = FilesModel::findByUuid($model->rct_bgcolor_image);
            if ($file !== null) {
                $template->image = '/' . $file->path;
            }
        }

        $cssId              = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId   = trim($cssId[0] ?? '', '"\'');
        $template->cssClass = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
