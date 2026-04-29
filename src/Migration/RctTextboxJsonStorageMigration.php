<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * rct_image_textbox + rct_icon_textbox teilen sich denselben Feld-Namespace
 * (rct_itb_*). Eine Migration deckt beide CE-Typen ab.
 *
 * Migriert: 8 Felder (image_alt, icon, headline, text, style + link-Trio).
 * Bleibt als Spalte: rct_itb_image (fileTree/BINARY-UUID),
 *                    rct_itb_link_page (pageTree mit foreignKey/relation).
 */
class RctTextboxJsonStorageMigration extends AbstractMigration
{
    private const FIELDS = [
        'rct_itb_image_alt',
        'rct_itb_icon',
        'rct_itb_headline',
        'rct_itb_text',
        'rct_itb_style',
        'rct_itb_link_url',
        'rct_itb_link_label',
        'rct_itb_link_target',
    ];
    private const CE_TYPES = ['rct_image_textbox', 'rct_icon_textbox'];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – rct_image_textbox + rct_icon_textbox to jsonData column';
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
             AND (jsonData IS NULL OR JSON_EXTRACT(jsonData, '$.rct_itb_style') IS NULL)",
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
            sprintf('rct_image_textbox + rct_icon_textbox → jsonData: %d Element(e) migriert.', $touched)
        );
    }
}
