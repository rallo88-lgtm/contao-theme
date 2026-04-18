<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;

#[AsHook('generatePage')]
class RctAssetListener
{
    public function __invoke(PageModel $page, LayoutModel $layout, PageRegular $pageRegular): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/rct/css/rct-layout.css||static';
        $GLOBALS['TL_CSS'][] = 'bundles/rct/css/rct-utilities.css||static';
        $GLOBALS['TL_CSS'][] = 'bundles/rct/css/rct-components.css||static';
        $GLOBALS['TL_CSS'][] = 'bundles/rct/css/rct-customize.css||static';

        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/vendor/imagesloaded.pkgd.min.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/vendor/isotope.pkgd.min.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/rct-canvas-config.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/gl-bg-animation.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/rct.js||static';
    }
}
