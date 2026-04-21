<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Module;
use Contao\ModuleModel;

#[AsHook('getFrontendModule')]
class RctVisibilityListener
{
    public function __invoke(ModuleModel $model, string $buffer, Module $module): string
    {
        $vis = (string) $model->rct_visibility;

        if ($vis === '' || $vis === 'both') {
            return $buffer;
        }

        return '<div data-rct-visibility="' . htmlspecialchars($vis) . '">' . $buffer . '</div>';
    }
}
