<?php
  $viewData = &$configurations->getViewData();
  $formatter = &$configurations->getFormatter();

  $playerData = $viewData->offsetGet('playerData');

  $scorerData = $viewData->offsetGet('scorerData');
  $assistData = $viewData->offsetGet('assistData');
  $addData = $viewData->offsetGet('additionalData');
?>

<div id="cfcleague-playerstats-info">
Stand nach <b><?php echo $addData['numberOfUsedMatches']; ?></b> von <b><?php echo $addData['numberOfMatches']; ?></b> Spielen.
</div>

<table class="cfcleague-playerstats-table">
<tr>
  <th>&nbsp;</th>
  <th colspan="2"><?php echo $configurations->getLL('playerstats_matches'); ?></th>
  <th colspan="3"><?php echo $configurations->getLL('playerstats_cards'); ?></th>
  <th>&nbsp;</th>
  <th>&nbsp;</th>
  <th>&nbsp;</th>
  <th colspan="2"><?php echo $configurations->getLL('playerstats_changes'); ?></th>
</tr>
<tr>
  <th><?php echo $configurations->getLL('playerstats_player'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_matchcount'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_minutes'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_card_yellow'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_card_yellowred'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_card_red'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_goals'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_assists'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_goals_own'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_change_in'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_change_out'); ?></th>
</tr>

<?php
  foreach ($playerData as $playerStats) {
      $player = $playerStats['player']; ?>
<tr>
  <td class="cfcleague-playerstats-colplayer"><?php if ($player) {
          echo $player->getName().'<!--'.$player->uid.'-->';
      } ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['MATCH_COUNT'], 'playerstats.matchcount.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['MATCH_MINUTES'], 'playerstats.matchminutes.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['CARD_YELLOW'], 'playerstats.cardyellow.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['CARD_YELLOWRED'], 'playerstats.cardyellowred.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['CARD_RED'], 'playerstats.cardred.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_ALL'], 'playerstats.goalsall.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_ASSIST'], 'playerstats.goalsassist.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_OWN'], 'playerstats.goalsassist.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['CHANGED_IN'], 'playerstats.changedin.'); ?></td>
  <td class="cfcleague-playerstats-colvalue"><?php echo $formatter->wrap($playerStats['CHANGED_OUT'], 'playerstats.changedout.'); ?></td>

</tr>
<?php
  }
?>
</table>

<h2><?php echo $configurations->getLL('playerstats_scorer_headline'); ?></h2>

<table class="cfcleague-scorer-table">
<tr>
  <th><?php echo $configurations->getLL('playerstats_scorer_name'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_scorer_goals'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_scorer_home'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_scorer_away'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_scorer_head'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_scorer_penalty'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_scorer_joker'); ?></th>
</tr>
<?php
  foreach ($scorerData as $playerStats) {
      $player = $playerStats['player']; ?>
<tr>
  <td class="cfcleague-scorer-colplayer"><?php if ($player) {
          echo $player->getName();
      } ?></td>
  <td class="cfcleague-scorer-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_ALL'], 'scorer.goalsall.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_HOME'], 'scorer.goalshome.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_AWAY'], 'scorer.goalsaway.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_HEAD'], 'scorer.goalshead.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_PENALTY'], 'scorer.goalspenalty.'); ?></td>
  <td class="cfcleague-scorer-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_JOKER'], 'scorer.goalsjoker.'); ?></td>

</tr>
<?php
  }
?>
</table>


<h2><?php echo $configurations->getLL('playerstats_assist_headline'); ?></h2>

<table class="cfcleague-scorer-table">
<tr>
  <th><?php echo $configurations->getLL('playerstats_assist_name'); ?></th>
  <th><?php echo $configurations->getLL('playerstats_assists'); ?></th>
</tr>
<?php
  foreach ($assistData as $playerStats) {
      $player = $playerStats['player']; ?>
<tr>
  <td class="cfcleague-scorer-colplayer"><?php if ($player) {
          echo $player->getName();
      } ?></td>
  <td class="cfcleague-scorer-colvalue"><?php echo $formatter->wrap($playerStats['GOALS_ASSIST'], 'assits.'); ?></td>

</tr>
<?php
  }
?>
</table>
