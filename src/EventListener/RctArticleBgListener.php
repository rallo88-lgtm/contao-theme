<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\ArticleModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Template;

#[AsHook('parseTemplate')]
class RctArticleBgListener
{
    public function __invoke(Template $template): void
    {
        if (!str_starts_with($template->getName(), 'mod_article')) {
            return;
        }

        $article = ArticleModel::findById($template->id);

        if (!$article) {
            return;
        }

        $classes = [];

        if ($article->rct_article_bg_color) {
            $classes[] = 'rct-article-bg-' . $article->rct_article_bg_color;

            $alpha = max(0, min(100, (int) ($article->rct_article_bg_alpha ?: 100)));
            $blur  = max(0, (int) $article->rct_article_blur);

            $style = '--rct-article-alpha:' . round($alpha / 100, 2) . ';';
            if ($blur > 0) {
                $style .= "backdrop-filter:blur({$blur}px);-webkit-backdrop-filter:blur({$blur}px);";
            }
            $template->style = $style . ($template->style ?? '');
        }

        if (in_array($article->rct_article_shadow, ['none', 'soft', 'strong'], true)) {
            $classes[] = 'rct-article-shadow-' . $article->rct_article_shadow;
        }

        if ($classes) {
            $template->class = implode(' ', $classes) . ($template->class ? ' ' . $template->class : '');
        }
    }
}
