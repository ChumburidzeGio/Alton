<?php
namespace App\Resources\Travel;

use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Illuminate\Support\Facades\DB;

class TravelWrapperAbstractRequest extends AbstractMethodRequest
{
    protected $cacheDays = false;
    public $resource2Request = true;

    protected $params = [];
    protected $result;

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        return $this->result;
    }

    protected function getOrderById($orderId)
    {
        try {
            return DocumentHelper::show('order', 'travel', $orderId);
        } catch (\Exception $e) {
            $this->setErrorString('Cannot find travel order `'. $orderId .'`.');
        }
        return null;
    }

    protected function getOrderByOrderId($orderId)
    {
        try {
            $orders = DocumentHelper::get('order', 'travel', ['filters' => [ResourceInterface::ORDER_ID => $orderId]]);

            if (count($orders['documents']) == 0)
                $this->setErrorString('Cannot find travel with field "order_id" `'. $orderId .'` (note: not __id).');

            return head($orders['documents']);
        } catch (\Exception $e) {
            $this->setErrorString('Cannot find travel with field "order_id" = `'. $orderId .'` (note: not __id): '. $e->getMessage());
        }
        return null;
    }

    protected function getOrderByTransactionId($transactionId)
    {
        try {
            $orders = DocumentHelper::get('order', 'travel', ['filters' => [ResourceInterface::PAYMENT_TRANSACTION_ID => $transactionId]]);

            if (count($orders['documents']) == 0)
                $this->setErrorString('Cannot find travel with field "payment_transaction_id" `'. $transactionId .'`.');

            return head($orders['documents']);
        } catch (\Exception $e) {
            $this->setErrorString('Cannot find travel with field "payment_transaction_id" `'. $transactionId .'`: '. $e->getMessage());
        }
        return null;
    }

    protected function getRightSetting($name, $user, $website)
    {
        $right = null;
        if (!$right && !empty($website))
            $right = DB::table('rights')->where('user_id', $user)->where('website_id', $website)->where('active', 1)->where('key', $name)->first();
        if (!$right && !empty($user)) {
            $right = DB::table('rights')->where('user_id', $user)->where('website_id', 0)->where('active', 1)->where('key', $name)->first();
        }
        if (!$right)
            return null;

        return $right->value;
    }

    public static function getRemoteOrderIdsFromOrder($order)
    {
        if ($order[ResourceInterface::REMOTE_ORDER_ID] === '') {
            return [];
        }

        return explode(',', $order[ResourceInterface::REMOTE_ORDER_ID]);
    }

    public static function getRemoteResourceType($productId)
    {
        $product = DocumentHelper::show('product', 'travel', $productId);

        switch ($product[ResourceInterface::SOURCE]) {
            case 'parcompare':
                return 'parkingci';
            default:
                return $product[ResourceInterface::SOURCE];
        }
    }

    // Todo: Move this 'conversion' method (Travel order -> Parking CI order IDs) to a separate class
    public static function getParkingCiOrderIdsFromOrder($order)
    {
        // Find the order IDs (may be more than one)
        $reservationResult = json_decode($order[ResourceInterface::RESERVATION_RESULT], true);

        if (isset($reservationResult[ResourceInterface::ORDER_ID]))
            return [$reservationResult[ResourceInterface::ORDER_ID]];

        $orderIds = [];
        if (is_array($reservationResult))
        {
            foreach ($reservationResult as $reservationOrder)
                if (array_get($reservationOrder, ResourceInterface::ORDER_ID) !== null)
                    $orderIds[] = array_get($reservationOrder, ResourceInterface::ORDER_ID);
        }
        $orderIds = array_unique($orderIds); // Just to be sure

        return $orderIds;
    }

    // Todo: Move this 'conversion' method (Travel order -> Remote orders) to a separate class
    public static function getRemoteOrdersParams(array $order)
    {
        if (self::getRemoteResourceType($order[ResourceInterface::PRODUCT_ID]) == 'parkingci') {
            $remoteOrders = self::getParkingCiOrdersParams($order);

            // For updates, set order id
            if (!empty($order[ResourceInterface::REMOTE_ORDER_ID])) {
                $remoteOrderIds = self::getRemoteOrderIdsFromOrder($order);
                foreach ($remoteOrders as $nr => $remoteOrder)
                    $remoteOrders[$nr][ResourceInterface::ORDER_ID] = array_get($remoteOrderIds, $nr);
            }

            return $remoteOrders;
        }

        $order = self::convertOrderUTCtoCest($order);

        // Almost all contract fields are like ParkingCI create_reservation fields, copy & correct some.
        $remoteOrder = [
            ResourceInterface::FIRST_NAME => 'Parcompare',
            ResourceInterface::LAST_NAME => $order[ResourceInterface::FIRST_NAME] .' '. $order[ResourceInterface::LAST_NAME],
            ResourceInterface::POSTAL_CODE => $order[ResourceInterface::POSTAL_CODE],
            ResourceInterface::HOUSE_NUMBER => $order[ResourceInterface::HOUSE_NUMBER],
            ResourceInterface::PHONE => $order[ResourceInterface::PHONE],
            ResourceInterface::EMAIL => 'noreply+'. $order[ResourceInterface::ORDER_ID] .'@parcompare.com',
            ResourceInterface::ARRIVAL_DATE => $order[ResourceInterface::DESTINATION_ARRIVAL_DATE],
            ResourceInterface::DEPARTURE_DATE => $order[ResourceInterface::DESTINATION_DEPARTURE_DATE],
            ResourceInterface::LICENSEPLATE => implode(',', (array)$order[ResourceInterface::LICENSEPLATE]),
            ResourceInterface::NUMBER_OF_PERSONS => $order[ResourceInterface::NUMBER_OF_PERSONS],
            ResourceInterface::RETURN_FLIGHT_NUMBER => $order[ResourceInterface::RETURN_FLIGHT_NUMBER],
            ResourceInterface::CUSTOMER_REMARKS => $order[ResourceInterface::CUSTOMER_REMARKS],
            ResourceInterface::INTERNAL_REMARKS => 'Parcompare booking',
            ResourceInterface::EXTERNAL_ID => $order[ResourceInterface::ORDER_ID],
            ResourceInterface::PAYMENT_AMOUNT => $order[ResourceInterface::PRICE_BASE] + $order[ResourceInterface::PRICE_OPTIONS],
            ResourceInterface::ORIGIN_GOOGLE_PLACE_ID => $order[ResourceInterface::ORIGIN_GOOGLE_PLACE_ID],
            ResourceInterface::DESTINATION_GOOGLE_PLACE_ID => $order[ResourceInterface::DESTINATION_GOOGLE_PLACE_ID],
            ResourceInterface::RESERVATION_KEY => $order[ResourceInterface::RESERVATION_KEY],

            ResourceInterface::OPTIONS => $order[ResourceInterface::OPTIONS],
        ];

        // We have stored product data to use
        if (!isset($order[ResourceInterface::PRODUCT]))
            throw new \Exception('Missing `product` data in order #'. $order['__id']);

        $product = json_decode($order[ResourceInterface::PRODUCT], true);
        if (empty($product[ResourceInterface::PARKING_ID]))
            throw new \Exception('Missing `parking_id` data in order #'. $order['__id']);
        $remoteOrder[ResourceInterface::LOCATION_ID] = $product[ResourceInterface::RESOURCE][ResourceInterface::ID];

        if (!empty($product[ResourceInterface::RESERVATION_KEY]))
            $ciOrder[ResourceInterface::RESERVATION_KEY] = $product[ResourceInterface::RESERVATION_KEY];

        // For updates, set order id
        if (!empty($order[ResourceInterface::REMOTE_ORDER_ID]))
            $remoteOrder[ResourceInterface::ORDER_ID] = $order[ResourceInterface::REMOTE_ORDER_ID];

        // Map external option IDs to local
        if (!empty($order[ResourceInterface::OPTIONS])) {
            // Map requested available options to remote option ids
            $remoteOrder[ResourceInterface::OPTIONS] = implode(',', self::mapOptionIdsToRemoteOptionIds($order, $product));
        }

        // Parcompare (old ParkingCI interface) has flipped arrival & departure naming from legacy times :(
        if (!empty($product[ResourceInterface::SOURCE]) && $product[ResourceInterface::SOURCE] == 'parcompare') {
            $arrivalDate = $order[ResourceInterface::ARRIVAL_DATE];
            $order[ResourceInterface::ARRIVAL_DATE] = $order[ResourceInterface::DEPARTURE_DATE];
            $order[ResourceInterface::DEPARTURE_DATE] = $arrivalDate;
        }

        // Multiple licenseplates means multiple cars, and multiple remote orders.
        $remoteOrders = [];
        $licensePlates = is_array($order[ResourceInterface::LICENSEPLATE]) ? $order[ResourceInterface::LICENSEPLATE] :  explode(',', (string)$order[ResourceInterface::LICENSEPLATE]);
        $numberOfCars = $order[ResourceInterface::NUMBER_OF_CARS];
        if (count($licensePlates) > 1)
        {
            // Get existing order IDs, if present
            $remoteOrderIds = self::getRemoteOrderIdsFromOrder($order);

            foreach (array_filter($licensePlates) as $nr => $licenseplate) {
                $carOrder = $remoteOrder;
                $carOrder[ResourceInterface::PAYMENT_AMOUNT] = ((double)($order[ResourceInterface::PRICE_BASE] + $order[ResourceInterface::PRICE_OPTIONS]) / ($numberOfCars > 0 ? $numberOfCars : 1));
                $carOrder[ResourceInterface::LICENSEPLATE] = $licenseplate;
                // We assume same order of license plates as order ids in `reservation_result`
                if (isset($remoteOrderIds[$nr]))
                    $carOrder[ResourceInterface::ORDER_ID] = $remoteOrderIds[$nr];

                $remoteOrders[] = $carOrder;
            }

            if (count($remoteOrders) != $numberOfCars) {
                throw new ResourceError('order.travel', $order, [[
                    'code'    => 'number-of-cars-not-match-licenseplates',
                    'field'   => ResourceInterface::NUMBER_OF_CARS,
                    'message' => 'Number of cars specified does not equal number of unique license plates specified.',
                    'type'    => null,
                ]]);
            }
        } else {
            // Otherwise, one order only
            $remoteOrders[] = $remoteOrder;
        }

        return $remoteOrders;
    }

    public static function mapOptionIdsToRemoteOptionIds($order, $product)
    {
        $optionIds = explode(',', $order[ResourceInterface::OPTIONS]);
        $remoteOptionIds = [];
        foreach($optionIds as $optionId){
            foreach ($product[ResourceInterface::OPTIONS] as $option) {
                if ($option['id'] && $option['id'] == $optionId) {
                    if ($product[ResourceInterface::SOURCE] == 'parcompare') {
                        $remoteOptionIds[] = $option[ResourceInterface::ID];
                    }
                    else if (isset($option[ResourceInterface::REMOTE_ID])) {
                        $remoteOptionIds[] = $option[ResourceInterface::REMOTE_ID];
                    }
                }
            }
        }
        return $remoteOptionIds;
    }

    // Todo: Move this 'conversion' method (Travel order -> Parking CI orders) to a separate class
    public static function getParkingCiOrdersParams(array $order)
    {
        $ciOrders = [];

        $order = self::convertOrderUTCtoCest($order);

        // Almost all contract fields are like ParkingCI create_reservation fields, copy & correct some.
        $ciOrder = [
            ResourceInterface::PRICE => $order[ResourceInterface::PRICE],
            ResourceInterface::FIRST_NAME => $order[ResourceInterface::FIRST_NAME],
            ResourceInterface::LAST_NAME => $order[ResourceInterface::LAST_NAME],
            ResourceInterface::POSTAL_CODE => $order[ResourceInterface::POSTAL_CODE],
            ResourceInterface::HOUSE_NUMBER => $order[ResourceInterface::HOUSE_NUMBER],
            ResourceInterface::PHONE => $order[ResourceInterface::PHONE],
            ResourceInterface::EMAIL => $order[ResourceInterface::EMAIL],
            ResourceInterface::DEPARTURE_DATE => $order[ResourceInterface::DESTINATION_ARRIVAL_DATE],
            ResourceInterface::ARRIVAL_DATE => $order[ResourceInterface::DESTINATION_DEPARTURE_DATE],
            ResourceInterface::LICENSEPLATE => implode(',', (array)$order[ResourceInterface::LICENSEPLATE]),
            ResourceInterface::NUMBER_OF_PERSONS => $order[ResourceInterface::NUMBER_OF_PERSONS],
            ResourceInterface::RETURN_FLIGHT_NUMBER => $order[ResourceInterface::RETURN_FLIGHT_NUMBER],
            ResourceInterface::CUSTOMER_REMARKS => $order[ResourceInterface::CUSTOMER_REMARKS],
            ResourceInterface::EXTERNAL_ID => $order[ResourceInterface::EXTERNAL_ID],
            ResourceInterface::ORIGIN_GOOGLE_PLACE_ID => $order[ResourceInterface::ORIGIN_GOOGLE_PLACE_ID],
            ResourceInterface::DESTINATION_GOOGLE_PLACE_ID => $order[ResourceInterface::DESTINATION_GOOGLE_PLACE_ID],
            ResourceInterface::COSTFREE_CANCELLATION => $order[ResourceInterface::COSTFREE_CANCELLATION],
            ResourceInterface::OPTIONS => $order[ResourceInterface::OPTIONS],
            ResourceInterface::USER => $order[ResourceInterface::USER],
            ResourceInterface::WEBSITE => $order[ResourceInterface::WEBSITE],
            ResourceInterface::PAYMENT_AMOUNT_PAID => $order[ResourceInterface::PAYMENT_AMOUNT_PAID],
            ResourceInterface::DISABLE_SEND_EMAIL => true,
            ResourceInterface::ONE_WAY => $order[ResourceInterface::ONE_WAY],
            ResourceInterface::RESERVATION_KEY => $order[ResourceInterface::RESERVATION_KEY],
            ResourceInterface::IS_TEST_ORDER => $order[ResourceInterface::IS_TEST_ORDER],
        ];

        if(isset($order[ResourceInterface::PRICE_COSTFREE_CANCELLATION])){
            //Remove the cost free cancellation price before sending to the provider
            $ciOrder[ResourceInterface::PRICE] = $ciOrder[ResourceInterface::PRICE] - $order[ResourceInterface::PRICE_COSTFREE_CANCELLATION];
        }

        // We have stored product data to use
        if (!isset($order[ResourceInterface::PRODUCT]))
            throw new \Exception('Missing `product` data in order #'. $order['__id']);

        $product = json_decode($order[ResourceInterface::PRODUCT], true);
        if (empty($product[ResourceInterface::PARKING_ID]))
            throw new \Exception('Missing `parking_id` data in order #'. $order['__id']);
        $ciOrder[ResourceInterface::LOCATION_ID] = $product[ResourceInterface::PARKING_ID];

        if (!empty($product[ResourceInterface::RESERVATION_KEY]))
            $ciOrder[ResourceInterface::RESERVATION_KEY] = $product[ResourceInterface::RESERVATION_KEY];

        // For updates, set order id
        if (!empty($order[ResourceInterface::ORDER_ID]))
            $ciOrder[ResourceInterface::ORDER_ID] = $order[ResourceInterface::ORDER_ID];

        // Get existing order IDs, if present
        $ciOrderIds = [];
        if (isset($order[ResourceInterface::RESERVATION_RESULT])) {
            $reservations = json_decode($order[ResourceInterface::RESERVATION_RESULT], true);
            foreach ($reservations as $reservation) {
                if (isset($reservation[ResourceInterface::ORDER_ID]))
                    $ciOrderIds[] = $reservation[ResourceInterface::ORDER_ID];
            }
        }

        // Multiple licenseplates means multiple cars, and multiple ParkingCI orders.
        $licensePlates = is_array($order[ResourceInterface::LICENSEPLATE]) ? $order[ResourceInterface::LICENSEPLATE] :  explode(',', (string)$order[ResourceInterface::LICENSEPLATE]);
        $numberOfCars = $order[ResourceInterface::NUMBER_OF_CARS];
        if (count($licensePlates) > 1)
        {
            foreach (array_filter($licensePlates) as $nr => $licenseplate) {
                $carOrder = $ciOrder;
                $carOrder[ResourceInterface::PRICE] = ((double)$order[ResourceInterface::PRICE] / ($numberOfCars > 0 ? $numberOfCars : 1));
                $carOrder[ResourceInterface::PAYMENT_AMOUNT_PAID] = ((double)$order[ResourceInterface::PAYMENT_AMOUNT_PAID] / ($numberOfCars > 0 ? $numberOfCars : 1));

                $carOrder[ResourceInterface::LICENSEPLATE] = $licenseplate;
                // We assume same order of license plates as order ids in `reservation_result`
                if (isset($ciOrderIds[$nr]))
                    $carOrder[ResourceInterface::ORDER_ID] = $ciOrderIds[$nr];

                $ciOrders[] = $carOrder;
            }

            if (count($ciOrders) != $numberOfCars) {
                throw new ResourceError('order.travel', $order, [[
                    'code'    => 'number-of-cars-not-match-licenseplates',
                    'field'   => ResourceInterface::NUMBER_OF_CARS,
                    'message' => 'Number of cars specified does not equal number of unique license plates specified.',
                    'type'    => null,
                ]]);
            }
        } else {
            // Otherwise, one order only
            $ciOrders[] = $ciOrder;
        }

        return $ciOrders;
    }

    public static function getProductForOrder($orderData)
    {
        // Different name mappings
        $productParams = [
            ResourceInterface::__ID => $orderData[ResourceInterface::PRODUCT_ID],
            ResourceInterface::AVAILABLE_OPTIONS => $orderData[ResourceInterface::OPTIONS],
            ResourceInterface::ENABLED => 1,
        ];
        // Straight mapping
        $productParams = array_merge($productParams, array_only($orderData, [
            ResourceInterface::DESTINATION_ARRIVAL_DATE,
            ResourceInterface::DESTINATION_DEPARTURE_DATE,
            ResourceInterface::ORIGIN_GOOGLE_PLACE_ID,
            ResourceInterface::DESTINATION_GOOGLE_PLACE_ID,
            ResourceInterface::NUMBER_OF_CARS,
            ResourceInterface::NUMBER_OF_PERSONS,
            ResourceInterface::ONE_WAY,
            ResourceInterface::WEBSITE,
            ResourceInterface::USER,
            ResourceInterface::COSTFREE_CANCELLATION,
        ]));
        $productParams = self::convertOrderUTCtoCest($productParams);

        $products = ResourceHelper::callResource2('product.travel', $productParams);

        if (!isset($products[0]))
            return false;

        return $products[0];
    }

    public static function convertOrderUTCtoCest($order)
    {
        foreach([ResourceInterface::DESTINATION_ARRIVAL_DATE, ResourceInterface::DESTINATION_DEPARTURE_DATE] as $field) {
            if (isset($order[$field])) {
                $order[$field] =
                    (new \DateTime($order[$field], new \DateTimeZone('UTC')))
                        ->setTimezone(new \DateTimeZone('Europe/Amsterdam'))
                        ->format('Y-m-d H:i:s');
            }
        }

        return $order;
    }
}