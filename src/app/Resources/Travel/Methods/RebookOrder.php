<?php


namespace App\Resources\Travel\Methods;

use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Listeners\Resources2\RestListener;
use App\Resources\Travel\Travel;
use App\Resources\Travel\TravelWrapperAbstractRequest;

/**
 * @property array product
 */
class RebookOrder extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $order = $this->getOrderById($this->params[ResourceInterface::__ID]);

        if (!$order)
            return;

        if (isset($this->params[ResourceInterface::OPTIONS]) && is_array($this->params[ResourceInterface::OPTIONS])) {
            $this->params[ResourceInterface::OPTIONS] = implode(',', $this->params[ResourceInterface::OPTIONS]);
        }
        if (isset($this->params[ResourceInterface::LICENSEPLATE]))
            $this->params[ResourceInterface::LICENSEPLATE] = implode(',', (array)array_get($this->params, ResourceInterface::LICENSEPLATE));

        if (isset($this->params[ResourceInterface::USER]) && $this->params[ResourceInterface::USER] != $order->user) {
            $this->setPrettyErrorString('Cannot rebook order, order unknown or does not belong to user.');
            return;
        }

        $newOrder = array_merge($order->toArray(), $this->params);

        try {
            $productData = self::getProductForOrder($newOrder);
        }
        catch (\Exception $e) {
            if ($this->debug())
                $this->setErrorString('Unexpected failure during product price calculation: `'. $e->getMessage() .'`');
            else
                $this->setPrettyErrorString('Unexpected failure during product price calculation.');
            return;
        }

        if ($productData === false) {
            if (!empty($newOrder[ResourceInterface::OPTIONS]))
            {
                try {
                    $newOrderNoOptions = $newOrder;
                    $newOrderNoOptions[ResourceInterface::OPTIONS] = '';
                    if ($productData !== false) {
                        $this->setPrettyErrorString('This product is not available with the options specified in the order.');
                        return;
                    }
                }
                catch (\Exception $e) {
                }
            }
            $this->setPrettyErrorString('This product is not available for given dates and times.');

            return;
        }
        if (!$productData[ResourceInterface::ENABLED]) {
            $this->setPrettyErrorString('This product is not enabled for this reseller.');
            return;
        }

        // Set price
        $newOrder[ResourceInterface::PRICE] = $productData[ResourceInterface::PRICE_ACTUAL];
        $newOrder[ResourceInterface::PRODUCT] = $productData;
        if (empty($newOrder[ResourceInterface::DESTINATION_DEPARTURE_TIME]))
            $newOrder[ResourceInterface::DESTINATION_DEPARTURE_TIME] = substr($newOrder[ResourceInterface::DESTINATION_DEPARTURE_DATE], -8);
        if (empty($newOrder[ResourceInterface::DESTINATION_ARRIVAL_TIME]))
            $newOrder[ResourceInterface::DESTINATION_ARRIVAL_TIME] = substr($newOrder[ResourceInterface::DESTINATION_ARRIVAL_DATE], -8);
        $newOrder[ResourceInterface::AGREE_POLICY_CONDITIONS] = 1;

        try {
            $newOrderResult = ResourceHelper::callResource2('contract.travel', $newOrder, RestListener::ACTION_STORE);
            $this->result = $newOrderResult;

            DocumentHelper::update('order', 'travel', $newOrderResult[ResourceInterface::ID], [
                ResourceInterface::PAYMENT_STATUS => ResourceValue::PAYMENT_DEFERRED, // Set payment state to deferred... because we assume it will be paid for somehow?
                ResourceInterface::REBOOK_ORDER_ID => $order->__id,
            ]);
            // Fire off payment update, so we make the actual reservation
            $update = ResourceHelper::callResource2('updatepaymentstatus.travel', [ResourceInterface::ORDER_ID => $newOrderResult[ResourceInterface::ORDER_ID]]);

            $this->result[ResourceInterface::SKIP_PAYMENT] = true;
            $this->result[ResourceInterface::PAYMENT_STATUS] = $update[ResourceInterface::PAYMENT_STATUS];
            $this->result[ResourceInterface::RESERVATION_STATUS] = $update[ResourceInterface::RESERVATION_STATUS];
            $this->result[ResourceInterface::RESERVATION_CODE] = $update[ResourceInterface::RESERVATION_CODE];

            try {
                if ($order->reservation_status != Travel::RESERVATION_STATUS_CANCELED)
                    ResourceHelper::callResource2('cancel_order.travel', [ResourceInterface::ORDER_ID => $order->order_id]);
            }
            catch (\Exception $e) {
                if ($this->debug())
                    $this->setErrorString('Could not rebook order, cancellation of order `'. $order->order_id .'` failed: `' . $e->getMessage() . '`.');
                else
                    $this->setPrettyErrorString('Could not rebook order, cancellation of order `'. $order->order_id .'` failed.');
                ResourceHelper::callResource2('cancel_order.travel', [ResourceInterface::ORDER_ID => $newOrderResult[ResourceInterface::ORDER_ID]]);
            }

            // Move paid amount to new order & mark as 'rebooked to'
            DocumentHelper::update('order', 'travel', $order->__id, [
                ResourceInterface::REBOOK_TO_ORDER_ID => $newOrderResult[ResourceInterface::ID],
                ResourceInterface::PAYMENT_AMOUNT_PAID => 0,
            ]);
            DocumentHelper::update('order', 'travel', $newOrderResult[ResourceInterface::ID], [
                ResourceInterface::PAYMENT_AMOUNT_PAID => $order[ResourceInterface::PAYMENT_AMOUNT_PAID],
            ]);
        }
        catch (ResourceError $e) {
            $this->setErrorString('Could not rebook order, failed creating new order: `' . json_encode($e->getMessages()) . '`.');
        }
        catch (\Exception $e) {
            $this->setErrorString('Could not rebook order, failed creating new order: `' . $e->getMessage() . '`.');
        }
    }
}