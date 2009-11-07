<?php

########################################################################
# Extension Manager/Repository config file for ext: "cfc_league_fe"
#
# Auto generated 12-12-2007 16:57
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'T3sports FE',
	'description' => 'FE-Plugins von T3sports. Liefert u.a. die Views Spielplan, Spielbericht, Tabellen, Team- und Spieleransicht. FE plugins for T3sports. Contains views for matchtable, leaguetable, matchreport, team and player reports and many more. Requires PHP5!',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.6.0',
	'dependencies' => '',
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
			'div' => '0.1.0-0.0.0',
			'lib' => '0.1.0-0.0.0',
			'rn_base' => '0.5.2-0.0.0',
			'rn_memento' => '',
			'pbimagegraph' => '1.1.1-0.0.0',
			'dam' => '1.0.11-0.0.0',
			'cfc_league' => '0.6.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:89:{s:9:"ChangeLog";s:4:"fb28";s:10:"README.txt";s:4:"6468";s:12:"ext_icon.gif";s:4:"0eb4";s:17:"ext_localconf.php";s:4:"6f31";s:14:"ext_tables.php";s:4:"6f8d";s:24:"flexform_competition.xml";s:4:"d389";s:19:"flexform_report.xml";s:4:"a639";s:13:"locallang.xml";s:4:"00b6";s:16:"locallang_db.xml";s:4:"98fc";s:61:"actions/class.tx_cfcleaguefe_actions_CompetitionSelection.php";s:4:"619b";s:56:"actions/class.tx_cfcleaguefe_actions_LeagueTableShow.php";s:4:"a07e";s:55:"actions/class.tx_cfcleaguefe_actions_LiveTickerList.php";s:4:"c742";s:52:"actions/class.tx_cfcleaguefe_actions_MatchReport.php";s:4:"838c";s:51:"actions/class.tx_cfcleaguefe_actions_MatchTable.php";s:4:"fa65";s:52:"actions/class.tx_cfcleaguefe_actions_ProfileList.php";s:4:"cea1";s:52:"actions/class.tx_cfcleaguefe_actions_ProfileView.php";s:4:"e665";s:51:"actions/class.tx_cfcleaguefe_actions_Statistics.php";s:4:"ff12";s:51:"actions/class.tx_cfcleaguefe_actions_TableChart.php";s:4:"86f5";s:49:"actions/class.tx_cfcleaguefe_actions_TeamView.php";s:4:"a4b4";s:59:"controllers/class.tx_cfcleaguefe_controllers_matchtable.php";s:4:"0ac9";s:55:"controllers/class.tx_cfcleaguefe_controllers_report.php";s:4:"d352";s:14:"doc/manual.sxw";s:4:"4812";s:19:"doc/wizard_form.dat";s:4:"73f4";s:20:"doc/wizard_form.html";s:4:"7f64";s:43:"models/class.tx_cfcleaguefe_models_base.php";s:4:"fedd";s:43:"models/class.tx_cfcleaguefe_models_club.php";s:4:"750e";s:50:"models/class.tx_cfcleaguefe_models_competition.php";s:4:"b2fe";s:58:"models/class.tx_cfcleaguefe_models_competition_penalty.php";s:4:"0f47";s:44:"models/class.tx_cfcleaguefe_models_group.php";s:4:"adfe";s:44:"models/class.tx_cfcleaguefe_models_match.php";s:4:"3e64";s:53:"models/class.tx_cfcleaguefe_models_match_calevent.php";s:4:"0d3b";s:49:"models/class.tx_cfcleaguefe_models_match_note.php";s:4:"a126";s:50:"models/class.tx_cfcleaguefe_models_matchreport.php";s:4:"6637";s:49:"models/class.tx_cfcleaguefe_models_matchtable.php";s:4:"4895";s:46:"models/class.tx_cfcleaguefe_models_profile.php";s:4:"8748";s:45:"models/class.tx_cfcleaguefe_models_saison.php";s:4:"5227";s:43:"models/class.tx_cfcleaguefe_models_team.php";s:4:"b64a";s:17:"res/cfcleague.css";s:4:"16cc";s:19:"res/gelbe-karte.gif";s:4:"49cc";s:22:"res/gelbrote-karte.gif";s:4:"05db";s:18:"res/rote-karte.gif";s:4:"4de0";s:20:"static/constants.txt";s:4:"196f";s:16:"static/setup.txt";s:4:"245b";s:20:"static/cal/setup.txt";s:4:"c52c";s:43:"sv1/class.tx_cfcleaguefe_sv1_MatchEvent.php";s:4:"1d01";s:49:"sv2/class.tx_cfcleaguefe_sv2_AssistStatistics.php";s:4:"be67";s:49:"sv2/class.tx_cfcleaguefe_sv2_PlayerStatistics.php";s:4:"7e22";s:55:"sv2/class.tx_cfcleaguefe_sv2_PlayerStatisticsMarker.php";s:4:"794a";s:56:"sv2/class.tx_cfcleaguefe_sv2_PlayerSummaryStatistics.php";s:4:"ba73";s:62:"sv2/class.tx_cfcleaguefe_sv2_PlayerSummaryStatisticsMarker.php";s:4:"6a41";s:49:"sv2/class.tx_cfcleaguefe_sv2_ScorerStatistics.php";s:4:"75ef";s:53:"sv2/class.tx_cfcleaguefe_sv2_TeamStatisticsMarker.php";s:4:"0979";s:50:"sv2/class.tx_cfcleaguefe_sv2_VisitorStatistics.php";s:4:"b9c7";s:62:"tests/class.tx_cfcleaguefe_tests_util_LeagueTable_testcase.php";s:4:"b6f5";s:36:"tests/fixtures/util_LeagueTable.yaml";s:4:"c658";s:46:"util/class.tx_cfcleaguefe_util_LeagueTable.php";s:4:"c6ee";s:46:"util/class.tx_cfcleaguefe_util_MatchMarker.php";s:4:"9c6a";s:46:"util/class.tx_cfcleaguefe_util_MatchTicker.php";s:4:"cd13";s:48:"util/class.tx_cfcleaguefe_util_ProfileMarker.php";s:4:"dd56";s:50:"util/class.tx_cfcleaguefe_util_ScopeController.php";s:4:"770e";s:45:"util/class.tx_cfcleaguefe_util_Statistics.php";s:4:"0e9b";s:51:"util/class.tx_cfcleaguefe_util_StatisticsHelper.php";s:4:"8ca6";s:45:"util/class.tx_cfcleaguefe_util_TeamMarker.php";s:4:"c751";s:48:"views/class.tx_cfcleaguefe_views_LeagueTable.php";s:4:"ed39";s:51:"views/class.tx_cfcleaguefe_views_LiveTickerList.php";s:4:"97c2";s:48:"views/class.tx_cfcleaguefe_views_MatchReport.php";s:4:"740d";s:47:"views/class.tx_cfcleaguefe_views_MatchTable.php";s:4:"baa0";s:48:"views/class.tx_cfcleaguefe_views_ProfileList.php";s:4:"1939";s:48:"views/class.tx_cfcleaguefe_views_ProfileView.php";s:4:"7813";s:51:"views/class.tx_cfcleaguefe_views_ScopeSelection.php";s:4:"2810";s:47:"views/class.tx_cfcleaguefe_views_Statistics.php";s:4:"6ea3";s:45:"views/class.tx_cfcleaguefe_views_TeamView.php";s:4:"a46b";s:40:"views/templates/competitionselection.php";s:4:"a12f";s:32:"views/templates/leaguetable.html";s:4:"9b5c";s:31:"views/templates/leaguetable.php";s:4:"a9da";s:39:"views/templates/leaguetable_teaser.html";s:4:"6353";s:38:"views/templates/leaguetable_teaser.php";s:4:"97a5";s:35:"views/templates/livetickerlist.html";s:4:"c441";s:32:"views/templates/match_event.html";s:4:"87f3";s:32:"views/templates/matchreport.html";s:4:"42b1";s:31:"views/templates/matchreport.php";s:4:"1095";s:31:"views/templates/matchtable.html";s:4:"58ae";s:30:"views/templates/matchtable.php";s:4:"e90b";s:36:"views/templates/playerstatistics.php";s:4:"4de0";s:32:"views/templates/profileview.html";s:4:"112f";s:35:"views/templates/scopeselection.html";s:4:"33e1";s:31:"views/templates/statistics.html";s:4:"c9c3";s:30:"views/templates/tablechart.php";s:4:"1d31";s:29:"views/templates/teamview.html";s:4:"6c94";}',
);

?>