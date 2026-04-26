<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\Template;

#[AsHook('parseTemplate')]
class RctArticleFullwidthListener
{
    public function __invoke(Template $template): void
    {
        if ($template->getName() !== 'mod_article_rct_fullwidth') {
            return;
        }

        $page = $GLOBALS['objPage'] ?? null;
        $isClassic = false;
        if ($page) {
            $layout = LayoutModel::findById($page->layout);
            $isClassic = $layout && $layout->template === 'fe_page_classic';
        }
        $template->isClassic = $isClassic;
    }
}
