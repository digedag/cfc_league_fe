<?php

declare(strict_types=1);

return [
    'tx_cfcleaguefe_actions_ClubList' => \System25\T3sports\Frontend\Action\ClubList::class,
    'tx_cfcleaguefe_actions_ClubView' => \System25\T3sports\Frontend\Action\ClubDetails::class,
    'tx_cfcleaguefe_actions_StadiumList' => \System25\T3sports\Frontend\Action\StadiumList::class,
    'tx_cfcleaguefe_actions_ProfileView' => \System25\T3sports\Frontend\Action\ProfileView::class,
    'tx_cfcleaguefe_util_ProfileMarker' => \System25\T3sports\Frontend\Marker\ProfileMarker::class,
    'tx_cfcleaguefe_util_MatchMarker' => \System25\T3sports\Frontend\Marker\MatchMarker::class,
    'tx_cfcleaguefe_util_ClubMarker' => \System25\T3sports\Frontend\Marker\ClubMarker::class,
    'tx_cfcleaguefe_util_StadiumMarker' => \System25\T3sports\Frontend\Marker\StadiumMarker::class,
    'tx_cfcleaguefe_util_AddressMarker' => \System25\T3sports\Frontend\Marker\AddressMarker::class,
    'tx_cfcleaguefe_util_Maps' => \System25\T3sports\Utility\MapsUtil::class,
];
