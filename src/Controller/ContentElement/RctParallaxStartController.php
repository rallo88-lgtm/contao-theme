<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_parallax_start', category: 'rct', template: 'content_element/rct_parallax_start')]
class RctParallaxStartController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($request->attributes->get('_scope') === 'backend') {
            return new Response('');
        }

        $template->overlay  = $model->rct_parallax_overlay ?: '';
        $template->minHeight = $model->rct_parallax_height ?: '';

        // Hintergrundbild
        $template->image = null;
        if ($model->rct_parallax_image) {
            $file = FilesModel::findByUuid($model->rct_parallax_image);
            if ($file !== null) {
                $template->image = '/' . $file->path;
            }
        }

        // Hintergrundvideo (MP4)
        $template->video = null;
        if ($model->rct_parallax_video) {
            $file = FilesModel::findByUuid($model->rct_parallax_video);
            if ($file !== null) {
                $template->video = '/' . $file->path;
            }
        }

        $cssId               = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId    = trim($cssId[0] ?? '', '"\'');
        $template->cssClass  = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
