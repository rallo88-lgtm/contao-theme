<?php

namespace Rallo\ContaoTheme\Twig;

use Rallo\ContaoTheme\RctBundle;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class RctTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return ['rct_version' => RctBundle::VERSION];
    }

    public function getFilters(): array
    {
        return [
            // Wandelt HTML-Entities zurück (Contao speichert text-Felder mit
            // &#60; statt <, was sanitize_html dann nicht mehr als Tag erkennt).
            new TwigFilter('html_decode', static fn(?string $s): string =>
                $s === null || $s === '' ? '' : html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ),
        ];
    }
}
