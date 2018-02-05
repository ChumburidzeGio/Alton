<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Models\Website;
use App\Resources\Travel\TravelRightsAbstractRequest;

class StoreWebsiteRights extends TravelRightsAbstractRequest
{
    public function executeFunction()
    {
        $website = $this->applyWebsitePermissionFilters(Website::query())->findOrFail($this->params[ResourceInterface::__ID]);

        $this->updateRights($this->params, $website->user->id, $website->id);

        $this->result = [ResourceInterface::__ID => $this->params[ResourceInterface::__ID], ResourceInterface::USER_ID => $website->user->id]
            + $this->getRights($website->user->id, $website->id, true);
    }
}