<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Models\User;
use App\Resources\Travel\TravelRightsAbstractRequest;

class UpdateUserRights extends TravelRightsAbstractRequest
{
    public function executeFunction()
    {
        $user = $this->applyUserPermissionFilters(User::query())->findOrFail($this->params[ResourceInterface::__ID]);

        $this->updateRights($this->params, $user->id);

        $this->result = [ResourceInterface::__ID => $this->params[ResourceInterface::__ID]]
            + $this->getRights($user->id);
    }
}