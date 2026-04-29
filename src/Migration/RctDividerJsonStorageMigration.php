<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * v1.5.5 — rct_divider Felder ins jsonData-Storage migrieren
 *
 * 13 Felder. Pattern wie RctFormHeader/RctAlert (siehe Memory:
 * feedback_dca_json_storage). shouldRun via JSON_EXTRACT auf das
 * mandatory variant-Feld.
 *
 * IconPicker-Wizard auf rct_divider_icon ist nur BE-Input-bezogen
 * (kein input_field_callback, kein save_callback) → JSON-Storage OK.
 */
class RctDividerJsonStorageMigration extends AbstractMigration
{
    private const FIELDS = [
        'rct_divider_variant',
        'rct_divider_height',
        'rct_divider_label',
        'rct_divider_index',
        'rct_divider_total',
        'rct_divider_segments',
        'rct_divider_progress',
        'rct_divider_start',
        'rct_divider_end',
        'rct_divider_status',
        'rct_divider_status_dot',
        'rct_divider_ruler_max',
        'rct_divider_icon',
    ];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle 1.5.5 – rct_divider fields to jsonData column';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_content', $tables)) {
            return false;
        }
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_content');

        $oldColumnsExist = false;
        foreach (self::FIELDS as $f) {
            if (isset($columns[strtolower($f)])) {
                $oldColumnsExist = true;
                break;
            }
        }
        if (!$oldColumnsExist) {
            return false;
        }

        if (!isset($columns['jsondata'])) {
            return (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM tl_content WHERE type = 'rct_divider'"
            ) > 0;
        }

        $unmigrated = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_content
             WHERE type = 'rct_divider'
             AND (jsonData IS NULL OR JSON_EXTRACT(jsonData, '$.rct_divider_variant') IS NULL)"
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

        // Nur die Felder selektieren die noch existieren (defensive bei
        // Teilmigrations / manueller DB-Manipulation)
        $existingFields = array_filter(
            self::FIELDS,
            fn($f) => isset($columns[strtolower($f)])
        );
        $cols = implode(', ', $existingFields);

        $rows = $this->db->fetchAllAssociative(
            "SELECT id, jsonData, $cols FROM tl_content WHERE type = 'rct_divider'"
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
            sprintf('rct_divider → jsonData: %d Element(e) migriert.', $touched)
        );
    }
}
