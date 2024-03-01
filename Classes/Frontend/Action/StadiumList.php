<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Frontend\Filter\Utility\CharBrowserFilter;
use Sys25\RnBase\Frontend\Filter\Utility\PageBrowserFilter;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use System25\T3sports\Model\Repository\StadiumRepository;

class StadiumList extends AbstractAction
{
    private $repo;

    public function __construct(?StadiumRepository $repo = null)
    {
        $this->repo = $repo ?: new StadiumRepository();
    }

    protected function handleRequest(RequestInterface $request)
    {
        $parameters = $request->getParameters();
        $configurations = $request->getConfigurations();
        $viewData = $request->getViewContext();
        $filter = BaseFilter::createFilter($request, $this->getConfId().'stadium.');
        $fields = $options = [];
        $filter->init($fields, $options);

        // Soll ein CharBrowser verwendet werden
        $cbFilter = new CharBrowserFilter();
        $cbFilter->handle($configurations, $this->getConfId().'stadium.charbrowser', $viewData, $fields, $options, [
            'searchcallback' => [
                $this->repo,
                'search',
            ],
            'colname' => 'name',
        ]);
        // Soll ein PageBrowser verwendet werden
        $pbFilter = new PageBrowserFilter();
        $pbFilter->handle($configurations, $this->getConfId().'stadium.pagebrowser', $viewData, $fields, $options, [
            'searchcallback' => [
                $this->repo,
                'search',
            ],
            'pbid' => 'stadium',
        ]);

        $items = $this->repo->search($fields, $options);

        $viewData->offsetSet('items', $items);

        return null;
    }

    protected function getViewClassName()
    {
        return \System25\T3sports\Frontend\View\StadiumList::class;
    }

    protected function getTemplateName()
    {
        return 'stadiumlist';
    }
}
