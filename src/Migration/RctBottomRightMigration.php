<?php

namespace Rallo\ContaoTheme\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * v1.5.8 — Bottom-Right Spacer-Modul
 *
 * Auf bestehenden Instanzen, die nach v1.5.2 auf den Drei-Spalten-Bottom
 * umgestellt wurden, fehlt das HTML-Modul "RCT Bottom Right". Ohne dieses
 * Modul rendert Contao die bottom_right Custom-Section nicht (block_section
 * mit leerer Zuweisung = kein DOM-Output), und der rechte Anker der
 * Bottom-Lane fehlt — #bottom_content sitzt asymmetrisch zur Main-Lane.
 *
 * Diese Migration legt das Modul an und mappt es in alle RCT-Theme-Layouts.
 *
 * Idempotent über die Existenz von tl_module name='RCT Bottom Right'
 * (type='html') unter dem RCT-Theme. Frischinstalls sind nicht betroffen —
 * RctSetupMigration legt das Modul direkt an.
 */
class RctBottomRightMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function getName(): string
    {
        return 'RCT Bundle 1.5.8 – Bottom-Right spacer module';
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
            "SELECT COUNT(*) FROM tl_module WHERE pid = ? AND name = 'RCT Bottom Right' AND type = 'html'",
            [$themeId]
        );
        return $exists === 0;
    }

    public function run(): MigrationResult
    {
        $now      = time();
        $themeId  = (int) $this->db->fetchOne("SELECT id FROM tl_theme WHERE name = 'RCT Theme'");
        $headline = serialize(['unit' => 'h2', 'value' => '']);
        $html     = '<div class="bottom-right">&nbsp;</div>';

        $this->db->executeStatement(
            "INSERT INTO tl_module (pid, tstamp, name, type, headline, html) VALUES (?, ?, ?, ?, ?, ?)",
            [$themeId, $now, 'RCT Bottom Right', 'html', $headline, $html]
        );
        $btmRight = (int) $this->db->lastInsertId();

        $layouts = $this->db->fetchAllAssociative(
            "SELECT id, modules FROM tl_layout WHERE pid = ?",
            [$themeId]
        );

        $touched = 0;
        foreach ($layouts as $layout) {
            $modules = @unserialize((string) $layout['modules']);
            if (!is_array($modules)) {
                $modules = [];
            }

            // Idempotenz pro Layout: nur einfuegen wenn (mod, col) noch nicht da
            $alreadyMapped = false;
            foreach ($modules as $m) {
                if ((int) $m['mod'] === $btmRight && $m['col'] === 'bottom_right') {
                    $alreadyMapped = true;
                    break;
                }
            }
            if ($alreadyMapped) {
                continue;
            }

            $modules[] = ['mod' => (string) $btmRight, 'col' => 'bottom_right', 'enable' => '1'];

            $this->db->executeStatement(
                "UPDATE tl_layout SET modules = ?, tstamp = ? WHERE id = ?",
                [serialize($modules), $now, (int) $layout['id']]
            );
            $touched++;
        }

        return $this->createResult(
            true,
            sprintf('RCT Bottom-Right Modul angelegt + in %d Layout(s) zugewiesen.', $touched)
        );
    }
}
