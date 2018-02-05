<?php

namespace App\Listeners\Resources2;

use Illuminate\Events\Dispatcher;

class TravelProvidersListener extends TravelUsersListener
{
    protected $_fixedRole = 'travel-provider';

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.providers.travel.process.input', [$this, 'addTravelFilter']);
        $events->listen('resource.providers.travel.process.input', [$this, 'processPassword']);
        $events->listen('resource.providers.travel.process.after', [$this, 'setTravelRelatedData']);
        $events->listen('resource.resellers.travel.process.after', [$this, 'fixZeroManagingUser']);
    }
}