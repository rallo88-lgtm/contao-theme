<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Rallo\ContaoTheme\RctBundle;

#[AsHook('replaceInsertTags')]
class RctInsertTagListener
{
    public function __invoke(string $tag): string|false
    {
        if ($tag === 'rct_version') {
            return RctBundle::VERSION;
        }

        return false;
    }
}
