<?php

########################################################################
# Extension Manager/Repository config file for ext "cfc_league_fe".
#
# Auto generated 17-07-2010 14:52
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'T3sports FE',
	'description' => 'FE-Plugins von T3sports. Liefert u.a. die Views Spielplan, Spielbericht, Tabellen, Team- und Spieleransicht. FE plugins for T3sports. Contains views for matchtable, leaguetable, matchreport, team and player reports and many more. Requires PHP5!',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.8.3',
	'dependencies' => 'rn_base,rn_memento,pbimagegraph,cfc_league',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Rene Nitzsche',
	'author_email' => 'rene@system25.de',
	'author_company' => 'System 25',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.0.0-0.0.0',
			'rn_base' => '0.11.8-0.0.0',
			'rn_memento' => '',
			'pbimagegraph' => '2.0.0-0.0.0',
			'cfc_league' => '0.8.1-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'dam' => '1.0.11-0.0.0',
		),
	),
	'_md5_values_when_last_written' => 'a:148:{s:9:"ChangeLog";s:4:"5b56";s:10:"README.txt";s:4:"6468";s:21:"ext_conf_template.txt";s:4:"c5f3";s:12:"ext_icon.gif";s:4:"0eb4";s:17:"ext_localconf.php";s:4:"b066";s:14:"ext_tables.php";s:4:"a41e";s:24:"flexform_competition.xml";s:4:"ffbb";s:19:"flexform_report.xml";s:4:"0add";s:13:"locallang.xml";s:4:"9c27";s:16:"locallang_db.xml";s:4:"d5ef";s:49:"actions/class.tx_cfcleaguefe_actions_ClubList.php";s:4:"7c7b";s:49:"actions/class.tx_cfcleaguefe_actions_ClubView.php";s:4:"1a19";s:61:"actions/class.tx_cfcleaguefe_actions_CompetitionSelection.php";s:4:"b8c1";s:59:"actions/class.tx_cfcleaguefe_actions_LeagueTableAllTime.php";s:4:"fdb4";s:56:"actions/class.tx_cfcleaguefe_actions_LeagueTableShow.php";s:4:"ea8a";s:55:"actions/class.tx_cfcleaguefe_actions_LiveTickerList.php";s:4:"88ca";s:56:"actions/class.tx_cfcleaguefe_actions_MatchCrossTable.php";s:4:"85c9";s:52:"actions/class.tx_cfcleaguefe_actions_MatchReport.php";s:4:"8137";s:51:"actions/class.tx_cfcleaguefe_actions_MatchTable.php";s:4:"631a";s:52:"actions/class.tx_cfcleaguefe_actions_ProfileList.php";s:4:"d408";s:52:"actions/class.tx_cfcleaguefe_actions_ProfileView.php";s:4:"a202";s:52:"actions/class.tx_cfcleaguefe_actions_StadiumList.php";s:4:"748b";s:52:"actions/class.tx_cfcleaguefe_actions_StadiumView.php";s:4:"5587";s:51:"actions/class.tx_cfcleaguefe_actions_Statistics.php";s:4:"03b7";s:51:"actions/class.tx_cfcleaguefe_actions_TableChart.php";s:4:"d65b";s:49:"actions/class.tx_cfcleaguefe_actions_TeamList.php";s:4:"5fcd";s:49:"actions/class.tx_cfcleaguefe_actions_TeamView.php";s:4:"e131";s:14:"doc/manual.sxw";s:4:"a968";s:19:"doc/wizard_form.dat";s:4:"73f4";s:20:"doc/wizard_form.html";s:4:"7f64";s:46:"filter/class.tx_cfcleaguefe_filter_Stadium.php";s:4:"ee85";s:43:"filter/class.tx_cfcleaguefe_filter_Team.php";s:4:"d668";s:53:"hooks/class.tx_cfcleaguefe_hooks_TableMatchMarker.php";s:4:"9875";s:50:"hooks/class.tx_cfcleaguefe_hooks_ttnewsMarkers.php";s:4:"0499";s:46:"models/class.tx_cfcleaguefe_models_address.php";s:4:"38b9";s:43:"models/class.tx_cfcleaguefe_models_base.php";s:4:"fedd";s:43:"models/class.tx_cfcleaguefe_models_club.php";s:4:"45ab";s:50:"models/class.tx_cfcleaguefe_models_competition.php";s:4:"c04f";s:58:"models/class.tx_cfcleaguefe_models_competition_penalty.php";s:4:"0202";s:44:"models/class.tx_cfcleaguefe_models_group.php";s:4:"6cf8";s:44:"models/class.tx_cfcleaguefe_models_match.php";s:4:"2593";s:53:"models/class.tx_cfcleaguefe_models_match_calevent.php";s:4:"71d9";s:49:"models/class.tx_cfcleaguefe_models_match_note.php";s:4:"405d";s:50:"models/class.tx_cfcleaguefe_models_matchreport.php";s:4:"64ef";s:49:"models/class.tx_cfcleaguefe_models_matchtable.php";s:4:"64e2";s:46:"models/class.tx_cfcleaguefe_models_profile.php";s:4:"3028";s:45:"models/class.tx_cfcleaguefe_models_saison.php";s:4:"13f1";s:43:"models/class.tx_cfcleaguefe_models_team.php";s:4:"033f";s:47:"models/class.tx_cfcleaguefe_models_teamNote.php";s:4:"84b8";s:51:"models/class.tx_cfcleaguefe_models_teamNoteType.php";s:4:"815c";s:17:"res/cfcleague.css";s:4:"16cc";s:17:"res/clublogo.html";s:4:"ed71";s:24:"res/gallerifficpics.html";s:4:"8f58";s:19:"res/gelbe-karte.gif";s:4:"49cc";s:22:"res/gelbrote-karte.gif";s:4:"05db";s:12:"res/goal.gif";s:4:"5632";s:21:"res/lightboxpics.html";s:4:"8f43";s:14:"res/nimbus.ttf";s:4:"41a7";s:18:"res/rote-karte.gif";s:4:"4de0";s:46:"search/class.tx_cfcleaguefe_search_Builder.php";s:4:"4ffa";s:50:"search/class.tx_cfcleaguefe_search_Competition.php";s:4:"fea7";s:44:"search/class.tx_cfcleaguefe_search_Match.php";s:4:"dd52";s:46:"search/class.tx_cfcleaguefe_search_Profile.php";s:4:"ae27";s:43:"search/class.tx_cfcleaguefe_search_Team.php";s:4:"cc2a";s:20:"static/constants.txt";s:4:"b5e2";s:16:"static/setup.txt";s:4:"839b";s:20:"static/cal/setup.txt";s:4:"e2e3";s:22:"static/nodam/setup.txt";s:4:"3491";s:45:"sv1/class.tx_cfcleaguefe_sv1_Competitions.php";s:4:"8367";s:43:"sv1/class.tx_cfcleaguefe_sv1_MatchEvent.php";s:4:"323a";s:40:"sv1/class.tx_cfcleaguefe_sv1_Matches.php";s:4:"c50e";s:41:"sv1/class.tx_cfcleaguefe_sv1_Profiles.php";s:4:"20be";s:38:"sv1/class.tx_cfcleaguefe_sv1_Teams.php";s:4:"52ab";s:49:"sv2/class.tx_cfcleaguefe_sv2_AssistStatistics.php";s:4:"8b09";s:49:"sv2/class.tx_cfcleaguefe_sv2_PlayerStatistics.php";s:4:"2ee5";s:55:"sv2/class.tx_cfcleaguefe_sv2_PlayerStatisticsMarker.php";s:4:"ca55";s:56:"sv2/class.tx_cfcleaguefe_sv2_PlayerSummaryStatistics.php";s:4:"82d9";s:62:"sv2/class.tx_cfcleaguefe_sv2_PlayerSummaryStatisticsMarker.php";s:4:"fa9a";s:49:"sv2/class.tx_cfcleaguefe_sv2_ScorerStatistics.php";s:4:"5c10";s:53:"sv2/class.tx_cfcleaguefe_sv2_TeamStatisticsMarker.php";s:4:"13a0";s:50:"sv2/class.tx_cfcleaguefe_sv2_VisitorStatistics.php";s:4:"f20e";s:53:"svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php";s:4:"322a";s:55:"svmarker/class.tx_cfcleaguefe_svmarker_MatchHistory.php";s:4:"0675";s:26:"svmarker/ext_localconf.php";s:4:"8451";s:62:"tests/class.tx_cfcleaguefe_tests_util_LeagueTable_testcase.php";s:4:"db70";s:36:"tests/fixtures/util_LeagueTable.yaml";s:4:"7041";s:48:"util/class.tx_cfcleaguefe_util_AddressMarker.php";s:4:"21f5";s:45:"util/class.tx_cfcleaguefe_util_ClubMarker.php";s:4:"f979";s:52:"util/class.tx_cfcleaguefe_util_CompetitionMarker.php";s:4:"56ba";s:46:"util/class.tx_cfcleaguefe_util_GroupMarker.php";s:4:"a9df";s:46:"util/class.tx_cfcleaguefe_util_LeagueTable.php";s:4:"68cc";s:52:"util/class.tx_cfcleaguefe_util_LeagueTableWriter.php";s:4:"e3dd";s:39:"util/class.tx_cfcleaguefe_util_Maps.php";s:4:"dd46";s:46:"util/class.tx_cfcleaguefe_util_MatchMarker.php";s:4:"23c6";s:57:"util/class.tx_cfcleaguefe_util_MatchMarkerBuilderInfo.php";s:4:"4c75";s:54:"util/class.tx_cfcleaguefe_util_MatchMarkerListInfo.php";s:4:"da07";s:45:"util/class.tx_cfcleaguefe_util_MatchTable.php";s:4:"d929";s:46:"util/class.tx_cfcleaguefe_util_MatchTicker.php";s:4:"ab50";s:48:"util/class.tx_cfcleaguefe_util_ProfileMarker.php";s:4:"33b6";s:50:"util/class.tx_cfcleaguefe_util_ScopeController.php";s:4:"7bb6";s:50:"util/class.tx_cfcleaguefe_util_ServiceRegistry.php";s:4:"f59c";s:48:"util/class.tx_cfcleaguefe_util_StadiumMarker.php";s:4:"e96a";s:45:"util/class.tx_cfcleaguefe_util_Statistics.php";s:4:"9dd4";s:51:"util/class.tx_cfcleaguefe_util_StatisticsHelper.php";s:4:"5943";s:45:"util/class.tx_cfcleaguefe_util_TeamMarker.php";s:4:"a9b3";s:42:"util/class.tx_cfcleaguefe_util_wizicon.php";s:4:"51bf";s:69:"util/league/class.tx_cfcleaguefe_util_league_AllTimeTableProvider.php";s:4:"fc94";s:69:"util/league/class.tx_cfcleaguefe_util_league_DefaultTableProvider.php";s:4:"34d8";s:73:"util/league/class.tx_cfcleaguefe_util_league_SingleMatchTableProvider.php";s:4:"72bf";s:62:"util/league/class.tx_cfcleaguefe_util_league_TableProvider.php";s:4:"56e9";s:45:"views/class.tx_cfcleaguefe_views_ClubList.php";s:4:"ed56";s:45:"views/class.tx_cfcleaguefe_views_ClubView.php";s:4:"eb3b";s:48:"views/class.tx_cfcleaguefe_views_LeagueTable.php";s:4:"0d70";s:55:"views/class.tx_cfcleaguefe_views_LeagueTableAllTime.php";s:4:"b13f";s:51:"views/class.tx_cfcleaguefe_views_LiveTickerList.php";s:4:"2d53";s:52:"views/class.tx_cfcleaguefe_views_MatchCrossTable.php";s:4:"62b3";s:48:"views/class.tx_cfcleaguefe_views_MatchReport.php";s:4:"6360";s:47:"views/class.tx_cfcleaguefe_views_MatchTable.php";s:4:"84ba";s:48:"views/class.tx_cfcleaguefe_views_ProfileList.php";s:4:"4672";s:48:"views/class.tx_cfcleaguefe_views_ProfileView.php";s:4:"b8e3";s:51:"views/class.tx_cfcleaguefe_views_ScopeSelection.php";s:4:"c0e5";s:48:"views/class.tx_cfcleaguefe_views_StadiumList.php";s:4:"2c9f";s:48:"views/class.tx_cfcleaguefe_views_StadiumView.php";s:4:"ff99";s:47:"views/class.tx_cfcleaguefe_views_Statistics.php";s:4:"6f7c";s:45:"views/class.tx_cfcleaguefe_views_TeamList.php";s:4:"821c";s:45:"views/class.tx_cfcleaguefe_views_TeamView.php";s:4:"8a82";s:29:"views/templates/clublist.html";s:4:"fe2d";s:40:"views/templates/competitionselection.php";s:4:"a12f";s:32:"views/templates/leaguetable.html";s:4:"b90c";s:31:"views/templates/leaguetable.php";s:4:"a9da";s:39:"views/templates/leaguetableAllTime.html";s:4:"15fe";s:39:"views/templates/leaguetable_teaser.html";s:4:"1390";s:38:"views/templates/leaguetable_teaser.php";s:4:"97a5";s:35:"views/templates/livetickerlist.html";s:4:"0144";s:32:"views/templates/match_event.html";s:4:"66dc";s:36:"views/templates/matchcrosstable.html";s:4:"b8ee";s:32:"views/templates/matchreport.html";s:4:"9004";s:31:"views/templates/matchreport.php";s:4:"1095";s:31:"views/templates/matchtable.html";s:4:"bb7d";s:30:"views/templates/matchtable.php";s:4:"e90b";s:36:"views/templates/playerstatistics.php";s:4:"4de0";s:32:"views/templates/profileview.html";s:4:"8186";s:35:"views/templates/scopeselection.html";s:4:"33e1";s:29:"views/templates/stadiums.html";s:4:"945e";s:31:"views/templates/statistics.html";s:4:"c9c3";s:30:"views/templates/tablechart.php";s:4:"f181";s:29:"views/templates/teamlist.html";s:4:"8e8f";s:29:"views/templates/teamview.html";s:4:"bdc8";}',
	'suggests' => array(
	),
);

?>