<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Rallo\ContaoTheme\DCA\RctFontOptionsCallback;
use Rallo\ContaoTheme\DCA\IconPickerWizard;

// Stil-Feld zur nativen Contao-Akkordeon-Palette hinzufügen.
// (cssID ist im Contao-5-Standard schon in der expert_legend — kein
// zusaetzliches addField noetig, sonst rendert es doppelt im BE.)
PaletteManipulator::create()
    ->addField('rct_accordion_style', 'closeAll', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('accordion', 'tl_content');

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = static function (): void {
    PaletteManipulator::create()
        ->removeField('customTpl')
        ->applyToPalette('accordion', 'tl_content');
};

// Max-Height + Effect zu den Slider-Paletten (sliderStart = Gruppen-Slider, swiper = standalone)
PaletteManipulator::create()
    ->addField('rct_slider_max_height', 'sliderContinuous', PaletteManipulator::POSITION_AFTER)
    ->addField('rct_slider_max_height_mobile', 'rct_slider_max_height', PaletteManipulator::POSITION_AFTER)
    ->addField('rct_slider_effect', 'rct_slider_max_height_mobile', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('sliderStart', 'tl_content');

PaletteManipulator::create()
    ->addField('rct_slider_max_height', 'sliderContinuous', PaletteManipulator::POSITION_AFTER)
    ->addField('rct_slider_max_height_mobile', 'rct_slider_max_height', PaletteManipulator::POSITION_AFTER)
    ->addField('rct_slider_effect', 'rct_slider_max_height_mobile', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('swiper', 'tl_content');

// Standard-CE-Mods ohne 'sql' → jsonData (RctStandardModsJsonStorageMigration)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_slider_max_height'] = [
    'label'     => ['Slide-Höhe (Desktop)', 'Max-Höhe der Slides ab 1025px (z.B. 400px, 50vh). Inhalt wird vertikal zentriert, Überstand abgeschnitten.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50', 'rgxp' => 'alnum'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_slider_max_height_mobile'] = [
    'label'     => ['Slide-Höhe (Mobile)', 'Max-Höhe der Slides ≤1024px (z.B. 300px, 60vh). Leer = Desktop-Wert wird verwendet.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50', 'rgxp' => 'alnum'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_slider_effect'] = [
    'label'     => ['Übergangs-Effekt', 'Slide (Standard) oder Fade (Cross-Fade)'],
    'inputType' => 'select',
    'options'   => ['' => 'Slide (Standard)', 'fade' => 'Fade (Cross-Fade)'],
    'eval'      => ['tl_class' => 'w50'],
];

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
];

// Schriftart + Textfarbe für Listen
PaletteManipulator::create()
    ->addField('rct_hl_font', 'listitems', PaletteManipulator::POSITION_AFTER)
    ->addField('rct_content_color', 'rct_hl_font', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('list', 'tl_content');

// Textfarbe in Standard-Elementen (text + headline)
PaletteManipulator::create()
    ->addField('rct_content_color', 'text', PaletteManipulator::POSITION_AFTER)
    ->removeField('customTpl')
    ->applyToPalette('text', 'tl_content');

PaletteManipulator::create()
    ->addField('rct_hl_font', 'hl', PaletteManipulator::POSITION_AFTER)
    ->addField('rct_content_color', 'rct_hl_font', PaletteManipulator::POSITION_AFTER)
    ->addField('rct_text_align', 'rct_content_color', PaletteManipulator::POSITION_AFTER)
    ->removeField('customTpl')
    ->applyToPalette('headline', 'tl_content');

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hl_font'] = [
    'label'            => ['Schriftart', 'Abweichender Font für diese Überschrift. Leer = globaler Standard-Font.'],
    'inputType'        => 'select',
    'options_callback' => [RctFontOptionsCallback::class, 'getHeadlineFonts'],
    'eval'             => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_content_color'] = [
    'label'     => ['Textfarbe', 'Optionale Farbe für Text/Überschrift. Hex (#27c4f4) oder leer lassen.'],
    'inputType' => 'text',
    'eval'      => ['colorpicker' => true, 'isHexColor' => true, 'tl_class' => 'w50 wizard', 'maxlength' => 64],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_text_align'] = [
    'label'     => ['Ausrichtung', 'Textausrichtung der Überschrift'],
    'inputType' => 'select',
    'options'   => ['' => '– Standard –', 'left' => 'Links', 'center' => 'Zentriert', 'right' => 'Rechts'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_people_box'] =
    '{type_legend},type;{people_legend},rct_person_name,rct_person_role,rct_person_image,size,rct_person_bio,rct_people_box_style;{contact_legend:hide},rct_person_email,rct_person_phone,rct_person_link,rct_person_link_text;{invisible_legend:hide},invisible,start,stop';

// rct_people_box_* + rct_person_* ohne 'sql' → jsonData (RctPeopleBoxJsonStorageMigration).
// rct_person_image (fileTree) bleibt als Spalte.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_people_box_style'] = [
    'label'     => ['Stil', 'Hell (Standard) oder Dunkel (Shell-Look)'],
    'inputType' => 'select',
    'options'   => ['light' => 'Hell (Standard)', 'dark' => 'Dunkel (Shell-Look)'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_name'] = [
    'label'     => ['Name', 'Vollständiger Name der Person'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_role'] = [
    'label'     => ['Rolle / Position', 'Berufsbezeichnung oder Funktion'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_bio'] = [
    'label'     => ['Kurzbiografie', 'Kurzer Beschreibungstext'],
    'inputType' => 'textarea',
    'eval'      => ['rte' => '', 'tl_class' => 'clr'],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_phone'] = [
    'label'     => ['Telefon', 'Telefonnummer'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_link'] = [
    'label'     => ['Link (URL)', 'z.B. LinkedIn-Profil oder Website'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'rgxp' => 'url', 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_person_link_text'] = [
    'label'     => ['Link-Text', 'Anzeigetext für den Link'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
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

// rct_gallery_layout/cols/sortby/filter/lightbox ohne 'sql' → jsonData
// (RctGalleryJsonStorageMigration). images + folder bleiben als fileTree-Spalten.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_layout'] = [
    'label'     => ['Layout', 'Anordnung der Bilder'],
    'inputType' => 'select',
    'options'   => ['masonry' => 'Masonry (dynamisch)', 'fitrows' => 'Gleichmäßiges Raster'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_cols'] = [
    'label'     => ['Spalten', 'Anzahl Spalten (Desktop)'],
    'inputType' => 'select',
    'options'   => ['2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'],
    'eval'      => ['tl_class' => 'w50'],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_filter'] = [
    'label'     => ['Filter-Buttons', 'Unterordner als Filterkategorien anzeigen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gallery_lightbox'] = [
    'label'     => ['Lightbox', 'Bilder in Lightbox öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_map'] =
    '{type_legend},type;{map_legend},rct_map_address,rct_map_marker,rct_map_zoom,rct_map_height;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_map_* ohne 'sql' → jsonData (RctMapJsonStorageMigration)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_map_address'] = [
    'label'     => ['Adresse', 'Vollständige Adresse (Straße, PLZ, Ort)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_map_marker'] = [
    'label'     => ['Marker-Text', 'Text im Popup-Fenster des Kartenmarkers (optional)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_map_zoom'] = [
    'label'     => ['Zoom-Stufe', 'Zoom der Karte (14 = Straßenebene)'],
    'inputType' => 'select',
    'options'   => ['10' => '10 – Stadtebene', '12' => '12 – Stadtteil', '14' => '14 – Straße (Standard)', '16' => '16 – Gebäude', '18' => '18 – Maximum'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_map_height'] = [
    'label'     => ['Kartenhöhe', 'Höhe des Kartenausschnitts'],
    'inputType' => 'select',
    'options'   => ['250px' => '250px', '300px' => '300px', '400px' => '400px (Standard)', '500px' => '500px', '600px' => '600px'],
    'eval'      => ['tl_class' => 'w50'],
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

// String-Felder ohne 'sql' → jsonData. fileTree-Felder oben behalten 'sql'
// (BINARY UUIDs lassen sich nicht sauber als JSON serialisieren).
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_parallax_height'] = [
    'label'     => ['Mindesthöhe', 'Minimale Höhe des Bereichs'],
    'inputType' => 'select',
    'options'   => ['' => 'Auto (Inhalt bestimmt Höhe)', '200px' => '200px', '300px' => '300px', '50vh' => '50vh', '100vh' => 'Fullscreen (100vh)'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_parallax_overlay'] = [
    'label'     => ['Overlay', 'Dunkles Overlay über dem Hintergrund (verbessert Lesbarkeit)'],
    'inputType' => 'select',
    'options'   => ['' => 'Kein Overlay', '20' => '20%', '40' => '40%', '60' => '60%', '80' => '80%'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_grid_start'] =
    '{type_legend},type;{grid_legend},rct_columns,rct_gap,rct_align;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_grid_col_start'] =
    '{type_legend},type;{grid_legend},rct_gap,rct_align;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_grid_col_end'] =
    '{type_legend},type;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_fullwidth_start'] =
    '{type_legend},type;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_fullwidth_end'] =
    '{type_legend},type;{invisible_legend:hide},invisible,start,stop';

// ============================================================
// RCT Slider Box
// ============================================================
$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_slider_box'] =
    '{type_legend},type;{image_legend},rct_sb_image,rct_sb_image_alt,rct_sb_bg_position,rct_sb_overlay,rct_sb_min_height;{content_legend},rct_sb_overline,rct_sb_headline,rct_sb_text,rct_sb_align,rct_content_color;{link_legend:hide},rct_sb_link_page,rct_sb_link_url,rct_sb_link_label,rct_sb_link_style,rct_sb_link_target,rct_sb_link2_page,rct_sb_link2_url,rct_sb_link2_label,rct_sb_link2_style,rct_sb_link2_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_image'] = [
    'label'     => ['Hintergrundbild', 'Bild als Slide-Hintergrund (cover, position konfigurierbar)'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif', 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'       => "binary(16) NULL",
];

// rct_sb_* ohne 'sql' → jsonData (RctSliderBoxJsonStorageMigration).
// rct_sb_image (fileTree) + rct_sb_link_page/link2_page (pageTree-relations) bleiben als Spalten.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_image_alt'] = [
    'label'     => ['Alt-Text', 'Bildbeschreibung (Barrierefreiheit)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_bg_position'] = [
    'label'     => ['Bild-Ausschnitt (BG-Position)', 'Welcher Teil des Bildes ist beim Croppen sichtbar (besonders relevant für Mobile)'],
    'inputType' => 'select',
    'options'   => [
        'center'        => 'Mitte (Standard)',
        'top'           => 'Oben',
        'bottom'        => 'Unten',
        'left'          => 'Links',
        'right'         => 'Rechts',
        'top left'      => 'Oben links',
        'top right'     => 'Oben rechts',
        'bottom left'   => 'Unten links',
        'bottom right'  => 'Unten rechts',
    ],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_overlay'] = [
    'label'     => ['Overlay', 'Dunkles Overlay über dem BG (verbessert Textlesbarkeit)'],
    'inputType' => 'select',
    'options'   => ['' => 'Kein Overlay', '20' => '20%', '30' => '30%', '40' => '40%', '50' => '50%', '60' => '60%', '70' => '70%', '80' => '80%'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_min_height'] = [
    'label'     => ['Mindesthöhe', 'z.B. 500px, 70vh, 100vh'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50', 'rgxp' => 'alnum'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_overline'] = [
    'label'     => ['Overline', 'Kleiner Text über der Headline (z.B. Kategorie, Eyebrow)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_headline'] = [
    'label'     => ['Headline', 'Hauptüberschrift (H2)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_text'] = [
    'label'     => ['Text', 'Body-Text unter der Headline'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:120px', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_align'] = [
    'label'     => ['Text-Ausrichtung', 'Horizontale Ausrichtung von Headline/Text/Button'],
    'inputType' => 'select',
    'options'   => ['left' => 'Links', 'center' => 'Mittig', 'right' => 'Rechts'],
    'eval'      => ['tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als CTA-Ziel (Vorrang vor URL)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (wird ignoriert wenn Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link_label'] = [
    'label'     => ['Link-Text', 'Beschriftung des CTA-Buttons (Standard: „Mehr erfahren")'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link_style'] = [
    'label'     => ['Button-Stil', 'Optisches Erscheinungsbild des Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Primary (gefüllt)', 'outline' => 'Outline (Rahmen)', 'ghost' => 'Ghost (transparent)'],
    'eval'      => ['tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link2_page'] = [
    'label'      => ['Zweiter Button — Interne Seite', 'Optionaler zweiter CTA-Button'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link2_url'] = [
    'label'     => ['Zweiter Button — Externe URL', 'Manuelle URL (wird ignoriert wenn Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link2_label'] = [
    'label'     => ['Zweiter Button — Text', 'Beschriftung; leer = Button wird nicht angezeigt'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link2_style'] = [
    'label'     => ['Zweiter Button — Stil', 'Optisches Erscheinungsbild'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Primary (gefüllt)', 'outline' => 'Outline (Rahmen)', 'ghost' => 'Ghost (transparent)'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_sb_link2_target'] = [
    'label'     => ['Zweiter Button — Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_icon_reference'] =
    '{type_legend},type;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// ============================================================
// RCT Image-Textbox & Icon-Textbox
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_image_textbox'] =
    '{type_legend},type;{content_legend},rct_itb_image,rct_itb_image_alt,rct_itb_headline,rct_itb_text,rct_itb_style,rct_itb_layout;{link_legend:hide},rct_itb_link_page,rct_itb_link_url,rct_itb_link_label,rct_itb_link_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_icon_textbox'] =
    '{type_legend},type;{content_legend},rct_itb_icon,rct_itb_headline,rct_itb_text,rct_itb_style,rct_itb_layout;{link_legend:hide},rct_itb_link_page,rct_itb_link_url,rct_itb_link_label,rct_itb_link_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_image'] = [
    'label'     => ['Bild', 'Bild oben in der Box'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif,gif', 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'       => "binary(16) NULL",
];

// rct_itb_* ohne 'sql' → jsonData (RctTextboxJsonStorageMigration).
// rct_itb_image (fileTree) + rct_itb_link_page (pageTree-relation) bleiben als Spalten.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_image_alt'] = [
    'label'     => ['Alt-Text', 'Alternativtext für das Bild (Barrierefreiheit)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_icon'] = [
    'label'     => ['Icon / Emoji', 'Emoji (🚀 ⭐), Unicode-Symbol oder tabler:<slug>. Picker-Button öffnet die Icon-Auswahl.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50 wizard'],
    'wizard'    => [[IconPickerWizard::class, 'generate']],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_headline'] = [
    'label'     => ['Überschrift', 'Titel der Box'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_text'] = [
    'label'     => ['Text', 'Beschreibungstext'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:100px', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_style'] = [
    'label'     => ['Stil', 'Hell oder dunkel'],
    'inputType' => 'select',
    'options'   => ['light' => 'Hell', 'dark' => 'Dunkel (Shell-Look)'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_layout'] = [
    'label'     => ['Layout', 'Anordnung von Bild und Text'],
    'inputType' => 'select',
    'options'   => ['top' => 'Bild oben (Standard)', 'left' => 'Bild links', 'right' => 'Bild rechts'],
    'default'   => 'top',
    'eval'      => ['tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte (Doctrine-Lazy-Load via tl_page)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_link_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als Ziel (Vorrang vor URL)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_link_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (wird ignoriert wenn Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_link_label'] = [
    'label'     => ['Link-Text', 'Beschriftung des Links (Standard: „Mehr erfahren")'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_itb_link_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// ============================================================
// RCT Fun-Box
// ============================================================
$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_fun_box'] =
    '{type_legend},type;{content_legend},rct_fb_image,rct_fb_image_alt,rct_fb_icon,rct_fb_headline,rct_fb_text,rct_fb_color,rct_content_color;{link_legend:hide},rct_fb_link_page,rct_fb_link_url,rct_fb_link_label,rct_fb_link_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_image'] = [
    'label'     => ['Hintergrundbild', 'Füllt die Karte als Hintergrund'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif,gif', 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'       => "binary(16) NULL",
];

// rct_fb_* ohne 'sql' → jsonData (RctFunBoxJsonStorageMigration).
// rct_fb_image (fileTree) + rct_fb_link_page (pageTree-relation) bleiben als Spalten.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_image_alt'] = [
    'label'     => ['Alt-Text', 'Alternativtext für das Bild (Barrierefreiheit)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_icon'] = [
    'label'     => ['Icon / Emoji', 'Emoji, Unicode-Symbol oder tabler:<slug>. Picker-Button öffnet die Icon-Auswahl.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50 wizard'],
    'wizard'    => [[IconPickerWizard::class, 'generate']],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_headline'] = [
    'label'     => ['Überschrift', 'Immer sichtbar'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_text'] = [
    'label'     => ['Text', 'Erscheint erst beim Hover'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:100px', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_color'] = [
    'label'     => ['Hover-Farbe', 'Farbe des Gradient-Overlays beim Hover'],
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
];

// pageTree mit relation → bleibt als Spalte
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_link_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als Ziel (Vorrang vor URL)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_link_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (wird ignoriert wenn Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_link_label'] = [
    'label'     => ['Link-Text', 'Text neben dem Pfeil (Standard: „Mehr erfahren")'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_fb_link_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// rct_columns / rct_gap / rct_align ohne 'sql' — Werte landen in
// tl_content.jsonData. Daten-Migration: RctGridJsonStorageMigration.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_columns'] = [
    'label'     => ['Spalten', 'Anzahl der Spalten'],
    'inputType' => 'select',
    'options'   => ['2' => '2 Spalten', '3' => '3 Spalten', '4' => '4 Spalten'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_gap'] = [
    'label'     => ['Abstand', 'Abstand zwischen den Elementen'],
    'inputType' => 'select',
    'options'   => ['' => 'Normal', 'rct-gap--sm' => 'Klein', 'rct-gap--lg' => 'Groß'],
    'eval'      => ['tl_class' => 'w50'],
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
];

// ============================================================
// RCT Stat Box
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_stat_box'] =
    '{type_legend},type;{stat_legend},rct_stat_value,rct_stat_prefix,rct_stat_unit,rct_stat_label,rct_stat_sublabel,rct_stat_icon,rct_stat_color,rct_stat_size;{invisible_legend:hide},invisible,start,stop';

// rct_stat_* ohne 'sql' → jsonData (RctStatBoxJsonStorageMigration)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_value'] = [
    'label'     => ['Wert', 'Die Kennzahl (Ganzzahl oder Dezimalzahl, z.B. 1250 oder 98.6)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_prefix'] = [
    'label'     => ['Präfix', 'Zeichen vor der Zahl, z.B. ">" oder "ca." oder "+"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_unit'] = [
    'label'     => ['Einheit', 'Einheit hinter der Zahl, z.B. "%" oder "€" oder "Mitglieder"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_label'] = [
    'label'     => ['Label', 'Beschriftung unter der Zahl'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_sublabel'] = [
    'label'     => ['Zweite Zeile', 'Optionaler Zusatztext unter dem Label (kleiner, gedimmt)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'clr long'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_icon'] = [
    'label'     => ['Icon / Emoji', 'Emoji (🏆 ✓), Unicode-Symbol oder tabler:<slug>. Picker-Button öffnet die Icon-Auswahl.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50 wizard'],
    'wizard'    => [[IconPickerWizard::class, 'generate']],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_stat_size'] = [
    'label'     => ['Größe', 'Schriftgröße der Kennzahl'],
    'inputType' => 'select',
    'options'   => ['sm' => 'Klein', 'md' => 'Mittel (Standard)', 'lg' => 'Groß'],
    'eval'      => ['tl_class' => 'w50'],
];

// ============================================================
// RCT CTA
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_cta'] =
    '{type_legend},type;{cta_legend},rct_cta_headline,rct_cta_text,rct_cta_icon,rct_cta_color,rct_cta_layout,rct_cta_style;{cta_bg_legend:hide},rct_cta_bg_color,rct_cta_bg_alpha,rct_cta_blur;{cta_btn1_legend},rct_cta_btn1_label,rct_cta_btn1_page,rct_cta_btn1_url,rct_cta_btn1_style,rct_cta_btn1_target;{cta_btn2_legend:hide},rct_cta_btn2_label,rct_cta_btn2_page,rct_cta_btn2_url,rct_cta_btn2_style,rct_cta_btn2_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_cta_* ohne 'sql' → jsonData (RctCtaJsonStorageMigration).
// rct_cta_btn1_page + rct_cta_btn2_page (pageTree-relations) bleiben als Spalten.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_headline'] = [
    'label'     => ['Überschrift', 'Hauptaussage des CTA'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_text'] = [
    'label'     => ['Text', 'Kurzer Begleittext (optional)'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_icon'] = [
    'label'     => ['Icon / Emoji', 'Emoji (🚀), Unicode-Symbol oder tabler:<slug>. Picker-Button öffnet die Icon-Auswahl.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50 wizard'],
    'wizard'    => [[IconPickerWizard::class, 'generate']],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_style'] = [
    'label'     => ['Stil', 'Hell (für weißen Seitenbereich) oder Dunkel (Shell-Look)'],
    'inputType' => 'select',
    'options'   => [
        'light' => 'Hell (Standard)',
        'dark'  => 'Dunkel (Shell-Look)',
    ],
    'eval'      => ['tl_class' => 'w50'],
];

// CTA Hintergrund (überschreibt den Stil-Default wenn gesetzt)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_bg_color'] = [
    'label'     => ['Hintergrundfarbe', 'Überschreibt den Stil-Default. Leer = Stil-Default verwenden'],
    'inputType' => 'select',
    'options'   => [
        ''       => 'Stil-Default',
        'dark'   => 'Dunkel (#171717)',
        'white'  => 'Weiß',
        'accent' => 'Akzentfarbe (Cyan)',
    ],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_bg_alpha'] = [
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

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_blur'] = [
    'label'     => ['Backdrop-Blur', 'Weichzeichner-Effekt hinter der CTA-Box (Frosted Glass)'],
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

// Button 1
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn1_label'] = [
    'label'     => ['Button-Text', 'Beschriftung des primären Buttons'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn1_style'] = [
    'label'     => ['Button-Stil', 'Optik des primären Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Gefüllt (Primary)', 'outline' => 'Outline', 'ghost' => 'Ghost'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn1_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// Button 2
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn2_label'] = [
    'label'     => ['Button 2 Text', 'Beschriftung des sekundären Buttons (optional)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn2_style'] = [
    'label'     => ['Button 2 Stil', 'Optik des sekundären Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Gefüllt (Primary)', 'outline' => 'Outline', 'ghost' => 'Ghost'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_cta_btn2_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// rct_accordion_style — wird vom nativen Contao-Akkordeon (accordion) genutzt.
// Ohne 'sql' → jsonData (RctAccordionStyleJsonStorageMigration).
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_accordion_style'] = [
    'label'     => ['Stil', 'Dunkel (Standard) oder Hell'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Classified Archive)', 'light' => 'Hell (Editorial)'],
    'eval'      => ['tl_class' => 'w50'],
];

// ============================================================
// RCT Timeline
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_timeline'] =
    '{type_legend},type;{timeline_legend},rct_timeline_data,rct_timeline_color,rct_timeline_variant,rct_timeline_show_line,rct_timeline_style;{invisible_legend:hide},invisible,start,stop';

// rct_timeline_* ohne 'sql' → jsonData (RctTimelineJsonStorageMigration)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_data'] = [
    'label'     => ['Timeline-Einträge', "Einträge durch '---' trennen.\nZeile 1: Datum|Titel|Farbe|Icon\nZeile 2+: Beschreibungstext"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:220px; font-family:monospace', 'tl_class' => 'clr'],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_variant'] = [
    'label'     => ['Darstellung', 'Wie die Einträge angeordnet werden'],
    'inputType' => 'select',
    'options'   => [
        'alternate' => 'Alternierend links/rechts (Standard)',
        'single'    => 'Einspaltig (immer links)',
    ],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_show_line'] = [
    'label'     => ['Verbindungslinie anzeigen', 'Vertikale Linie zwischen den Einträgen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_timeline_style'] = [
    'label'     => ['Stil', 'Dunkel (Shell-Look) oder Hell (für weißen Seitenbereich)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Standard)', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
];

// ============================================================
// RCT Chart Bars
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_chart_bars'] =
    '{type_legend},type;{chart_legend},rct_chart_bars_data,rct_chart_orientation,rct_chart_color,rct_chart_show_values,rct_content_color;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_chart_* ohne 'sql' → jsonData (RctChartBarsJsonStorageMigration)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_chart_bars_data'] = [
    'label'     => ['Balken-Daten', 'Eine Zeile pro Balken: Label|Wert (0–100). Zeilen mit # werden ignoriert.'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:140px; font-family:monospace', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_chart_orientation'] = [
    'label'     => ['Diagramm-Typ', 'Darstellung der Daten'],
    'inputType' => 'select',
    'options'   => [
        'vertical'   => 'Vertikal (Säulendiagramm)',
        'horizontal' => 'Horizontal (Balkendiagramm)',
        'pie'        => 'Tortendiagramm',
        'donut'      => 'Donut-Diagramm',
    ],
    'eval'      => ['tl_class' => 'w50'],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_chart_show_values'] = [
    'label'     => ['Prozentzahl anzeigen', 'Wert als hochzählende Zahl neben dem Label anzeigen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// ============================================================
// RCT Icon Box
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_icon_box'] =
    '{type_legend},type;{icon_box_legend},rct_icon_box_icon,rct_icon_box_headline,rct_icon_box_text,rct_icon_box_color,rct_icon_box_align,rct_icon_box_style;{icon_box_link_legend:hide},rct_icon_box_link_page,rct_icon_box_link_url,rct_icon_box_link_label,rct_icon_box_link_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_icon_box_* ohne 'sql' → jsonData. link_page bleibt als int-Spalte
// (foreignKey/relation für Lazy-Load via tl_page).
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_icon'] = [
    'label'     => ['Icon / Emoji', 'Emoji (🚀 ⭐), Unicode-Symbol oder tabler:<slug>. Picker-Button öffnet die Icon-Auswahl.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50 wizard'],
    'wizard'    => [[IconPickerWizard::class, 'generate']],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_headline'] = [
    'label'     => ['Überschrift', 'Titel der Feature-Box'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_text'] = [
    'label'     => ['Text', 'Beschreibungstext der Feature-Box'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'tl_class' => 'clr'],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_align'] = [
    'label'     => ['Ausrichtung', 'Inhalt zentriert oder linksbündig'],
    'inputType' => 'select',
    'options'   => [
        'centered' => 'Zentriert',
        'left'     => 'Linksbündig',
    ],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_style'] = [
    'label'     => ['Stil', 'Dunkel (Shell-Look) oder Hell (für weißen Seitenbereich)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Standard)', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte (Doctrine-Lazy-Load via tl_page)
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_link_label'] = [
    'label'     => ['Link-Text', 'Beschriftung des Links (Standard: „Mehr erfahren")'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_icon_box_link_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr'],
];

// ============================================================
// RCT Alert
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_alert'] =
    '{type_legend},type;{alert_legend},rct_alert_type,rct_alert_title,rct_alert_text,rct_alert_dismissible,rct_alert_style;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_alert_* Felder ohne 'sql' — Werte landen in tl_content.jsonData
// (Contao 5.7+ JSON-Storage). Daten-Migration: RctAlertJsonStorageMigration (v1.5.4).
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_title'] = [
    'label'     => ['Titel', 'Optionale Überschrift der Meldung'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_text'] = [
    'label'     => ['Text', 'Inhalt der Meldung'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_dismissible'] = [
    'label'     => ['Schließbar', 'Schließen-Button anzeigen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_alert_style'] = [
    'label'     => ['Stil', 'Dunkel (Shell-Look) oder Hell (für weißen Seitenbereich)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Standard)', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
];

// ============================================================
// RCT Tabs
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_tabs'] =
    '{type_legend},type;{tabs_legend},rct_tabs_data,rct_tabs_color,rct_tabs_style;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_tabs_* ohne 'sql' → jsonData (RctTabsJsonStorageMigration)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_tabs_data'] = [
    'label'     => ['Tab-Inhalte', "Ein Tab pro Block, getrennt durch ---\nErste Zeile = Tab-Titel, dann Inhalt.\nEmoji möglich: 🚀 Tab-Titel"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:200px; font-family: monospace;', 'tl_class' => 'clr'],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_tabs_style'] = [
    'label'     => ['Stil', 'Dunkel (Shell-Look) oder Hell (für weißen Seitenbereich)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel (Standard)', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
];

// ============================================================
// RCT Pricing Table
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_pricing_table'] =
    '{type_legend},type;{pricing_legend},rct_pricing_data,rct_pricing_style;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_pricing_* ohne 'sql' → jsonData (RctPricingTableJsonStorageMigration)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_pricing_data'] = [
    'label'     => ['Preistabelle', "Blöcke durch --- trennen. Zeile 1: Name|Preis|Zeitraum|highlight — dann Features mit + / - / neutral — dann > Button|/url"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:220px; font-family:monospace', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_pricing_style'] = [
    'label'     => ['Stil', 'Dunkel (Terminal-Look) oder Hell (Editorial)'],
    'inputType' => 'select',
    'options'   => ['dark' => 'Dunkel', 'light' => 'Hell'],
    'eval'      => ['tl_class' => 'w50'],
];

// ============================================================
// RCT Hero
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_hero'] =
    '{type_legend},type;{hero_legend},rct_hero_overline,rct_hero_headline,rct_hl_font,rct_content_color,rct_hero_body,rct_hero_layout,rct_hero_max_width;{hero_bg_legend:hide},rct_hero_bg_color,rct_hero_bg_alpha,rct_hero_blur;{hero_btn1_legend},rct_hero_btn1_label,rct_hero_btn1_page,rct_hero_btn1_url,rct_hero_btn1_style,rct_hero_btn1_target;{hero_btn2_legend:hide},rct_hero_btn2_label,rct_hero_btn2_page,rct_hero_btn2_url,rct_hero_btn2_style,rct_hero_btn2_target;{hero_image_legend:hide},rct_hero_image,rct_hero_image_alt,rct_hero_slide_speed;{hero_stats_legend:hide},rct_hero_stats;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_hero_* ohne 'sql' → jsonData (RctHeroJsonStorageMigration).
// rct_hero_image (fileTree) + image_order (orderField) +
// btn1_page/btn2_page (pageTree-relations) bleiben als Spalten.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_overline'] = [
    'label'     => ['Overline', 'Kleiner Text über der Überschrift, z.B. "Contao 5 · Design System"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_headline'] = [
    'label'     => ['Überschrift', 'Hauptüberschrift. Tipp: <em>Wort</em> umschließen → das Wort erscheint in der Akzentfarbe'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 512, 'tl_class' => 'clr long'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_body'] = [
    'label'     => ['Begleittext', 'Kurzer beschreibender Text unter der Überschrift (optional)'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_layout'] = [
    'label'     => ['Layout', 'Zentriert (kein Bild) oder zweispaltig mit Bild rechts'],
    'inputType' => 'select',
    'options'   => ['centered' => 'Zentriert', 'split' => 'Zweispaltig (Bild rechts)'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_max_width'] = [
    'label'     => ['Maximale Breite', 'Begrenzt die Hero-Breite — sinnvoll in Fullwidth-Articles. Leer = volle Breite.'],
    'inputType' => 'select',
    'options'   => [
        ''       => 'Volle Breite (kein Limit)',
        '1920px' => '1920px',
        '1600px' => '1600px',
        '1440px' => '1440px (Theme-Standard)',
        '1200px' => '1200px',
        '1000px' => '1000px',
        '800px'  => '800px',
    ],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

// Hero Hintergrund (analog CTA — färbt direkt die .rct-hero-Section)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_bg_color'] = [
    'label'     => ['Hintergrundfarbe', 'Färbt die Hero-Section direkt. Leer = transparent.'],
    'inputType' => 'select',
    'options'   => [
        ''       => 'Transparent',
        'dark'   => 'Dunkel (#171717)',
        'white'  => 'Weiß',
        'accent' => 'Akzentfarbe (Cyan)',
    ],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_bg_alpha'] = [
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

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_blur'] = [
    'label'     => ['Backdrop-Blur', 'Weichzeichner-Effekt hinter der Hero-Section (Frosted Glass)'],
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

// Button 1
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn1_label'] = [
    'label'     => ['Button-Text', 'Beschriftung des primären Buttons'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn1_style'] = [
    'label'     => ['Button-Stil', 'Optik des primären Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Gefüllt (Primary)', 'outline' => 'Outline', 'ghost' => 'Ghost'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn1_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// Button 2
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn2_label'] = [
    'label'     => ['Button 2 Text', 'Beschriftung des sekundären Buttons (optional)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn2_style'] = [
    'label'     => ['Button 2 Stil', 'Optik des sekundären Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Gefüllt (Primary)', 'outline' => 'Outline', 'ghost' => 'Ghost'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_btn2_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// Bilder (nur relevant bei Layout "split")
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_image'] = [
    'label'     => ['Bilder', 'Ein oder mehrere Bilder für die rechte Spalte. Mehrere Bilder = automatischer Fade-Wechsel.'],
    'inputType' => 'fileTree',
    'eval'      => ['multiple' => true, 'filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif', 'fieldType' => 'checkbox', 'orderField' => 'rct_hero_image_order', 'tl_class' => 'clr'],
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
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_image_alt'] = [
    'label'     => ['Alt-Text', 'Alternativtext für das Bild (Barrierefreiheit)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

// Stats
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_hero_stats'] = [
    'label'     => ['Kennzahlen', "Bis zu 3 Kennzahlen, eine pro Zeile.\nFormat: Wert|Beschriftung, z.B.: 18|Komponenten"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:80px; font-family:monospace', 'tl_class' => 'clr'],
];

// ============================================================
// RCT Divider
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_divider'] =
    '{type_legend},type;{divider_legend},rct_divider_variant,rct_divider_height;{divider_data_legend:hide},rct_divider_label,rct_divider_index,rct_divider_total,rct_divider_segments,rct_divider_progress,rct_divider_start,rct_divider_end,rct_divider_status,rct_divider_status_dot,rct_divider_ruler_max,rct_divider_icon;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_divider_* Felder ohne 'sql' — Werte landen in tl_content.jsonData
// (Contao 5.7+ JSON-Storage). Daten-Migration: RctDividerJsonStorageMigration (v1.5.5).
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_variant'] = [
    'label'     => ['Variante', '13 Trenner-Stile zur Auswahl'],
    'inputType' => 'select',
    'options'   => [
        'fade'     => '01 — Fade (Akzent-Verlauf)',
        'ticks'    => '02 — Tick Rail',
        'labeled'  => '03 — Labeled (mit Mono-Label)',
        'section'  => '04 — Section Starter (Akzentbalken)',
        'coord'    => '05 — Coord (Vermessung)',
        'marker'   => '06 — Marker (Diamond / Icon)',
        'bracket'  => '07 — Bracket Rail',
        'stepped'  => '08 — Stepped Accent',
        'caption'  => '09 — Caption Rule',
        'aurora'   => '10 — Aurora Gradient',
        'ruler'    => '11 — Ruler (Skala)',
        'counter'  => '12 — Counter (Pagination)',
        'dots'     => '13 — Dotted Rail',
    ],
    'eval'      => ['tl_class' => 'w50', 'mandatory' => true],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_height'] = [
    'label'     => ['Höhe (px)', 'Nur für Fade & Aurora — leer = 1px Hairline'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'maxlength' => 4, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_label'] = [
    'label'     => ['Label / Titel', 'Labeled (03), Section (04), Caption (09)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_index'] = [
    'label'     => ['Index / Nummer', 'Labeled "02", Section "§ 04", Counter "03" — fettgedruckt im Akzent'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_total'] = [
    'label'     => ['Total (Counter)', 'Counter (12): "03 / 12" → hier "12" eintragen'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 16, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_segments'] = [
    'label'     => ['Segmente (Stepped)', 'Stepped (08): Anzahl Segmente — Default 6'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'maxlength' => 2, 'tl_class' => 'w50', 'default' => 6],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_progress'] = [
    'label'     => ['Fortschritt (Stepped)', 'Stepped (08): Wieviele Segmente sind "an" (Akzent)'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'maxlength' => 2, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_start'] = [
    'label'     => ['Start-Label', 'Coord (05): "0,000" — Bracket (07): "BEGIN"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_end'] = [
    'label'     => ['End-Label', 'Coord (05): "1,440" — Bracket (07): "END"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_status'] = [
    'label'     => ['Status-Text (Caption)', 'Caption (09): rechter Status, z.B. "Live"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_status_dot'] = [
    'label'     => ['Status-Punkt', 'Caption (09): pulsierender Akzent-Punkt rechts'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_ruler_max'] = [
    'label'     => ['Skala-Maximum (Ruler)', 'Ruler (11): höchster Wert der Skala — Default 1200, 7 Beschriftungen 0…max'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'maxlength' => 6, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_divider_icon'] = [
    'label'     => ['Icon (Marker)', 'Marker (06): Emoji oder tabler:<slug>. Leer = klassische Diamond-Raute. Picker-Button öffnet die Icon-Auswahl.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50 wizard'],
    'wizard'    => [[IconPickerWizard::class, 'generate']],
];

// ============================================================
// RCT Productbox
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_productbox'] =
    '{type_legend},type;{productbox_legend},rct_productbox_banner,rct_productbox_color,rct_productbox_layout,rct_productbox_style;{productbox_image_legend},rct_productbox_images,rct_productbox_image_alt,rct_productbox_slide_speed;{productbox_content_legend},rct_productbox_headline,rct_productbox_subheadline,rct_productbox_stock,rct_productbox_stock_label,rct_productbox_text;{productbox_price_legend},rct_productbox_price_extra,rct_productbox_price_old,rct_productbox_price,rct_productbox_price_note;{productbox_btn_legend:hide},rct_productbox_btn_label,rct_productbox_btn_page,rct_productbox_btn_url,rct_productbox_btn_style,rct_productbox_btn_target;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_productbox_* ohne 'sql' → jsonData (RctProductboxJsonStorageMigration).
// rct_productbox_image (legacy fileTree) + _images (multi-fileTree) +
// _btn_page (pageTree-relation) bleiben als Spalten.
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_banner'] = [
    'label'     => ['Banner-Text', 'Optional. Erscheint oben links als Akzent-Streifen (z.B. "TOP PREIS", "NEU", "AKTION"). Leer lassen für keinen Banner.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_color'] = [
    'label'     => ['Akzentfarbe', 'Färbt Banner, Subheadline und Preis'],
    'inputType' => 'select',
    'options'   => [
        'accent'    => 'Akzentfarbe (Standard)',
        'primary'   => 'Primärfarbe',
        'secondary' => 'Lavendel',
        'orange'    => 'Orange',
        'red'       => 'Rot',
        'green'     => 'Grün',
        'purple'    => 'Lila',
    ],
    'eval'      => ['tl_class' => 'w50'],
];

// Legacy single-image (nicht mehr in Palette, dient als Fallback)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_image'] = [
    'label'     => ['Produktbild (legacy)', 'Wird nicht mehr in der Palette gezeigt — Fallback für vor v1.5'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif,gif,svg', 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'       => "binary(16) NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_images'] = [
    'label'     => ['Produktbilder', 'Ein oder mehrere Bilder. Bei mehreren: automatischer Fade-Wechsel auf der Karte.'],
    'inputType' => 'fileTree',
    'eval'      => ['multiple' => true, 'fieldType' => 'checkbox', 'filesOnly' => true, 'extensions' => 'jpg,jpeg,png,webp,avif,gif,svg', 'orderField' => 'orderSRC', 'tl_class' => 'clr'],
    'sql'       => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_slide_speed'] = [
    'label'     => ['Wechsel-Intervall (Sek.)', 'Nur bei mehreren Bildern. Default 5 Sekunden.'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'maxlength' => 3, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_layout'] = [
    'label'     => ['Layout', 'Vertikal (Bild oben) oder horizontal (Bild links). Mobile fällt automatisch auf vertikal zurück.'],
    'inputType' => 'select',
    'options'   => ['vertical' => 'Vertikal (Bild oben)', 'horizontal' => 'Horizontal (Bild links)'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_stock'] = [
    'label'     => ['Verfügbarkeits-Indikator', 'Optional. Färbt einen kleinen Punkt grün/orange/rot.'],
    'inputType' => 'select',
    'options'   => [
        ''           => 'Kein Indikator',
        'available'  => 'Verfügbar (grün, pulsiert)',
        'low'        => 'Wenige verfügbar (orange)',
        'sold_out'   => 'Ausverkauft (rot)',
    ],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_stock_label'] = [
    'label'     => ['Verfügbarkeits-Text', 'Text neben dem Indikator-Punkt, z.B. "Auf Lager", "Wenige verfügbar", "Ausverkauft"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_price_old'] = [
    'label'     => ['Vorheriger Preis (durchgestrichen)', 'Optional. z.B. "€ 149,90". Wird klein und durchgestrichen über dem aktuellen Preis angezeigt.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_image_alt'] = [
    'label'     => ['Alt-Text', 'Alternativtext für das Produktbild (Barrierefreiheit)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_headline'] = [
    'label'     => ['Produktname / Überschrift', 'z.B. "Propangas-Alukaufflasche 11 KG"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_subheadline'] = [
    'label'     => ['Subheadline', 'Kleine Mono-Zeile in Akzentfarbe direkt unter der Headline (z.B. Verfügbarkeit, Verkaufsort)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_text'] = [
    'label'     => ['Beschreibungstext', 'Kurze Beschreibung. Erlaubte Tags: <strong>, <em>, <b>, <i>, <u>, <small>, <br>. Attribute werden entfernt.'],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:100px', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_style'] = [
    'label'     => ['Stil', 'Hell (weiße Karte) oder Dunkel (Shell-Look)'],
    'inputType' => 'select',
    'options'   => ['light' => 'Hell', 'dark' => 'Dunkel (Shell-Look)'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_price_extra'] = [
    'label'     => ['Preis-Hinweis (oben)', 'Optional. Kleine Zeile über dem Preis (z.B. "Solange der Vorrat reicht", "Jetzt nur")'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_price'] = [
    'label'     => ['Preis', 'Großer Preis-Wert in Akzentfarbe, z.B. "€ 124,90". Leer lassen für keinen Preis.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_price_note'] = [
    'label'     => ['MwSt-Hinweis', 'Kleine Zeile unter dem Preis, z.B. "inkl. 19% MwSt." oder "zzgl. Versand"'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_btn_label'] = [
    'label'     => ['Button-Text', 'Optional. Leer = kein Button. Beschriftung des CTA-Buttons unten.'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
];

// pageTree mit relation → bleibt als Spalte
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_btn_page'] = [
    'label'      => ['Interne Seite', 'Contao-Seite als Ziel (Vorrang vor URL)'],
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_btn_url'] = [
    'label'     => ['Externe URL', 'Manuelle URL (wird ignoriert wenn Seite gewählt ist)'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_btn_style'] = [
    'label'     => ['Button-Stil', 'Optik des Buttons'],
    'inputType' => 'select',
    'options'   => ['primary' => 'Primary (gefüllt)', 'outline' => 'Outline', 'ghost' => 'Ghost (Pfeil)'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_productbox_btn_target'] = [
    'label'     => ['Neues Tab', 'Link in neuem Tab öffnen'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

// ============================================================
// RCT Form Header (Meta-Strip)
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_form_header'] =
    '{type_legend},type;{form_header_legend},rct_form_header_items,rct_form_header_style;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// rct_form_header_* Felder ohne 'sql' — Werte landen in tl_content.jsonData
// (Contao 5.7+ JSON-Storage, siehe PR #8838).
// Daten-Migration: RctFormHeaderJsonStorageMigration (v1.5.3).
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_form_header_items'] = [
    'label'     => ['Meta-Items', "Eine Zeile pro Item — werden im Frontend mit vertikalen Trenn-Strichen gerendert. Die erste Zeile bekommt die Akzentfarbe.\n\nBeispiel:\nFORM /CONTACT\n5 Felder\n~ 60 Sek.\nDSGVO"],
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:100px; font-family:monospace', 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_form_header_style'] = [
    'label'     => ['Stil', 'Hell (für weiße Surfaces) oder Dunkel (für Shell-/Aurora-BG)'],
    'inputType' => 'select',
    'options'   => ['light' => 'Hell', 'dark' => 'Dunkel'],
    'eval'      => ['tl_class' => 'w50'],
];

// ============================================================
// RCT Emitter (Particle-Effekt: Schnee, Blätter, Konfetti, …)
// ============================================================

$GLOBALS['TL_DCA']['tl_content']['palettes']['rct_emitter'] =
    '{type_legend},type;'
    . '{emitter_legend},rct_emitter_preset,rct_emitter_target;'
    . '{emitter_custom_legend:hide},rct_emitter_shapes,rct_emitter_colors,rct_emitter_direction,rct_emitter_min_size,rct_emitter_max_size,rct_emitter_speed,rct_emitter_rotation,rct_emitter_rotation_speed,rct_emitter_natural_fall,rct_emitter_natural_start,rct_emitter_fadeout,rct_emitter_new_on,rct_emitter_pool_size;'
    . '{expert_legend:hide},cssID;'
    . '{invisible_legend:hide},invisible,start,stop';

// rct_emitter_* Felder ohne 'sql' → tl_content.jsonData (Contao 5.7+ JSON-Storage)
$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_preset'] = [
    'label'     => ['Preset', 'Voreinstellung — alle weiteren Felder sind optionale Overrides'],
    'inputType' => 'select',
    'options'   => [
        'snow'     => '❄ Schnee',
        'leaves'   => '🍂 Herbstlaub',
        'petals'   => '🌸 Blütenblätter',
        'confetti' => '🎉 Konfetti',
        'sparks'   => '✨ Funken (steigen)',
        'hearts'   => '❤ Herzen (steigen)',
        'bubbles'  => '○ Bubbles (steigen)',
        'custom'   => '⚙ Custom (alles selbst)',
    ],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_target'] = [
    'label'     => ['Ziel-Selector', 'CSS-Selector des Ziel-Containers (z.B. #meinHero, .my-section). Leer lassen → wirkt auf den umschließenden Article'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
];

// ── Custom-Override-Felder (alle optional) ──

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_shapes'] = [
    'label'     => ['Shapes', 'Komma-getrennte Liste: Emojis, Symbole, Buchstaben. Leer = Preset-Default'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_colors'] = [
    'label'     => ['Farben', 'Komma-getrennte Hex-/CSS-Farben (z.B. #fff,#27c4f4). Leer = Preset-Default'],
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_direction'] = [
    'label'     => ['Richtung', 'Bewegungsrichtung der Partikel'],
    'inputType' => 'select',
    'options'   => ['' => '— Preset-Default —', 'down' => 'Down', 'up' => 'Up', 'left' => 'Left', 'right' => 'Right'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_min_size'] = [
    'label'     => ['Min Size (px)', 'Minimale Partikelgröße. Leer = Preset-Default'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'digit', 'maxlength' => 4, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_max_size'] = [
    'label'     => ['Max Size (px)', 'Maximale Partikelgröße. Leer = Preset-Default'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'digit', 'maxlength' => 4, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_speed'] = [
    'label'     => ['Speed', '1 = sehr langsam, 50 = sehr schnell. Leer = Preset-Default'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'digit', 'maxlength' => 3, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_rotation'] = [
    'label'     => ['Rotation', 'Partikel rotieren während des Falls'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_rotation_speed'] = [
    'label'     => ['Rotation Speed', 'Sekunden pro Umdrehung'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'digit', 'maxlength' => 4, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_natural_fall'] = [
    'label'     => ['Natürlicher Fall', 'Sway-Effekt + leichte Streuung horizontal'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_natural_start'] = [
    'label'     => ['Natürlicher Start', 'Start-Positionen werden zusätzlich verstreut'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_fadeout'] = [
    'label'     => ['Fadeout', 'Partikel werden während des Flugs transparent'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_new_on'] = [
    'label'     => ['Spawn-Intervall (ms)', 'Wie oft ein neuer Partikel kommt. Niedrig = dichter Effekt'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'digit', 'maxlength' => 5, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['rct_emitter_pool_size'] = [
    'label'     => ['Pool Size', 'Maximal gleichzeitig sichtbare Partikel'],
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'digit', 'maxlength' => 4, 'tl_class' => 'w50'],
];
