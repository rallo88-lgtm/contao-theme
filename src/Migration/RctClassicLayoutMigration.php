<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * v1.5.8 — Classic-Layout fuer bestehende Instanzen nachziehen.
 *
 * RctSetupMigration legt seit v1.5.8 das 5. Layout "RCT - Classic" + 4
 * Classic-spezifische Module mit an. Bestehende Instanzen, deren Setup
 * vor v1.5.8 lief, haben das nicht — diese Migration zieht es nach:
 *  - 4 Module (Logo Classic, Logo Classic - Menu, RCT Classic Bottom-
 *    Links, RCT Classic Suche Toggle)
 *  - Layout "RCT - Classic" mit allen Custom-Sections + Mappings
 *
 * Die Update-Migration nutzt existierende Module aus Setup/BottomLayout
 * (nav, navTop, logoH, navT, sT, bottomCopy, legal, btmCtl, btmRight)
 * fuer die Mappings — diese muessen vor dieser Migration vorhanden sein.
 *
 * Idempotent ueber Existenz von tl_layout name='RCT - Classic'.
 */
class RctClassicLayoutMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle 1.5.8 – Classic Layout + Module';
    }

    public function shouldRun(): bool
    {
        $tables = $this->db->createSchemaManager()->listTableNames();
        if (!in_array('tl_module', $tables) || !in_array('tl_layout', $tables) || !in_array('tl_theme', $tables)) {
            return false;
        }

        $themeId = (int) $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");
        if (!$themeId) {
            return false;
        }

        // Schon migriert? -> abbrechen
        $exists = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_layout WHERE pid = ? AND name = 'RCT - Classic'",
            [$themeId]
        );
        return $exists === 0;
    }

    public function run(): MigrationResult
    {
        $now      = time();
        $themeId  = (int) $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");
        $headline = serialize(['unit' => 'h2', 'value' => '']);

        $legalHtml = '<div class="bottom-legal-links"><a href="/datenschutz">Datenschutz</a><a href="/impressum">Impressum</a><a href="/kontakt">Kontakt</a></div>';

        // 1. Classic-spezifische Module anlegen (alle vier neu)
        $logoCl    = $this->insertLogoModule($themeId, $now, 'Logo Classic',        'sidebar', $headline);
        $logoClM   = $this->insertLogoModule($themeId, $now, 'Logo Classic - Menu', 'sidebar', $headline);
        $classicST = $this->insertModule($themeId, $now, 'RCT Classic Suche Toggle', 'rct_search_toggle', $headline);
        $classicBtm = $this->insertHtmlModule($themeId, $now, 'RCT Classic Bottom-Links', 'html', $headline, $legalHtml);

        // 2. Existing Module per Name+Type finden (von Setup/BottomLayoutMig)
        $nav        = $this->findModuleId($themeId, 'navigation',          'RCT Sidebar-Navigation');
        $navTop     = $this->findModuleId($themeId, 'navigation',          'RCT Header-Navigation');
        $logoH      = $this->findModuleId($themeId, 'rct_logo',            'Logo Header');
        $navT       = $this->findModuleId($themeId, 'rct_nav_toggle',      'RCT Nav Toggle');
        $sT         = $this->findModuleId($themeId, 'rct_search_toggle',   'RCT Suche Toggle');
        $bottomCopy = $this->findModuleId($themeId, 'html',                'RCT Bottom Copy');
        $legal      = $this->findModuleId($themeId, 'html',                'RCT Legal Links');
        $btmCtl     = $this->findModuleId($themeId, 'rct_bottom_controls', 'RCT Bottom Controls');
        $btmRight   = $this->findModuleId($themeId, 'html',                'RCT Bottom Right');

        // 3. Layout "RCT - Classic" anlegen (cols 2cll fuer Mobile-Drawer)
        $sections = serialize([
            ['title' => 'Classic Bottom Logo',  'id' => 'classic_bottom_logo',  'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Classic Header',       'id' => 'classic_header',       'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Classic Navbar Left',  'id' => 'classic_navbar_left',  'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Classic Navbar Right', 'id' => 'classic_navbar_right', 'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Classic Bottom',       'id' => 'classic_bottom',       'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Links',         'id' => 'bottom_left',          'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Mitte',         'id' => 'bottom_content',       'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Rechts',        'id' => 'bottom_right',         'template' => 'block_section', 'position' => 'manual'],
        ]);

        $modules = [];
        // Nur Mappings fuer Module aufnehmen, die wirklich existieren
        if ($logoClM)   $modules[] = ['mod' => $logoClM,    'col' => 'left',                  'enable' => '1'];
        if ($nav)       $modules[] = ['mod' => $nav,        'col' => 'left',                  'enable' => '1'];
        $modules[]                  = ['mod' => '0',        'col' => 'main',                  'enable' => '1'];
        if ($logoCl)    $modules[] = ['mod' => $logoCl,     'col' => 'classic_navbar_left',   'enable' => '1'];
        if ($navTop)    $modules[] = ['mod' => $navTop,     'col' => 'classic_navbar_right',  'enable' => '1'];
        if ($classicST) $modules[] = ['mod' => $classicST,  'col' => 'classic_navbar_right',  'enable' => '1'];
        if ($logoH)     $modules[] = ['mod' => $logoH,      'col' => 'classic_header',        'enable' => '1'];
        if ($navT)      $modules[] = ['mod' => $navT,       'col' => 'classic_header',        'enable' => '1'];
        if ($sT)        $modules[] = ['mod' => $sT,         'col' => 'classic_header',        'enable' => '1'];
        if ($bottomCopy)$modules[] = ['mod' => $bottomCopy, 'col' => 'bottom_left',           'enable' => '1'];
        if ($legal)     $modules[] = ['mod' => $legal,      'col' => 'bottom_content',        'enable' => '1'];
        if ($btmCtl)    $modules[] = ['mod' => $btmCtl,     'col' => 'bottom_content',        'enable' => '1'];
        if ($btmRight)  $modules[] = ['mod' => $btmRight,   'col' => 'bottom_right',          'enable' => '1'];
        if ($classicBtm)$modules[] = ['mod' => $classicBtm, 'col' => 'classic_bottom',        'enable' => '1'];

        $this->db->executeStatement(
            "INSERT INTO tl_layout (pid, tstamp, name, `rows`, cols, template, viewport, sections, modules) VALUES (?, ?, ?, '3rw', '2cll', 'fe_page', 'width=device-width,initial-scale=1.0,shrink-to-fit=no', ?, ?)",
            [$themeId, $now, 'RCT - Classic', $sections, serialize($modules)]
        );

        return $this->createResult(
            true,
            'RCT Classic-Layout angelegt: 4 Module + Layout mit ' . count($modules) . ' Mappings.'
        );
    }

    private function findModuleId(int $themeId, string $type, string $name): string
    {
        $id = $this->db->fetchOne(
            "SELECT id FROM tl_module WHERE pid = ? AND type = ? AND name = ? LIMIT 1",
            [$themeId, $type, $name]
        );
        return $id ? (string) $id : '';
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
}
