<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Frischinstall: legt das RCT-Theme komplett an — alle Module, Custom
 * Sections und 5 Layouts (Standard, Nav Left, Nav Right, Nav Top, Classic).
 *
 * shouldRun() prueft ueber rct_nav_toggle in tl_module ob bereits
 * gelaufen — laeuft daher auf bestehenden Instanzen NICHT erneut.
 * Strukturelle Aenderungen fuer existing Instanzen kommen ueber separate
 * Update-Migrations (siehe RctClassicLayoutMigration, RctBottomRightMigration).
 */
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
        $search = $this->insertModule($themePid, $now, 'RCT Suche',              'search',     $headline);
        $login  = $this->insertModule($themePid, $now, 'RCT Frontend Login',     'login',      $headline);

        // 3. Bottom-HTML-Module (Bottom-Bar-Inhalte)
        $copyHtml  = '<div class="bottom-copy"><p class="rct-footer-copy">&copy; {{date::Y}} RCT v{{rct_version}} &mdash; Powered by <a href="https://contao.org" target="_blank" rel="noopener">Contao</a></p></div>';
        $legalHtml = '<div class="bottom-legal-links"><a href="/datenschutz">Datenschutz</a><a href="/impressum">Impressum</a><a href="/kontakt">Kontakt</a></div>';
        $bottomCopy = $this->insertHtmlModule($themePid, $now, 'RCT Bottom Copy',   'html', $headline, $copyHtml);
        $legal      = $this->insertHtmlModule($themePid, $now, 'RCT Legal Links',   'html', $headline, $legalHtml);
        $btmRight   = $this->insertHtmlModule($themePid, $now, 'RCT Bottom Right',  'html', $headline, '<div class="bottom-right">&nbsp;</div>');
        $classicBtm = $this->insertHtmlModule($themePid, $now, 'RCT Classic Bottom-Links', 'html', $headline, $legalHtml);

        // 4. Logo-Module (Sidebar + Header + Classic-Variante + Classic-Menu)
        $logoL   = $this->insertLogoModule($themePid, $now, 'Logo Sidebar Links',  'sidebar', $headline);
        $logoR   = $this->insertLogoModule($themePid, $now, 'Logo Sidebar Rechts', 'sidebar', $headline);
        $logoH   = $this->insertLogoModule($themePid, $now, 'Logo Header',         'header',  $headline);
        $logoCl  = $this->insertLogoModule($themePid, $now, 'Logo Classic',        'sidebar', $headline);
        $logoClM = $this->insertLogoModule($themePid, $now, 'Logo Classic - Menu', 'sidebar', $headline);

        // 5. RCT Header-Controls + Bottom-Controls
        $lang     = $this->insertModule($themePid, $now, 'RCT Sprachschalter',         'rct_language_switcher', $headline);
        $fs       = $this->insertModule($themePid, $now, 'RCT Fullscreen Toggle',      'rct_fullscreen_toggle', $headline);
        $navT     = $this->insertModule($themePid, $now, 'RCT Nav Toggle',             'rct_nav_toggle',        $headline);
        $sT       = $this->insertModule($themePid, $now, 'RCT Suche Toggle',           'rct_search_toggle',     $headline);
        $loginT   = $this->insertModule($themePid, $now, 'RCT Anmeldung Toggle',       'rct_login_toggle',      $headline);
        $rightT   = $this->insertModule($themePid, $now, 'RCT Right Toggle',           'rct_right_toggle',      $headline);
        $layS     = $this->insertModule($themePid, $now, 'RCT Layout Switcher',        'rct_layout_switcher',   $headline);
        $themeS   = $this->insertModule($themePid, $now, 'RCT Theme Switcher',         'rct_theme_switcher',    $headline);
        $btmCtl   = $this->insertModule($themePid, $now, 'RCT Bottom Controls',        'rct_bottom_controls',   $headline);
        $classicST= $this->insertModule($themePid, $now, 'RCT Classic Suche Toggle',   'rct_search_toggle',     $headline);

        // Header-Right Reihenfolge — wie auf rct.-Layout 1 (NavToggle fuehrt)
        $headerRight = [
            ['mod' => $navT,   'col' => 'header_right', 'enable' => '1'],
            ['mod' => $fs,     'col' => 'header_right', 'enable' => '1'],
            ['mod' => $sT,     'col' => 'header_right', 'enable' => '1'],
            ['mod' => $loginT, 'col' => 'header_right', 'enable' => '1'],
            ['mod' => $layS,   'col' => 'header_right', 'enable' => '1'],
            ['mod' => $themeS, 'col' => 'header_right', 'enable' => '1'],
            ['mod' => $rightT, 'col' => 'header_right', 'enable' => '1'],
            ['mod' => $lang,   'col' => 'header_right', 'enable' => '1'],
        ];

        $headerLeft = [
            ['mod' => $logoH,  'col' => 'header_left', 'enable' => '1'],
            ['mod' => $crumb,  'col' => 'header_left', 'enable' => '1'],
        ];

        // Sidebar-Logo + -Footer Sections (gilt fuer Standard/Nav-Layouts)
        $sidebarFooters = [
            ['mod' => $logoL,  'col' => 'left_logo',    'enable' => '1'],
            ['mod' => $logoR,  'col' => 'right_logo',   'enable' => '1'],
            ['mod' => $legal,  'col' => 'left_bottom',  'enable' => '1'],
            ['mod' => $legal,  'col' => 'right_bottom', 'enable' => '1'],
        ];

        // Bottom-Bar-Mappings (bottom_left/content/right)
        $bottomBar = [
            ['mod' => $bottomCopy, 'col' => 'bottom_left',    'enable' => '1'],
            ['mod' => $legal,      'col' => 'bottom_content', 'enable' => '1'],
            ['mod' => $btmCtl,     'col' => 'bottom_content', 'enable' => '1'],
            ['mod' => $btmRight,   'col' => 'bottom_right',   'enable' => '1'],
        ];

        $stdSections     = $this->buildStandardSections();
        $classicSections = $this->buildClassicSections();

        // 6. Layouts

        // Standard (left + right Sidebar)
        $this->insertLayout($themePid, $now, 'RCT - Standard', '3cl', array_merge(
            [
                ['mod' => $nav,    'col' => 'left',  'enable' => '1'],
                ['mod' => $nav,    'col' => 'right', 'enable' => '1'],
                ['mod' => '0',     'col' => 'main',  'enable' => '1'],
                ['mod' => $footer, 'col' => 'footer','enable' => '1'],
                ['mod' => $navTop, 'col' => 'navbar','enable' => '1'],
            ],
            $headerLeft, $headerRight, $sidebarFooters, $bottomBar
        ), $stdSections);

        // Nav Left
        $this->insertLayout($themePid, $now, 'RCT - Nav Left', '3cl', array_merge(
            [
                ['mod' => $nav,    'col' => 'left',  'enable' => '1'],
                ['mod' => '0',     'col' => 'right', 'enable' => '1'],
                ['mod' => '0',     'col' => 'main',  'enable' => '1'],
                ['mod' => $footer, 'col' => 'footer','enable' => '1'],
                ['mod' => $navTop, 'col' => 'navbar','enable' => '1'],
            ],
            $headerLeft, $headerRight, $sidebarFooters, $bottomBar
        ), $stdSections);

        // Nav Right
        $this->insertLayout($themePid, $now, 'RCT - Nav Right', '3cl', array_merge(
            [
                ['mod' => '0',     'col' => 'left',  'enable' => '1'],
                ['mod' => $nav,    'col' => 'right', 'enable' => '1'],
                ['mod' => '0',     'col' => 'main',  'enable' => '1'],
                ['mod' => $footer, 'col' => 'footer','enable' => '1'],
                ['mod' => $navTop, 'col' => 'navbar','enable' => '1'],
            ],
            $headerLeft, $headerRight, $sidebarFooters, $bottomBar
        ), $stdSections);

        // Nav Top
        $this->insertLayout($themePid, $now, 'RCT - Nav Top', '2cll', array_merge(
            [
                ['mod' => $nav,    'col' => 'left',  'enable' => '1'],
                ['mod' => '0',     'col' => 'main',  'enable' => '1'],
                ['mod' => $footer, 'col' => 'footer','enable' => '1'],
                ['mod' => $navTop, 'col' => 'navbar','enable' => '1'],
            ],
            $headerLeft, $headerRight, $sidebarFooters, $bottomBar
        ), $stdSections);

        // Classic (mobile-drawer + edge-to-edge Navbar)
        $this->insertLayout($themePid, $now, 'RCT - Classic', '2cll', [
            ['mod' => $logoClM,    'col' => 'left',                  'enable' => '1'],
            ['mod' => $nav,        'col' => 'left',                  'enable' => '1'],
            ['mod' => '0',         'col' => 'main',                  'enable' => '1'],
            ['mod' => $logoCl,     'col' => 'classic_navbar_left',   'enable' => '1'],
            ['mod' => $navTop,     'col' => 'classic_navbar_right',  'enable' => '1'],
            ['mod' => $classicST,  'col' => 'classic_navbar_right',  'enable' => '1'],
            ['mod' => $logoH,      'col' => 'classic_header',        'enable' => '1'],
            ['mod' => $navT,       'col' => 'classic_header',        'enable' => '1'],
            ['mod' => $sT,         'col' => 'classic_header',        'enable' => '1'],
            ['mod' => $bottomCopy, 'col' => 'bottom_left',           'enable' => '1'],
            ['mod' => $legal,      'col' => 'bottom_content',        'enable' => '1'],
            ['mod' => $btmCtl,     'col' => 'bottom_content',        'enable' => '1'],
            ['mod' => $btmRight,   'col' => 'bottom_right',          'enable' => '1'],
            ['mod' => $classicBtm, 'col' => 'classic_bottom',        'enable' => '1'],
        ], $classicSections);

        return $this->createResult(true, 'RCT default modules and layouts created successfully (5 Layouts incl. Classic).');
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

    private function insertHtmlModule(int $pid, int $now, string $name, string $type, string $headline, string $html): string
    {
        $this->db->executeStatement(
            "INSERT INTO tl_module (pid, tstamp, name, type, headline, html) VALUES (?, ?, ?, ?, ?, ?)",
            [$pid, $now, $name, $type, $headline, $html]
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

    private function buildStandardSections(): string
    {
        return serialize([
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
    }

    private function buildClassicSections(): string
    {
        return serialize([
            ['title' => 'Classic Bottom Logo',  'id' => 'classic_bottom_logo',  'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Classic Header',       'id' => 'classic_header',       'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Classic Navbar Left',  'id' => 'classic_navbar_left',  'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Classic Navbar Right', 'id' => 'classic_navbar_right', 'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Classic Bottom',       'id' => 'classic_bottom',       'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Links',         'id' => 'bottom_left',          'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Mitte',         'id' => 'bottom_content',       'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Rechts',        'id' => 'bottom_right',         'template' => 'block_section', 'position' => 'manual'],
        ]);
    }
}
