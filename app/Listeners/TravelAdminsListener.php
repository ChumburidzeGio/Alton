<?php

namespace App\Listeners\Resources2;

use App\Models\Application;
use ArrayObject;
use App\Models\Resource;
use Illuminate\Events\Dispatcher;

class TravelAdminsListener extends TravelUsersListener
{
    protected $_fixedRole = 'travel-admin';

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.admins.travel.process.input', [$this, 'addTravelFilter']);
        $events->listen('resource.admins.travel.process.input', [$this, 'processPassword']);
        $events->listen('resource.admins.travel.process.after', [$this, 'setTravelRelatedData']);
    }
}