<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * v1.5.3 — rct_form_header Felder ins jsonData-Storage migrieren
 *
 * Contao 5.7+ unterstützt JSON-Storage für DCA-Felder ohne 'sql'-Definition
 * (PR #8838). Diese Migration kopiert bestehende Werte aus den dedizierten
 * Spalten rct_form_header_items + rct_form_header_style nach tl_content.jsonData,
 * BEVOR Doctrine sie beim Schema-Sync droppt.
 *
 * Reihenfolge in contao:migrate: PHP-Migrations laufen ZUERST, dann erst
 * Doctrine-Schema-Sync. Damit ist der Datenverlust ausgeschlossen, solange
 * der User nicht --schema-only ruft.
 *
 * Pattern für künftige CE-Felder → JSON-Migrations.
 */
class RctFormHeaderJsonStorageMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle 1.5.3 – rct_form_header fields to jsonData column';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_content', $tables)) {
            return false;
        }
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_content');
        return isset($columns['rct_form_header_items']) || isset($columns['rct_form_header_style']);
    }

    public function run(): MigrationResult
    {
        $now = time();

        // Defensive: jsonData-Spalte sicherstellen. Contao 5.7+ legt sie selbst
        // beim Schema-Sync an, der hier aber NACH uns läuft.
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_content');
        if (!isset($columns['jsondata'])) {
            $this->db->executeStatement(
                "ALTER TABLE tl_content ADD COLUMN jsonData LONGTEXT NULL DEFAULT NULL"
            );
        }

        $rows = $this->db->fetchAllAssociative(
            "SELECT id, jsonData, rct_form_header_items, rct_form_header_style
             FROM tl_content
             WHERE type = 'rct_form_header'"
        );

        $touched = 0;
        foreach ($rows as $row) {
            $existing = json_decode((string) $row['jsonData'], true);
            if (!is_array($existing)) {
                $existing = [];
            }

            $existing['rct_form_header_items'] = (string) $row['rct_form_header_items'];
            $existing['rct_form_header_style'] = (string) $row['rct_form_header_style'];

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
            sprintf('rct_form_header → jsonData: %d Element(e) migriert.', $touched)
        );
    }
}
