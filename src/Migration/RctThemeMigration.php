<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RctThemeMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->db->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_theme', 'tl_layout'])) {
            return false;
        }

        return 0 === (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_theme WHERE name = 'RCT Theme'"
        );
    }

    public function run(): MigrationResult
    {
        $now = time();

        $this->db->insert('tl_theme', [
            'tstamp' => $now,
            'name'   => 'RCT Theme',
            'author' => 'Ralph Engels',
        ]);

        $themeId = (int) $this->db->lastInsertId();

        $emptySize     = serialize(['unit' => '', 'value' => '']);
        $defaultSections = serialize([
            ['title' => 'Bottom', 'id' => 'bottom',  'template' => 'block_section', 'position' => 'main'],
            ['title' => 'Navbar', 'id' => 'navbar',  'template' => 'block_section', 'position' => 'manual'],
        ]);
        $defaultModules = serialize([
            ['mod' => '0', 'col' => 'main', 'enable' => '1'],
        ]);
        $viewport = 'width=device-width,initial-scale=1.0,shrink-to-fit=no';

        $layouts = [
            ['RCT - Standard',  'fe_page'],
            ['RCT - Nav Left',  'fe_page_nav_left'],
            ['RCT - Nav Right', 'fe_page_nav_right'],
            ['RCT - Nav Top',   'fe_page_nav_top'],
        ];

        foreach ($layouts as [$name, $template]) {
            $this->db->insert('tl_layout', [
                'pid'            => $themeId,
                'tstamp'         => $now,
                'name'           => $name,
                'template'       => $template,
                'rows'           => '3rw',
                'headerHeight'   => $emptySize,
                'footerHeight'   => $emptySize,
                'cols'           => '3cl',
                'widthLeft'      => $emptySize,
                'widthRight'     => $emptySize,
                'sections'       => $defaultSections,
                'modules'        => $defaultModules,
                'viewport'       => $viewport,
                'combineScripts' => '1',
                'minifyMarkup'   => '1',
            ]);
        }

        return $this->createResult(true, 'RCT Theme mit 4 Layouts wurde angelegt.');
    }
}
