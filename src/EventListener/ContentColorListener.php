<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

#[AsHook('getContentElement')]
class ContentColorListener
{
    private const SUPPORTED_COLOR = ['text', 'headline'];

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

        $raw = trim((string) $model->rct_content_color);
        if ($raw !== '') {
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
        if ($font !== '' && $model->type === 'headline') {
            $buffer = $this->injectStyle($buffer, "font-family:'" . str_replace("'", "\\'", $font) . "'");
        }

        return $buffer;
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
