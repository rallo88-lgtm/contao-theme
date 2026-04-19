<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsHook('generatePage')]
class RctAssetListener
{
    private const BUILT_IN_FONTS = ['Space Grotesk', 'DM Mono'];

    public function __construct(
        private readonly Connection $db,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {}

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

        $this->injectThemeConfig();
    }

    private function injectThemeConfig(): void
    {
        try {
            $tables = $this->db->createSchemaManager()->listTableNames();
            if (!in_array('tl_rct_config', $tables)) {
                return;
            }
            $config = $this->db->fetchAssociative("SELECT * FROM tl_rct_config LIMIT 1");
        } catch (\Throwable) {
            return;
        }

        if (!$config) {
            return;
        }

        $css = $this->buildFontFaceDeclarations($config);
        $css .= ":root {\n";
        $css .= "  --rct-font-body: '" . $this->escape($config['rct_font_body']) . "', sans-serif;\n";
        $css .= "  --rct-font-headline: '" . $this->escape($config['rct_font_body']) . "', sans-serif;\n";
        $css .= "  --rct-font-mono: '" . $this->escape($config['rct_font_mono']) . "', monospace;\n";
        $css .= "  --rct-accent: " . $this->escape($config['rct_color_accent']) . ";\n";
        $css .= "  --rct-sidebar-width: " . $this->escape($config['rct_sidebar_width']) . ";\n";
        $css .= "  --rct-sidebar-left-width: " . $this->escape($config['rct_sidebar_width']) . ";\n";
        $css .= "  --rct-sidebar-right-width: " . $this->escape($config['rct_sidebar_width']) . ";\n";
        $css .= "  --rct-header-height: " . $this->escape($config['rct_header_height']) . ";\n";
        $css .= "  --rct-radius: " . $this->escape($config['rct_radius']) . ";\n";
        $css .= "  --rct-radius-lg: calc(" . $this->escape($config['rct_radius']) . " * 2);\n";
        $css .= "  --rct-radius-xl: calc(" . $this->escape($config['rct_radius']) . " * 4);\n";
        $css .= "}\n";

        // Only override default theme vars when no other theme is active
        $css .= ":root:not([data-theme]), :root[data-theme=\"default\"] {\n";
        $css .= "  --rct-primary: " . $this->escape($config['rct_color_primary']) . ";\n";
        $css .= "  --rct-primary-light: " . $this->escape($config['rct_color_primary_light']) . ";\n";
        $css .= "  --grad-1: " . $this->escape($config['rct_grad1']) . ";\n";
        $css .= "  --grad-2: " . $this->escape($config['rct_grad2']) . ";\n";
        $css .= "  --grad-3: " . $this->escape($config['rct_grad3']) . ";\n";
        $css .= "  --grad-4: " . $this->escape($config['rct_grad4']) . ";\n";
        $css .= "}\n";

        $GLOBALS['TL_HEAD'][] = '<style id="rct-theme-config">' . $css . '</style>';
    }

    private function buildFontFaceDeclarations(array $config): string
    {
        $fontsDir = $this->projectDir . '/public/bundles/rct/fonts';
        if (!is_dir($fontsDir)) {
            $fontsDir = \dirname(__DIR__, 2) . '/Resources/public/fonts';
        }
        if (!is_dir($fontsDir)) {
            return '';
        }

        $customFamilies = [];
        foreach (glob($fontsDir . '/*.{woff2,woff,ttf,otf}', GLOB_BRACE) as $file) {
            $family = $this->extractFamilyName(basename($file));
            if ($family && !in_array($family, self::BUILT_IN_FONTS)) {
                $customFamilies[$family][] = basename($file);
            }
        }

        if (empty($customFamilies)) {
            return '';
        }

        // Only generate @font-face for families actually selected in config
        $usedFamilies = [$config['rct_font_body'], $config['rct_font_mono']];
        $css = '';

        foreach ($customFamilies as $family => $files) {
            if (!in_array($family, $usedFamilies)) {
                continue;
            }
            foreach ($files as $filename) {
                $weight = $this->detectWeight($filename);
                $style  = stripos($filename, 'italic') !== false ? 'italic' : 'normal';
                $format = $this->detectFormat($filename);
                $css   .= "@font-face { font-family: '" . $this->escape($family) . "'; font-weight: {$weight}; font-style: {$style}; font-display: swap; src: url('/bundles/rct/fonts/" . rawurlencode($filename) . "') format('{$format}'); }\n";
            }
        }

        return $css;
    }

    private function extractFamilyName(string $filename): string
    {
        $name = preg_replace('/\.(woff2?|ttf|otf)$/i', '', $filename);
        $name = preg_replace('/-v\d+.*$/', '', $name);
        $name = preg_replace('/[-_](regular|bold|italic|light|medium|semibold|extrabold|black|\d{3})([-_].*)?$/i', '', $name);
        $name = preg_replace('/[-_]latin$/i', '', $name);
        return ucwords(str_replace(['-', '_'], ' ', $name));
    }

    private function detectWeight(string $filename): int
    {
        if (preg_match('/[-_](900|black)/i', $filename))     return 900;
        if (preg_match('/[-_](800|extrabold)/i', $filename)) return 800;
        if (preg_match('/[-_](700|bold)/i', $filename))      return 700;
        if (preg_match('/[-_](600|semibold)/i', $filename))  return 600;
        if (preg_match('/[-_](500|medium)/i', $filename))    return 500;
        if (preg_match('/[-_](300|light)/i', $filename))     return 300;
        if (preg_match('/[-_](200|extralight)/i', $filename))return 200;
        if (preg_match('/[-_](100|thin)/i', $filename))      return 100;
        return 400;
    }

    private function detectFormat(string $filename): string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'woff2' => 'woff2',
            'woff'  => 'woff',
            'ttf'   => 'truetype',
            'otf'   => 'opentype',
            default => 'woff2',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
    }
}
