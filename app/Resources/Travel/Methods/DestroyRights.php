<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Models\User;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class DestroyRights extends TravelWrapperAbstractRequest
{
    public function setParams(Array $params)
    {
        $this->params[ResourceInterface::WEBSITE_ID] = $params[ResourceInterface::WEBSITE_ID];
        $this->params[ResourceInterface::USER_ID] = $params[ResourceInterface::USER_ID];
    }

    public function executeFunction()
    {
        //Check if the user can perform the action he is requesting
        $user = User::find($this->params[ResourceInterface::USER_ID]);
        $website = $user->websites()->find($this->params[ResourceInterface::WEBSITE_ID]);
        $this->result = [];

        if($website){
            //TODO: implement update here
        }

    }
}