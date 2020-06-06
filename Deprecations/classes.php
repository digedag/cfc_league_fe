<?php

class tx_cfcleaguefe_actions_StadiumList extends System25\T3sports\Action\StadiumList
{
}
class tx_cfcleaguefe_views_StadiumList extends System25\T3sports\View\StadiumList
{
}
class tx_cfcleaguefe_filter_Match extends System25\T3sports\Filter\MatchFilter
{
}
class tx_cfcleaguefe_filter_MatchNote extends System25\T3sports\Filter\MatchNoteFilter
{
}
class tx_cfcleaguefe_filter_Stadium extends System25\T3sports\Filter\StadiumFilter
{
}
class tx_cfcleaguefe_filter_Team extends System25\T3sports\Filter\TeamFilter
{
}
class tx_cfcleaguefe_search_Builder extends System25\T3sports\Search\SearchBuilder
{
}
class tx_cfcleaguefe_search_Competition extends System25\T3sports\Search\CompetitionSearch
{
}
class tx_cfcleaguefe_search_Match extends System25\T3sports\Search\MatchSearch
{
}
class tx_cfcleaguefe_search_Profile extends System25\T3sports\Search\ProfileSearch
{
}
class tx_cfcleaguefe_search_Team extends System25\T3sports\Search\TeamSearch
{
}

class tx_cfcleaguefe_sv1_Competitions extends System25\T3sports\Service\CompetitionService
{
}
class tx_cfcleaguefe_sv1_Teams extends System25\T3sports\Service\TeamService
{
}
class tx_cfcleaguefe_sv1_Profiles extends System25\T3sports\Service\ProfileService
{
}
class tx_cfcleaguefe_sv1_Matches extends System25\T3sports\Service\MatchService
{
}

if (tx_rnbase_util_Extensions::isLoaded('cal')) {
    class tx_cfcleaguefe_sv1_MatchEvent extends System25\T3sports\Service\MatchEventService
    {
    }
}
class tx_cfcleaguefe_sv2_PlayerStatistics extends System25\T3sports\Statistics\Service\PlayerStatistics
{
}
class tx_cfcleaguefe_sv2_PlayerStatisticsMarker extends System25\T3sports\Statistics\PlayerStatisticsMarker
{
}
class tx_cfcleaguefe_sv2_PlayerSummaryStatistics extends System25\T3sports\Statistics\Service\PlayerSummaryStatistics
{
}
class tx_cfcleaguefe_sv2_PlayerSummaryStatisticsMarker extends System25\T3sports\Statistics\PlayerSummaryStatisticsMarker
{
}
class tx_cfcleaguefe_sv2_AssistStatistics extends System25\T3sports\Statistics\Service\AssistStatistics
{
}
class tx_cfcleaguefe_sv2_ScorerStatistics extends System25\T3sports\Statistics\Service\ScorerStatistics
{
}
class tx_cfcleaguefe_sv2_VisitorStatistics extends System25\T3sports\Statistics\Service\VisitorStatistics
{
}
class tx_cfcleaguefe_sv2_TeamStatisticsMarker extends System25\T3sports\Statistics\TeamStatisticsMarker
{
}
