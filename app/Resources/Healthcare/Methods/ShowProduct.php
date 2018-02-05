<?php

namespace App\Resources\Healthcare\Methods;


use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Resources\Healthcare\HealthcareAbstractRequest;

class ShowProduct extends IndexProduct
{
    public function executeFunction()
    {
        parent::executeFunction();

        if (count($this->result) == 0)
            $this->setErrorString('Could not find product `'. array_get($this->params, ResourceInterface::__ID) .'`.');

        $this->result = (array)head($this->result);
    }
}