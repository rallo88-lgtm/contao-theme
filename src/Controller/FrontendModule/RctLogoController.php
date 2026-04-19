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
        $template->logoUrl   = $model->rct_logo_url ?: '/';
        $template->altText   = $model->rct_logo_alt ?: (string) $model->name;
        $template->logoImage = null;

        if ($model->rct_logo_image) {
            $file = FilesModel::findByUuid($model->rct_logo_image);
            if ($file !== null && is_file(\TL_ROOT . '/' . $file->path)) {
                $template->logoImage = $file->path;
            }
        }

        return $template->getResponse();
    }
}
