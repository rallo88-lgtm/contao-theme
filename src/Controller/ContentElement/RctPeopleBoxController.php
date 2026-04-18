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

#[AsContentElement(type: 'rct_people_box', category: 'rct', template: 'content_element/rct_people_box')]
class RctPeopleBoxController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->name     = $model->rct_person_name;
        $template->role     = $model->rct_person_role;
        $template->bio      = $model->rct_person_bio;
        $template->email    = $model->rct_person_email;
        $template->phone    = $model->rct_person_phone;
        $template->link     = $model->rct_person_link;
        $template->linkText = $model->rct_person_link_text ?: $model->rct_person_link;

        // Bild auflösen — radio speichert UUID direkt (nicht serialisiert)
        $template->image = null;
        if ($model->rct_person_image) {
            $file = FilesModel::findByUuid($model->rct_person_image);
            if ($file !== null) {
                $template->image = '/' . $file->path;
            }
        }

        $template->boxStyle = $model->rct_people_box_style ?: 'light';

        return $template->getResponse();
    }
}
