<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['rct_logo'] =
    '{title_legend},name,headline,type;{logo_legend},rct_logo_image,rct_logo_url,rct_logo_alt;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['rct_logo_image'] = [
    'label'     => ['Logo-Bild', 'Eigenes Bild hochladen. Leer lassen für das Standard-RCT-SVG-Logo.'],
    'inputType' => 'fileTree',
    'eval'      => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'jpg,jpeg,png,gif,svg,webp', 'tl_class' => 'clr'],
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
