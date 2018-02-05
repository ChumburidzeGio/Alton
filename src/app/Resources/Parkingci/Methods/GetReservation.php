<?php

namespace App\Resources\Parkingci\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkingci\ParkingciAbstractRequest;

class GetReservation extends ParkingciAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'order_id' => ResourceInterface::ORDER_ID,
        'parkeerbeheerder.parkeeroptie' => ResourceInterface::LOCATION_ID,
        'arrival_date' => ResourceInterface::ARRIVAL_DATE,
        'departure_date' => ResourceInterface::DEPARTURE_DATE,
        '_' => ResourceInterface::FIRST_NAME,
        'name' => ResourceInterface::LAST_NAME,
        'zipcode' => ResourceInterface::POSTAL_CODE,
        'housenumber' => ResourceInterface::HOUSE_NUMBER,
        'email' => ResourceInterface::EMAIL,
        'phone' => ResourceInterface::PHONE,
        'notes' => ResourceInterface::CUSTOMER_REMARKS,
        'person_count' => ResourceInterface::NUMBER_OF_PERSONS,
        'cars.car.kenteken' => ResourceInterface::LICENSEPLATE,
        'vluchtnummerretour' => ResourceInterface::RETURN_FLIGHT_NUMBER,
        'price.total' => ResourceInterface::PRICE_ACTUAL,
        'price.parking' => ResourceInterface::PRICE_BASE,
        'price.options' => ResourceInterface::PRICE_OPTIONS,
        'price.costfree_cancellation' => ResourceInterface::PRICE_COSTFREE_CANCELLATION,
        'price.administration_fee' => ResourceInterface::PRICE_ADMINISTRATION_FEE,
        'price.nachttoeslag' => ResourceInterface::PRICE_NIGHT_SURCHARGE,
        'reservation_code' => ResourceInterface::RESERVATION_CODE,
        'timestamp' => ResourceInterface::CREATION_DATE,
        'requested_options' => ResourceInterface::OPTIONS,
        'status' => ResourceInterface::STATUS,
        'payment_notification' => ResourceInterface::PAYMENT_COMPLETE,
        'one_way' => ResourceInterface::ONE_WAY,
        'send_no_email' => ResourceInterface::DISABLE_SEND_EMAIL,
    ];
    protected $resultTransformations = [
        ResourceInterface::ARRIVAL_DATE => 'addArrivalTime',
        ResourceInterface::DEPARTURE_DATE => 'addDepartureTime',
        ResourceInterface::FIRST_NAME => 'extractFirstName',
        ResourceInterface::LAST_NAME => 'extractLastName',
    ];

    public function __construct()
    {
        $methodPath = $this->isTestEnvironment() ? 'order_test/{order_id}' : 'order/{order_id}';
        parent::__construct($methodPath);
    }


    public function getResult()
    {
        if (isset($this->result['order']))
            $this->result = $this->result['order'];

        return parent::getResult();
    }

    protected function addArrivalTime($value, $result)
    {
        return date('Y-m-d H:i:s', strtotime($value .' '. $result['@unmapped']['arrival_time']));
    }

    protected function addDepartureTime($value, $result)
    {
        return date('Y-m-d H:i:s', strtotime($value .' '. $result['@unmapped']['departure_time']));
    }

    protected function extractFirstName($value, $result)
    {
        $name = explode(' ', $result[ResourceInterface::LAST_NAME], 2);

        return count($name) > 1 ? $name[0] : '';
    }

    protected function extractLastName($value)
    {
        $name = explode(' ', $value, 2);

        return isset($name[1]) ? $name[1] : $name[0];
    }
}