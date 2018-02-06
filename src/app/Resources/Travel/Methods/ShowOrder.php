<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class ShowOrder extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $resource = Resource::where('name', 'order.travel')->firstOrFail();

        $data = new \ArrayObject();
        RestListener::process($resource, new \ArrayObject($this->params), $data, RestListener::ACTION_SHOW, array_get($this->params, ResourceInterface::__ID), true);

        $this->result = $data->getArrayCopy();
    }
}