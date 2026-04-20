<?php

$GLOBALS['TL_DCA']['tl_rct_config'] = [
    'config' => [
        'dataContainer' => 'Table',
        'sql'           => ['keys' => ['id' => 'primary']],
    ],
    'fields' => [
        'id'                     => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp'                 => ['sql' => 'int(10) unsigned NOT NULL default 0'],
        'rct_font_body'          => ['sql' => "varchar(255) NOT NULL default 'Space Grotesk'"],
        'rct_font_mono'          => ['sql' => "varchar(255) NOT NULL default 'DM Mono'"],
        'rct_color_accent'       => ['sql' => "varchar(7) NOT NULL default '#27c4f4'"],
        'rct_color_primary'      => ['sql' => "varchar(7) NOT NULL default '#2951c7'"],
        'rct_color_primary_light'=> ['sql' => "varchar(7) NOT NULL default '#27c4f4'"],
        'rct_grad1'              => ['sql' => "varchar(7) NOT NULL default '#27c4f4'"],
        'rct_grad2'              => ['sql' => "varchar(7) NOT NULL default '#2951c7'"],
        'rct_grad3'              => ['sql' => "varchar(7) NOT NULL default '#1d2db2'"],
        'rct_grad4'              => ['sql' => "varchar(7) NOT NULL default '#14054a'"],
        'rct_sidebar_width'      => ['sql' => "varchar(10) NOT NULL default '260px'"],
        'rct_header_height'      => ['sql' => "varchar(10) NOT NULL default '64px'"],
        'rct_radius'             => ['sql' => "varchar(10) NOT NULL default '0.125rem'"],
        'rct_allowed_themes'     => ['sql' => "varchar(500) NOT NULL default ''"],
    ],
];
