<?php

// ########################################################################
// # Extension Manager/Repository config file for ext "cfc_league_fe".
// #
// # Auto generated 17-07-2010 14:52
// #
// # Manual updates:
// # Only the data in the array - everything else is removed by next
// # writing. "version" and "dependencies" must not be touched!
// ########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'T3sports FE',
    'description' => 'FE-Plugins von T3sports. Liefert u.a. die Views Spielplan, Spielbericht, Tabellen, Team- und Spieleransicht. FE plugins for T3sports. Contains views for matchtable, leaguetable, matchreport, team and player reports and many more. Requires PHP5!',
    'category' => 'plugin',
    'version' => '1.11.1',
    'dependencies' => 'rn_base,cfc_league',
    'module' => '',
    'state' => 'beta',
    'uploadfolder' => 1,
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Rene Nitzsche',
    'author_email' => 'rene@system25.de',
    'author_company' => 'System 25',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-12.4.99',
            'php' => '7.1.0-8.9.99',
            'rn_base' => '1.17.1-0.0.0',
            'cfc_league' => '1.11.0-0.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
