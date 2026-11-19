<?php

namespace Rallo\ContaoTheme\Twig;

use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Rallo\ContaoTheme\Controller\Backend\RctConfigController;
use Rallo\ContaoTheme\RctBundle;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RctTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /** Bedien-Texte des Themes (Tooltips, Screenreader) – Deutsch auf deutschen Seiten, sonst Englisch */
    public const I18N = [
        'fullscreen' => ['de' => 'Vollbild umschalten', 'en' => 'Toggle fullscreen'],
        'login' => ['de' => 'Anmelden', 'en' => 'Log in'],
        'nav_open' => ['de' => 'Navigation öffnen', 'en' => 'Open navigation'],
        'menu_toggle' => ['de' => 'Menü umschalten', 'en' => 'Toggle menu'],
        'info_panel' => ['de' => 'Info-Panel', 'en' => 'Info panel'],
        'search_open' => ['de' => 'Suche öffnen', 'en' => 'Open search'],
        'close' => ['de' => 'Schließen', 'en' => 'Close'],
        'img_next' => ['de' => 'Nächstes Bild', 'en' => 'Next image'],
        'img_prev' => ['de' => 'Vorheriges Bild', 'en' => 'Previous image'],
        'gallery_filter' => ['de' => 'Galerie-Filter', 'en' => 'Gallery filter'],
        'nav_main' => ['de' => 'Hauptnavigation', 'en' => 'Main navigation'],
        'menu_close' => ['de' => 'Menü schließen', 'en' => 'Close menu'],
        'nav_side' => ['de' => 'Nebennavigation', 'en' => 'Secondary navigation'],
        'privacy' => ['de' => 'Datenschutzeinstellungen', 'en' => 'Privacy settings'],
        'to_top' => ['de' => 'Zurück nach oben', 'en' => 'Back to top'],
        'archive_pager' => ['de' => 'Seitennavigation innerhalb des Archivs', 'en' => 'Pagination within the archive'],
        'all' => ['de' => 'Alle', 'en' => 'All'],
    ];

    public function __construct(private readonly Connection $db, private readonly RequestStack $requestStack) {}

    public function getGlobals(): array
    {
        return ['rct_version' => RctBundle::VERSION];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('rct_themes', [$this, 'getAllowedThemes']),
            new TwigFunction('rct_t', [$this, 'translate']),
        ];
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

    /** rct_t('key'): Text in der Sprache der aktuellen Seite (de* → Deutsch, alles andere → Englisch) */
    public function translate(string $key): string
    {
        $entry = self::I18N[$key] ?? null;
        if ($entry === null) {
            return $key;
        }

        return str_starts_with($this->language(), 'de') ? $entry['de'] : $entry['en'];
    }

    private function language(): string
    {
        $page = $GLOBALS['objPage'] ?? null;
        if ($page instanceof PageModel && $page->language) {
            return strtolower((string) $page->language);
        }
        $request = $this->requestStack->getMainRequest();
        if ($request !== null && $request->attributes->get('pageModel') instanceof PageModel) {
            return strtolower((string) $request->attributes->get('pageModel')->language);
        }

        return 'de';   // ohne Seitenkontext (Backend-Vorschau u. Ä.) wie bisher Deutsch
    }

    /**
     * Liefert ein assoziatives Array slug => label aller im BE freigegebenen
     * Themes. Single Source of Truth ist RctConfigController::ALL_THEMES;
     * tl_rct_config.rct_allowed_themes (CSV der Slugs) filtert die Liste.
     * Leer/ungesetzt = alle Themes (abwaertskompatibel zum Hardcode-Stand).
     */
    public function getAllowedThemes(): array
    {
        $allowed = '';
        try {
            $tables = $this->db->createSchemaManager()->listTableNames();
            if (in_array('tl_rct_config', $tables, true)) {
                $allowed = (string) ($this->db->fetchOne(
                    "SELECT rct_allowed_themes FROM tl_rct_config WHERE id = 1"
                ) ?: '');
            }
        } catch (\Throwable $e) {
            // DB-Fehler oder Tabelle noch nicht da -> Default = alle Themes
        }

        if ($allowed === '') {
            return RctConfigController::ALL_THEMES;
        }

        $slugs = array_filter(array_map('trim', explode(',', $allowed)));
        // Reihenfolge aus ALL_THEMES beibehalten, nicht aus der CSV
        return array_intersect_key(RctConfigController::ALL_THEMES, array_flip($slugs));
    }
}
