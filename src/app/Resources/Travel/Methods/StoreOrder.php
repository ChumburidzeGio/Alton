<?php

namespace App\Resources\Travel\Methods;


use App\Exception\PrettyServiceError;
use App\Exception\ResourceError;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Resources\Travel\TravelWrapperAbstractRequest;
use Illuminate\Support\Facades\Request;

class StoreOrder extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $resource = Resource::where('name', 'order.travel')->firstOrFail();

        //Call the contract with the params
        try {
            $this->params[ResourceInterface::IP] = Request::getClientIp();
            $this->params[ResourceInterface::AGREE_POLICY_CONDITIONS] = true;

            //Fire the contract
            $contractResult = ResourceHelper::callResource2('contract.travel', $this->params, RestListener::ACTION_STORE);
            //Fetch the order
            $orderResult = ResourceHelper::callResource2('order.travel', ['order_id' => $contractResult['order_id']], RestListener::ACTION_INDEX);
            //Set it as the result
            $this->result = $orderResult[0];
        } catch(ResourceError $ex) {

            if (array_get($ex->getMessages(), 'errors.0.field') == ResourceInterface::AGREE_POLICY_CONDITIONS) {
                throw new PrettyServiceError($resource, $this->params, 'Order could not be created: '. array_get($ex->getMessages(), 'errors.0.message'));
            }

            throw $ex;
        } catch(\Exception $ex) {
            throw new PrettyServiceError($resource, $this->params, 'Order could not be created, server error.' . $ex->getMessage());
        }
    }
}