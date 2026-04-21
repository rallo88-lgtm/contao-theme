<?php

namespace Rallo\ContaoTheme\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: 'rct_logo', category: 'rct', template: 'frontend_module/rct_logo')]
class RctLogoController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->logoUrl         = $model->rct_logo_url ?: '/';
        $template->altText         = $model->rct_logo_alt ?: (string) $model->name;
        $template->hideMobile      = (bool) $model->rct_logo_hide_mobile;
        $template->logoStyle       = $model->rct_logo_style ?: 'sidebar';
        $template->logoImage       = null;
        $template->logoImageMobile = null;
        $template->rct_visibility  = (string) $model->rct_visibility;

        if ($model->rct_logo_image) {
            $file = FilesModel::findByUuid($model->rct_logo_image);
            if ($file !== null) {
                $template->logoImage = $file->path;
            }
        }

        if ($model->rct_logo_image_mobile) {
            $file = FilesModel::findByUuid($model->rct_logo_image_mobile);
            if ($file !== null) {
                $template->logoImageMobile = $file->path;
            }
        }

        return $template->getResponse();
    }
}
