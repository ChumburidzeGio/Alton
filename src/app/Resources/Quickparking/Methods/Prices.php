<?php
namespace App\Resources\Quickparking\Methods;

use App\Resources\AbstractMethodRequest;

class Prices extends AbstractMethodRequest
{
    public function __construct()
    {
        $this->setErrorString('`Prices` functionality not available for this parking API service. Please use the `Price` service.');
    }
}