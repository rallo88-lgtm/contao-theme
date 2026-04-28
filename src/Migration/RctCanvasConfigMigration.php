<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RctCanvasConfigMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – Canvas config columns';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_rct_config', $tables)) {
            return false;
        }
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_rct_config');
        return !isset($columns['rct_canvas_enabled']);
    }

    public function run(): MigrationResult
    {
        $this->db->executeStatement("
            ALTER TABLE tl_rct_config
                ADD rct_canvas_enabled  char(1) NOT NULL DEFAULT '1',
                ADD rct_dots_enabled    char(1) NOT NULL DEFAULT '1',
                ADD rct_aurora_speed    varchar(5) NOT NULL DEFAULT '1.0'
        ");

        return $this->createResult(true, 'RCT canvas config columns added.');
    }
}
