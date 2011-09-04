<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Rene Nitzsche (rene@system25.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

tx_rnbase::load('tx_rnbase_util_BaseMarker');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays der Teams verantwortlich
 */
class tx_cfcleaguefe_util_TeamMarker extends tx_rnbase_util_BaseMarker {
	private $options = null;
	function __construct($options = null) {
		$this->options = $options;
	}

	/**
	 * @param string $template das HTML-Template
	 * @param tx_cfcleaguefe_models_team $team das Team
 	 * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
	 * @param string $teamConfId Pfad der TS-Config des Profils, z.B. 'listView.profile.'
	 * @param array $links Array mit Link-Instanzen, wenn Verlinkung möglich sein soll. Zielseite muss vorbereitet sein.
	 * @param string $teamMarker Name des Markers für das Team, z.B. TEAM, MATCH_HOME usw.
	 *        Von diesem String hängen die entsprechenden weiteren Marker ab: ###COACH_SIGN###, ###COACH_LINK###
	 * @return String das geparste Template
	 */
	public function parseTemplate($template, $team, $formatter, $confId, $marker = 'TEAM') {
		if(!is_object($team)) {
			return $formatter->configurations->getLL('team_notFound');
		}
		$this->prepareRecord($team);
		tx_rnbase_util_Misc::callHook('cfc_league_fe','teamMarker_initRecord', array('item' => &$team, 'template'=>&$template, 'confid'=>$confId, 'marker'=>$marker, 'formatter'=>$formatter), $this);
		// Es wird das MarkerArray mit den Daten des Teams gefüllt.
		$ignore = self::findUnusedCols($team->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($team->record, $confId , $ignore, $marker.'_',$team->getColumnNames());
		$wrappedSubpartArray = array();
		$subpartArray = array();
		$this->prepareLinks($team, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
		// Die Spieler setzen
		$this->pushTT('add player');
		if($this->containsMarker($template, $marker.'_PLAYERS'))
			$template = $this->_addProfiles($template, $team, $formatter, $confId.'player.', $marker.'_PLAYER','players');
		$this->pullTT();

		// Die Trainer setzen
		$this->pushTT('add coaches');
		if($this->containsMarker($template, $marker.'_COACHS'))
			$template = $this->_addProfiles($template, $team, $formatter, $confId.'coach.', $marker.'_COACH','coaches');
		$this->pullTT();

		// Die Betreuer setzen
		$this->pushTT('add supporter');
		if($this->containsMarker($template, $marker.'_SUPPORTERS'))
			$template = $this->_addProfiles($template, $team, $formatter, $confId.'supporter.', $marker.'_SUPPORTER','supporters');
		$this->pullTT();

		// set club data
		$this->pushTT('Club data');
		if(self::containsMarker($template, $marker.'_CLUB_'))
			$template = $this->_addClubData($template, $team->getClub(), $formatter, $confId.'club.', $marker.'_CLUB');
		$this->pullTT();

		if($this->containsMarker($template, $marker.'_GROUP_'))
			$template = $this->addGroup($template, $team, $formatter, $confId.'group.', $marker.'_GROUP');

		$template = self::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		tx_rnbase_util_Misc::callHook('cfc_league_fe','teamMarker_afterSubst', array('item' => &$team, 'template'=>&$template, 'confid'=>$confId, 'marker'=>$marker, 'formatter'=>$formatter), $this);
		return $template;
	}

	/**
	 * Prepare team record before rendering
	 *
	 * @param tx_cfcleaguefe_models_team $item
	 */
	private function prepareRecord(&$item) {
		$srv = tx_cfcleague_util_ServiceRegistry::getTeamService();
		$group = $srv->getAgeGroup($item);
//		$group = $item->getAgeGroup();
		$GLOBALS['TSFE']->register['T3SPORTS_TEAMGROUP'] = is_object($group) ? $group->uid : 0;
		$item->record['group'] = is_object($group) ?  $group->getUid() : '0';
		$item->record['agegroup_name'] = is_object($group) ?  $group->getName() : '';
		$item->record['firstpicture'] = $item->record['dam_images'];
		$item->record['pictures'] = $item->record['dam_images'];
	}
  
	/**
	 * Hinzufügen der Daten des Vereins
	 *
	 * @param string $template
	 * @param tx_cfcleaguefe_models_club $club
	 */
	protected function _addClubData($template, $club, $formatter, $clubConf, $markerPrefix) {
		$clubMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ClubMarker');
		$template = $clubMarker->parseTemplate($template, $club, $formatter, $clubConf, $markerPrefix);
		return $template;
	}
	/**
	 * Hinzufügen der Altersklasse
	 *
	 * @param string $template
	 * @param tx_cfcleaguefe_models_team $item
	 */
	protected function addGroup($template, $item, $formatter, $confId, $markerPrefix) {
		$srv = tx_cfcleague_util_ServiceRegistry::getTeamService();
		$group = $srv->getAgeGroup($item);

		$groupMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_GroupMarker');
		$template = $groupMarker->parseTemplate($template, $group, $formatter, $confId, $markerPrefix);
		return $template;
	}

	/**
	 * Hinzufügen der Spieler des Teams.
	 * @param string $template HTML-Template für die Profile
	 * @param tx_cfcleaguefe_models_team $team
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId Config-String für den Wrap der Profile
	 * @param string $markerPrefix Prefix für die Daten des Profile-Records
	 * @param string $joinCol Name der Teamspalte mit den Profilen players, coaches, supporters
	 */
	private function _addProfiles($template, &$team, &$formatter, $confId, $markerPrefix, $joinCol) {
  	// Ohne Template gibt es nichts zu tun!
    if(strlen(trim($template)) == 0) return '';

		//$srv = tx_cfcleague_util_ServiceRegistry::getProfileService();
    $srv = tx_cfcleaguefe_util_ServiceRegistry::getProfileService();
		$fields['PROFILE.UID'][OP_IN_INT] = $team->record[$joinCol];
		$options = array();
		tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->configurations, $confId.'fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->configurations, $confId.'options.');
		$children = $srv->search($fields, $options);
		if(!empty($children) && !array_key_exists('orderby', $options)) // Default sorting
			$children = $this->sortProfiles($children, $team->record[$joinCol]);
			
		$options['team'] = $team;
		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
		$out = $listBuilder->render($children,
						new ArrayObject(), $template, 'tx_cfcleaguefe_util_ProfileMarker',
						$confId, $markerPrefix, $formatter, $options);
		return $out;
	}
	/**
	 * Sortiert die Profile nach der Reihenfolge im Team
	 *
	 * @param array $profiles
	 * @param string $sortArr
	 * @return array
	 */
	function sortProfiles(&$profiles, $sortArr) {
		$sortArr = array_flip(t3lib_div::intExplode(',', $sortArr));
		foreach($profiles As $profile) 
			$sortArr[$profile->uid] = $profile;
		$ret = array();
		foreach($sortArr As $profile)
			$ret[] = $profile;
		return $ret;
	}

	/**
	 * Initialisiert die Labels für die Team-Klasse
	 *
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param array $defaultMarkerArr
	 */
	public function initLabelMarkers(&$formatter, $confId, $defaultMarkerArr = 0, $marker = 'TEAM') {
		return $this->prepareLabelMarkers('tx_cfcleaguefe_models_team', $formatter, $confId, $defaultMarkerArr, $marker);
	}

	/**
	 * @return tx_cfcleaguefe_util_ClubMarker
	 */
	private function getClubMarker() {
		if(!$this->clubMarker) {
			$this->clubMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ClubMarker');
		}
		return $this->clubMarker;
	}
	/**
	 * Create a marker for GoogleMaps. This can be done if the team has a club with address data.
	 * @param string $template
	 * @param tx_cfcleague_models_Team $item
	 */
	public function createMapMarker($template, $item, $formatter, $confId, $markerPrefix) {
		$club = $item->getClub();
		if(!$club) return false;

		$template = $this->parseTemplate($template, $item, $formatter, $confId, $markerPrefix);
		$marker = $this->getClubMarker()->createMapMarker($template, $club, $formatter, $confId, $markerPrefix);
		return $marker;
	}

	/**
	 * Links vorbereiten
	 *
	 * @param tx_cfcleaguefe_models_team $team
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	protected function prepareLinks(&$team, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showmatchtable', $marker, array('teamId' => $team->uid), $template);
		if($team->hasReport()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showteam', $marker, array('teamId' => $team->uid), $template);
		}
		else {
			$linkId = 'showteam';
			$linkMarker = $marker . '_' . strtoupper($linkId).'LINK';
			$remove = intval($formatter->configurations->get($confId.'links.'.$linkId.'.removeIfDisabled')); 
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
		}
	}
	public function getOptions() {
		return $this->options;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_TeamMarker.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_TeamMarker.php']);
}
?>