<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


// list static templates in templates selection
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY,'static/', 'T3sports');
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY,'static/cal/','T3sports cal-events');
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY,'static/volleyball/','T3sports for Volleyball');

