<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Marker\Templates;
use System25\T3sports\Table\Football\Configurator;

/**
 * Renderer for table control elements (e.g., dropdowns for table type, scope, point system).
 * This class handles the UI generation for table configuration controls.
 */
class TableControlRenderer
{
    /**
     * Renders the control panel for table settings.
     *
     * @param string $template The HTML template for controls
     * @param Configurator $configurator The table configurator
     * @param ConfigurationInterface $configurations The configuration object
     * @param string $confId The configuration ID
     * @return string The rendered control HTML
     */
    public function renderControls(string $template, Configurator $configurator, ConfigurationInterface $configurations, string $confId): string
    {
        $markerArray = [];
        $subpartArray = [
            '###CONTROL_TABLETYPE###' => '',
            '###CONTROL_TABLESCOPE###' => '',
            '###CONTROL_POINTSYSTEM###' => '',
        ];

        // Table type => Home/Away
        if ($configurations->get('tabletypeSelectionInput') || $configurations->get('leaguetable.tablecfg.tabletypeSelectionInput')) {
            $items = [0, 1, 2];
            $arr = [$items, $configurator->getTableType()];
            $subpartArray['###CONTROL_TABLETYPE###'] = $this->fillControlTemplate(
                Templates::getSubpart($template, '###CONTROL_TABLETYPE###'),
                $arr,
                'TABLETYPE',
                $configurations,
                $confId
            );
        }

        // Table scope => First/Second half
        if ($configurations->get('tablescopeSelectionInput') || $configurations->get('leaguetable.tablecfg.tablescopeSelectionInput')) {
            $items = [0, 1, 2];
            $arr = [$items, $configurator->getTableScope()];
            $subpartArray['###CONTROL_TABLESCOPE###'] = $this->fillControlTemplate(
                Templates::getSubpart($template, '###CONTROL_TABLESCOPE###'),
                $arr,
                'TABLESCOPE',
                $configurations,
                $confId
            );
        }

        // Point system
        if ($configurations->get('pointSystemSelectionInput') || $configurations->get('leaguetable.tablecfg.pointSystemSelectionInput')) {
            $sports = $configurator->getCompetition()->getSports();
            $srv = \System25\T3sports\Utility\ServiceRegistry::getCompetitionService();
            $systems = $srv->getPointSystems($sports);
            $items = [];
            foreach ($systems as $system) {
                $items[] = $system[1];
            }
            $arr = [$items, $configurator->getPointSystem()];
            $subpartArray['###CONTROL_POINTSYSTEM###'] = $this->fillControlTemplate(
                Templates::getSubpart($template, '###CONTROL_POINTSYSTEM###'),
                $arr,
                'POINTSYSTEM',
                $configurations,
                $confId
            );
        }

        return Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
    }

    /**
     * Fills a control template with selectable items.
     *
     * @param string $template The template for the control
     * @param array $itemsArr Array with [items, currentValue]
     * @param string $markerName Name of the marker (e.g., 'TABLETYPE')
     * @param ConfigurationInterface $configurations Configuration object
     * @param string $confId Configuration ID
     * @return string The filled template
     */
    protected function fillControlTemplate(string $template, array $itemsArr, string $markerName, ConfigurationInterface $configurations, string $confId): string
    {
        $currItem = $itemsArr[1];
        $confName = strtolower($markerName);
        $formatter = $configurations->getFormatter();

        $currentNoLink = $configurations->getInt($confId . $confName . '.current.noLink');

        $token = $this->getToken();
        $markerArray = $subpartArray = [];

        foreach ($itemsArr[0] as $key => $value) {
            $link = $configurations->createLink();
            $link->label($token);
            $params = array_merge($link->overruledParameters, [$confName => $key]);
            $link->initByTS($configurations, $confId . $confName . '.link.', $params);

            $isCurrent = ($key == $currItem);
            $markerLabel = $formatter->wrap($key, $confId . $confName . '.' . $key . '.');

            $data['iscurrent'] = $isCurrent ? 1 : 0;
            $data['value'] = $value;

            $tempArray = $formatter->getItemMarkerArrayWrapped($data, $confId . $confName . '.', 0, 'CONTROL_' . $markerName . '_' . $markerLabel . '_');
            $tempArray['###CONTROL_' . $markerName . '_' . $markerLabel . '###'] = $tempArray['###CONTROL_' . $markerName . '_' . $markerLabel . '_VALUE###'];
            $markerArray = array_merge($markerArray, $tempArray);

            $url = $formatter->wrap($link->makeUrl(false), $confId . $confName . ($isCurrent ? '.current.' : '.normal.'));
            $markerArray['###CONTROL_' . $markerName . '_' . $markerLabel . '_LINK_URL###'] = $url;
            $markerArray['###CONTROL_' . $markerName . '_' . $markerLabel . '_LINKURL###'] = $url;

            $linkStr = ($currentNoLink && $key == $currItem) ? $token : $link->makeTag();
            $linkStr = $formatter->wrap($linkStr, $confId . $confName . ($isCurrent ? '.current.' : '.normal.'));
            $wrappedSubpartArray['###CONTROL_' . $markerName . '_' . $markerLabel . '_LINK###'] = explode($token, $linkStr);
        }

        return Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }

    /**
     * Returns a token string for link generation.
     *
     * @return string
     */
    protected static function getToken(): string
    {
        static $token = '';
        if (!$token) {
            $token = md5(microtime());
        }
        return $token;
    }
}