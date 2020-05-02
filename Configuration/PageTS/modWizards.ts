mod.wizards {
	newContentElement.wizardItems.plugins {
		elements {
			t3sports_competition {
				iconIdentifier = t3sports_plugin
				title = LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.label
				description = LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.description
				tt_content_defValues {
					CType = list
					list_type = tx_cfcleaguefe_competition
				}
			}
			t3sports_report {
				iconIdentifier = t3sports_plugin
				title = LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.label
				description = LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.description
				tt_content_defValues {
					CType = list
					list_type = tx_cfcleaguefe_report
				}
			}
		}
	}
}
