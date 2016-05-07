<?php if (!defined ('TYPO3_MODE')) 	die ('Access denied.'); ?>

<?php
  tx_div::load('tx_rnbase_util_FormUtil');
  // Die ViewData bereitstellen
  $viewData =& $configurations->getViewData();
  $data = $viewData->offsetGet('matches');

  if(count($data)) {

    $reportPage = $configurations->get('reportPage');
    if($reportPage) {
      $link->destination($reportPage); // Das Ziel der Seite vorbereiten
    }
    else
      $link = 0; // Es werden keine Links gesetzt

?>

<table cellspacing="0" cellpadding="0" class="cfcleague-matchtable">
<?php
  $cnt = 0;
  foreach($data As $match){
    $css = ($cnt++) % 2;
?>
<tr class="cfcleague-matchtable-row<? echo $css ?> cfcleague-matchtable-rowinfo">
<td colspan="10"><? echo $match->record['competition_name']; ?> (<? echo $match->record['round_name']; ?>)</td>
</tr>
<tr class="cfcleague-matchtable-row<? echo $css ?> cfcleague-matchtable-rowmatch">
<td><? echo $match->getDate($formatter); ?></td>
<td><? echo $match->record['home_name']; ?></td>
<td>-</td>
<td><? echo $match->record['guest_name']; ?></td>

<td class="cfcleague-matchtable-result">
<? if($match->record['status'] > 0) {
     $showLink = $reportPage && $match->hasReport();
     if($showLink) {
// Wir setzen den Parameter für die Ziel-Url
       $link->parameters(array('matchId' => $match->uid));

?>
 <a href="<? echo $link->makeUrl(FALSE); ?>">
<?
     }
?>
<?   echo $match->record['goals_home_2']; ?> : <? echo $match->record['goals_guest_2']; ?>
<?   if($showLink) { ?></a><? } ?>

<? } else { ?>
- : -
<? } ?>
</td>

</tr>
<?php
  }  // Close foreach
?>
</table>

<?
 } else {
?>
<h2>Es wurden keine Spiele gefunden!</h2>
<? } ?>