<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * RCT-Erweiterungen ("Mods") auf Standard-Contao-CEs:
 *  - rct_slider_max_height/_mobile/_effect → sliderStart, swiper
 *  - rct_download_style → download, downloads
 *  - rct_hl_font, rct_content_color → list, text, headline
 *  - rct_text_align → headline
 *
 * Diese Felder sind global an tl_content angehängt (per PaletteManipulator),
 * aber nur in bestimmten CE-Typen sichtbar. Migration kopiert sie für
 * ALLE Rows die in mindestens einem Feld einen nicht-leeren Wert haben.
 *
 * (Plus-Effekt: rct_content_color wird auch von vielen RCT-CEs als Shared-Feld
 * benutzt — z.B. rct_slider_box, rct_chart_bars, rct_fun_box, rct_hero, rct_cta,
 * rct_timeline. Diese Werte werden auch mit migriert — kein Datenverlust.)
 */
class RctStandardModsJsonStorageMigration extends AbstractMigration
{
    private const FIELDS = [
        'rct_slider_max_height',
        'rct_slider_max_height_mobile',
        'rct_slider_effect',
        'rct_download_style',
        'rct_hl_font',
        'rct_content_color',
        'rct_text_align',
    ];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – Standard-CE Mods (rct_hl_font, rct_content_color, rct_text_align, rct_slider_*, rct_download_style) to jsonData';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_content', $tables)) {
            return false;
        }
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_content');

        // Welche der Spalten existieren noch?
        $existing = array_filter(self::FIELDS, fn($f) => isset($columns[strtolower($f)]));
        if (empty($existing)) {
            return false;
        }

        // Hat jsonData den Marker-Key (rct_content_color = wichtigstes Shared-Feld)?
        // Wenn nicht → Rows mit Nicht-Leer-Werten finden.
        $whereParts = [];
        foreach ($existing as $f) {
            $whereParts[] = "$f != ''";
        }
        $whereNonEmpty = '(' . implode(' OR ', $whereParts) . ')';

        if (!isset($columns['jsondata'])) {
            return (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM tl_content WHERE $whereNonEmpty"
            ) > 0;
        }

        $unmigrated = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_content
             WHERE $whereNonEmpty
             AND (jsonData IS NULL OR JSON_EXTRACT(jsonData, '$.rct_content_color') IS NULL)"
        );
        return $unmigrated > 0;
    }

    public function run(): MigrationResult
    {
        $now = time();

        $columns = $this->db->createSchemaManager()->listTableColumns('tl_content');
        if (!isset($columns['jsondata'])) {
            $this->db->executeStatement(
                "ALTER TABLE tl_content ADD COLUMN jsonData LONGTEXT NULL DEFAULT NULL"
            );
        }

        $existingFields = array_filter(
            self::FIELDS,
            fn($f) => isset($columns[strtolower($f)])
        );
        if (empty($existingFields)) {
            return $this->createResult(true, 'Standard-Mods: keine Felder zu migrieren.');
        }

        $cols = implode(', ', $existingFields);

        // Filter auf Rows die in mind. einem Feld einen Nicht-Leer-Wert haben
        $whereParts = [];
        foreach ($existingFields as $f) {
            $whereParts[] = "$f != ''";
        }
        $whereNonEmpty = '(' . implode(' OR ', $whereParts) . ')';

        $rows = $this->db->fetchAllAssociative(
            "SELECT id, jsonData, $cols FROM tl_content WHERE $whereNonEmpty"
        );

        $touched = 0;
        foreach ($rows as $row) {
            $existing = json_decode((string) $row['jsonData'], true);
            if (!is_array($existing)) {
                $existing = [];
            }
            foreach ($existingFields as $f) {
                $existing[$f] = (string) ($row[$f] ?? '');
            }

            $this->db->executeStatement(
                "UPDATE tl_content SET jsonData = ?, tstamp = ? WHERE id = ?",
                [
                    json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $now,
                    (int) $row['id'],
                ]
            );
            $touched++;
        }

        return $this->createResult(
            true,
            sprintf('Standard-Mods → jsonData: %d Element(e) migriert.', $touched)
        );
    }
}
