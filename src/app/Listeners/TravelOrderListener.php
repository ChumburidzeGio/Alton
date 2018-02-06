<?php

namespace App\Listeners\Resources2;

use Agent;
use App\Exception\InvalidResourceInput;
use App\Exception\PrettyServiceError;
use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Resources\Travel\TravelWrapperAbstractRequest;
use ArrayObject;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

class TravelOrderListener
{
    protected $externalUpdatableFields = [
        // Personal details
        ResourceInterface::FIRST_NAME,
        ResourceInterface::LAST_NAME,
        ResourceInterface::PHONE,
        ResourceInterface::EMAIL,
        ResourceInterface::ADDRESS,

        // Reservation details
        ResourceInterface::DESTINATION_DEPARTURE_DATE,
        ResourceInterface::DESTINATION_ARRIVAL_DATE,
        ResourceInterface::LICENSEPLATE,
        ResourceInterface::OPTIONS,
        ResourceInterface::RETURN_FLIGHT_NUMBER,
        ResourceInterface::NUMBER_OF_PERSONS,
        ResourceInterface::NUMBER_OF_CARS,
        ResourceInterface::EXTERNAL_ID,
        ResourceInterface::CUSTOMER_REMARKS,

        ResourceInterface::DESTINATION_GOOGLE_PLACE_ID,
        ResourceInterface::ORIGIN_GOOGLE_PLACE_ID,
    ];

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.process.before', [$this, 'updateOrderBefore']);
        $events->listen('resource.process.before', [$this, 'checkLicensePlateNumberChange']);
        $events->listen('resource.order.travel.process.input', [$this, 'implodeArrays']);
        $events->listen('resource.order.travel.process.input', [$this, 'calculateFieldDiff']);
        $events->listen('resource.order.travel.process.after', [$this, 'sendEmailWithChanges']);
    }

    public function sendEmailWithChanges(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id){
        if ( $action != 'update' || !$input->offsetExists(OptionsListener::OPTION_FIELD_DIFF)) {
            return;
        }
        //Get the field diff from the input that contains the changes to the order
        $fieldDiff = $input->offsetGet(OptionsListener::OPTION_FIELD_DIFF);

        if (count($fieldDiff)) {
            Event::fire('email.notify', ['travel', 'reservation.updated', $data['__id'], $data[ResourceInterface::WEBSITE]]);
        }
    }

    public function calculateFieldDiff(Resource $resource, ArrayObject $input, $action, $id){
        if ( $action != 'update') {
            return;
        }
        $reserved = ResourceHelper::getReservedParamNames();
        //Get the user input without reserved params
        $user_input = Arr::except($input->getArrayCopy(),$reserved);
        //Get the data from the order
        $order = $this->getOrderById($id);
        if (!$order)
            return;
        $relevantOrderData = Arr::only($order->toArray(), array_keys($user_input));
        $changes = [];
        foreach ($user_input as $inputName => $inputValue){
            if(isset($relevantOrderData[$inputName]) && $relevantOrderData[$inputName] != $inputValue){
                $changes[$inputName] = $inputValue;
            }
        }
        if(count($changes)){
            $input->offsetSet(OptionsListener::OPTION_FIELD_DIFF, $changes);
        }
    }

    public function implodeArrays(Resource $resource, ArrayObject $input, $action)
    {
        if($action != 'update'){
            return;
        }
        if($input->offsetExists(ResourceInterface::OPTIONS) && is_array($input->offsetGet(ResourceInterface::OPTIONS))){
            $input->offsetSet(ResourceInterface::OPTIONS, implode(',', $input->offsetGet(ResourceInterface::OPTIONS)));
        }
    }

    /**
     * Do any external API calls required when changing the order. If external API call fails, do not update the order.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     * @param $action
     * @param $id
     * @throws PrettyServiceError
     * @throws ResourceError
     */
    public function updateOrderBefore(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id){
        if ($resource->name !== 'order.travel' || $action != 'update') {
            return;
        }

        if($input->offsetExists(ResourceInterface::OPTIONS) && is_array($input->offsetGet(ResourceInterface::OPTIONS))){
            $input->offsetSet(ResourceInterface::OPTIONS, implode(',', $input->offsetGet(ResourceInterface::OPTIONS)));
        }
        if (isset($input[ResourceInterface::LICENSEPLATE]))
            $input[ResourceInterface::LICENSEPLATE] = (array)array_get($input->getArrayCopy(), ResourceInterface::LICENSEPLATE);

        $order = DocumentHelper::show('order', 'travel', $id);

        if (isset($input[ResourceInterface::NUMBER_OF_CARS]) && $input[ResourceInterface::NUMBER_OF_CARS] != $order[ResourceInterface::NUMBER_OF_CARS])
            throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Cannot change the number of cars in an order update, please rebook instead.');

        if (isset($input[ResourceInterface::PRODUCT_ID]) && $input[ResourceInterface::PRODUCT_ID] != $order[ResourceInterface::PRODUCT_ID])
            throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Cannot change the product in an order update, please rebook instead.');

        // Create update order data
        $updateOrderFields = array_only($input->getArrayCopy(), $this->externalUpdatableFields);

        // Nothing to update externally? Just continue.
        if (count($updateOrderFields) == 0)
            return;

        // No changes on the actual data? Just continue.
        $changes = false;
        foreach (array_only($order->toArray(), array_keys($updateOrderFields)) as $k => $v) {
            if (isset($updateOrderFields[$k]) && $updateOrderFields[$k] != $v) {
                $changes = true;
                break;
            }
        }
        if (!$changes)
            return;

        // Test orders don't do external calls
        if ($order[ResourceInterface::IS_TEST_ORDER]) {
            return;
        }

        if ($order[ResourceInterface::USE_DIRECT_RESERVATION]) {
            // No remote orders? Nothing to update.
            if (empty($order[ResourceInterface::USE_DIRECT_RESERVATION]))
                return;

            $remoteOrders = TravelWrapperAbstractRequest::getRemoteOrdersParams(array_merge($order->toArray(), $updateOrderFields));
            $resourceType = TravelWrapperAbstractRequest::getRemoteResourceType($order[ResourceInterface::PRODUCT_ID]);
        }
        else {
            $remoteOrders = TravelWrapperAbstractRequest::getParkingCiOrdersParams(array_merge($order->toArray(), $updateOrderFields));
            $resourceType = 'parkingci';
        }

        if (count($remoteOrders) != $order[ResourceInterface::NUMBER_OF_CARS]) {
            throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Cannot change number of cars on order update.');
        }

        if (isset($remoteOrders['error_messages'])) {
            throw new ResourceError($resource, $input->getArrayCopy(), $remoteOrders['error_messages']);
        }
        if (isset($remoteOrders['error'])) {
            throw new PrettyServiceError($resource, $input->getArrayCopy(), $remoteOrders['error']);
        }

        // Fire off all update requests
        $orderResults = [];
        foreach ($remoteOrders as $remoteOrder) {
            try {
                $orderResults[] = ResourceHelper::callResource2('update_reservation.'. $resourceType, $remoteOrder);
            } catch (InvalidResourceInput $e) {
                $orderResults[] = ['errors' => $e->getMessages()];
            } catch (ResourceError $e) {
                $orderResults[] = ['error_messages' => $e->getMessages()];
            } catch (\Exception $e) {
                $orderResults[] = ['error' => $e->getMessage()];
            }
        }

        // Set any field-message errors
        $errorMessages = [];
        foreach ($orderResults as $orderResult) {
            if (isset($orderResult['error_messages'])) {
                foreach ($orderResult['error_messages'] as $message) {
                    // Map some fields back from ParkingCI
                    if ($message['field'] == ResourceInterface::DEPARTURE_DATE)
                        $message['field'] = ($resourceType == 'parkingci' ? ResourceInterface::DESTINATION_ARRIVAL_DATE : ResourceInterface::DESTINATION_DEPARTURE_DATE);
                    if ($message['field'] == ResourceInterface::ARRIVAL_DATE)
                        $message['field'] = ($resourceType == 'parkingci' ? ResourceInterface::DESTINATION_DEPARTURE_DATE : ResourceInterface::DESTINATION_ARRIVAL_DATE);
                    $errorMessages[] = $message;
                }
            }
            if (isset($orderResult['errors'])) {
                foreach ($orderResult['errors'] as $field => $messages) {
                    // Map some fields back from ParkingCI
                    foreach ($messages as $message) {
                        $errorMessage = ['message' => $message, 'field' => $field, 'code' => $message];
                        if ($field == ResourceInterface::DEPARTURE_DATE)
                            $errorMessage['field'] = ($resourceType == 'parkingci' ? ResourceInterface::DESTINATION_ARRIVAL_DATE : ResourceInterface::DESTINATION_DEPARTURE_DATE);
                        if ($field == ResourceInterface::ARRIVAL_DATE)
                            $errorMessage['field'] = ($resourceType == 'parkingci' ? ResourceInterface::DESTINATION_DEPARTURE_DATE : ResourceInterface::DESTINATION_ARRIVAL_DATE);
                        $errorMessages[] = $errorMessage;
                    }
                }
            }
            if (isset($orderResult['error'])) {
                throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Could not update external reservation: ' . $orderResult['error']);
            }
        }
        if ($errorMessages)
            throw new ResourceError($resource, $input->getArrayCopy(), $errorMessages);

        // Fetch all reservations, to get (new) total price
        if ($order[ResourceInterface::USE_DIRECT_RESERVATION]) {
            $input[ResourceInterface::PRICE_BASE] = null;
            $input[ResourceInterface::PRICE_OPTIONS] = null;

            foreach ($remoteOrders as $remoteOrder) {
                $newRemoteOrder = ResourceHelper::callResource2('get_reservation.' . $resourceType, [ResourceInterface::ORDER_ID => $remoteOrder[ResourceInterface::ORDER_ID]]);

                $input[ResourceInterface::PRICE_BASE] += $newRemoteOrder[ResourceInterface::PRICE_ACTUAL];
                if ((float)$newRemoteOrder[ResourceInterface::PRICE_OPTIONS])
                    $input[ResourceInterface::PRICE_OPTIONS] += $newRemoteOrder[ResourceInterface::PRICE_OPTIONS];
            }

            if (round($input[ResourceInterface::PRICE_BASE] + $input[ResourceInterface::PRICE_OPTIONS], 2) != round($order[ResourceInterface::PRICE_BASE] + $order[ResourceInterface::PRICE_OPTIONS], 2)) {
                // We have a changed price - update 'amount' & 'price', and do not forget the original transaction costs
                $input[ResourceInterface::PRICE] = $input[ResourceInterface::PRICE_BASE] + $input[ResourceInterface::PRICE_OPTIONS] + $order[ResourceInterface::ADMINISTRATION_FEE];
                $input[ResourceInterface::AMOUNT] = $input[ResourceInterface::PRICE] + $order[ResourceInterface::TRANSACTION_COSTS];
            }
        }
        else {
            $input[ResourceInterface::PRICE] = 0;
            $input[ResourceInterface::PRICE_BASE] = null;
            $input[ResourceInterface::PRICE_ADMINISTRATION_FEE] = null;
            $input[ResourceInterface::PRICE_OPTIONS] = null;

            foreach ($remoteOrders as $remoteOrder) {
                $newRemoteOrder = ResourceHelper::callResource2('get_reservation.' . $resourceType, [ResourceInterface::ORDER_ID => $remoteOrder[ResourceInterface::ORDER_ID]]);

                $input[ResourceInterface::PRICE] += $newRemoteOrder[ResourceInterface::PRICE_ACTUAL];
                $input[ResourceInterface::PRICE_BASE] += $newRemoteOrder[ResourceInterface::PRICE_BASE];
                $input[ResourceInterface::PRICE_ADMINISTRATION_FEE] += $newRemoteOrder[ResourceInterface::PRICE_ADMINISTRATION_FEE];
                if ((float)$newRemoteOrder[ResourceInterface::PRICE_OPTIONS])
                    $input[ResourceInterface::PRICE_OPTIONS] += $newRemoteOrder[ResourceInterface::PRICE_OPTIONS];
            }

            if (round($order[ResourceInterface::PRICE], 4) != round($input[ResourceInterface::PRICE], 4)) {
                // We have a changed price - update 'amount', and do not forget the original transaction costs
                $input[ResourceInterface::AMOUNT] = $input[ResourceInterface::PRICE] + $order[ResourceInterface::TRANSACTION_COSTS];
            }
        }
        $input[ResourceInterface::RESERVATION_UPDATE_RESULT] = json_encode($remoteOrders);

        // Success! We continue, and the actual update handler will set the data on the order
    }

    public function checkLicensePlateNumberChange(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id){
        if ($resource->name !== 'order.travel' || $action != 'update') {
            return;
        }

        if (!isset($input[ResourceInterface::LICENSEPLATE])){
            return;
        }
        //Get license plates from input
        $licenseplates = $input[ResourceInterface::LICENSEPLATE];
        if(!is_array($licenseplates)){
            $licenseplates = explode(',', $input[ResourceInterface::LICENSEPLATE]);
        }

        //Get license plates from db
        $order = $this->getOrderById($id);
        $existingCount = 0;
        if(isset($order[ResourceInterface::LICENSEPLATE])){
            $existingCount = count($order[ResourceInterface::LICENSEPLATE]);
        }

        if(count($licenseplates) !== $existingCount){
            throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Number of license plates cannot change.');
        }
        //Count is ok allow processing to go forward
    }

    protected function getOrderById($orderId)
    {
        try {
            return DocumentHelper::show('order', 'travel', $orderId);
        } catch (\Exception $e) {
            return null;
        }

    }
}