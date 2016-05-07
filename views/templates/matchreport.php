<?php if (!defined ('TYPO3_MODE')) 	die ('Access denied.'); ?>

<?
  $matchReport = $viewData->offsetGet('matchReport');
  $home = $matchReport->match->getHome();
  $guest = $matchReport->match->getGuest();
?>

<h3 class="cfcleague-report-head3"><? echo $matchReport->getRoundName() . ' ' . $matchReport->getCompetitionName(); ?></h3>

<h1 class="cfcleague-report-head1">
<? echo $matchReport->getTeamNameHome() . ' - ' . $matchReport->getTeamNameGuest() . ' ' .
         $matchReport->match->record['goals_home_2'] . ' : ' . $matchReport->match->record['goals_guest_2'] ; ?>
</h1>

<table id="cfcleague-report-introtable">
<tr>
<td class="cfcleague-report-logo"><? echo $matchReport->getLogoHome(); ?></td>
<td class="cfcleague-report-intro">
 Stadion: <b><? echo $matchReport->getStadium(); ?></b><br />
 Datum: <b><? echo $matchReport->getDate(); ?></b><br />
 Schiedsrichter: <b><? echo $matchReport->getRefereeName(); ?></b><br />
 SRA: <b><? echo $matchReport->getAssistNames(); ?></b><br />
 Zuschauer: <b><? echo $matchReport->getVisitors(); ?></b><br />
</td>
<td class="cfcleague-report-logo"><? echo $matchReport->getLogoGuest(); ?></td>
</tr>
</table>

<div id="cfcleague-report-teams">
  <table id="cfcleague-report-statstable">
  <tr>
    <td></td>
    <td><b><? echo $matchReport->getTeamNameHome(); ?></b></td>
    <td><b><? echo $matchReport->getTeamNameGuest(); ?></b></td>
  </tr>
  <tr>
    <td class="cfcleague-report-coach-head">Trainer</td>
    <td class="cfcleague-report-coach">
<?
  echo $matchReport->getCoachNameHome();
?>
    </td>
    <td class="cfcleague-report-coach">
<?
  echo $matchReport->getCoachNameGuest();
?>
    </td>
  </tr>
  <tr>
    <td class="cfcleague-report-team-head">Aufstellung</td>
    <td class="cfcleague-report-team">
<?
  echo $matchReport->getPlayerNamesHome();
?>
    </td>
    <td class="cfcleague-report-team">
<?
  echo $matchReport->getPlayerNamesGuest();
?>
    </td>
  </tr>
  <tr>
    <td class="cfcleague-report-subst-head">Reserve</td>
    <td class="cfcleague-report-subst">
<?
  echo $matchReport->getSubstituteNamesHome();
?>
    </td>
    <td class="cfcleague-report-subst">
<?
  echo $matchReport->getSubstituteNamesGuest();
?>
    </td>
  </tr>

  <tr>
    <td class="cfcleague-report-scorer-head">Torschützen</td>
    <td class="cfcleague-report-scorer">
<?
  echo $matchReport->getScorerHome();
?>
    </td>
    <td class="cfcleague-report-scorer">
<?
  echo $matchReport->getScorerGuest();
?>
    </td>
  </tr>

<? /*
  <tr>
    <td class="cfcleague-report-changes-head">Wechsel</td>
    <td class="cfcleague-report-changes">
< ?
  echo $matchReport->getChangesHome();
? >
    </td>
    <td class="cfcleague-report-changes">
< ?
  echo $matchReport->getChangesGuest();
? >
    </td>
  </tr>
  <tr>
    <td class="cfcleague-report-penalties-head">Karten</td>
    <td class="cfcleague-report-penalties">
< ?
  echo $matchReport->getPenaltiesHome();
? >
    </td>
    <td class="cfcleague-report-penalties">
< ?
  echo $matchReport->getPenaltiesGuest();
? >
    </td>
  </tr>
*/
?>

  </table>
</div>

<?
/**** Die Trainerkommentare **************************/
$media = $matchReport->getMedia();
if(is_array($media) && count($media)) {
?>
<div id="cfcleague-report-media">
<h2>Trainerkommentare</h2>
<?
    foreach($media as $str) {
      echo $str;
?>


<?
    } ?>
</div>
<? } ?>



<?
/**** Der Spielbericht **************************/
  $report = $matchReport->getReport();
  if(strlen(trim($report)) > 0) {
?>
<div id="cfcleague-report-summary-author">
<? if($matchReport->getReportAuthor()) { ?>
<p>Spielbericht von <? echo $matchReport->getReportAuthor();?></p>
<? } else { ?>
<h2>Spielbericht</h2>
<? } ?>
</div>

<div id="cfcleague-report-summary">
<? echo $matchReport->getReport();?>
</div>
<? } // if report ?>


<?
/**** Der Liveticker **************************/
?>

<?
  if($matchReport->match->isTicker()) {
    // Den Link zur Liveticker-Seite einblenden
    $link->destination('145'); // Das Ziel der Seite vorbereiten
    $link->parameters(array('matchId' => $matchReport->match->uid));
    $link->label('Zum Ticker');
?>
  <a href="<? echo $link->makeUrl(FALSE); ?>">Den Liveticker öffnen.</a>
<?
  }
?>

<?
  $arr = $matchReport->getMatchTicker();
  if(count($arr)) {
?>
<h2>Spielstenogramm</h2>
<div id="cfcleague-report-ticker">
  <table>
<?
    foreach($arr As $ticker) {
      if(intval($ticker->record['minute']) >= 0) {
?>
  <tr>
    <td class="cfcleague-report-ticker-head"><?= $ticker->getMinute(); ?>. min <?= $ticker->getExtraTime() > 0 ? '(+' . $ticker->getExtraTime() . ')' : '';  ?> Spielstand: <? echo $ticker->getScore(); ?></td>
  </tr>
  <tr>
    <td class="cfcleague-report-ticker-msg">

    <? if($ticker->isChange()) { ?>
      <p class="cfcleague-report-ticker-player, cfcleague-report-ticker-playerhome">
      Spielerwechsel:
      <?
         $p = $ticker->getPlayerChangeIn();
         echo is_object($p) ? $p->getName() : 'ERROR!'; ?>
f&uuml;r <? $p = $ticker->getPlayerChangeOut();
         echo is_object($p) ? $p->getName() : 'ERROR!'; ?>
      </p>

    <? } else { ?>
    <? echo $ticker->getTypeName($configurations); ?>
    <?   if($ticker->getPlayerHome()) { ?>
        <span class="cfcleague-report-ticker-player, cfcleague-report-ticker-playerhome"><? $p = $ticker->getPlayerHome(); echo $p->getName(); ?></span>
    <?   } ?>

    <?   if($ticker->getPlayerGuest()) { ?>
      <span class="cfcleague-report-ticker-player, cfcleague-report-ticker-playerguest"><? $p = $ticker->getPlayerGuest(); echo $p->getName(); ?></span>
    <?   } ?>
    <? } ?>

    <? if(strlen($ticker->record['comment'])>0) echo '<p>'.$ticker->record['comment'].'</p>'; ?>

    </td>
  <tr>
<?
      } // if minute
    } // foreach
?>
  </table>
</div>
<?
  } // if
?>

<div id="cfcleague-report-images">
<?  echo $matchReport->getPictures(); ?>
</div>
