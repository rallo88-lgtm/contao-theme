<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RctBodyBgMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – Add rct_color_body_bg column to tl_rct_config';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_rct_config', $tables)) {
            return false;
        }
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_rct_config');
        return !array_key_exists('rct_color_body_bg', $columns);
    }

    public function run(): MigrationResult
    {
        $this->db->executeStatement(
            "ALTER TABLE tl_rct_config ADD rct_color_body_bg VARCHAR(7) NOT NULL DEFAULT '#000000'"
        );

        return $this->createResult(true, 'Added rct_color_body_bg column to tl_rct_config.');
    }
}
