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
        // Alle Custom Sections die RCT-FE-Module + Bottom-Refactor + Header-Controls brauchen.
        // Modul-Zuordnungen kommen aus RctModuleMigration + RctBottomLayoutMigration —
        // hier nur die Section-Definitionen.
        $defaultSections = serialize([
            ['title' => 'Bottom',                'id' => 'bottom',         'template' => 'block_section', 'position' => 'main'],
            ['title' => 'Navbar',                'id' => 'navbar',         'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Logo Links',            'id' => 'left_logo',      'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Logo Rechts',           'id' => 'right_logo',     'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Sidebar Footer Links',  'id' => 'left_bottom',    'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Sidebar Footer Rechts', 'id' => 'right_bottom',   'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Header Links',          'id' => 'header_left',    'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Header Rechts',         'id' => 'header_right',   'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Links',          'id' => 'bottom_left',    'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Mitte',          'id' => 'bottom_content', 'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Rechts',         'id' => 'bottom_right',   'template' => 'block_section', 'position' => 'manual'],
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
            $this->db->executeStatement(
                'INSERT INTO tl_layout (pid, tstamp, name, template, `rows`, headerHeight, footerHeight, `cols`, widthLeft, widthRight, sections, modules, viewport, combineScripts, minifyMarkup) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$themeId, $now, $name, $template, '3rw', $emptySize, $emptySize, '3cl', $emptySize, $emptySize, $defaultSections, $defaultModules, $viewport, 1, 1],
            );
        }

        return $this->createResult(true, 'RCT Theme mit 4 Layouts wurde angelegt.');
    }
}
