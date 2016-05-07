<?php if (!defined ('TYPO3_MODE')) 	die ('Access denied.'); ?>
<?php
  tx_rnbase::load('tx_rnbase_util_FormUtil');
  $viewData =& $configurations->getViewData();
  $pointSystem = $viewData->offsetGet('tablePointSystem'); // Das verwendete Punktesystem holen

  $league = $viewData->offsetGet('league'); // Die aktuelle Liga
  $marks = $league->getTableMarks();

  $link->destination($GLOBALS['TSFE']->id); // Das Ziel der Seite vorbereiten
?>
<style>
</style>

<div class="cfcleague-leaguetable-form">
<form action="<? echo $link->makeUrl(); ?>">
<?
  $keepVars = $configurations->getKeepVars();
  $keepVars = $keepVars->getArrayCopy();

  if($viewData->offsetGet('tabletype_select')) {
    echo tx_rnbase_util_FormUtil::createSelect($configurations->getQualifier() . '[tabletype]',
            $viewData->offsetGet('tabletype_select'),
            'onchange="submit();"') . '<br />';
    unset($keepVars[$configurations->createParamName('tabletype')]);
  }
  if($viewData->offsetGet('tablescope_select')) {
    echo tx_rnbase_util_FormUtil::createSelect($configurations->getQualifier() . '[tablescope]',
            $viewData->offsetGet('tablescope_select'),
            'onchange="submit();"') . '<br />';
    unset($keepVars[$configurations->createParamName('tablescope')]);
  }
  if($viewData->offsetGet('pointsystem_select')) {
    echo tx_rnbase_util_FormUtil::createSelect($configurations->getQualifier() . '[pointsystem]',
            $viewData->offsetGet('pointsystem_select'),
            'onchange="submit();"') . '<br />';
    unset($keepVars[$configurations->createParamName('pointsystem')]);
  }

  echo tx_rnbase_util_FormUtil::getAsHiddenFields($keepVars);
?>
</form>
</div>

<div class="cfcleague-leaguetable">
<table cellspacing="0" cellpadding="0" class="cfcleague-leaguetable">
<tr>
  <th>Pl.</th><th>Verein</th><th>Spiele</th><th>S</th><th>U</th><th>N</th><th>Tore</th><th>Diff</th><th>Punkte</th>
</tr>
<?php
  $data = $viewData->offsetGet('tableData');

  $cnt = 0;
  foreach($data As $row){
    $css = ($cnt++) % 2;
    if($row['penalties']) $penalties[] = $row['penalties'];
?>
<tr class="cfcleague-leaguetable-row<? echo $css ?><? if($row['markClub']){ ?> cfcleague-leaguetable-rowTeam<? } ?><? if(is_array($marks) && array_key_exists($cnt, $marks)){ ?> cfcleague-leaguetable-row_<? echo $marks[$cnt][0]; } ?>">
  <td><? echo $cnt ?>.</td>
  <td><? echo $row['teamName']; if($row['penalties']) { echo '*'; } ?> </td>
  <td><? echo $row['matchCount'] ?></td>
  <td><? echo $row['winCount'] ?></td>
  <td><? echo $row['drawCount'] ?></td>
  <td><? echo $row['loseCount'] ?></td>
  <td><? echo $row['goals1'] ?>:<? echo $row['goals2'] ?></td>
  <td><? echo ($row['goals1'] - $row['goals2']) ?></td>
  <td><? echo $row['points'] ?><? if($pointSystem == 1){ echo ':' . $row['points2'];}?> </td>

</tr>
<?php
  } // Close foreach
?>
</table>

<? if($penalties) { ?>
<div class="cfcleague-leaguetable-comments">
<h3>Hinweise</h3>
<?   foreach($penalties As $penaltyArr) { ?>
<p class="cfcleague-leaguetable-comment">
<?        foreach($penaltyArr As $penalty) {
          echo '* '. $penalty->record['comment'] . '<br />';
       } ?>
</p>
<?     } ?>
</div>
<? } ?>
</div>
