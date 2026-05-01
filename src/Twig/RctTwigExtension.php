<?php

namespace Rallo\ContaoTheme\Twig;

use Doctrine\DBAL\Connection;
use Rallo\ContaoTheme\Controller\Backend\RctConfigController;
use Rallo\ContaoTheme\RctBundle;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RctTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly Connection $db) {}

    public function getGlobals(): array
    {
        return ['rct_version' => RctBundle::VERSION];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('rct_themes', [$this, 'getAllowedThemes']),
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
