<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * v1.5.8 — Legacy rct_cta_layout='horizontal' → 'centered' (DCA-Default)
 *
 * Frueher gab's eine "horizontal" Layout-Option im rct_cta-CE; mittlerweile
 * ist sie aus den DCA-options entfernt (centered/banner/card). Bestehende
 * Datensaetze mit dem Legacy-Wert zeigen im BE "unbekannte Option:
 * horizontal" und im FE fehlt das Padding (kein matching CSS-Selector).
 *
 * Diese Migration mappt alle rct_cta_layout='horizontal' auf 'centered'
 * (= aktueller Default).
 *
 * Idempotent ueber Existenz von tl_content-Records mit dem Legacy-Wert.
 */
class RctCtaLayoutLegacyMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle 1.5.8 – CTA legacy layout=horizontal → centered';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_content', $tables, true)) {
            return false;
        }
        $columns = array_keys($this->db->createSchemaManager()->listTableColumns('tl_content'));
        if (!in_array('jsonData', $columns, true)) {
            return false;
        }

        $count = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_content
             WHERE type = 'rct_cta'
               AND JSON_EXTRACT(jsonData, '$.rct_cta_layout') = 'horizontal'"
        );
        return $count > 0;
    }

    public function run(): MigrationResult
    {
        $affected = $this->db->executeStatement(
            "UPDATE tl_content
             SET jsonData = JSON_SET(jsonData, '$.rct_cta_layout', 'centered'),
                 tstamp = ?
             WHERE type = 'rct_cta'
               AND JSON_EXTRACT(jsonData, '$.rct_cta_layout') = 'horizontal'",
            [time()]
        );

        return $this->createResult(
            true,
            sprintf('CTA-Legacy-Layout: %d Datensatz/Datensaetze von horizontal auf centered umgesetzt.', (int) $affected)
        );
    }
}
