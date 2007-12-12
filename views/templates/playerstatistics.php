<? 
  $viewData =& $configurations->getViewData();
  $formatter =& $configurations->getFormatter();

  $playerData = $viewData->offsetGet('playerData');

  $scorerData = $viewData->offsetGet('scorerData');
  $assistData = $viewData->offsetGet('assistData');
  $addData = $viewData->offsetGet('additionalData');
?>

<div id="cfcleague-playerstats-info">
Stand nach <b><? echo $addData['numberOfUsedMatches']; ?></b> von <b><? echo $addData['numberOfMatches']; ?></b> Spielen.
</div>

<table class="cfcleague-playerstats-table">
<tr>
  <th>&nbsp;</th>
  <th colspan="2"><? echo $configurations->getLL('playerstats_matches') ?></th>
  <th colspan="3"><? echo $configurations->getLL('playerstats_cards') ?></th>
  <th>&nbsp;</th>
  <th>&nbsp;</th>
  <th>&nbsp;</th>
  <th colspan="2"><? echo $configurations->getLL('playerstats_changes') ?></th>
</tr>
<tr>
  <th><? echo $configurations->getLL('playerstats_player') ?></th>
  <th><? echo $configurations->getLL('playerstats_matchcount') ?></th>
  <th><? echo $configurations->getLL('playerstats_minutes') ?></th>
  <th><? echo $configurations->getLL('playerstats_card_yellow') ?></th>
  <th><? echo $configurations->getLL('playerstats_card_yellowred') ?></th>
  <th><? echo $configurations->getLL('playerstats_card_red') ?></th>
  <th><? echo $configurations->getLL('playerstats_goals') ?></th>
  <th><? echo $configurations->getLL('playerstats_assists') ?></th>
  <th><? echo $configurations->getLL('playerstats_goals_own') ?></th>
  <th><? echo $configurations->getLL('playerstats_change_in') ?></th>
  <th><? echo $configurations->getLL('playerstats_change_out') ?></th>
</tr>

<?
  foreach($playerData As $playerStats) {
    $player = $playerStats['player'];
?>
<tr>
  <td class="cfcleague-playerstats-colplayer"><? if($player) echo $player->getName() . '<!--' . $player->uid .'-->'; ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['MATCH_COUNT'],'playerstats.matchcount.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['MATCH_MINUTES'],'playerstats.matchminutes.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['CARD_YELLOW'],'playerstats.cardyellow.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['CARD_YELLOWRED'],'playerstats.cardyellowred.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['CARD_RED'],'playerstats.cardred.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['GOALS_ALL'],'playerstats.goalsall.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['GOALS_ASSIST'],'playerstats.goalsassist.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['GOALS_OWN'],'playerstats.goalsassist.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['CHANGED_IN'],'playerstats.changedin.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><? echo $formatter->wrap($playerStats['CHANGED_OUT'],'playerstats.changedout.'); ?></td>

</tr>
<?
  }
?>
</table>

<h2><? echo $configurations->getLL('playerstats_scorer_headline') ?></h2>

<table class="cfcleague-scorer-table">
<tr>
  <th><? echo $configurations->getLL('playerstats_scorer_name') ?></th>
  <th><? echo $configurations->getLL('playerstats_scorer_goals') ?></th>
  <th><? echo $configurations->getLL('playerstats_scorer_home') ?></th>
  <th><? echo $configurations->getLL('playerstats_scorer_away') ?></th>
  <th><? echo $configurations->getLL('playerstats_scorer_head') ?></th>
  <th><? echo $configurations->getLL('playerstats_scorer_penalty') ?></th>
  <th><? echo $configurations->getLL('playerstats_scorer_joker') ?></th>
</tr>
<?
  foreach($scorerData As $playerStats) {
    $player = $playerStats['player'];
?>
<tr>
  <td class="cfcleague-scorer-colplayer"><? if($player) echo $player->getName(); ?></td>
  <td class="cfcleague-scorer-colvalue"><? echo $formatter->wrap($playerStats['GOALS_ALL'],'scorer.goalsall.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><? echo $formatter->wrap($playerStats['GOALS_HOME'],'scorer.goalshome.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><? echo $formatter->wrap($playerStats['GOALS_AWAY'],'scorer.goalsaway.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><? echo $formatter->wrap($playerStats['GOALS_HEAD'],'scorer.goalshead.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><? echo $formatter->wrap($playerStats['GOALS_PENALTY'],'scorer.goalspenalty.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><? echo $formatter->wrap($playerStats['GOALS_JOKER'],'scorer.goalsjoker.'); ?></td>

</tr>
<?
  }
?>
</table>


<h2><? echo $configurations->getLL('playerstats_assist_headline') ?></h2>

<table class="cfcleague-scorer-table">
<tr>
  <th><? echo $configurations->getLL('playerstats_assist_name') ?></th>
  <th><? echo $configurations->getLL('playerstats_assists') ?></th>
</tr>
<?
  foreach($assistData As $playerStats) {
    $player = $playerStats['player'];
?>
<tr>
  <td class="cfcleague-scorer-colplayer"><? if($player) echo $player->getName(); ?></td>
  <td class="cfcleague-scorer-colvalue"><? echo $formatter->wrap($playerStats['GOALS_ASSIST'],'assits.'); ?></td>

</tr>
<?
  }
?>
</table>
