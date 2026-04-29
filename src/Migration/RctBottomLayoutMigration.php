<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * v1.5.2 — Drei-Spalten-Bottom-Refactor
 *
 * Auf bestehenden Instanzen, deren RctSetupMigration schon gelaufen ist,
 * fehlen die neuen Bottom-Sections + Module. Diese Migration zieht das nach:
 *  - rct_bottom_controls + RCT Bottom Copy (mod_html) + RCT Legal Links (mod_html)
 *  - bottom_left / bottom_content / bottom_right Custom Sections in jedem
 *    RCT-Theme-Layout
 *  - Modul-Zuweisungen (Copy → bottom_left, Legal+Controls → bottom_content)
 *
 * Idempotent über die Existenz von tl_module type='rct_bottom_controls'.
 * Frischinstalls sind nicht betroffen — die legen die neuen Module/Sections
 * direkt über RctSetupMigration an.
 */
class RctBottomLayoutMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle 1.5.2 – Bottom-bar three-column refactor';
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
            "SELECT COUNT(*) FROM tl_module WHERE pid = ? AND type = 'rct_bottom_controls'",
            [$themeId]
        );
        return $exists === 0;
    }

    public function run(): MigrationResult
    {
        $now      = time();
        $themeId  = (int) $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");
        $headline = serialize(['unit' => 'h2', 'value' => '']);

        $copyHtml  = '<div class="bottom-copy"><p class="rct-footer-copy">&copy; {{date::Y}} RCT v{{rct_version}} &mdash; Powered by <a href="https://contao.org" target="_blank" rel="noopener">Contao</a></p></div>';
        $legalHtml = '<div class="bottom-legal-links"><a href="/datenschutz">Datenschutz</a><a href="/impressum">Impressum</a><a href="/kontakt">Kontakt</a></div>';

        $btmCtl  = $this->insertModule($themeId, $now, 'RCT Bottom Controls', 'rct_bottom_controls', $headline, '');
        $btmCopy = $this->insertModule($themeId, $now, 'RCT Bottom Copy',     'html',                $headline, $copyHtml);
        $legal   = $this->insertModule($themeId, $now, 'RCT Legal Links',     'html',                $headline, $legalHtml);

        $newSections = [
            ['title' => 'Bottom Links',  'id' => 'bottom_left',    'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Mitte',  'id' => 'bottom_content', 'template' => 'block_section', 'position' => 'manual'],
            ['title' => 'Bottom Rechts', 'id' => 'bottom_right',   'template' => 'block_section', 'position' => 'manual'],
        ];

        $layouts = $this->db->fetchAllAssociative(
            "SELECT id, sections, modules FROM tl_layout WHERE pid = ?",
            [$themeId]
        );

        $touched = 0;
        foreach ($layouts as $layout) {
            $sections = @unserialize((string) $layout['sections']);
            $modules  = @unserialize((string) $layout['modules']);
            if (!is_array($sections)) $sections = [];
            if (!is_array($modules))  $modules  = [];

            $changed     = false;
            $existingIds = array_column($sections, 'id');

            foreach ($newSections as $newSection) {
                if (!in_array($newSection['id'], $existingIds, true)) {
                    $sections[] = $newSection;
                    $changed = true;
                }
            }

            $assign = function (int $modId, string $col) use (&$modules, &$changed): void {
                foreach ($modules as $m) {
                    if ((int) $m['mod'] === $modId && $m['col'] === $col) {
                        return;
                    }
                }
                $modules[] = ['mod' => (string) $modId, 'col' => $col, 'enable' => '1'];
                $changed = true;
            };

            $assign($btmCopy, 'bottom_left');
            $assign($legal,   'bottom_content');
            $assign($btmCtl,  'bottom_content');

            if ($changed) {
                $this->db->executeStatement(
                    "UPDATE tl_layout SET sections = ?, modules = ?, tstamp = ? WHERE id = ?",
                    [serialize($sections), serialize($modules), $now, (int) $layout['id']]
                );
                $touched++;
            }
        }

        return $this->createResult(
            true,
            sprintf('RCT Bottom-Refactor: 3 Module + 3 Sections in %d Layout(s) ergaenzt.', $touched)
        );
    }

    private function insertModule(int $pid, int $now, string $name, string $type, string $headline, string $html): int
    {
        $this->db->executeStatement(
            "INSERT INTO tl_module (pid, tstamp, name, type, headline, html) VALUES (?, ?, ?, ?, ?, ?)",
            [$pid, $now, $name, $type, $headline, $html]
        );
        return (int) $this->db->lastInsertId();
    }
}
