<?php
namespace App\Resources\Parkandfly\Methods;

use App\Resources\AbstractMethodRequest;

class Options extends AbstractMethodRequest
{
    public function executeFunction()
    {
        // No actual request needs to be made
    }

    public function getResult()
    {
        // Park and Fly does not offer options through this API.
        return [];
    }
}