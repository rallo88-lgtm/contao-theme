<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RctModuleMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->db->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module', 'tl_theme', 'tl_layout'])) {
            return false;
        }

        $themeId = $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");

        if (!$themeId) {
            return false;
        }

        return 0 === (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_module WHERE name = 'RCT Sidebar-Navigation' AND pid = ?",
            [(int) $themeId]
        );
    }

    public function run(): MigrationResult
    {
        $now     = time();
        $themeId = (int) $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");

        $headline  = fn(string $v = '') => serialize(['unit' => 'h2', 'value' => $v]);
        $cssId     = fn(string $id = '', string $cls = '') => serialize([$id, $cls]);
        $emptySize = serialize(['', '', '']);

        // --- 9 Module anlegen ---

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Sidebar-Navigation', 'type' => 'navigation',
            'headline' => $headline(), 'cssID' => $cssId('', 'rct-nav'),
            'numberOfItems' => 3, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);
        $navId = (int) $this->db->lastInsertId();

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Breadcrumb', 'type' => 'breadcrumb',
            'headline' => $headline(), 'cssID' => $cssId(),
            'numberOfItems' => 3, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);
        $breadcrumbId = (int) $this->db->lastInsertId();

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Seitentitel', 'type' => 'html',
            'headline' => $headline(),
            'html' => '<div id="headerTitle">{{page::rootPageTitle}}</div>',
            'numberOfItems' => 3, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);
        $seitentitelId = (int) $this->db->lastInsertId();

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Footer', 'type' => 'html',
            'headline' => $headline(), 'cssID' => $cssId(),
            'html' => '<!-- Footer-Inhalt hier einfügen -->',
            'numberOfItems' => 3, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Header-Navigation', 'type' => 'navigation',
            'headline' => $headline(), 'cssID' => $cssId('headerNav', 'rct-topnav'),
            'navigationTpl' => 'nav_default',
            'numberOfItems' => 3, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);
        $headerNavId = (int) $this->db->lastInsertId();

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Bottom', 'type' => 'html',
            'headline' => $headline(), 'cssID' => $cssId(),
            'html' => '<p class="rct-footer-copy">&copy; {{date::Y}} RCT &mdash; Powered by <a href="https://contao.org" target="_blank" rel="noopener">Contao</a></p>',
            'numberOfItems' => 3, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);
        $bottomId = (int) $this->db->lastInsertId();

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Suche', 'type' => 'search',
            'headline' => $headline('Webseite durchsuchen nach:'),
            'cssID' => $cssId(), 'imgSize' => $emptySize,
            'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
            'contextLength' => serialize(['', '']),
            'numberOfItems' => 3,
        ]);

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Frontend Login', 'type' => 'login',
            'headline' => $headline('Login'), 'cssID' => $cssId('rct-login'),
            'numberOfItems' => 3, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);
        $loginId = (int) $this->db->lastInsertId();

        $this->db->insert('tl_module', [
            'pid' => $themeId, 'tstamp' => $now,
            'name' => 'RCT Newslist Timeline', 'type' => 'newslist',
            'headline' => $headline(), 'cssID' => $cssId(), 'imgSize' => $emptySize,
            'customTpl' => 'mod_newslist_timeline',
            'news_template' => 'news_short_timeline',
            'news_order' => 'order_date_asc',
            'numberOfItems' => 0, 'queryType' => 'and', 'searchType' => 'simple', 'minKeywordLength' => 4,
        ]);

        // --- Layouts mit Modul-Zuweisungen aktualisieren ---

        $mod = fn(int $id, string $col) => ['mod' => (string) $id, 'col' => $col, 'enable' => '1'];

        $common = [
            $mod($seitentitelId, 'header'),
            $mod($breadcrumbId,  'header'),
            $mod(0,              'main'),
            $mod($loginId,       'footer'),
            $mod($bottomId,      'footer'),
            $mod($headerNavId,   'navbar'),
        ];

        $layoutModules = [
            'fe_page'           => [$common[0], $common[1], $mod($navId, 'left'), $mod($navId, 'right'), $common[2], $common[3], $common[4], $common[5]],
            'fe_page_nav_left'  => [$common[0], $common[1], $mod($navId, 'left'), $mod(0, 'right'),       $common[2], $common[3], $common[4], $common[5]],
            'fe_page_nav_right' => [$common[0], $common[1], $mod(0, 'left'),       $mod($navId, 'right'), $common[2], $common[3], $common[4], $common[5]],
            'fe_page_nav_top'   => [$common[0], $common[1], $mod(0, 'left'),       $mod($navId, 'left'),  $common[2], $common[3], $common[4], $common[5]],
        ];

        foreach ($layoutModules as $template => $modules) {
            $this->db->update(
                'tl_layout',
                ['modules' => serialize($modules), 'tstamp' => $now],
                ['pid' => $themeId, 'template' => $template]
            );
        }

        return $this->createResult(true, '9 RCT-Module angelegt und Layouts aktualisiert.');
    }
}
