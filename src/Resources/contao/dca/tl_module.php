<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_logo'] =
    '{title_legend},name,headline,type;{logo_legend},rct_logo_image,rct_logo_image_mobile,rct_logo_url,rct_logo_alt,rct_logo_hide_mobile;{expert_legend:hide},cssID';

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
    'sql'       => "varchar(255) NOT NULL default '/'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_alt'] = [
    'label'     => ['Alt-Text / Aria-Label', 'Beschreibung des Logos für Screenreader.'],
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_hide_mobile'] = [
    'label'     => ['Auf Mobile ausblenden', 'Logo im Sidebar-Menü auf kleinen Bildschirmen nicht anzeigen.'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr m12'],
    'sql'       => "char(1) NOT NULL default ''",
];
