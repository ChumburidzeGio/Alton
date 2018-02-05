<?php

namespace App\Listeners\Resources2;

use App\Models\Application;
use ArrayObject;
use App\Models\Resource;
use Illuminate\Events\Dispatcher;

class TravelResellersListener extends TravelUsersListener
{
    protected $_fixedRole = 'publisher';

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.resellers.travel.process.input', [$this, 'addTravelFilter']);
        $events->listen('resource.resellers.travel.process.input', [$this, 'processPassword']);
        $events->listen('resource.resellers.travel.process.after', [$this, 'setTravelRelatedData']);
        $events->listen('resource.resellers.travel.process.after', [$this, 'addUserTokenToResult']);
        $events->listen('resource.resellers.travel.process.after', [$this, 'fixZeroManagingUser']);
    }
}