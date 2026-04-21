<?php

namespace Rallo\ContaoTheme\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: 'rct_fullscreen_toggle', category: 'rct', template: 'frontend_module/rct_fullscreen_toggle')]
class RctFullscreenToggleController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->rct_visibility = (string) $model->rct_visibility;

        return $template->getResponse();
    }
}
