<?php


namespace App\Resources\Travel\Methods;

use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Listeners\Resources2\EloquentRestListener;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Resources\Travel\Travel;
use App\Resources\Travel\TravelWrapperAbstractRequest;

/**
 * @property array product
 */
class SetManagingUser extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $managing_user = $this->params[ResourceInterface::MANAGING_USER];

        $resource = Resource::where('name', 'resellers.travel')->firstOrFail();

        $data = new \ArrayObject();
        EloquentRestListener::process($resource, new \ArrayObject([ResourceInterface::MANAGING_USER => $managing_user]), $data, RestListener::ACTION_UPDATE, array_get($this->params, ResourceInterface::__ID));

        $this->result = ['success' => true];
    }
}