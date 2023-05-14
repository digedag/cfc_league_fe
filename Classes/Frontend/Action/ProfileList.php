<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Frontend\Filter\Utility\CharBrowserFilter;
use Sys25\RnBase\Frontend\Filter\Utility\PageBrowserFilter;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Search\SearchBase;
use System25\T3sports\Frontend\View\ProfileListView;
use System25\T3sports\Model\Repository\ProfileRepository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2021 Rene Nitzsche (rene@system25.de)
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

/**
 * Controller für die Anzeige einer Personenliste
 * Die Liste wird sortiert nach Namen angezeigt.
 * Dabei wird ein Pager verwendet, der für
 * jeden Buchstaben eine eigene Seite erstellt.
 */
class ProfileList extends AbstractAction
{
    private $profileRepo;

    public function __construct(ProfileRepository $repo = null)
    {
        $this->profileRepo = $repo ?: new ProfileRepository();
    }

    /**
     * {@inheritDoc}
     *
     * @see \Sys25\RnBase\Frontend\Controller\AbstractAction::handleRequest()
     */
    protected function handleRequest(RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $viewData = $request->getViewContext();

        $filter = BaseFilter::createFilter($request, $this->getConfId().'profile.');
        $fields = $options = [];
        $filter->init($fields, $options);

        // Soll ein CharBrowser verwendet werden
        $cbFilter = new CharBrowserFilter();
        $cbFilter->handle($configurations, $this->getConfId().'profile.charbrowser', $viewData, $fields, $options, [
            'searchcallback' => [
                $this->profileRepo,
                'search',
            ],
            'colname' => 'last_name',
        ]);

        // FIXME: in Filterklasse auslagern
        $this->initSearch($fields, $options, $parameters, $configurations);

        // Soll ein PageBrowser verwendet werden
        $pbFilter = new PageBrowserFilter();
        $pbFilter->handle($configurations, $this->getConfId().'profile.pagebrowser', $viewData, $fields, $options, [
            'searchcallback' => [
                $this->profileRepo,
                'search',
            ],
            'pbid' => 'profile',
        ]);

        $result = $this->profileRepo->search($fields, $options);
        $viewData->offsetSet('profiles', $result);

        return null;
    }

    protected function initSearch(&$fields, &$options, &$parameters, &$configurations)
    {
        // ggf. die Konfiguration aus der TS-Config lesen
        SearchBase::setConfigFields($fields, $configurations, 'profilelist.fields.');
        SearchBase::setConfigOptions($options, $configurations, 'profilelist.options.');
        $timeRange = $configurations->get('profilelist.birthdays');
        if ($timeRange) {
            // Die Sortierung funktioniert nicht für Geburtstage vor 1970
            $timePattern = 'DAY' == $timeRange ? '%d%m' : '%m';
            $where = " ( (DATE_FORMAT(FROM_UNIXTIME(PROFILE.birthday), '{$timePattern}') = DATE_FORMAT(CURDATE(), '{$timePattern}')";
            $where .= ' AND PROFILE.birthday > 0) OR ';
            // Variante 2 für Zeiten vor 1970
            $where .= " (DATE_FORMAT(SUBDATE('1970-01-01', DATEDIFF(FROM_UNIXTIME( ABS( PROFILE.birthday )), '1970-01-01')), '{$timePattern}') = DATE_FORMAT(CURDATE(), '{$timePattern}')";
            $where .= ' AND PROFILE.birthday < 0 ))';
            if (isset($fields[SEARCH_FIELD_CUSTOM])) {
                $fields[SEARCH_FIELD_CUSTOM] .= ' AND ' . $where;
            } else {
                $fields[SEARCH_FIELD_CUSTOM] = $where;
            }

            // Sortierung nach Datum
            $sort = [
                'DATE_FORMAT(FROM_UNIXTIME(PROFILE.birthday), \'%m%d\')' => 'asc',
            ];
            if (is_array($options['orderby'])) {
                $options['orderby'] = array_merge($sort, $options['orderby']);
            } else {
                $options['orderby'] = $sort;
            }
        }
    }

    /**
     * Wir verwenden einen alphabetischen Pager.
     * Also muß zunächst ermittelt werden, welche
     * Buchstaben überhaupt vorkommen.
     *
     * @param \System25\T3sports\Service\ProfileService $service
     * @param ConfigurationInterface $configurations
     */
    protected function findPagerData($service, ConfigurationInterface $configurations)
    {
        $options = [];
        $options['what'] = 'LEFT(UCASE(last_name),1) As first_char, count(LEFT(UCASE(last_name),1)) As size';
        $options['groupby'] = 'LEFT(UCASE(last_name),1)';
        $fields = [];
        SearchBase::setConfigFields($fields, $configurations, 'profilelist.fields.');
        SearchBase::setConfigOptions($options, $configurations, 'profilelist.options.');
        // replace orderby
        unset($options['orderby']);
        $options['orderby']['first_char'] = 'asc';

        $rows = $service->search($fields, $options);

        $specials = SearchBase::getSpecialChars();
        $wSpecials = [];
        foreach ($specials as $key => $special) {
            foreach ($special as $char) {
                $wSpecials[$char] = $key;
            }
        }

        $ret = [];
        foreach ($rows as $row) {
            if (array_key_exists($row['first_char'], $wSpecials)) {
                $ret[$wSpecials[$row['first_char']]] = intval($ret[$wSpecials[$row['first_char']]]) + $row['size'];
            } else {
                $ret[$row['first_char']] = $row['size'];
            }
        }

        $current = 0;
        if (count($ret)) {
            $keys = array_keys($ret);
            $current = $keys[0];
        }
        $data = [
            'list' => $ret,
            'default' => $current,
        ];

        return $data;
    }

    protected function getTemplateName()
    {
        return 'profilelist';
    }

    protected function getViewClassName()
    {
        return ProfileListView::class;
    }
}
