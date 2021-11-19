<?php

declare(strict_types=1);

return [
    'tx_cfcleaguefe_actions_ClubList' => \System25\T3sports\Frontend\Action\ClubList::class,
    'tx_cfcleaguefe_actions_ClubView' => \System25\T3sports\Frontend\Action\ClubDetails::class,
    'tx_cfcleaguefe_actions_LeagueTable' => \System25\T3sports\Frontend\Action\LeagueTable::class,
    'tx_cfcleaguefe_actions_LeagueTableShow' => \System25\T3sports\Frontend\Action\LeagueTable::class,
    'tx_cfcleaguefe_actions_LeagueTableAllTime' => \System25\T3sports\Frontend\Action\LeagueTableAllTime::class,
    'tx_cfcleaguefe_actions_LiveTickerList' => \System25\T3sports\Frontend\Action\LiveTickerList::class,
    'tx_cfcleaguefe_actions_MatchReport' => \System25\T3sports\Frontend\Action\MatchReport::class,
    'tx_cfcleaguefe_actions_MatchTable' => \System25\T3sports\Frontend\Action\MatchTable::class,
    'tx_cfcleaguefe_actions_ProfileList' => \System25\T3sports\Frontend\Action\ProfileList::class,
    'tx_cfcleaguefe_actions_StadiumView' => \System25\T3sports\Frontend\Action\StadiumDetails::class,

    'tx_cfcleaguefe_actions_StadiumList' => \System25\T3sports\Frontend\Action\StadiumList::class,
    'tx_cfcleaguefe_actions_ProfileView' => \System25\T3sports\Frontend\Action\ProfileView::class,
    'tx_cfcleaguefe_actions_TeamView' => \System25\T3sports\Frontend\Action\TeamDetails::class,
    'tx_cfcleaguefe_actions_Statistics' => \System25\T3sports\Frontend\Action\Statistics::class,
    'tx_cfcleaguefe_actions_CompetitionSelection' => \System25\T3sports\Frontend\Action\ScopeSelection::class,

    'tx_cfcleaguefe_util_ProfileMarker' => \System25\T3sports\Frontend\Marker\ProfileMarker::class,
    'tx_cfcleaguefe_util_MatchMarker' => \System25\T3sports\Frontend\Marker\MatchMarker::class,
    'tx_cfcleaguefe_util_ClubMarker' => \System25\T3sports\Frontend\Marker\ClubMarker::class,
    'tx_cfcleaguefe_util_StadiumMarker' => \System25\T3sports\Frontend\Marker\StadiumMarker::class,
    'tx_cfcleaguefe_util_AddressMarker' => \System25\T3sports\Frontend\Marker\AddressMarker::class,
    'tx_cfcleaguefe_util_Maps' => \System25\T3sports\Utility\MapsUtil::class,

    'tx_cfcleaguefe_models_address' => \System25\T3sports\Model\Address::class,
    'tx_cfcleaguefe_models_club' => \System25\T3sports\Model\Club::class,
    'tx_cfcleaguefe_models_competition' => \System25\T3sports\Model\Competition::class,
    'tx_cfcleaguefe_models_competition_penalty' => \System25\T3sports\Model\CompetitionPenalty::class,
    'tx_cfcleaguefe_models_competition_round' => \System25\T3sports\Model\CompetitionRound::class,
];
