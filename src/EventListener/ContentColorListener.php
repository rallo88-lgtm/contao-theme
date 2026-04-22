<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsHook('getContentElement')]
class ContentColorListener
{
    private const SUPPORTED_COLOR = ['text', 'headline', 'rct_hero'];

    private static array $injectedFonts = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir
    ) {}

    public function __invoke(ContentModel $model, string $buffer, mixed $element): string
    {
        // Natives Contao-Akkordeon: style-dark / style-light Klasse injizieren
        if ($model->type === 'accordion') {
            return $this->injectAccordionStyle($model, $buffer);
        }

        // Download / Downloads: style-dark Klasse injizieren
        if ($model->type === 'download' || $model->type === 'downloads') {
            return $this->injectDownloadStyle($model, $buffer);
        }

        if (!\in_array($model->type, self::SUPPORTED_COLOR, true)) {
            return $buffer;
        }

        // rct_hero: Farbe wird im Controller direkt auf <h1> gesetzt
        $raw = trim((string) $model->rct_content_color);
        if ($raw !== '' && $model->type !== 'rct_hero') {
            if (str_starts_with($raw, 'var(')) {
                $color = $raw;
            } elseif (preg_match('/^#?[0-9a-fA-F]{3,8}$/', $raw)) {
                $color = '#' . ltrim($raw, '#');
            } else {
                $color = '';
            }

            if ($color !== '') {
                $buffer = $this->injectStyle($buffer, 'color: ' . $color);
            }
        }

        $font = trim((string) $model->rct_hl_font);
        if ($font !== '') {
            if ($model->type !== 'rct_hero') {
                $buffer = $this->injectStyle($buffer, "font-family:'" . str_replace("'", "\\'", $font) . "'");
            }
            $this->ensureFontFace($font);
        }

        return $buffer;
    }

    private function ensureFontFace(string $family): void
    {
        if (isset(self::$injectedFonts[$family])) {
            return;
        }
        self::$injectedFonts[$family] = true;

        $bundleDir   = $this->projectDir . '/public/bundles/rct/fonts';
        if (!is_dir($bundleDir)) {
            $bundleDir = \dirname(__DIR__, 2) . '/Resources/public/fonts';
        }
        $userDir = $this->projectDir . '/files/rct-fonts';

        $css = '';
        foreach ([$bundleDir, $userDir] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.{woff2,woff,ttf,otf}', GLOB_BRACE) as $file) {
                $basename = basename($file);
                if ($this->extractFamilyName($basename) !== $family) {
                    continue;
                }
                $isUser = $dir === $userDir;
                $url    = ($isUser ? '/files/rct-fonts/' : '/bundles/rct/fonts/') . rawurlencode($basename);
                $weight = $this->detectWeight($basename);
                $style  = stripos($basename, 'italic') !== false ? 'italic' : 'normal';
                $format = $this->detectFormat($basename);
                $css   .= "@font-face{font-family:'" . addslashes($family) . "';font-weight:{$weight};font-style:{$style};font-display:swap;src:url('" . $url . "')format('{$format}');}\n";
            }
        }

        if ($css !== '') {
            $GLOBALS['TL_HEAD'][] = '<style>' . $css . '</style>';
        }
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
        if (preg_match('/[-_](900|black)/i', $filename))      return 900;
        if (preg_match('/[-_](800|extrabold)/i', $filename))  return 800;
        if (preg_match('/[-_](700|bold)/i', $filename))       return 700;
        if (preg_match('/[-_](600|semibold)/i', $filename))   return 600;
        if (preg_match('/[-_](500|medium)/i', $filename))     return 500;
        if (preg_match('/[-_](300|light)/i', $filename))      return 300;
        if (preg_match('/[-_](200|extralight)/i', $filename)) return 200;
        if (preg_match('/[-_](100|thin)/i', $filename))       return 100;
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

    private function injectStyle(string $buffer, string $styleDecl): string
    {
        return preg_replace_callback(
            '/(<(?:div|h[1-6]|article|section)\b)([^>]*)(>)/i',
            static function (array $m) use ($styleDecl): string {
                $tag   = $m[1];
                $attrs = $m[2];
                $close = $m[3];

                if (preg_match('/\bstyle="([^"]*)"/i', $attrs)) {
                    $attrs = preg_replace(
                        '/\bstyle="([^"]*)"/i',
                        'style="$1; ' . htmlspecialchars($styleDecl, ENT_QUOTES) . '"',
                        $attrs
                    );
                } else {
                    $attrs .= ' style="' . htmlspecialchars($styleDecl, ENT_QUOTES) . '"';
                }

                return $tag . $attrs . $close;
            },
            $buffer,
            1
        ) ?? $buffer;
    }

    private function injectDownloadStyle(ContentModel $model, string $buffer): string
    {
        $style = (string) ($model->rct_download_style ?: '');
        if ($style === '') {
            return $buffer;
        }
        $class = 'style-' . ($style === 'light' ? 'light' : 'dark');

        $needle = 'class="content-download';
        $pos    = strpos($buffer, $needle);
        if ($pos === false) {
            return $buffer;
        }
        $insertAt = $pos + strlen($needle);
        return substr($buffer, 0, $insertAt) . ' ' . $class . substr($buffer, $insertAt);
    }

    private function injectAccordionStyle(ContentModel $model, string $buffer): string
    {
        $style = (string) ($model->rct_accordion_style ?: 'dark');
        $class = 'style-' . ($style === 'light' ? 'light' : 'dark');

        $needle = 'class="content-accordion';
        $pos    = strpos($buffer, $needle);
        if ($pos === false) {
            return $buffer;
        }
        $insertAt = $pos + strlen($needle);
        return substr($buffer, 0, $insertAt) . ' ' . $class . substr($buffer, $insertAt);
    }
}
