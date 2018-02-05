<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class GetParkingciServices extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $this->result = $this->getParkingCiServices();
    }

    protected function getParkingCiServices()
    {
        $documents = [];
        foreach ($this->internalRequest('parkingci', 'services') as $item)
        {
            $item[ResourceInterface::__ID] = $item[ResourceInterface::ID];
            unset($item[ResourceInterface::ID]);
            $documents[] = $item;
        }

        return $documents;
    }
}