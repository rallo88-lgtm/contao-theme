<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\PageModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Open-Graph-Ergänzung (Ralph 19.11.2026): ergänzt im Seitenkopf nur die og-Angaben, die noch FEHLEN
 * (og:title, og:description, og:url, og:locale, og:locale:alternate). Vorhandene Tags aus Layout/Seite
 * bleiben unangetastet. Quelle: Seitentitel, meta description, canonical, html lang, hreflang-Links.
 */
#[AsEventListener(event: 'kernel.response', priority: -10)]
class RctOpenGraphListener
{
    private const LOCALES = ['de' => 'de_DE', 'en' => 'en_US', 'fr' => 'fr_FR', 'es' => 'es_ES', 'it' => 'it_IT', 'nl' => 'nl_NL'];

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $page = $event->getRequest()->attributes->get('pageModel');
        $response = $event->getResponse();
        if (!$page instanceof PageModel || !str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return;
        }
        $html = $response->getContent();
        if (!\is_string($html) || ($pos = stripos($html, '</head>')) === false) {
            return;
        }
        $head = substr($html, 0, $pos);
        $add = [];

        if (!$this->has($head, 'og:title')) {
            $title = trim(html_entity_decode((string) ($page->pageTitle ?: $page->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($title !== '' && !str_contains($title, '{{')) {
                $add[] = $this->tag('og:title', $title);
            }
        }
        if (!$this->has($head, 'og:description') && preg_match('/<meta\s+name="description"\s+content="([^"]*)"/i', $head, $m) && trim($m[1]) !== '') {
            $add[] = '<meta property="og:description" content="' . $m[1] . '">';   // schon escaped aus dem Kopf übernommen
        }
        if (!$this->has($head, 'og:url') && preg_match('/<link\s+rel="canonical"\s+href="([^"]+)"/i', $head, $m)) {
            $add[] = '<meta property="og:url" content="' . $m[1] . '">';
        }
        if (!$this->has($head, 'og:locale') && preg_match('/<html[^>]*\blang="([a-z]{2})/i', $html, $m)) {
            $lang = strtolower($m[1]);
            $add[] = $this->tag('og:locale', self::LOCALES[$lang] ?? $lang);
            if (preg_match_all('/<link\s+rel="alternate"\s+hreflang="([a-z]{2})"/i', $head, $alts)) {
                foreach (array_unique(array_map('strtolower', $alts[1])) as $alt) {
                    if ($alt !== $lang) {
                        $add[] = $this->tag('og:locale:alternate', self::LOCALES[$alt] ?? $alt);
                    }
                }
            }
        }

        if ($add) {
            $response->setContent(substr($html, 0, $pos) . implode("\n", $add) . "\n" . substr($html, $pos));
        }
    }

    private function has(string $head, string $property): bool
    {
        return (bool) preg_match('/property="' . preg_quote($property, '/') . '"/i', $head);
    }

    private function tag(string $property, string $content): string
    {
        return '<meta property="' . $property . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">';
    }
}
