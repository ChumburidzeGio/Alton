<?php

namespace App\Resources\Healthcare\Methods;


use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Resources\Healthcare\HealthcareAbstractRequest;

class StoreProduct extends HealthcareAbstractRequest
{
    public function executeFunction()
    {
        $resource = Resource::where('name', 'product.healthcare2018')->firstOrFail();

        $data = new \ArrayObject();
        RestListener::process($resource, new \ArrayObject($this->params), $data, RestListener::ACTION_STORE, array_get($this->params, ResourceInterface::__ID));

        $this->result = $data->getArrayCopy();
    }
}