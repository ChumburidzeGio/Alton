<?php

namespace App\Resources\Google\Api\Methods;


use App\Resources\Google\Api\GoogleApiAbstractRequest;

class SheetWithOptions extends GoogleApiAbstractRequest
{

    public function getResult()
    {
        return $this->getResultWithOptions();
    }
}

