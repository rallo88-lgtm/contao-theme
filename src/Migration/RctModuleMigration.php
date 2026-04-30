<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt alle 23 Default-RCT-Module an + ordnet sie den Custom-Sections der
 * 4 RCT-Layouts zu.
 *
 * Strategie: idempotent + additiv. `insertOrFind` prüft pro Modul ob ein
 * Eintrag mit gleichem name+type+pid schon existiert (kein Duplikat).
 * Layout-Mappings werden additiv ergänzt (gleiches Pattern wie
 * RctBottomLayoutMigration) — niemals existing Mappings überschreiben.
 *
 * shouldRun() prüft auf das Vorhandensein von 'RCT Sidebar-Navigation' UND
 * 'rct_theme_switcher'. Fehlt eines davon, läuft die Migration und ergänzt
 * additiv was fehlt. Auf bestehenden Installs (rct./aws./mb.) wo beides
 * manuell angelegt wurde: shouldRun=false, kein Eingriff.
 */
class RctModuleMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function getName(): string
    {
        return 'RCT Bundle – Default modules and layout mappings';
    }

    public function shouldRun(): bool
    {
        $sm = $this->db->createSchemaManager();
        if (!$sm->tablesExist(['tl_module', 'tl_theme', 'tl_layout'])) {
            return false;
        }

        $themeId = (int) $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");
        if (!$themeId) {
            return false;
        }

        // Wenn beide Schlüssel-Module existieren ist das Setup vollständig
        $hasNav = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_module WHERE pid = ? AND type = 'navigation' AND name = 'RCT Sidebar-Navigation'",
            [$themeId]
        ) > 0;
        $hasThemeS = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_module WHERE pid = ? AND type = 'rct_theme_switcher'",
            [$themeId]
        ) > 0;

        return !$hasNav || !$hasThemeS;
    }

    public function run(): MigrationResult
    {
        $now     = time();
        $themeId = (int) $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");

        $headline  = fn(string $v = '') => serialize(['unit' => 'h2', 'value' => $v]);
        $cssId     = fn(string $id = '', string $cls = '') => serialize([$id, $cls]);
        $emptySize = serialize(['', '', '']);

        // ─── Module idempotent anlegen via name+type-Lookup ────────────────
        $insertOrFind = function (string $name, string $type, array $extra = []) use ($themeId, $now): int {
            $existing = $this->db->fetchOne(
                "SELECT id FROM tl_module WHERE pid = ? AND name = ? AND type = ? LIMIT 1",
                [$themeId, $name, $type]
            );
            if ($existing) {
                return (int) $existing;
            }
            $row = array_merge(
                ['pid' => $themeId, 'tstamp' => $now, 'name' => $name, 'type' => $type],
                $extra
            );
            $this->db->insert('tl_module', $row);
            return (int) $this->db->lastInsertId();
        };

        $stdMeta = [
            'numberOfItems' => 3,
            'queryType' => 'and',
            'searchType' => 'simple',
            'minKeywordLength' => 4,
        ];

        // 9 Standard-Module
        $nav = $insertOrFind('RCT Sidebar-Navigation', 'navigation', array_merge($stdMeta, [
            'headline' => $headline(), 'cssID' => $cssId('', 'rct-nav'),
        ]));
        $crumb = $insertOrFind('RCT Breadcrumb', 'breadcrumb', array_merge($stdMeta, [
            'headline' => $headline(), 'cssID' => $cssId(),
        ]));
        $title = $insertOrFind('RCT Seitentitel', 'html', array_merge($stdMeta, [
            'headline' => $headline(),
            'html' => '<div id="headerTitle">{{page::rootPageTitle}}</div>',
        ]));
        $footer = $insertOrFind('RCT Footer', 'html', array_merge($stdMeta, [
            'headline' => $headline(), 'cssID' => $cssId(),
            'html' => '<!-- Footer-Inhalt hier einfügen -->',
        ]));
        $headerNav = $insertOrFind('RCT Header-Navigation', 'navigation', array_merge($stdMeta, [
            'headline' => $headline(), 'cssID' => $cssId('headerNav', 'rct-topnav'),
            'navigationTpl' => 'nav_default',
        ]));
        $bottom = $insertOrFind('RCT Bottom', 'html', array_merge($stdMeta, [
            'headline' => $headline(), 'cssID' => $cssId(),
            'html' => '<p class="rct-footer-copy">&copy; {{date::Y}} RCT &mdash; Powered by <a href="https://contao.org" target="_blank" rel="noopener">Contao</a></p>',
        ]));
        $insertOrFind('RCT Suche', 'search', [
            'headline' => $headline('Webseite durchsuchen nach:'),
            'cssID' => $cssId(), 'imgSize' => $emptySize,
            'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
            'contextLength' => serialize(['', '']),
            'numberOfItems' => 3,
        ]);
        $login = $insertOrFind('RCT Frontend Login', 'login', array_merge($stdMeta, [
            'headline' => $headline('Login'), 'cssID' => $cssId('rct-login'),
        ]));
        $insertOrFind('RCT Newslist Timeline', 'newslist', [
            'headline' => $headline(), 'cssID' => $cssId(), 'imgSize' => $emptySize,
            'customTpl' => 'mod_newslist_timeline',
            'news_template' => 'news_short_timeline',
            'news_order' => 'order_date_asc',
            'numberOfItems' => 0, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);

        // 5 rct_logo Module (Sidebar Links/Rechts/Header/Classic/Classic-Menu)
        $logoLeft        = $insertOrFind('Logo Sidebar Links',  'rct_logo', ['headline' => $headline(), 'rct_logo_style' => 'sidebar']);
        $logoRight       = $insertOrFind('Logo Sidebar Rechts', 'rct_logo', ['headline' => $headline(), 'rct_logo_style' => 'sidebar']);
        $logoHeader      = $insertOrFind('Logo Header',         'rct_logo', ['headline' => $headline(), 'rct_logo_style' => 'header']);
        $insertOrFind('Logo Classic',        'rct_logo', ['headline' => $headline(), 'rct_logo_style' => 'classic']);
        $insertOrFind('Logo Classic - Menu', 'rct_logo', ['headline' => $headline(), 'rct_logo_style' => 'classic_menu']);

        // 8 Header-Controls (alle gehen in header_right)
        $lang       = $insertOrFind('RCT Sprachschalter',     'rct_language_switcher',  ['headline' => $headline()]);
        $fullscreen = $insertOrFind('RCT Fullscreen Toggle',  'rct_fullscreen_toggle',  ['headline' => $headline()]);
        $navT       = $insertOrFind('RCT Nav Toggle',         'rct_nav_toggle',         ['headline' => $headline()]);
        $searchT    = $insertOrFind('RCT Suche Toggle',       'rct_search_toggle',      ['headline' => $headline()]);
        $loginT     = $insertOrFind('RCT Anmeldung Toggle',   'rct_login_toggle',       ['headline' => $headline()]);
        $rightT     = $insertOrFind('RCT Right Toggle',       'rct_right_toggle',       ['headline' => $headline()]);
        $layoutS    = $insertOrFind('RCT Layout Switcher',    'rct_layout_switcher',    ['headline' => $headline()]);
        $themeS     = $insertOrFind('RCT Theme Switcher',     'rct_theme_switcher',     ['headline' => $headline()]);

        // Classic-spezifische Module (zugewiesen wenn Classic-Layout existiert)
        $insertOrFind('RCT Classic Suche Toggle', 'rct_search_toggle', ['headline' => $headline()]);
        $insertOrFind('RCT Classic Bottom-Links', 'html', [
            'headline' => $headline(),
            'html' => '<div class="rct-classic-bottom-links"><a href="/datenschutz">Datenschutz</a><a href="/impressum">Impressum</a><a href="/kontakt">Kontakt</a></div>',
        ]);

        // ─── Layout-Mappings additiv ergänzen ──────────────────────────────
        $layouts = $this->db->fetchAllAssociative(
            "SELECT id, template, modules FROM tl_layout WHERE pid = ?",
            [$themeId]
        );

        $touched = 0;
        foreach ($layouts as $layout) {
            $modules = @unserialize((string) $layout['modules']);
            if (!is_array($modules)) {
                $modules = [];
            }
            $changed = false;

            $assign = function (int $modId, string $col) use (&$modules, &$changed): void {
                if ($modId === 0) {
                    // 0 = Article-Container — duplizierbar pro Layout, aber nur 1× in main
                    foreach ($modules as $m) {
                        if ((int) $m['mod'] === 0 && $m['col'] === $col) {
                            return;
                        }
                    }
                    $modules[] = ['mod' => '0', 'col' => $col, 'enable' => '1'];
                    $changed = true;
                    return;
                }
                foreach ($modules as $m) {
                    if ((int) $m['mod'] === $modId && $m['col'] === $col) {
                        return;
                    }
                }
                $modules[] = ['mod' => (string) $modId, 'col' => $col, 'enable' => '1'];
                $changed = true;
            };

            $template = (string) $layout['template'];

            // Common für alle Layouts
            $assign(0, 'main');
            $assign($login, 'footer');
            $assign($bottom, 'footer');
            $assign($headerNav, 'navbar');
            $assign($footer, 'bottom');

            // Sidebar-Nav je nach Layout
            switch ($template) {
                case 'fe_page':           // Standard
                    $assign($nav, 'left');
                    $assign($nav, 'right');
                    break;
                case 'fe_page_nav_left':
                    $assign($nav, 'left');
                    break;
                case 'fe_page_nav_right':
                    $assign($nav, 'right');
                    break;
                case 'fe_page_nav_top':
                    // nur navbar
                    break;
            }

            // Custom-Section Mappings — gelten für alle 4 Layouts
            $assign($logoLeft,   'left_logo');
            $assign($logoRight,  'right_logo');
            $assign($logoHeader, 'header_left');
            $assign($title,      'header_left');
            $assign($crumb,      'header_left');

            // header_right: Reihenfolge wie auf rct. produktiv
            $assign($lang,       'header_right');
            $assign($navT,       'header_right');
            $assign($fullscreen, 'header_right');
            $assign($searchT,    'header_right');
            $assign($loginT,     'header_right');
            $assign($rightT,     'header_right');
            $assign($layoutS,    'header_right');
            $assign($themeS,     'header_right');

            if ($changed) {
                $this->db->executeStatement(
                    "UPDATE tl_layout SET modules = ?, tstamp = ? WHERE id = ?",
                    [serialize($modules), $now, (int) $layout['id']]
                );
                $touched++;
            }
        }

        return $this->createResult(
            true,
            sprintf('23 RCT-Module gesichert + Layout-Mappings in %d Layout(s) ergänzt.', $touched)
        );
    }
}
