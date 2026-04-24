<?php

namespace Rallo\ContaoTheme\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RctIconExtension extends AbstractExtension
{
    private static array $cache = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('rct_icon', [$this, 'renderIcon'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Rendert Icon-Input:
     * - "tabler:rocket" → inline SVG aus Bundle
     * - "🚀" oder andere Text/Emoji → escaped Text
     * - leer → leerer String
     */
    public function renderIcon(?string $input): string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return '';
        }

        if (str_starts_with($input, 'tabler:')) {
            return $this->loadTablerIcon(substr($input, 7));
        }

        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    private function loadTablerIcon(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return '';
        }

        if (isset(self::$cache[$slug])) {
            return self::$cache[$slug];
        }

        $publicDir = $this->projectDir . '/public/bundles/rct/icons/tabler';
        $bundleDir = \dirname(__DIR__) . '/Resources/public/icons/tabler';

        foreach ([$publicDir, $bundleDir] as $dir) {
            $file = $dir . '/' . $slug . '.svg';
            if (is_file($file)) {
                $svg = (string) file_get_contents($file);
                // Kommentar-Header (tags: ...) entfernen
                $svg = preg_replace('/^<!--.*?-->\s*/s', '', $svg);
                // Class-Hook hinzufügen
                $svg = preg_replace(
                    '/<svg\b([^>]*)>/i',
                    '<svg$1 class="rct-icon rct-icon-tabler" aria-hidden="true">',
                    $svg,
                    1
                );
                return self::$cache[$slug] = (string) $svg;
            }
        }

        return self::$cache[$slug] = '';
    }
}
