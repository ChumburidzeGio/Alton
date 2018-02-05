<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Models\Website;
use App\Resources\Travel\TravelRightsAbstractRequest;

class ShowWebsiteRights extends TravelRightsAbstractRequest
{
    public function executeFunction()
    {
        $website = $this->applyWebsitePermissionFilters(Website::query())->findOrFail($this->params[ResourceInterface::__ID]);

        $this->result = [ResourceInterface::__ID => $this->params[ResourceInterface::__ID], ResourceInterface::USER_ID => $website->user->id]
            + $this->getRights($website->user->id, $website->id, true);
    }
}