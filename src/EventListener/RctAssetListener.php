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
        $GLOBALS['TL_CSS'][] = 'bundles/rct/css/klaro.min.css||static';

        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/vendor/imagesloaded.pkgd.min.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/vendor/isotope.pkgd.min.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/klaro-config.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/klaro.min.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/rct-baker-sky.js||static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/rct/js/rct-baker.js||static';
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
        $css .= "  --rct-sidebar-width: " . $this->escape($config['rct_sidebar_width']) . ";\n";
        $css .= "  --rct-sidebar-left-width: " . $this->escape($config['rct_sidebar_width']) . ";\n";
        $css .= "  --rct-sidebar-right-width: " . $this->escape($config['rct_sidebar_width']) . ";\n";
        $css .= "  --rct-header-height: " . $this->escape($config['rct_header_height']) . ";\n";
        $css .= "  --rct-radius: " . $this->escape($config['rct_radius']) . ";\n";
        $css .= "  --rct-radius-lg: calc(" . $this->escape($config['rct_radius']) . " * 2);\n";
        $css .= "  --rct-radius-xl: calc(" . $this->escape($config['rct_radius']) . " * 4);\n";
        $css .= "}\n";

        // Colors only for default theme — theme switcher overrides these for all other themes
        $css .= ":root:not([data-theme]), :root[data-theme=\"default\"] {\n";
        $css .= "  --rct-accent: " . $this->escape($config['rct_color_accent']) . ";\n";
        $css .= "  --rct-primary: " . $this->escape($config['rct_color_primary']) . ";\n";
        $css .= "  --rct-primary-light: " . $this->escape($config['rct_color_primary_light']) . ";\n";
        $css .= "  --grad-1: " . $this->escape($config['rct_grad1']) . ";\n";
        $css .= "  --grad-2: " . $this->escape($config['rct_grad2']) . ";\n";
        $css .= "  --grad-3: " . $this->escape($config['rct_grad3']) . ";\n";
        $css .= "  --grad-4: " . $this->escape($config['rct_grad4']) . ";\n";
        $css .= "}\n";

        $GLOBALS['TL_HEAD'][] = '<style id="rct-theme-config">' . $css . '</style>';

        $this->injectAllowedThemes($config);
        $this->injectCanvasConfig($config);
    }

    private function injectAllowedThemes(array $config): void
    {
        $raw = $config['rct_allowed_themes'] ?? '';
        if ($raw === '') {
            return;
        }

        $all     = array_keys(\Rallo\ContaoTheme\Controller\Backend\RctConfigController::ALL_THEMES);
        $allowed = array_values(array_intersect(explode(',', $raw), $all));

        if (empty($allowed) || count($allowed) === count($all)) {
            return;
        }

        $json = json_encode($allowed, JSON_UNESCAPED_UNICODE);
        $script  = '<script id="rct-allowed-themes">';
        $script .= 'window.rctAllowedThemes=' . $json . ';';
        // FOUC-Fix: erzwinge das Theme sofort wenn nur eines erlaubt ist
        $script .= 'if(window.rctAllowedThemes.length===1){';
        $script .= 'document.documentElement.setAttribute(\'data-theme\',window.rctAllowedThemes[0]);';
        $script .= '}';
        $script .= '</script>';

        $GLOBALS['TL_HEAD'][] = $script;
    }

    private function injectCanvasConfig(array $config): void
    {
        $canvas = (int)($config['rct_canvas_enabled'] ?? 1) !== 0 ? 'true' : 'false';
        $dots   = (int)($config['rct_dots_enabled']   ?? 1) !== 0 ? 'true' : 'false';
        $speed  = number_format(max(0.1, min(5.0, (float)($config['rct_aurora_speed'] ?? '1.0'))), 1, '.', '');

        $GLOBALS['TL_HEAD'][] = '<script id="rct-canvas-flags">'
            . 'window.rctCanvasEnabled=' . $canvas . ';'
            . 'window.rctDotsEnabled='   . $dots   . ';'
            . 'window.rctAuroraSpeed='   . $speed  . ';'
            . '</script>';
    }

    private function buildFontFaceDeclarations(array $config): string
    {
        $bundleDir = $this->projectDir . '/public/bundles/rct/fonts';
        if (!is_dir($bundleDir)) {
            $bundleDir = \dirname(__DIR__, 2) . '/Resources/public/fonts';
        }

        $customFamilies = [];

        foreach ([$bundleDir, $this->projectDir . '/files/rct-fonts'] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.{woff2,woff,ttf,otf}', GLOB_BRACE) as $file) {
                $family = $this->extractFamilyName(basename($file));
                if ($family && !in_array($family, self::BUILT_IN_FONTS)) {
                    $customFamilies[$family][] = ['file' => basename($file), 'dir' => $dir];
                }
            }
        }

        if (empty($customFamilies)) {
            return '';
        }

        // Only generate @font-face for families actually selected in config
        $usedFamilies = [$config['rct_font_body'], $config['rct_font_mono']];
        $css = '';

        $userFontsDir = $this->projectDir . '/files/rct-fonts';

        foreach ($customFamilies as $family => $entries) {
            if (!in_array($family, $usedFamilies)) {
                continue;
            }
            foreach ($entries as $entry) {
                $filename = $entry['file'];
                $isUser   = $entry['dir'] === $userFontsDir;
                $url      = $isUser
                    ? '/files/rct-fonts/' . rawurlencode($filename)
                    : '/bundles/rct/fonts/' . rawurlencode($filename);
                $weight = $this->detectWeight($filename);
                $style  = stripos($filename, 'italic') !== false ? 'italic' : 'normal';
                $format = $this->detectFormat($filename);
                $css   .= "@font-face { font-family: '" . $this->escape($family) . "'; font-weight: {$weight}; font-style: {$style}; font-display: swap; src: url('" . $url . "') format('{$format}'); }\n";
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
