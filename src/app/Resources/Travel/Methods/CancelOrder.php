<?php

namespace App\Resources\Travel\Methods;


use App\Exception\PrettyServiceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Travel\Travel;
use App\Resources\Travel\TravelWrapperAbstractRequest;
use Illuminate\Support\Facades\Event;

class CancelOrder extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $order = $this->getOrderByOrderId($this->params[ResourceInterface::ORDER_ID]);

        if ($this->hasErrors()) {
            return;
        }

        if (isset($this->params[ResourceInterface::USER]) && $this->params[ResourceInterface::USER] != $order->user) {
            $this->setPrettyErrorString('Cannot cancel order, order unknown or does not belong to user.');
            return;
        }

        if ($order[ResourceInterface::USE_DIRECT_RESERVATION]) {
            $this->cancelRemoteOrder($order);
        }
        else {
            $this->cancelParkingCiOrder($order);
        }

        if (!$this->hasErrors()) {
            DocumentHelper::update('order', 'travel', $order->__id, [
                ResourceInterface::RESERVATION_STATUS => Travel::RESERVATION_STATUS_CANCELED,
            ]);
            Event::fire('email.notify', ['travel', 'order.cancel.success', $order['__id'], $order[ResourceInterface::WEBSITE]]);
        }

        $order = $this->getOrderById($order->__id);

        $this->result = array_only($order->toArray(), [ResourceInterface::__ID, ResourceInterface::ORDER_ID, ResourceInterface::RESERVATION_STATUS]);
    }

    protected function cancelRemoteOrder($order)
    {
        if (!$order[ResourceInterface::IS_TEST_ORDER]) {
            $remoteOrdersIds = self::getRemoteOrderIdsFromOrder($order);
            $remoteResource = 'cancel_reservation.' . self::getRemoteResourceType($order[ResourceInterface::PRODUCT_ID]);

            foreach ($remoteOrdersIds as $remoteOrdersId) {
                try {
                    $result = ResourceHelper::callResource2($remoteResource, [
                        ResourceInterface::ORDER_ID => $remoteOrdersId,
                        ResourceInterface::EMAIL => 'noreply+'. $order[ResourceInterface::ORDER_ID] .'@parcompare.com', // The email is required when cancelling SchipholParking orders
                    ]);

                    if ($this->resultHasError($result)) {
                        $this->addErrorData($result);
                    }
                } catch (PrettyServiceError $e) {
                    $this->setPrettyErrorString($e->getMessage());
                } catch (\Exception $e) {
                    $this->setErrorString($e->getMessage());
                }
            }
        }
    }

    /**
     * Note: this method can be removed once the Parking CodeIgniter is removed when USE_DIRECT_RESERVATION is on for every environment.
     */
    protected function cancelParkingCiOrder($order)
    {
        $parkingCiOrderIds = self::getParkingCiOrderIdsFromOrder($order);

        foreach ($parkingCiOrderIds as $parkingCiOrderId) {
            try {
                $result = ResourceHelper::callResource2('cancel_reservation.parkingci', [ResourceInterface::ORDER_ID => $parkingCiOrderId]);

                if ($this->resultHasError($result)) {
                    $this->addErrorData($result);
                }
            }
            catch (PrettyServiceError $e) {
                $this->setPrettyErrorString($e->getMessage());
            }
            catch (\Exception $e) {
                $this->setErrorString($e->getMessage());
            }
        }
    }
}