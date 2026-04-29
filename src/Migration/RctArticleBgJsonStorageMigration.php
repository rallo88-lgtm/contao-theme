<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * RCT-BG-Palette für tl_article (rct_article_bg_color/alpha/blur).
 * Migriert nur Rows mit Nicht-Default-Werten.
 */
class RctArticleBgJsonStorageMigration extends AbstractMigration
{
    private const FIELDS = [
        'rct_article_bg_color',
        'rct_article_bg_alpha',
        'rct_article_blur',
    ];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – tl_article BG-Palette to jsonData column';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_article', $tables)) {
            return false;
        }
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_article');

        $existing = array_filter(self::FIELDS, fn($f) => isset($columns[strtolower($f)]));
        if (empty($existing)) {
            return false;
        }

        // Filter: bg_color != '' OR bg_alpha != '100' OR blur != '0'
        $whereParts = [];
        foreach ($existing as $f) {
            if ($f === 'rct_article_bg_alpha') {
                $whereParts[] = "$f != '100'";
            } elseif ($f === 'rct_article_blur') {
                $whereParts[] = "$f != '0'";
            } else {
                $whereParts[] = "$f != ''";
            }
        }
        $whereNonDefault = '(' . implode(' OR ', $whereParts) . ')';

        if (!isset($columns['jsondata'])) {
            return (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM tl_article WHERE $whereNonDefault"
            ) > 0;
        }

        $unmigrated = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_article
             WHERE $whereNonDefault
             AND (jsonData IS NULL OR JSON_EXTRACT(jsonData, '$.rct_article_bg_color') IS NULL)"
        );
        return $unmigrated > 0;
    }

    public function run(): MigrationResult
    {
        $now = time();

        $columns = $this->db->createSchemaManager()->listTableColumns('tl_article');
        if (!isset($columns['jsondata'])) {
            $this->db->executeStatement(
                "ALTER TABLE tl_article ADD COLUMN jsonData LONGTEXT NULL DEFAULT NULL"
            );
        }

        $existingFields = array_filter(
            self::FIELDS,
            fn($f) => isset($columns[strtolower($f)])
        );
        if (empty($existingFields)) {
            return $this->createResult(true, 'tl_article BG: keine Felder zu migrieren.');
        }

        $cols = implode(', ', $existingFields);

        $whereParts = [];
        foreach ($existingFields as $f) {
            if ($f === 'rct_article_bg_alpha') {
                $whereParts[] = "$f != '100'";
            } elseif ($f === 'rct_article_blur') {
                $whereParts[] = "$f != '0'";
            } else {
                $whereParts[] = "$f != ''";
            }
        }
        $whereNonDefault = '(' . implode(' OR ', $whereParts) . ')';

        $rows = $this->db->fetchAllAssociative(
            "SELECT id, jsonData, $cols FROM tl_article WHERE $whereNonDefault"
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
                "UPDATE tl_article SET jsonData = ?, tstamp = ? WHERE id = ?",
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
            sprintf('tl_article BG → jsonData: %d Article(s) migriert.', $touched)
        );
    }
}
