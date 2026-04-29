<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * v1.5.4 — rct_alert Felder ins jsonData-Storage migrieren
 *
 * 5 Felder: rct_alert_type, _title, _text, _dismissible, _style.
 * Pattern wie RctFormHeaderJsonStorageMigration (siehe Memory:
 * feedback_dca_json_storage). shouldRun prüft Zielzustand via
 * JSON_EXTRACT, nicht Spalten-Existenz.
 */
class RctAlertJsonStorageMigration extends AbstractMigration
{
    private const FIELDS = [
        'rct_alert_type',
        'rct_alert_title',
        'rct_alert_text',
        'rct_alert_dismissible',
        'rct_alert_style',
    ];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle 1.5.4 – rct_alert fields to jsonData column';
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
                "SELECT COUNT(*) FROM tl_content WHERE type = 'rct_alert'"
            ) > 0;
        }

        // Zielzustand: ist rct_alert_type-Key in jsonData für jedes rct_alert-Element?
        $unmigrated = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_content
             WHERE type = 'rct_alert'
             AND (jsonData IS NULL OR JSON_EXTRACT(jsonData, '$.rct_alert_type') IS NULL)"
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

        $cols = implode(', ', self::FIELDS);
        $rows = $this->db->fetchAllAssociative(
            "SELECT id, jsonData, $cols FROM tl_content WHERE type = 'rct_alert'"
        );

        $touched = 0;
        foreach ($rows as $row) {
            $existing = json_decode((string) $row['jsonData'], true);
            if (!is_array($existing)) {
                $existing = [];
            }
            foreach (self::FIELDS as $f) {
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
            sprintf('rct_alert → jsonData: %d Element(e) migriert.', $touched)
        );
    }
}
