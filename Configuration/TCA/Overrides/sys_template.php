<?php

if (!(defined('TYPO3') || defined('TYPO3_MODE'))) {
    exit('Access denied.');
}

call_user_func(function () {
    $extKey = 'cfc_league_fe';

    // list static templates in templates selection
    Sys25\RnBase\Utility\Extensions::addStaticFile($extKey, 'static/', 'T3sports');
    Sys25\RnBase\Utility\Extensions::addStaticFile($extKey, 'static/cal/', 'T3sports cal-events');
    Sys25\RnBase\Utility\Extensions::addStaticFile($extKey, 'static/volleyball/', 'T3sports for Volleyball');
});
