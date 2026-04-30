<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * RCT-Felder in tl_module ins jsonData migrieren.
 * Migriert: rct_languages, rct_logo_style/url/alt/hide_mobile, rct_visibility.
 * Bleibt als Spalte: rct_logo_image, rct_logo_image_mobile (fileTree BINARY).
 */
class RctModuleFieldsJsonStorageMigration extends AbstractMigration
{
    private const FIELDS = [
        'rct_languages',
        'rct_logo_style',
        'rct_logo_url',
        'rct_logo_alt',
        'rct_logo_hide_mobile',
        'rct_visibility',
    ];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – tl_module RCT fields to jsonData column';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_module', $tables)) {
            return false;
        }
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_module');

        $existing = array_filter(self::FIELDS, fn($f) => isset($columns[strtolower($f)]));
        if (empty($existing)) {
            return false;
        }

        // Filter: alle Default-Werte sind '' bis auf rct_logo_style ('sidebar')
        // und rct_logo_url ('/').
        $whereParts = [];
        foreach ($existing as $f) {
            if ($f === 'rct_logo_style') {
                $whereParts[] = "$f != 'sidebar'";
            } elseif ($f === 'rct_logo_url') {
                $whereParts[] = "$f != '/'";
            } else {
                $whereParts[] = "$f != ''";
            }
        }
        $whereNonDefault = '(' . implode(' OR ', $whereParts) . ')';

        if (!isset($columns['jsondata'])) {
            return (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM tl_module WHERE $whereNonDefault"
            ) > 0;
        }

        $unmigrated = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_module
             WHERE $whereNonDefault
             AND (jsonData IS NULL OR JSON_EXTRACT(jsonData, '$.rct_visibility') IS NULL)"
        );
        return $unmigrated > 0;
    }

    public function run(): MigrationResult
    {
        $now = time();

        $columns = $this->db->createSchemaManager()->listTableColumns('tl_module');
        if (!isset($columns['jsondata'])) {
            $this->db->executeStatement(
                "ALTER TABLE tl_module ADD COLUMN jsonData LONGTEXT NULL DEFAULT NULL"
            );
        }

        $existingFields = array_filter(
            self::FIELDS,
            fn($f) => isset($columns[strtolower($f)])
        );
        if (empty($existingFields)) {
            return $this->createResult(true, 'tl_module: keine Felder zu migrieren.');
        }

        $cols = implode(', ', $existingFields);

        $whereParts = [];
        foreach ($existingFields as $f) {
            if ($f === 'rct_logo_style') {
                $whereParts[] = "$f != 'sidebar'";
            } elseif ($f === 'rct_logo_url') {
                $whereParts[] = "$f != '/'";
            } else {
                $whereParts[] = "$f != ''";
            }
        }
        $whereNonDefault = '(' . implode(' OR ', $whereParts) . ')';

        $rows = $this->db->fetchAllAssociative(
            "SELECT id, jsonData, $cols FROM tl_module WHERE $whereNonDefault"
        );

        $touched = 0;
        foreach ($rows as $row) {
            $existing = json_decode((string) $row['jsonData'], true);
            if (!is_array($existing)) {
                $existing = [];
            }
            // Alle FIELDS mit Default '' setzen, auch wenn keine Source-Spalte existiert.
            // Sonst bleibt shouldRun immer true (z.B. rct_visibility hat keine Spalte → wird
            // nie ins jsonData geschrieben → JSON_EXTRACT IS NULL → Endlos-Loop).
            foreach (self::FIELDS as $f) {
                if (in_array($f, $existingFields, true)) {
                    $existing[$f] = (string) ($row[$f] ?? '');
                } elseif (!isset($existing[$f])) {
                    $existing[$f] = '';
                }
            }

            $this->db->executeStatement(
                "UPDATE tl_module SET jsonData = ?, tstamp = ? WHERE id = ?",
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
            sprintf('tl_module RCT → jsonData: %d Modul(e) migriert.', $touched)
        );
    }
}
