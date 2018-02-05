<?php

namespace App\Resources\VVGHealthcarech\Methods;


use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Resources\Healthcare\HealthcareAbstractRequest;

class ShowProduct extends HealthcareAbstractRequest
{
    public function executeFunction()
    {
        dd('implement this');
//        $resource = Resource::where('name', 'product.healthcare')->firstOrFail();
//
//        $data = new \ArrayObject();
//        RestListener::process($resource, new \ArrayObject($this->params), $data, RestListener::ACTION_SHOW, array_get($this->params, ResourceInterface::__ID));
//
//        $this->result = $data->getArrayCopy();
    }
}