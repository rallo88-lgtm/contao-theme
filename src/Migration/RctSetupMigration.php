<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RctSetupMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – Default modules and layouts';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_module', $tables) || !in_array('tl_layout', $tables)) {
            return false;
        }
        // Wait until schema migration has added RCT columns
        $columns = array_keys($this->db->createSchemaManager()->listTableColumns('tl_module'));
        if (!in_array('rct_logo_style', $columns)) {
            return false;
        }
        return (int) $this->db->fetchOne("SELECT COUNT(*) FROM tl_module WHERE type = 'rct_nav_toggle'") === 0;
    }

    public function run(): MigrationResult
    {
        $now = time();
        $headline = serialize(['unit' => 'h2', 'value' => '']);

        // 1. Theme
        $themePid = $this->ensureTheme($now);

        // 2. Standard-Module (Contao-Typen)
        $nav    = $this->insertModule($themePid, $now, 'RCT Sidebar-Navigation', 'navigation', $headline);
        $navTop = $this->insertModule($themePid, $now, 'RCT Header-Navigation',  'navigation', $headline);
        $crumb  = $this->insertModule($themePid, $now, 'RCT Breadcrumb',         'breadcrumb', $headline);
        $footer = $this->insertModule($themePid, $now, 'RCT Footer',             'html',       $headline);
        $bottom = $this->insertModule($themePid, $now, 'RCT Bottom',             'html',       $headline);
        $sfooter= $this->insertModule($themePid, $now, 'RCT Sidebar Footer',     'html',       $headline);
        $search = $this->insertModule($themePid, $now, 'RCT Suche',              'search',     $headline);
        $login  = $this->insertModule($themePid, $now, 'RCT Anmeldung',          'login',      $headline);

        // 3. Logo-Module
        $logoL  = $this->insertLogoModule($themePid, $now, 'Logo Sidebar Links',  'sidebar', $headline);
        $logoR  = $this->insertLogoModule($themePid, $now, 'Logo Sidebar Rechts', 'sidebar', $headline);
        $logoH  = $this->insertLogoModule($themePid, $now, 'Logo Header',         'header',  $headline);

        // 4. RCT Header-Controls
        $lang   = $this->insertModule($themePid, $now, 'RCT Sprachschalter',   'rct_language_switcher', $headline);
        $fs     = $this->insertModule($themePid, $now, 'RCT Fullscreen Toggle','rct_fullscreen_toggle', $headline);
        $navT   = $this->insertModule($themePid, $now, 'RCT Nav Toggle',       'rct_nav_toggle',        $headline);
        $sT     = $this->insertModule($themePid, $now, 'RCT Suche Toggle',     'rct_search_toggle',     $headline);
        $loginT = $this->insertModule($themePid, $now, 'RCT Anmeldung Toggle', 'rct_login_toggle',      $headline);
        $rightT = $this->insertModule($themePid, $now, 'RCT Right Toggle',     'rct_right_toggle',      $headline);
        $layS   = $this->insertModule($themePid, $now, 'RCT Layout Switcher',  'rct_layout_switcher',   $headline);
        $themeS = $this->insertModule($themePid, $now, 'RCT Theme Switcher',   'rct_theme_switcher',    $headline);

        // Header-Right module set (Reihenfolge wie auf rct.)
        $headerRight = [
            ['mod' => $lang,   'col' => 'header_right', 'enable' => '1'],
            ['mod' => $navT,   'col' => 'header_right', 'enable' => '1'],
            ['mod' => $fs,     'col' => 'header_right', 'enable' => '1'],
            ['mod' => $sT,     'col' => 'header_right', 'enable' => '1'],
            ['mod' => $loginT, 'col' => 'header_right', 'enable' => '1'],
            ['mod' => $rightT, 'col' => 'header_right', 'enable' => '1'],
            ['mod' => $layS,   'col' => 'header_right', 'enable' => '1'],
            ['mod' => $themeS, 'col' => 'header_right', 'enable' => '1'],
        ];

        $headerLeft = [
            ['mod' => $logoH,  'col' => 'header_left', 'enable' => '1'],
            ['mod' => $crumb,  'col' => 'header_left', 'enable' => '1'],
        ];

        $sections = $this->buildSections();

        // 5. Layouts
        $this->insertLayout($themePid, $now, 'RCT - Standard', '3cl', array_merge(
            [
                ['mod' => $nav,    'col' => 'left',        'enable' => '1'],
                ['mod' => $nav,    'col' => 'right',       'enable' => '1'],
                ['mod' => '0',     'col' => 'main',        'enable' => '1'],
                ['mod' => $footer, 'col' => 'footer',      'enable' => '1'],
                ['mod' => $bottom, 'col' => 'bottom',      'enable' => '1'],
                ['mod' => $navTop, 'col' => 'navbar',      'enable' => '1'],
                ['mod' => $logoL,  'col' => 'left_logo',   'enable' => '1'],
                ['mod' => $logoR,  'col' => 'right_logo',  'enable' => '1'],
                ['mod' => $sfooter,'col' => 'left_bottom', 'enable' => '1'],
                ['mod' => $sfooter,'col' => 'right_bottom','enable' => '1'],
            ],
            $headerLeft, $headerRight
        ), $sections);

        $this->insertLayout($themePid, $now, 'RCT - Nav Left', '3cl', array_merge(
            [
                ['mod' => $nav,    'col' => 'left',        'enable' => '1'],
                ['mod' => '0',     'col' => 'right',       'enable' => '1'],
                ['mod' => '0',     'col' => 'main',        'enable' => '1'],
                ['mod' => $footer, 'col' => 'footer',      'enable' => '1'],
                ['mod' => $bottom, 'col' => 'bottom',      'enable' => '1'],
                ['mod' => $navTop, 'col' => 'navbar',      'enable' => '1'],
                ['mod' => $logoL,  'col' => 'left_logo',   'enable' => '1'],
                ['mod' => $logoR,  'col' => 'right_logo',  'enable' => '1'],
                ['mod' => $sfooter,'col' => 'left_bottom', 'enable' => '1'],
                ['mod' => $sfooter,'col' => 'right_bottom','enable' => '1'],
            ],
            $headerLeft, $headerRight
        ), $sections);

        $this->insertLayout($themePid, $now, 'RCT - Nav Right', '3cl', array_merge(
            [
                ['mod' => '0',     'col' => 'left',        'enable' => '1'],
                ['mod' => $nav,    'col' => 'right',       'enable' => '1'],
                ['mod' => '0',     'col' => 'main',        'enable' => '1'],
                ['mod' => $footer, 'col' => 'footer',      'enable' => '1'],
                ['mod' => $bottom, 'col' => 'bottom',      'enable' => '1'],
                ['mod' => $navTop, 'col' => 'navbar',      'enable' => '1'],
                ['mod' => $logoL,  'col' => 'left_logo',   'enable' => '1'],
                ['mod' => $logoR,  'col' => 'right_logo',  'enable' => '1'],
                ['mod' => $sfooter,'col' => 'left_bottom', 'enable' => '1'],
                ['mod' => $sfooter,'col' => 'right_bottom','enable' => '1'],
            ],
            $headerLeft, $headerRight
        ), $sections);

        $this->insertLayout($themePid, $now, 'RCT - Nav Top', '2cll', array_merge(
            [
                ['mod' => $nav,    'col' => 'left',        'enable' => '1'],
                ['mod' => '0',     'col' => 'main',        'enable' => '1'],
                ['mod' => $footer, 'col' => 'footer',      'enable' => '1'],
                ['mod' => $bottom, 'col' => 'bottom',      'enable' => '1'],
                ['mod' => $navTop, 'col' => 'navbar',      'enable' => '1'],
                ['mod' => $logoL,  'col' => 'left_logo',   'enable' => '1'],
                ['mod' => $logoR,  'col' => 'right_logo',  'enable' => '1'],
                ['mod' => $sfooter,'col' => 'left_bottom', 'enable' => '1'],
                ['mod' => $sfooter,'col' => 'right_bottom','enable' => '1'],
            ],
            $headerLeft, $headerRight
        ), $sections);

        return $this->createResult(true, 'RCT default modules and layouts created successfully.');
    }

    private function ensureTheme(int $now): int
    {
        $id = $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");
        if ($id) return (int) $id;

        $this->db->executeStatement(
            "INSERT INTO tl_theme (tstamp, name, author) VALUES (?, ?, ?)",
            [$now, 'RCT Theme', 'RCT Bundle']
        );
        return (int) $this->db->lastInsertId();
    }

    private function insertModule(int $pid, int $now, string $name, string $type, string $headline): string
    {
        $this->db->executeStatement(
            "INSERT INTO tl_module (pid, tstamp, name, type, headline) VALUES (?, ?, ?, ?, ?)",
            [$pid, $now, $name, $type, $headline]
        );
        return (string) $this->db->lastInsertId();
    }

    private function insertLogoModule(int $pid, int $now, string $name, string $style, string $headline): string
    {
        $this->db->executeStatement(
            "INSERT INTO tl_module (pid, tstamp, name, type, headline, rct_logo_style) VALUES (?, ?, ?, 'rct_logo', ?, ?)",
            [$pid, $now, $name, $headline, $style]
        );
        return (string) $this->db->lastInsertId();
    }

    private function insertLayout(int $pid, int $now, string $name, string $cols, array $modules, string $sections): void
    {
        $this->db->executeStatement(
            "INSERT INTO tl_layout (pid, tstamp, name, `rows`, cols, template, viewport, sections, modules) VALUES (?, ?, ?, '3rw', ?, 'fe_page', 'width=device-width,initial-scale=1.0,shrink-to-fit=no', ?, ?)",
            [$pid, $now, $name, $cols, $sections, serialize($modules)]
        );
    }

    private function buildSections(): string
    {
        return serialize([
            ['title' => 'Bottom',               'id' => 'bottom',       'template' => 'block_section', 'position' => 'main'],
            ['title' => 'Navbar',               'id' => 'navbar',       'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Logo Links',           'id' => 'left_logo',    'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Logo Rechts',          'id' => 'right_logo',   'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Sidebar Footer Links', 'id' => 'left_bottom',    'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Sidebar Footer Rechts','id' => 'right_bottom',   'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Header Links',         'id' => 'header_left',    'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Header Rechts',        'id' => 'header_right',   'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Links',         'id' => 'bottom_left',    'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Mitte',         'id' => 'bottom_content', 'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Rechts',        'id' => 'bottom_right',   'template' => 'block_section', 'position' => 'manual'],
        ]);
    }
}
