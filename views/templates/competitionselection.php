<?php if (!defined ('TYPO3_MODE')) 	die ('Access denied.'); ?>

<? 
  tx_div::load('tx_rnbase_util_FormUtil');

  $viewData =& $configurations->getViewData();
//t3lib_div::debug($configurations->_keepVars,'vd');
  $link->destination($GLOBALS['TSFE']->id); // Das Ziel der Seite vorbereiten
?>

<form action="<? echo $link->makeUrl(); ?>">
<?
  $keepVars = $configurations->getKeepVars();
  $keepVars = $keepVars->getArrayCopy();

  if($viewData->offsetGet('saison_select')) {
    echo tx_rnbase_util_FormUtil::createSelect($configurations->getQualifier().'[saison]', 
            $viewData->offsetGet('saison_select'),
            'onchange="submit();"') . '<br />';
    unset($keepVars[$configurations->createParamName('saison')]);
  }
  if($viewData->offsetGet('group_select')) {
    echo tx_rnbase_util_FormUtil::createSelect($configurations->getQualifier().'[group]', 
            $viewData->offsetGet('group_select'),
            'onchange="submit();"') . '<br />'; 
    unset($keepVars[$configurations->createParamName('group')]);
  }
  if($viewData->offsetGet('competition_select')) {
    echo tx_rnbase_util_FormUtil::createSelect($configurations->getQualifier().'[competition]', 
            $viewData->offsetGet('competition_select'),
            'onchange="submit();"') . '<br />';
    unset($keepVars[$configurations->createParamName('competition')]);
  }
  if($viewData->offsetGet('round_select')) {
    echo tx_rnbase_util_FormUtil::createSelect($configurations->getQualifier().'[round]', 
            $viewData->offsetGet('round_select'),
            'onchange="submit();"') . '<br />';
    unset($keepVars[$configurations->createParamName('round')]);
  }

  echo tx_rnbase_util_FormUtil::getAsHiddenFields($keepVars);

?>
</form>


