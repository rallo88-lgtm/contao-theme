<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RctConfigMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – Theme config table';
    }

    public function shouldRun(): bool
    {
        return !in_array('tl_rct_config', $this->db->createSchemaManager()->listTableNames());
    }

    public function run(): MigrationResult
    {
        $this->db->executeStatement("
            CREATE TABLE tl_rct_config (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                tstamp int(10) unsigned NOT NULL DEFAULT 0,
                rct_font_body varchar(255) NOT NULL DEFAULT 'Space Grotesk',
                rct_font_mono varchar(255) NOT NULL DEFAULT 'DM Mono',
                rct_color_accent varchar(7) NOT NULL DEFAULT '#27c4f4',
                rct_color_primary varchar(7) NOT NULL DEFAULT '#2951c7',
                rct_color_primary_light varchar(7) NOT NULL DEFAULT '#27c4f4',
                rct_grad1 varchar(7) NOT NULL DEFAULT '#27c4f4',
                rct_grad2 varchar(7) NOT NULL DEFAULT '#2951c7',
                rct_grad3 varchar(7) NOT NULL DEFAULT '#1d2db2',
                rct_grad4 varchar(7) NOT NULL DEFAULT '#14054a',
                rct_sidebar_width varchar(10) NOT NULL DEFAULT '260px',
                rct_header_height varchar(10) NOT NULL DEFAULT '64px',
                rct_radius varchar(10) NOT NULL DEFAULT '0.125rem',
                rct_allowed_themes varchar(500) NOT NULL DEFAULT '',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->executeStatement(
            "INSERT INTO tl_rct_config (tstamp) VALUES (?)",
            [time()]
        );

        return $this->createResult(true, 'RCT config table created.');
    }
}
