<?php

namespace Rallo\ContaoTheme\Twig;

use Rallo\ContaoTheme\RctBundle;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class RctTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return ['rct_version' => RctBundle::VERSION];
    }
}
