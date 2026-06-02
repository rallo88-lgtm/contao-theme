<?php

// Visibility legend for standard Contao modules
$GLOBALS['TL_DCA']['tl_module']['palettes']['breadcrumb'] =
    str_replace('{expert_legend:hide}', '{visibility_legend},rct_visibility;{expert_legend:hide}',
        $GLOBALS['TL_DCA']['tl_module']['palettes']['breadcrumb'] ?? '{title_legend},name,headline,type;{visibility_legend},rct_visibility;{expert_legend:hide},cssID'
    );

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_theme_switcher'] =
    '{title_legend},name,headline,type;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_layout_switcher'] =
    '{title_legend},name,headline,type;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_right_toggle'] =
    '{title_legend},name,headline,type;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_login_toggle'] =
    '{title_legend},name,headline,type;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_search_toggle'] =
    '{title_legend},name,headline,type;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_nav_toggle'] =
    '{title_legend},name,headline,type;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_fullscreen_toggle'] =
    '{title_legend},name,headline,type;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_language_switcher'] =
    '{title_legend},name,headline,type;{languages_legend},rct_languages;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

// RCT News Pager — Vorgänger/Nachfolger im aktuellen News-Archive
$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_news_pager'] =
    '{title_legend},name,headline,type;{pager_legend},rct_pager_position,rct_pager_style,rct_pager_sort_order,rct_pager_show_cover_link,rct_pager_loop,rct_pager_keyboard,rct_pager_swipe;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_pager_position'] = [
    'label'     => ['Position', 'Wo soll der Pager im Newsreader-Layout erscheinen'],
    'inputType' => 'select',
    'options'   => ['bottom' => 'Unten (Standard)', 'top' => 'Oben', 'both' => 'Oben und Unten'],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_pager_style'] = [
    'label'     => ['Stil', 'Visuelles Erscheinungsbild des Pagers'],
    'inputType' => 'select',
    'options'   => ['arrows' => 'Nur Pfeile', 'arrows-counter' => 'Pfeile + Position (3 / 8)', 'arrows-labels' => 'Pfeile + Titel der Nachbar-Seite'],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_pager_sort_order'] = [
    'label'     => ['Sortier-Richtung', 'Aufsteigend = Seite 1 → N (z.B. Magazin-Reihenfolge), Absteigend = neueste zuerst (Blog-Reihenfolge)'],
    'inputType' => 'select',
    'options'   => ['asc' => 'Aufsteigend (Seite 1 → N)', 'desc' => 'Absteigend (Neueste zuerst)'],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_pager_show_cover_link'] = [
    'label'     => ['„Zum Cover"-Link', 'Zeigt einen zusätzlichen Link zur ersten Seite des Archivs (z.B. „Heft-Cover")'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_pager_loop'] = [
    'label'     => ['Loop am Ende', 'Wenn am letzten/ersten Eintrag — wieder von vorn/hinten beginnen (Endlos-Schleife)'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_pager_keyboard'] = [
    'label'     => ['Tastatur-Pfeile', 'Pfeil-links/rechts auf der Tastatur navigiert zwischen den Seiten'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_pager_swipe'] = [
    'label'     => ['Touch-Swipe', 'Wischen auf Touch-Geräten navigiert zwischen den Seiten'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// RCT-Felder in tl_module ohne 'sql' → jsonData (RctModuleFieldsJsonStorageMigration).
// rct_logo_image + rct_logo_image_mobile (fileTree) bleiben als Spalten.
$GLOBALS['TL_DCA']['tl_module']['fields']['rct_languages'] = [
    'label'     => ['Sprachen', "Eine Zeile pro Sprache: CODE|Bezeichnung|/url\nBeispiel: DE|Deutsch|/"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px; font-family: monospace', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_logo'] =
    '{title_legend},name,headline,type;{logo_legend},rct_logo_style,rct_logo_image,rct_logo_image_mobile,rct_logo_url,rct_logo_alt,rct_logo_hide_mobile;{visibility_legend},rct_visibility;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_style'] = [
    'label'     => ['Position / Stil', 'Sidebar: Logo in linker oder rechter Sidebar. Header: Logo im Seitenheader.'],
    'inputType' => 'select',
    'options'   => ['sidebar' => 'Sidebar', 'header' => 'Header'],
    'eval'      => ['tl_class' => 'w50 clr'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_image'] = [
    'label'     => ['Logo-Bild', 'Eigenes Bild hochladen. Leer lassen für das Standard-RCT-SVG-Logo.'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'jpg,jpeg,png,gif,svg,webp', 'tl_class' => 'w50'],
    'sql'       => 'binary(16) NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_image_mobile'] = [
    'label'     => ['Logo-Bild Mobile', 'Alternatives Bild für Mobile. Leer lassen = selbes Bild wie Desktop.'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'jpg,jpeg,png,gif,svg,webp', 'tl_class' => 'w50'],
    'sql'       => 'binary(16) NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_url'] = [
    'label'     => ['Logo-Link', 'URL auf die das Logo verlinkt. Standard: /'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'url', 'decodeEntities' => true, 'tl_class' => 'w50', 'maxlength' => 255],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_alt'] = [
    'label'     => ['Alt-Text / Aria-Label', 'Beschreibung des Logos für Screenreader.'],
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_hide_mobile'] = [
    'label'     => ['Auf Mobile ausblenden', 'Logo auf kleinen Bildschirmen nicht anzeigen.'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr m12'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_visibility'] = [
    'label'     => ['Sichtbarkeit', 'Legt fest, ob das Modul auf Desktop, Mobile oder beiden angezeigt wird.'],
    'inputType' => 'select',
    'options'   => ['' => 'Immer anzeigen', 'mobile' => 'Nur Mobile (≤768px)', 'tablet' => 'Mobil + Tablet (≤1024px)', 'desktop' => 'Nur Desktop (>1024px)'],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];
