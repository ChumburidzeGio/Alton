<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class GetParkingciOptions extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $this->result = $this->getParkingCiOptions();
    }

    protected function getParkingCiOptions()
    {
        $documents = [];
        foreach ($this->internalRequest('parkingci', 'options') as $item)
        {
            $item[ResourceInterface::__ID] = $item[ResourceInterface::ID];
            unset($item[ResourceInterface::ID]);
            $documents[] = $item;
        }

        return $documents;
    }
}