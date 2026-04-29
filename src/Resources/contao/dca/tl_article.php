<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('rct_article_legend', 'title_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField(['rct_article_bg_color', 'rct_article_bg_alpha', 'rct_article_blur', 'rct_article_shadow'], 'rct_article_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_article');

// rct_article_* ohne 'sql' → jsonData (RctArticleBgJsonStorageMigration)
$GLOBALS['TL_DCA']['tl_article']['fields']['rct_article_bg_color'] = [
    'label'     => ['Hintergrundfarbe', 'Basis-Farbe des Artikel-Hintergrunds'],
    'inputType' => 'select',
    'options'   => [
        ''       => 'Standard (kein Hintergrund)',
        'dark'   => 'Dunkel (#171717)',
        'white'  => 'Weiß',
        'accent' => 'Akzentfarbe (Cyan)',
    ],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_article']['fields']['rct_article_bg_alpha'] = [
    'label'     => ['Transparenz', 'Deckkraft des Hintergrunds (0% = unsichtbar, 100% = deckend)'],
    'inputType' => 'select',
    'options'   => [
        '0'   => '0% (unsichtbar)',
        '5'   => '5%',
        '10'  => '10%',
        '15'  => '15%',
        '20'  => '20%',
        '30'  => '30%',
        '40'  => '40%',
        '50'  => '50%',
        '60'  => '60%',
        '70'  => '70%',
        '80'  => '80%',
        '90'  => '90%',
        '100' => '100% (deckend)',
    ],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_article']['fields']['rct_article_blur'] = [
    'label'     => ['Backdrop-Blur', 'Weichzeichner-Effekt hinter dem Artikel (Frosted Glass)'],
    'inputType' => 'select',
    'options'   => [
        '0'  => 'Kein Blur',
        '4'  => 'Leicht (4px)',
        '8'  => 'Mittel (8px)',
        '12' => 'Stark (12px)',
        '20' => 'Sehr stark (20px)',
    ],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_article']['fields']['rct_article_shadow'] = [
    'label'     => ['Schlagschatten', 'Standard = Layout-Default (Shadow für normale, keiner für Fullwidth)'],
    'inputType' => 'select',
    'options'   => [
        ''       => 'Standard (Layout-Default)',
        'none'   => 'Aus (freischwebend)',
        'soft'   => 'Sanft (glassy)',
        'strong' => 'Stark (dramatisch)',
    ],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];
