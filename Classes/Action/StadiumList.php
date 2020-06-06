<?php

namespace System25\T3sports\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;

class StadiumList extends AbstractAction
{
    protected function handleRequest(RequestInterface $request)
    {
        $parameters = $request->getParameters();
        $configurations = $request->getConfigurations();
        $viewData = $request->getViewContext();
        $srv = \tx_cfcleague_util_ServiceRegistry::getStadiumService();

        $filter = \tx_rnbase_filter_BaseFilter::createFilter($parameters, $configurations, $viewData, $this->getConfId());

        $fields = $options = [];
        $filter->init($fields, $options, $parameters, $configurations, $this->getConfId());

        // Soll ein PageBrowser verwendet werden
        \tx_rnbase_filter_BaseFilter::handleCharBrowser($configurations, $this->getConfId().'stadium.charbrowser', $viewData, $fields, $options, [
            'searchcallback' => [
                $srv,
                'search',
            ],
            'colname' => 'name',
        ]);
        \tx_rnbase_filter_BaseFilter::handlePageBrowser($configurations, $this->getConfId().'stadium.pagebrowser', $viewData, $fields, $options, [
            'searchcallback' => [
                $srv,
                'search',
            ],
            'pbid' => 'stadium',
        ]);

        $items = $srv->search($fields, $options);
        $viewData->offsetSet('items', $items);

        return null;
    }

    protected function getViewClassName()
    {
        return \System25\T3sports\View\StadiumList::class;
    }

    protected function getTemplateName()
    {
        return 'stadiumlist';
    }
}
