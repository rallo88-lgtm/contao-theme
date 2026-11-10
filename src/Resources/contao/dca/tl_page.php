<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\System;
use Rallo\ContaoTheme\Controller\Backend\RctConfigController;

// Theme pro Seite (v1.7.6): leer = von der Elternseite erben.
// Ohne Theme-Umschalter im Layout gilt immer das Seiten-Theme, mit Umschalter ist es nur der Startwert.
PaletteManipulator::create()
    ->addField('rct_page_theme', 'layout_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('regular', 'tl_page')
    ->applyToPalette('root', 'tl_page')
    ->applyToPalette('rootfallback', 'tl_page')
    ->applyToPalette('error_401', 'tl_page')
    ->applyToPalette('error_403', 'tl_page')
    ->applyToPalette('error_404', 'tl_page')
    ->applyToPalette('error_503', 'tl_page');

$GLOBALS['TL_DCA']['tl_page']['fields']['rct_page_theme'] = [
    'label'            => ['RCT-Theme', 'Theme dieser Seite (nur die in den Theme-Einstellungen freigegebenen). Leer = von der Elternseite erben. Ist im Layout ein Theme-Umschalter, darf der Besucher es überschreiben.'],
    'inputType'        => 'select',
    'options_callback' => static function (): array {
        $all = RctConfigController::ALL_THEMES;
        try {
            $raw = (string) System::getContainer()->get('database_connection')
                ->fetchOne('SELECT rct_allowed_themes FROM tl_rct_config LIMIT 1');
        } catch (\Throwable) {
            $raw = '';
        }
        if ($raw === '') {
            return $all;
        }
        $allowed = array_flip(explode(',', $raw));

        return array_intersect_key($all, $allowed) ?: $all;
    },
    'eval'             => ['includeBlankOption' => true, 'blankOptionLabel' => '– erben (Elternseite) –', 'chosen' => true, 'tl_class' => 'w50 clr'],
    'sql'              => "varchar(64) NOT NULL default ''",
];
