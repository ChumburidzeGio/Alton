<?php

namespace App\Resources\Parking2\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\Parking2WrapperAbstractRequest;

class Options extends Parking2WrapperAbstractRequest
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