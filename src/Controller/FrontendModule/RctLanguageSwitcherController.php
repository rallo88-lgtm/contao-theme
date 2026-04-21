<?php

namespace Rallo\ContaoTheme\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: 'rct_language_switcher', category: 'rct', template: 'frontend_module/rct_language_switcher')]
class RctLanguageSwitcherController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $languages = [];
        foreach (array_filter(array_map('trim', explode("\n", (string) $model->rct_languages))) as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 3) {
                $languages[] = ['code' => strtoupper($parts[0]), 'label' => $parts[1], 'url' => $parts[2]];
            }
        }
        $template->languages      = $languages ?: [];
        $template->rct_visibility = (string) $model->rct_visibility;

        return $template->getResponse();
    }
}
