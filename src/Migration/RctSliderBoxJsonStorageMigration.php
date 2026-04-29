<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RctSliderBoxJsonStorageMigration extends AbstractMigration
{
    private const FIELDS = [
        'rct_sb_image_alt',
        'rct_sb_bg_position',
        'rct_sb_overlay',
        'rct_sb_min_height',
        'rct_sb_overline',
        'rct_sb_headline',
        'rct_sb_text',
        'rct_sb_align',
        'rct_sb_link_url',
        'rct_sb_link_label',
        'rct_sb_link_target',
        'rct_sb_link_style',
        'rct_sb_link2_url',
        'rct_sb_link2_label',
        'rct_sb_link2_style',
        'rct_sb_link2_target',
    ];
    private const CE_TYPES = ['rct_slider_box'];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – rct_slider_box fields to jsonData column';
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
             AND (jsonData IS NULL OR JSON_EXTRACT(jsonData, '$.rct_sb_min_height') IS NULL)",
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
            sprintf('rct_slider_box → jsonData: %d Element(e) migriert.', $touched)
        );
    }
}
