<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RctAllowedThemesMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – Add allowed themes column';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_rct_config', $tables)) {
            return false;
        }
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_rct_config');
        return !isset($columns['rct_allowed_themes']);
    }

    public function run(): MigrationResult
    {
        $this->db->executeStatement(
            "ALTER TABLE tl_rct_config ADD COLUMN rct_allowed_themes varchar(500) NOT NULL DEFAULT ''"
        );
        return $this->createResult(true, 'RCT allowed themes column added.');
    }
}
