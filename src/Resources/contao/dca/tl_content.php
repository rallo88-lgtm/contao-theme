<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Rallo\ContaoTheme\DCA\RctFontOptionsCallback;

// Stil-Feld + Experteneinstellungen zur nativen Contao-Akkordeon-Palette hinzufügen
PaletteManipulator::create()
    ->addField('rct_accordion_style', 'closeAll', PaletteManipulator::POSITION_AFTER)
    ->addField('cssID', 'expert_legend', PaletteManipulator::POSITION_APPEND, 'legend')
    ->applyToPalette('accordion', 'tl_content');

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = static function (): void {
    PaletteManipulator::create()
        ->removeField('customTpl')
        ->applyToPalette('accordion', 'tl_content');
};

// Stil-Feld zu Download + Downloads hinzufügen
PaletteManipulator::create()
    ->addField('rct_download_style', 'inline', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('download', 'tl_content');

PaletteManipulator::create()
    ->addField('rct_download_style', 'inline', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('downloads', 'tl_content');

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_download_style'] = [
    'label'     => ['Stil', 'Dunkel (Terminal) oder Hell (Standard)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Terminal)', 'light' => 'Hell (Standard)'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default ''",
];

// Textfarbe in Standard-Elementen (text + headline)
PaletteManipulator::create()
    ->addField('rct_content_color', 'text', PaletteManipulator::POSITION_AFTER)
    ->removeField('customTpl')
    ->applyToPalette('text', 'tl_content');

PaletteManipulator::create()
    ->addField('rct_hl_font', 'hl', PaletteManipulator::POSITION_AFTER)
    ->addField('rct_content_color', 'rct_hl_font', PaletteManipulator::POSITION_AFTER)
    ->removeField('customTpl')
    ->applyToPalette('headline', 'tl_content');

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hl_font'] = [
    'label'            => ['Schriftart', 'Abweichender Font für diese Überschrift. Leer = globaler Standard-Font.'],
    'inputType'        => 'select',
    'options_callback' => [RctFontOptionsCallback::class, 'getHeadlineFonts'],
    'eval'             => ['tl_class' => 'w50', 'includeBlankOption' => false],
    'sql'              => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_content_color'] = [
    'label'     => ['Textfarbe', 'Optionale Farbe für Text/Überschrift. Hex (#27c4f4) oder leer lassen.'],
    'inputType' => 'text',
    'eval'      => ['colorpicker' => true, 'isHexColor' => true, 'tl_class' => 'w50 wizard', 'maxlength' => 64],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_people_box'] =
    '{type_legend},type;{people_legend},rct_person_name,rct_person_role,rct_person_image,rct_person_bio,rct_people_box_style;{contact_legend:hide},rct_person_email,rct_person_phone,rct_person_link,rct_person_link_text;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_people_box_style'] = [
    'label'     => ['Stil', 'Hell (Standard) oder Dunkel (Shell-Look)'],
    'inputType' => 'select',
    'options'   => ['light' => 'Hell (Standard)', 'dark' => 'Dunkel (Shell-Look)'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default 'light'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_name'] = [
    'label'     => ['Name', 'Vollständiger Name der Person'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql'       => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_role'] = [
    'label'     => ['Rolle / Position', 'Berufsbezeichnung oder Funktion'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql'       => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_bio'] = [
    'label'     => ['Kurzbiografie', 'Kurzer Beschreibungstext'],
    'inputType' => 'textarea',
    'eval'      => ['rte' => '', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_image'] = [
    'label'     => ['Foto', 'Profilbild der Person'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif', 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'       => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_email'] = [
    'label'     => ['E-Mail', 'E-Mail-Adresse'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'rgxp' => 'email', 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_phone'] = [
    'label'     => ['Telefon', 'Telefonnummer'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_link'] = [
    'label'     => ['Link (URL)', 'z.B. LinkedIn-Profil oder Website'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'rgxp' => 'url', 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_link_text'] = [
    'label'     => ['Link-Text', 'Anzeigetext für den Link'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql'       => "varchar(128) NOT NULL default ''",
];

// ============================================================
// RCT Gallery (Isotope)
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_gallery'] =
    '{type_legend},type;{gallery_legend},rct_gallery_images,rct_gallery_folder,rct_gallery_layout,rct_gallery_cols,rct_gallery_sortby,rct_gallery_filter,rct_gallery_lightbox;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_images'] = [
    'label'     => ['Einzelne Bilder', 'Einzelne Bilder manuell auswählen (alternativ zum Ordner)'],
    'inputType' => 'fileTree',
    'eval'      => ['multiple' => true, 'fieldType' => 'checkbox', 'filesOnly' => true, 'extensions' => 'jpg,jpeg,png,gif,webp,avif', 'tl_class' => 'clr'],
    'sql'       => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_folder'] = [
    'label'     => ['Oder: ganzer Ordner', 'Alle Bilder aus einem Ordner laden (wird ignoriert wenn oben Einzelbilder gewählt)'],
    'inputType' => 'fileTree',
    'eval'      => ['fieldType' => 'radio', 'files' => false, 'tl_class' => 'clr'],
    'sql'       => "binary(16) NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_layout'] = [
    'label'     => ['Layout', 'Anordnung der Bilder'],
    'inputType' => 'select',
    'options'   => ['masonry' => 'Masonry (dynamisch)', 'fitrows' => 'Gleichmäßiges Raster'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(16) NOT NULL default 'masonry'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_cols'] = [
    'label'     => ['Spalten', 'Anzahl Spalten (Desktop)'],
    'inputType' => 'select',
    'options'   => ['2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(2) NOT NULL default '3'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_sortby'] = [
    'label'     => ['Sortierung', 'Reihenfolge der Bilder'],
    'inputType' => 'select',
    'options'   => [
        'name_asc'  => 'Dateiname A→Z',
        'name_desc' => 'Dateiname Z→A',
        'random'    => 'Zufällig',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(16) NOT NULL default 'name_asc'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_filter'] = [
    'label'     => ['Filter-Buttons', 'Unterordner als Filterkategorien anzeigen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_lightbox'] = [
    'label'     => ['Lightbox', 'Bilder in Lightbox öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_map'] =
    '{type_legend},type;{map_legend},rct_map_address,rct_map_marker,rct_map_zoom,rct_map_height;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_map_address'] = [
    'label'     => ['Adresse', 'Vollständige Adresse (Straße, PLZ, Ort)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_map_marker'] = [
    'label'     => ['Marker-Text', 'Text im Popup-Fenster des Kartenmarkers (optional)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_map_zoom'] = [
    'label'     => ['Zoom-Stufe', 'Zoom der Karte (14 = Straßenebene)'],
    'inputType' => 'select',
    'options'   => ['10' => '10 – Stadtebene', '12' => '12 – Stadtteil', '14' => '14 – Straße (Standard)', '16' => '16 – Gebäude', '18' => '18 – Maximum'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(2) NOT NULL default '14'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_map_height'] = [
    'label'     => ['Kartenhöhe', 'Höhe des Kartenausschnitts'],
    'inputType' => 'select',
    'options'   => ['250px' => '250px', '300px' => '300px', '400px' => '400px (Standard)', '500px' => '500px', '600px' => '600px'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(6) NOT NULL default '400px'",
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_parallax_start'] =
    '{type_legend},type;{parallax_legend},rct_parallax_image,rct_parallax_video,rct_parallax_height,rct_parallax_overlay;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_parallax_stop'] =
    '{type_legend},type;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_parallax_image'] = [
    'label'     => ['Hintergrundbild', 'Bild für den Parallax-Hintergrund (JPG, PNG, WebP)'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif', 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'       => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_parallax_video'] = [
    'label'     => ['Hintergrundvideo', 'MP4-Video als Hintergrund (überschreibt Parallax-Effekt)'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'extensions' => 'mp4,webm', 'fieldType' => 'radio', 'tl_class' => 'w50'],
    'sql'       => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_parallax_height'] = [
    'label'     => ['Mindesthöhe', 'Minimale Höhe des Bereichs'],
    'inputType' => 'select',
    'options'   => ['' => 'Auto (Inhalt bestimmt Höhe)', '200px' => '200px', '300px' => '300px', '50vh' => '50vh', '100vh' => 'Fullscreen (100vh)'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(10) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_parallax_overlay'] = [
    'label'     => ['Overlay', 'Dunkles Overlay über dem Hintergrund (verbessert Lesbarkeit)'],
    'inputType' => 'select',
    'options'   => ['' => 'Kein Overlay', '20' => '20%', '40' => '40%', '60' => '60%', '80' => '80%'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(3) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_grid_start'] =
    '{type_legend},type;{grid_legend},rct_columns,rct_gap,rct_align;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_columns'] = [
    'label'     => ['Spalten', 'Anzahl der Spalten'],
    'inputType' => 'select',
    'options'   => ['2' => '2 Spalten', '3' => '3 Spalten', '4' => '4 Spalten'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(2) NOT NULL default '3'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gap'] = [
    'label'     => ['Abstand', 'Abstand zwischen den Elementen'],
    'inputType' => 'select',
    'options'   => ['' => 'Normal', 'rct-gap--sm' => 'Klein', 'rct-gap--lg' => 'Groß'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(20) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_align'] = [
    'label'     => ['Ausrichtung', 'Vertikale Ausrichtung'],
    'inputType' => 'select',
    'options'   => [
        ''                       => 'Stretch (default)',
        'rct-flex--align-start'  => 'Oben',
        'rct-flex--align-center' => 'Mitte',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(30) NOT NULL default ''",
];

// ============================================================
// RCT Stat Box
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_stat_box'] =
    '{type_legend},type;{stat_legend},rct_stat_value,rct_stat_prefix,rct_stat_unit,rct_stat_label,rct_stat_sublabel,rct_stat_icon,rct_stat_color,rct_stat_size;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_value'] = [
    'label'     => ['Wert', 'Die Kennzahl (Ganzzahl oder Dezimalzahl, z.B. 1250 oder 98.6)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
    'sql'       => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_prefix'] = [
    'label'     => ['Präfix', 'Zeichen vor der Zahl, z.B. ">" oder "ca." oder "+"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50'],
    'sql'       => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_unit'] = [
    'label'     => ['Einheit', 'Einheit hinter der Zahl, z.B. "%" oder "€" oder "Mitglieder"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
    'sql'       => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_label'] = [
    'label'     => ['Label', 'Beschriftung unter der Zahl'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql'       => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_sublabel'] = [
    'label'     => ['Zweite Zeile', 'Optionaler Zusatztext unter dem Label (kleiner, gedimmt)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'clr long'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_icon'] = [
    'label'     => ['Icon / Emoji', 'Emoji oder kurzes Symbol über der Zahl, z.B. 🏆 oder ✓'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50'],
    'sql'       => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_color'] = [
    'label'     => ['Farbe', 'Akzentfarbe der Box'],
    'inputType' => 'select',
    'options'   => [
        'accent'    => 'Akzentfarbe (Standard)',
        'primary'   => 'Primärfarbe',
        'dim'       => 'Lime-Grün',
        'fixed'     => 'Gelbgrün',
        'secondary' => 'Lavendel',
        'purple'    => 'Lila',
        'orange'    => 'Orange',
        'red'       => 'Rot',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'accent'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_size'] = [
    'label'     => ['Größe', 'Schriftgröße der Kennzahl'],
    'inputType' => 'select',
    'options'   => ['sm' => 'Klein', 'md' => 'Mittel (Standard)', 'lg' => 'Groß'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(4) NOT NULL default 'md'",
];

// ============================================================
// RCT CTA
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_cta'] =
    '{type_legend},type;{cta_legend},rct_cta_headline,rct_cta_text,rct_cta_icon,rct_cta_color,rct_cta_layout,rct_cta_style;{cta_btn1_legend},rct_cta_btn1_label,rct_cta_btn1_page,rct_cta_btn1_url,rct_cta_btn1_style,rct_cta_btn1_target;{cta_btn2_legend:hide},rct_cta_btn2_label,rct_cta_btn2_page,rct_cta_btn2_url,rct_cta_btn2_style,rct_cta_btn2_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_headline'] = [
    'label'     => ['Überschrift', 'Hauptaussage des CTA'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_text'] = [
    'label'     => ['Text', 'Kurzer Begleittext (optional)'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_icon'] = [
    'label'     => ['Icon / Emoji', 'Emoji oder Symbol über der Überschrift, z.B. 🚀'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50'],
    'sql'       => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_color'] = [
    'label'     => ['Farbe', 'Akzentfarbe'],
    'inputType' => 'select',
    'options'   => [
        'accent'    => 'Akzentfarbe (Standard)',
        'primary'   => 'Primärfarbe',
        'dim'       => 'Lime-Grün',
        'fixed'     => 'Gelbgrün',
        'secondary' => 'Lavendel',
        'purple'    => 'Lila',
        'orange'    => 'Orange',
        'red'       => 'Rot',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'accent'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_layout'] = [
    'label'     => ['Layout', 'Darstellungsform des CTA'],
    'inputType' => 'select',
    'options'   => [
        'centered' => 'Zentriert (Sektion-Abschluss)',
        'banner'   => 'Banner (Text links, Buttons rechts)',
        'card'     => 'Karte (mit Border und Glow)',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'centered'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_style'] = [
    'label'     => ['Stil', 'Hell (für weißen Seitenbereich) oder Dunkel (Shell-Look)'],
    'inputType' => 'select',
    'options'   => [
        'light' => 'Hell (Standard)',
        'dark'  => 'Dunkel (Shell-Look)',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default 'light'",
];

// Button 1
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn1_label'] = [
    'label'     => ['Button-Text', 'Beschriftung des primären Buttons'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn1_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als Ziel (hat Vorrang vor URL-Feld)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn1_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (wird ignoriert wenn eine Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn1_style'] = [
    'label'     => ['Button-Stil', 'Optik des primären Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Gefüllt (Primary)', 'outline' => 'Outline', 'ghost' => 'Ghost'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'primary'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn1_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

// Button 2
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn2_label'] = [
    'label'     => ['Button 2 Text', 'Beschriftung des sekundären Buttons (optional)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn2_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als Ziel (hat Vorrang vor URL-Feld)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn2_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (wird ignoriert wenn eine Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn2_style'] = [
    'label'     => ['Button 2 Stil', 'Optik des sekundären Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Gefüllt (Primary)', 'outline' => 'Outline', 'ghost' => 'Ghost'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'outline'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn2_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

// rct_accordion_style — wird vom nativen Contao-Akkordeon (accordion) genutzt
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_accordion_style'] = [
    'label'     => ['Stil', 'Dunkel (Standard) oder Hell'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Classified Archive)', 'light' => 'Hell (Editorial)'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default 'dark'",
];

// ============================================================
// RCT Timeline
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_timeline'] =
    '{type_legend},type;{timeline_legend},rct_timeline_data,rct_timeline_color,rct_timeline_variant,rct_timeline_show_line,rct_timeline_style;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_data'] = [
    'label'     => ['Timeline-Einträge', "Einträge durch '---' trennen.\nZeile 1: Datum|Titel|Farbe|Icon\nZeile 2+: Beschreibungstext"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:220px; font-family:monospace', 'tl_class' => 'clr'],
    'sql'       => "mediumtext NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_color'] = [
    'label'     => ['Standard-Farbe', 'Farbe für alle Einträge ohne eigene Farbangabe'],
    'inputType' => 'select',
    'options'   => [
        'accent'    => 'Akzentfarbe (Standard)',
        'primary'   => 'Primärfarbe',
        'dim'       => 'Lime-Grün',
        'fixed'     => 'Gelbgrün',
        'secondary' => 'Lavendel',
        'purple'    => 'Lila',
        'orange'    => 'Orange',
        'red'       => 'Rot',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'accent'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_variant'] = [
    'label'     => ['Darstellung', 'Wie die Einträge angeordnet werden'],
    'inputType' => 'select',
    'options'   => [
        'alternate' => 'Alternierend links/rechts (Standard)',
        'single'    => 'Einspaltig (immer links)',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'alternate'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_show_line'] = [
    'label'     => ['Verbindungslinie anzeigen', 'Vertikale Linie zwischen den Einträgen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_style'] = [
    'label'     => ['Stil', 'Dunkel (Shell-Look) oder Hell (für weißen Seitenbereich)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Standard)', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default 'dark'",
];

// ============================================================
// RCT Chart Bars
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_chart_bars'] =
    '{type_legend},type;{chart_legend},rct_chart_bars_data,rct_chart_orientation,rct_chart_color,rct_chart_show_values,rct_content_color;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_chart_bars_data'] = [
    'label'     => ['Balken-Daten', 'Eine Zeile pro Balken: Label|Wert (0–100). Zeilen mit # werden ignoriert.'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:140px; font-family:monospace', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_chart_orientation'] = [
    'label'     => ['Ausrichtung', 'Vertikale oder horizontale Balken'],
    'inputType' => 'select',
    'options'   => ['vertical' => 'Vertikal (Säulendiagramm)', 'horizontal' => 'Horizontal (Balkendiagramm)'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'vertical'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_chart_color'] = [
    'label'     => ['Farbe', 'Farbgebung der Balken'],
    'inputType' => 'select',
    'options'   => [
        'accent'  => 'Akzentfarbe (Standard)',
        'primary' => 'Primärfarbe',
        'mixed'   => 'Gemischt (wechselt durch)',
        'warn'    => 'Warnfarbe / Orange',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'accent'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_chart_show_values'] = [
    'label'     => ['Prozentzahl anzeigen', 'Wert als hochzählende Zahl neben dem Label anzeigen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default '1'",
];

// ============================================================
// RCT Icon Box
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_icon_box'] =
    '{type_legend},type;{icon_box_legend},rct_icon_box_icon,rct_icon_box_headline,rct_icon_box_text,rct_icon_box_color,rct_icon_box_align,rct_icon_box_style;{icon_box_link_legend:hide},rct_icon_box_link_page,rct_icon_box_link_url,rct_icon_box_link_label,rct_icon_box_link_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_icon'] = [
    'label'     => ['Icon / Emoji', 'Emoji oder Symbol, z.B. 🚀 ✓ ⭐'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w25'],
    'sql'       => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_headline'] = [
    'label'     => ['Überschrift', 'Titel der Feature-Box'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_text'] = [
    'label'     => ['Text', 'Beschreibungstext der Feature-Box'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_color'] = [
    'label'     => ['Farbe', 'Akzentfarbe für Icon und Glow'],
    'inputType' => 'select',
    'options'   => [
        'accent'    => 'Akzentfarbe (Standard)',
        'primary'   => 'Primärfarbe',
        'dim'       => 'Lime-Grün',
        'fixed'     => 'Gelbgrün',
        'secondary' => 'Lavendel',
        'purple'    => 'Lila',
        'orange'    => 'Orange',
        'red'       => 'Rot',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'accent'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_align'] = [
    'label'     => ['Ausrichtung', 'Inhalt zentriert oder linksbündig'],
    'inputType' => 'select',
    'options'   => [
        'centered' => 'Zentriert',
        'left'     => 'Linksbündig',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'centered'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_style'] = [
    'label'     => ['Stil', 'Dunkel (Shell-Look) oder Hell (für weißen Seitenbereich)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Standard)', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default 'dark'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_link_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als Link-Ziel (Vorrang vor URL)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_link_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (ignoriert wenn Seite gewählt)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_link_label'] = [
    'label'     => ['Link-Text', 'Beschriftung des Links (Standard: „Mehr erfahren")'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_link_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr'],
    'sql'       => "char(1) NOT NULL default ''",
];

// ============================================================
// RCT Alert
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_alert'] =
    '{type_legend},type;{alert_legend},rct_alert_type,rct_alert_title,rct_alert_text,rct_alert_dismissible,rct_alert_style;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_type'] = [
    'label'     => ['Typ', 'Art der Meldung'],
    'inputType' => 'select',
    'options'   => [
        'info'    => 'Info',
        'warning' => 'Warnung',
        'success' => 'Erfolg',
        'error'   => 'Fehler',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'info'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_title'] = [
    'label'     => ['Titel', 'Optionale Überschrift der Meldung'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_text'] = [
    'label'     => ['Text', 'Inhalt der Meldung'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_dismissible'] = [
    'label'     => ['Schließbar', 'Schließen-Button anzeigen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_style'] = [
    'label'     => ['Stil', 'Dunkel (Shell-Look) oder Hell (für weißen Seitenbereich)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Standard)', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default 'dark'",
];

// ============================================================
// RCT Tabs
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_tabs'] =
    '{type_legend},type;{tabs_legend},rct_tabs_data,rct_tabs_color,rct_tabs_style;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_tabs_data'] = [
    'label'     => ['Tab-Inhalte', "Ein Tab pro Block, getrennt durch ---\nErste Zeile = Tab-Titel, dann Inhalt.\nEmoji möglich: 🚀 Tab-Titel"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:200px; font-family: monospace;', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_tabs_color'] = [
    'label'     => ['Akzentfarbe', 'Farbe der aktiven Tab-Linie'],
    'inputType' => 'select',
    'options'   => [
        'accent'    => 'Akzentfarbe (Standard)',
        'primary'   => 'Primärfarbe',
        'dim'       => 'Lime-Grün',
        'fixed'     => 'Gelbgrün',
        'secondary' => 'Lavendel',
        'purple'    => 'Lila',
        'orange'    => 'Orange',
        'red'       => 'Rot',
    ],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'accent'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_tabs_style'] = [
    'label'     => ['Stil', 'Dunkel (Shell-Look) oder Hell (für weißen Seitenbereich)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Standard)', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default 'dark'",
];

// ============================================================
// RCT Pricing Table
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_pricing_table'] =
    '{type_legend},type;{pricing_legend},rct_pricing_data,rct_pricing_style;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_pricing_data'] = [
    'label'     => ['Preistabelle', "Blöcke durch --- trennen. Zeile 1: Name|Preis|Zeitraum|highlight — dann Features mit + / - / neutral — dann > Button|/url"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:220px; font-family:monospace', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_pricing_style'] = [
    'label'     => ['Stil', 'Dunkel (Terminal-Look) oder Hell (Editorial)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(8) NOT NULL default 'dark'",
];

// ============================================================
// RCT Hero
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_hero'] =
    '{type_legend},type;{hero_legend},rct_hero_overline,rct_hero_headline,rct_hl_font,rct_content_color,rct_hero_body,rct_hero_layout;{hero_btn1_legend},rct_hero_btn1_label,rct_hero_btn1_page,rct_hero_btn1_url,rct_hero_btn1_style,rct_hero_btn1_target;{hero_btn2_legend:hide},rct_hero_btn2_label,rct_hero_btn2_page,rct_hero_btn2_url,rct_hero_btn2_style,rct_hero_btn2_target;{hero_image_legend:hide},rct_hero_image,rct_hero_image_alt,rct_hero_slide_speed;{hero_stats_legend:hide},rct_hero_stats;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_overline'] = [
    'label'     => ['Overline', 'Kleiner Text über der Überschrift, z.B. "Contao 5 · Design System"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql'       => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_headline'] = [
    'label'     => ['Überschrift', 'Hauptüberschrift. Tipp: <em>Wort</em> umschließen → das Wort erscheint in der Akzentfarbe'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 512, 'tl_class' => 'clr long'],
    'sql'       => "varchar(512) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_body'] = [
    'label'     => ['Begleittext', 'Kurzer beschreibender Text unter der Überschrift (optional)'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_layout'] = [
    'label'     => ['Layout', 'Zentriert (kein Bild) oder zweispaltig mit Bild rechts'],
    'inputType' => 'select',
    'options'   => ['centered' => 'Zentriert', 'split' => 'Zweispaltig (Bild rechts)'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'centered'",
];

// Button 1
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn1_label'] = [
    'label'     => ['Button-Text', 'Beschriftung des primären Buttons'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql'       => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn1_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als Ziel (hat Vorrang vor URL-Feld)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn1_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (wird ignoriert wenn eine Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'rgxp' => 'url', 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn1_style'] = [
    'label'     => ['Button-Stil', 'Optik des primären Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Gefüllt (Primary)', 'outline' => 'Outline', 'ghost' => 'Ghost'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'primary'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn1_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

// Button 2
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn2_label'] = [
    'label'     => ['Button 2 Text', 'Beschriftung des sekundären Buttons (optional)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql'       => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn2_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als Ziel (hat Vorrang vor URL-Feld)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn2_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (wird ignoriert wenn eine Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'rgxp' => 'url', 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn2_style'] = [
    'label'     => ['Button 2 Stil', 'Optik des sekundären Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Gefüllt (Primary)', 'outline' => 'Outline', 'ghost' => 'Ghost'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(12) NOT NULL default 'outline'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn2_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''",
];

// Bilder (nur relevant bei Layout "split")
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_image'] = [
    'label'     => ['Bilder', 'Ein oder mehrere Bilder für die rechte Spalte. Mehrere Bilder = automatischer Fade-Wechsel.'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif', 'fieldType' => 'checkbox', 'orderField' => 'rct_hero_image_order', 'tl_class' => 'clr'],
    'sql'       => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_image_order'] = [
    'sql' => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_slide_speed'] = [
    'label'     => ['Wechsel-Intervall', 'Zeit zwischen Bildwechseln (nur bei mehreren Bildern)'],
    'inputType' => 'select',
    'options'   => ['3' => '3 Sekunden', '5' => '5 Sekunden', '8' => '8 Sekunden', '10' => '10 Sekunden'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(4) NOT NULL default '5'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_image_alt'] = [
    'label'     => ['Alt-Text', 'Alternativtext für das Bild (Barrierefreiheit)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

// Stats
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_stats'] = [
    'label'     => ['Kennzahlen', "Bis zu 3 Kennzahlen, eine pro Zeile.\nFormat: Wert|Beschriftung, z.B.: 18|Komponenten"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px; font-family:monospace', 'tl_class' => 'clr'],
    'sql'       => "text NULL",
];
