<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * RCT Default-Setup: Theme + alle Module + alle 5 Layouts (Standard,
 * NavLeft, NavRight, NavTop, Classic). Single Source of Truth fuer das
 * RCT-Default-Setup; konsolidiert ehemals RctSetupMigration +
 * RctModuleMigration + RctThemeMigration.
 *
 * Idempotent + additiv via find-or-create pro Entitaet (Theme, jedes Modul,
 * jedes Layout, jedes Layout-Mapping). Kein Re-Run-Schaden auf
 * Bestandsinstanzen.
 *
 * shouldRun()-Marker ist EXISTENCE-BASED auf den 5 erwarteten Layouts —
 * unabhaengig davon ob ein einzelnes Modul (z.B. rct_nav_toggle) von einer
 * anderen Migration gesetzt wurde. Damit kann die Migration auch nach einem
 * frueheren Crash erneut sauber durchlaufen.
 *
 * Self-Repair fuer tl_module.jsonData: Bei Frischinstall existiert die
 * jsonData-Spalte ggf. noch nicht wenn diese PHP-Migration laeuft (Doctrine
 * legt sie erst beim finalen --with-deletes-Pass an). insertLogoModule()
 * schreibt aber bereits in jsonData → Self-Repair via ensureJsonDataColumn()
 * vor dem ersten Logo-Insert. Loest den Frischinstall-Crash der bei v1.6.0
 * zu nur 1 statt 5 Layouts gefuehrt hat.
 */
