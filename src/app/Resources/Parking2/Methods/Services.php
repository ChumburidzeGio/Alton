<?php

namespace App\Resources\Parking2\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\Parking2WrapperAbstractRequest;

class Services extends Parking2WrapperAbstractRequest
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