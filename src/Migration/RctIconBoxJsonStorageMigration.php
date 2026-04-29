<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * rct_icon_box: 9 Felder ins jsonData (alle außer link_page).
 *
 * rct_icon_box_link_page bleibt als int-Spalte mit foreignKey/relation —
 * Doctrine-Lazy-Load via tl_page-Relation funktioniert nur mit dedizierter
 * Spalte. Pattern wie fileTree-Felder bei rct_parallax.
 */
class RctIconBoxJsonStorageMigration extends AbstractMigration
{
    private const FIELDS = [
        'rct_icon_box_icon',
        'rct_icon_box_headline',
        'rct_icon_box_text',
        'rct_icon_box_color',
        'rct_icon_box_align',
        'rct_icon_box_style',
        'rct_icon_box_link_url',
        'rct_icon_box_link_label',
        'rct_icon_box_link_target',
    ];
    private const CE_TYPES = ['rct_icon_box'];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – rct_icon_box fields to jsonData column';
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

        $placeholders = implode(',', array_fill(0, count(self::CE_TYPES), '?'));

        if (!isset($columns['jsondata'])) {
            return (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM tl_content WHERE type IN ($placeholders)",
                self::CE_TYPES
            ) > 0;
        }

        $unmigrated = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_content
             WHERE type IN ($placeholders)
             AND (jsonData IS NULL OR JSON_EXTRACT(jsonData, '$.rct_icon_box_color') IS NULL)",
            self::CE_TYPES
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
        $cols = implode(', ', $existingFields);
        $placeholders = implode(',', array_fill(0, count(self::CE_TYPES), '?'));

        $rows = $this->db->fetchAllAssociative(
            "SELECT id, jsonData, $cols FROM tl_content WHERE type IN ($placeholders)",
            self::CE_TYPES
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
            sprintf('rct_icon_box → jsonData: %d Element(e) migriert.', $touched)
        );
    }
}