class RctSetupMigration extends AbstractMigration
{
    private const EXPECTED_LAYOUTS = [
        'RCT - Standard',
        'RCT - Nav Left',
        'RCT - Nav Right',
        'RCT - Nav Top',
        'RCT - Classic',
    ];

    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle – Default theme, modules and layouts';
    }

    public function shouldRun(): bool
    {
        $sm = $this->db->createSchemaManager();
        if (!$sm->tablesExist(['tl_theme', 'tl_module', 'tl_layout'])) {
            return false;
        }

        $themeId = (int) $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");
        if (!$themeId) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count(self::EXPECTED_LAYOUTS), '?'));
        $existing = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_layout WHERE pid = ? AND name IN ($placeholders)",
            array_merge([$themeId], self::EXPECTED_LAYOUTS)
        );
        return $existing < count(self::EXPECTED_LAYOUTS);
    }

    public function run(): MigrationResult
    {
        $now      = time();
        $headline = serialize(['unit' => 'h2', 'value' => '']);
        $emptySize = serialize(['', '', '']);

        $this->ensureJsonDataColumn('tl_module');

        $themeId = $this->ensureTheme($now);

        // ─── Modul-Inventar deklarativ ────────────────────────────────────
        // Jeder Eintrag: [name, type, extra_cols]. find-or-create per name+type+pid.

        $copyHtml  = '<div class="bottom-copy"><p class="rct-footer-copy">&copy; {{date::Y}} RCT v{{rct_version}} &mdash; Powered by <a href="https://contao.org" target="_blank" rel="noopener">Contao</a></p></div>';
        $legalHtml = '<div class="bottom-legal-links"><a href="/datenschutz">Datenschutz</a><a href="/impressum">Impressum</a><a href="/kontakt">Kontakt</a></div>';
        $bottomRightHtml = '<div class="bottom-right">&nbsp;</div>';
        $titleHtml = '<div id="headerTitle">{{page::rootPageTitle}}</div>';
        $bottomHtml = '<p class="rct-footer-copy">&copy; {{date::Y}} RCT &mdash; Powered by <a href="https://contao.org" target="_blank" rel="noopener">Contao</a></p>';

        $stdMeta = [
            'numberOfItems'   => 3,
            'queryType'       => 'and',
            'searchType'      => 'simple',
            'minKeywordLength'=> 4,
        ];

        $defs = [
            // Standard-Contao-Module
            ['RCT Sidebar-Navigation', 'navigation', $stdMeta + ['headline' => $headline, 'cssID' => serialize(['', 'rct-nav'])]],
            ['RCT Header-Navigation',  'navigation', $stdMeta + ['headline' => $headline, 'cssID' => serialize(['headerNav', 'rct-topnav']), 'navigationTpl' => 'nav_default']],
            ['RCT Breadcrumb',         'breadcrumb', $stdMeta + ['headline' => $headline, 'cssID' => serialize(['', ''])]],
            ['RCT Footer',             'html',       $stdMeta + ['headline' => $headline, 'cssID' => serialize(['', '']), 'html' => '<!-- Footer-Inhalt hier einfügen -->']],
            ['RCT Suche',              'search',     ['headline' => serialize(['unit' => 'h2', 'value' => 'Webseite durchsuchen nach:']), 'cssID' => serialize(['', '']), 'imgSize' => $emptySize, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4, 'contextLength' => serialize(['', '']), 'numberOfItems' => 3]],
            ['RCT Frontend Login',     'login',      $stdMeta + ['headline' => serialize(['unit' => 'h2', 'value' => 'Login']), 'cssID' => serialize(['rct-login', ''])]],
            ['RCT Newslist Timeline',  'newslist',   ['headline' => $headline, 'cssID' => serialize(['', '']), 'imgSize' => $emptySize, 'customTpl' => 'mod_newslist_timeline', 'news_template' => 'news_short_timeline', 'news_order' => 'order_date_asc', 'numberOfItems' => 0, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4]],

            // HTML-Module (Bottom-Bar + Header-Title + Legacy "RCT Bottom")
            ['RCT Bottom Copy',          'html', ['headline' => $headline, 'html' => $copyHtml]],
            ['RCT Legal Links',          'html', ['headline' => $headline, 'html' => $legalHtml]],
            ['RCT Bottom Right',         'html', ['headline' => $headline, 'html' => $bottomRightHtml]],
            ['RCT Classic Bottom-Links', 'html', ['headline' => $headline, 'html' => $legalHtml]],
            ['RCT Seitentitel',          'html', $stdMeta + ['headline' => $headline, 'html' => $titleHtml]],
            ['RCT Bottom',               'html', $stdMeta + ['headline' => $headline, 'cssID' => serialize(['', '']), 'html' => $bottomHtml]],

            // RCT-Header-Controls
            ['RCT Bottom Controls',  'rct_bottom_controls',   ['headline' => $headline]],
            ['RCT Sprachschalter',   'rct_language_switcher', ['headline' => $headline]],
            ['RCT Fullscreen Toggle','rct_fullscreen_toggle', ['headline' => $headline]],
            ['RCT Nav Toggle',       'rct_nav_toggle',        ['headline' => $headline]],
            ['RCT Suche Toggle',     'rct_search_toggle',     ['headline' => $headline]],
            ['RCT Anmeldung Toggle', 'rct_login_toggle',      ['headline' => $headline]],
            ['RCT Right Toggle',     'rct_right_toggle',      ['headline' => $headline]],
            ['RCT Layout Switcher',  'rct_layout_switcher',   ['headline' => $headline]],
            ['RCT Theme Switcher',   'rct_theme_switcher',    ['headline' => $headline]],
            ['RCT Classic Suche Toggle', 'rct_search_toggle', ['headline' => $headline]],
        ];

        $ids = [];
        foreach ($defs as [$name, $type, $extra]) {
            $ids[$name] = $this->insertOrFindModule($themeId, $now, $name, $type, $extra);
        }

        // Logo-Module mit jsonData (rct_logo_style)
        $ids['Logo Sidebar Links']  = $this->insertOrFindLogoModule($themeId, $now, 'Logo Sidebar Links',  'sidebar', $headline);
        $ids['Logo Sidebar Rechts'] = $this->insertOrFindLogoModule($themeId, $now, 'Logo Sidebar Rechts', 'sidebar', $headline);
        $ids['Logo Header']         = $this->insertOrFindLogoModule($themeId, $now, 'Logo Header',         'header',  $headline);
        $ids['Logo Classic']        = $this->insertOrFindLogoModule($themeId, $now, 'Logo Classic',        'sidebar', $headline);
        $ids['Logo Classic - Menu'] = $this->insertOrFindLogoModule($themeId, $now, 'Logo Classic - Menu', 'sidebar', $headline);

        // ─── Layouts find-or-create + additive Mappings ───────────────────

        $stdSections     = $this->buildStandardSections();
        $classicSections = $this->buildClassicSections();

        $headerLeft = [
            ['mod' => $ids['Logo Header'],  'col' => 'header_left', 'enable' => '1'],
            ['mod' => $ids['RCT Seitentitel'],'col' => 'header_left','enable'=> '1'],
            ['mod' => $ids['RCT Breadcrumb'],'col' => 'header_left','enable'=> '1'],
        ];

        $headerRight = [
            ['mod' => $ids['RCT Nav Toggle'],         'col' => 'header_right', 'enable' => '1'],
            ['mod' => $ids['RCT Fullscreen Toggle'],  'col' => 'header_right', 'enable' => '1'],
            ['mod' => $ids['RCT Suche Toggle'],       'col' => 'header_right', 'enable' => '1'],
            ['mod' => $ids['RCT Anmeldung Toggle'],   'col' => 'header_right', 'enable' => '1'],
            ['mod' => $ids['RCT Layout Switcher'],    'col' => 'header_right', 'enable' => '1'],
            ['mod' => $ids['RCT Theme Switcher'],     'col' => 'header_right', 'enable' => '1'],
            ['mod' => $ids['RCT Right Toggle'],       'col' => 'header_right', 'enable' => '1'],
            ['mod' => $ids['RCT Sprachschalter'],     'col' => 'header_right', 'enable' => '1'],
        ];

        $sidebarFooters = [
            ['mod' => $ids['Logo Sidebar Links'],  'col' => 'left_logo',    'enable' => '1'],
            ['mod' => $ids['Logo Sidebar Rechts'], 'col' => 'right_logo',   'enable' => '1'],
            ['mod' => $ids['RCT Legal Links'],     'col' => 'left_bottom',  'enable' => '1'],
            ['mod' => $ids['RCT Legal Links'],     'col' => 'right_bottom', 'enable' => '1'],
        ];

        $bottomBar = [
            ['mod' => $ids['RCT Bottom Copy'],     'col' => 'bottom_left',    'enable' => '1'],
            ['mod' => $ids['RCT Legal Links'],     'col' => 'bottom_content', 'enable' => '1'],
            ['mod' => $ids['RCT Bottom Controls'], 'col' => 'bottom_content', 'enable' => '1'],
            ['mod' => $ids['RCT Bottom Right'],    'col' => 'bottom_right',   'enable' => '1'],
        ];

        // Belegung von 'footer' (Layout-Block) und 'bottom' (Custom-Section)
        // ist aus dem Stand auf rct./mb./aws. uebernommen, wo RctModule additiv
        // ergaenzt hat: footer = Login + RCT Bottom (html); bottom = RCT Footer.
        $commonFooterAndBottom = [
            ['mod' => $ids['RCT Frontend Login'],    'col' => 'footer', 'enable' => '1'],
            ['mod' => $ids['RCT Bottom'],            'col' => 'footer', 'enable' => '1'],
            ['mod' => $ids['RCT Footer'],            'col' => 'bottom', 'enable' => '1'],
            ['mod' => $ids['RCT Header-Navigation'], 'col' => 'navbar', 'enable' => '1'],
        ];

        // Standard
        $this->insertOrUpdateLayout($themeId, $now, 'RCT - Standard', '3cl', 'fe_page', $stdSections, array_merge(
            [
                ['mod' => $ids['RCT Sidebar-Navigation'], 'col' => 'left',  'enable' => '1'],
                ['mod' => $ids['RCT Sidebar-Navigation'], 'col' => 'right', 'enable' => '1'],
                ['mod' => '0',                            'col' => 'main',  'enable' => '1'],
            ],
            $commonFooterAndBottom, $headerLeft, $headerRight, $sidebarFooters, $bottomBar
        ));

        // Nav Left
        $this->insertOrUpdateLayout($themeId, $now, 'RCT - Nav Left', '3cl', 'fe_page_nav_left', $stdSections, array_merge(
            [
                ['mod' => $ids['RCT Sidebar-Navigation'], 'col' => 'left',  'enable' => '1'],
                ['mod' => '0',                            'col' => 'right', 'enable' => '1'],
                ['mod' => '0',                            'col' => 'main',  'enable' => '1'],
            ],
            $commonFooterAndBottom, $headerLeft, $headerRight, $sidebarFooters, $bottomBar
        ));

        // Nav Right
        $this->insertOrUpdateLayout($themeId, $now, 'RCT - Nav Right', '3cl', 'fe_page_nav_right', $stdSections, array_merge(
            [
                ['mod' => '0',                            'col' => 'left',  'enable' => '1'],
                ['mod' => $ids['RCT Sidebar-Navigation'], 'col' => 'right', 'enable' => '1'],
                ['mod' => '0',                            'col' => 'main',  'enable' => '1'],
            ],
            $commonFooterAndBottom, $headerLeft, $headerRight, $sidebarFooters, $bottomBar
        ));

        // Nav Top
        $this->insertOrUpdateLayout($themeId, $now, 'RCT - Nav Top', '2cll', 'fe_page_nav_top', $stdSections, array_merge(
            [
                ['mod' => $ids['RCT Sidebar-Navigation'], 'col' => 'left',  'enable' => '1'],
                ['mod' => '0',                            'col' => 'main',  'enable' => '1'],
            ],
            $commonFooterAndBottom, $headerLeft, $headerRight, $sidebarFooters, $bottomBar
        ));

        // Classic — eigenes Section-Set + Mappings (kein sidebarFooters, kein
        // navbar-Mapping wie bei Standard — nutzt classic_navbar_left/right).
        $this->insertOrUpdateLayout($themeId, $now, 'RCT - Classic', '2cll', 'fe_page_classic', $classicSections, [
            ['mod' => $ids['Logo Classic - Menu'],     'col' => 'left',                  'enable' => '1'],
            ['mod' => $ids['RCT Sidebar-Navigation'],  'col' => 'left',                  'enable' => '1'],
            ['mod' => '0',                             'col' => 'main',                  'enable' => '1'],
            ['mod' => $ids['Logo Classic'],            'col' => 'classic_navbar_left',   'enable' => '1'],
            ['mod' => $ids['RCT Header-Navigation'],   'col' => 'classic_navbar_right',  'enable' => '1'],
            ['mod' => $ids['RCT Classic Suche Toggle'],'col' => 'classic_navbar_right',  'enable' => '1'],
            ['mod' => $ids['Logo Header'],             'col' => 'classic_header',        'enable' => '1'],
            ['mod' => $ids['RCT Nav Toggle'],          'col' => 'classic_header',        'enable' => '1'],
            ['mod' => $ids['RCT Suche Toggle'],        'col' => 'classic_header',        'enable' => '1'],
            ['mod' => $ids['RCT Bottom Copy'],         'col' => 'bottom_left',           'enable' => '1'],
            ['mod' => $ids['RCT Legal Links'],         'col' => 'bottom_content',        'enable' => '1'],
            ['mod' => $ids['RCT Bottom Controls'],     'col' => 'bottom_content',        'enable' => '1'],
            ['mod' => $ids['RCT Bottom Right'],        'col' => 'bottom_right',          'enable' => '1'],
            ['mod' => $ids['RCT Classic Bottom-Links'],'col' => 'classic_bottom',        'enable' => '1'],
            ['mod' => $ids['RCT Frontend Login'],      'col' => 'footer',                'enable' => '1'],
            ['mod' => $ids['RCT Bottom'],              'col' => 'footer',                'enable' => '1'],
            ['mod' => $ids['RCT Footer'],              'col' => 'bottom',                'enable' => '1'],
        ]);

        return $this->createResult(
            true,
            sprintf('RCT Default-Setup: 1 Theme, %d Module, 5 Layouts (find-or-create).', count($ids))
        );
    }

    private function ensureJsonDataColumn(string $table): void
    {
        $columns = array_keys($this->db->createSchemaManager()->listTableColumns($table));
        if (!in_array('jsondata', array_map('strtolower', $columns), true)) {
            $this->db->executeStatement(
                sprintf("ALTER TABLE %s ADD COLUMN jsonData LONGTEXT NULL DEFAULT NULL", $table)
            );
        }
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

    private function insertOrFindModule(int $themeId, int $now, string $name, string $type, array $extra): string
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM tl_module WHERE pid = ? AND name = ? AND type = ? LIMIT 1",
            [$themeId, $name, $type]
        );
        if ($existing) {
            return (string) $existing;
        }
        $row = array_merge(
            ['pid' => $themeId, 'tstamp' => $now, 'name' => $name, 'type' => $type],
            $extra
        );
        $this->db->insert('tl_module', $row);
        return (string) $this->db->lastInsertId();
    }

    private function insertOrFindLogoModule(int $themeId, int $now, string $name, string $style, string $headline): string
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM tl_module WHERE pid = ? AND name = ? AND type = 'rct_logo' LIMIT 1",
            [$themeId, $name]
        );
        if ($existing) {
            return (string) $existing;
        }
        // rct_logo_style ist seit dem JSON-Storage-Rollout (v1.5.6) keine
        // Spalte mehr — landet in jsonData. ensureJsonDataColumn() wurde
        // vorher aufgerufen, falls Frischinstall noch keine Spalte hatte.
        $jsonData = json_encode(['rct_logo_style' => $style], JSON_UNESCAPED_UNICODE);
        $this->db->executeStatement(
            "INSERT INTO tl_module (pid, tstamp, name, type, headline, jsonData) VALUES (?, ?, ?, 'rct_logo', ?, ?)",
            [$themeId, $now, $name, $headline, $jsonData]
        );
        return (string) $this->db->lastInsertId();
    }

    private function insertOrUpdateLayout(int $themeId, int $now, string $name, string $cols, string $template, string $sections, array $modules): void
    {
        $existing = $this->db->fetchAssociative(
            "SELECT id, sections, modules FROM tl_layout WHERE pid = ? AND name = ? LIMIT 1",
            [$themeId, $name]
        );

        if (!$existing) {
            $this->db->executeStatement(
                "INSERT INTO tl_layout (pid, tstamp, name, `rows`, cols, template, viewport, sections, modules) VALUES (?, ?, ?, '3rw', ?, ?, 'width=device-width,initial-scale=1.0,shrink-to-fit=no', ?, ?)",
                [$themeId, $now, $name, $cols, $template, $sections, serialize($modules)]
            );
            return;
        }

        // Layout existiert bereits — Sections + Mappings additiv ergaenzen.
        $existingSections = @unserialize((string) $existing['sections']) ?: [];
        $existingModules  = @unserialize((string) $existing['modules'])  ?: [];

        $newSections = unserialize($sections) ?: [];
        $existingSectionIds = array_column($existingSections, 'id');
        $sectionsChanged = false;
        foreach ($newSections as $newSection) {
            if (!in_array($newSection['id'], $existingSectionIds, true)) {
                $existingSections[] = $newSection;
                $sectionsChanged = true;
            }
        }

        $modulesChanged = false;
        foreach ($modules as $mapping) {
            $found = false;
            foreach ($existingModules as $em) {
                if ((string) $em['mod'] === (string) $mapping['mod'] && $em['col'] === $mapping['col']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $existingModules[] = $mapping;
                $modulesChanged = true;
            }
        }

        if ($sectionsChanged || $modulesChanged) {
            $this->db->executeStatement(
                "UPDATE tl_layout SET sections = ?, modules = ?, tstamp = ? WHERE id = ?",
                [serialize($existingSections), serialize($existingModules), $now, (int) $existing['id']]
            );
        }
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
