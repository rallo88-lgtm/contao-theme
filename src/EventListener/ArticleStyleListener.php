<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\ArticleModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Template;

#[AsHook('parseTemplate')]
class ArticleStyleListener
{
    private const COLOR_MAP = [
        'dark'   => '23, 23, 23',
        'white'  => '255, 255, 255',
        'accent' => '39, 196, 244',
    ];

    public function __invoke(Template $template): void
    {
        if ($template->getName() !== 'mod_article') {
            return;
        }

        $article = ArticleModel::findById((int) $template->id);
        if ($article === null) {
            return;
        }

        $bgColor = (string) $article->rct_article_bg_color;
        $alpha   = (int) ($article->rct_article_bg_alpha ?? 100);
        $blur    = (int) ($article->rct_article_blur ?? 0);

        if ($bgColor === '' && $blur === 0) {
            return;
        }

        $styles = [];

        if ($bgColor !== '' && isset(self::COLOR_MAP[$bgColor])) {
            $rgb      = self::COLOR_MAP[$bgColor];
            $alphaVal = round($alpha / 100, 2);
            $styles[] = "background-color: rgba({$rgb}, {$alphaVal})";
        }

        if ($blur > 0) {
            $styles[] = "-webkit-backdrop-filter: blur({$blur}px)";
            $styles[] = "backdrop-filter: blur({$blur}px)";
        }

        if (empty($styles)) {
            return;
        }

        // mod_article.html5 rendert ->addStyle($this->style) direkt in wrapperAttributes
        $existing = (string) ($template->style ?? '');
        $combined = array_filter(array_merge(
            $existing ? [$existing] : [],
            $styles
        ));
        $template->style = implode('; ', $combined);
    }
}
