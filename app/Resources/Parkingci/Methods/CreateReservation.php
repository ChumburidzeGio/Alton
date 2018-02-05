<?php

namespace App\Resources\Parkingci\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkingci\ParkingciAbstractRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;

class CreateReservation extends ParkingciAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
        ResourceInterface::FIRST_NAME => 'addLastName',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::LOCATION_ID => 'park_id',
        ResourceInterface::ARRIVAL_DATE => ['arrival', 'arrival_hour'],
        ResourceInterface::DEPARTURE_DATE => ['departure', 'departure_hour'],
        ResourceInterface::LICENSEPLATE => 'kenteken',
        ResourceInterface::FIRST_NAME => 'naam',
        ResourceInterface::EMAIL => 'email',
        ResourceInterface::PHONE => 'phonenumber',
        ResourceInterface::OPTIONS => 'options',
        ResourceInterface::RETURN_FLIGHT_NUMBER => 'vluchtnummerretour', // TODO: Check with Chris if exists
        ResourceInterface::NUMBER_OF_PERSONS => 'personen',
        ResourceInterface::EXTERNAL_ID => 'subid',
        //ResourceInterface::INTERNAL_REMARKS => 'internalRemarks', // TODO: Check with Chris if exists
        ResourceInterface::CUSTOMER_REMARKS => 'opmerking',
        ResourceInterface::PRICE => 'price_total', // Expected price (for check)
        ResourceInterface::COSTFREE_CANCELLATION => 'costfree_cancellation',

        ResourceInterface::POSTAL_CODE => 'zipcode',
        ResourceInterface::HOUSE_NUMBER => 'housenumber',

        ResourceInterface::PAYMENT_COMPLETE => 'payment',
        ResourceInterface::PAYMENT_AMOUNT_PAID => 'payment_deposit',

        ResourceInterface::USER => 'par_id',
        ResourceInterface::WEBSITE => 'website_id',

        ResourceInterface::RESERVATION_KEY => 'reservation_key',
        ResourceInterface::ORIGIN_GOOGLE_PLACE_ID => 'origin_google_place_id',
        ResourceInterface::DESTINATION_GOOGLE_PLACE_ID => 'destination_google_place_id',

        ResourceInterface::DISABLE_SEND_EMAIL => 'send_no_email',

        ResourceInterface::ONE_WAY => 'one_way',

        ResourceInterface::IS_TEST_ORDER => 'test',

        // Not mapped or in defaults:
        // 'payonline', 'sleep', 'card', 'luggage', 'price_nachttoeslag',
        // 'price_school', 'price_options', 'sessionid', 'customerid'
    ];
    protected $externalToResultMapping = [
        'transaction_id' => ResourceInterface::ORDER_ID,
    ];
    protected $resultTransformations = [];

    public function __construct()
    {
        $methodPath = $this->isTestEnvironment() ? 'order_test' : 'order';
        parent::__construct($methodPath, self::METHOD_PUT);
    }

    protected function mapInputToExternal(array $inputParams, array $params, $unsetNullValues = true, $unsetEmptyArrays = true)
    {
        $params = parent::mapInputToExternal($inputParams, $params, $unsetNullValues, $unsetEmptyArrays);

        if (isset($params['arrival'])) {
            $params['arrival'] = $this->formatDate($params['arrival']);
            $params['arrival_hour'] = $this->formatTime($params['arrival_hour']);
        }

        if (isset($params['departure'])) {
            $params['departure'] = $this->formatDate($params['departure']);
            $params['departure_hour'] = $this->formatTime($params['departure_hour']);
        }

        // Cannot overwrite 'test' to be 0 on test env
        if (!$params['test'] && $this->isTestEnvironment())
            $params['test'] = 1;

        // Switch to test endpoint
        if (!empty($params['test']))
            $this->url = ((app()->configure('resource_parkingci')) ? '' : config('resource_parkingci.settings.url')) . 'order_test';

        return $params;
    }

    public function getDefaultParams()
    {
        return [
            'json' => 1,
            'par_id' => 14, // 'user_id' - Default is Komparu.
            'test' => $this->isTestEnvironment() ? 1 : 0,
            'payonline' => 0, // We are not going to use the payment method of the old system
        ];
    }

    protected function addLastName($value, $params)
    {
        return $value .' '. $params[ResourceInterface::LAST_NAME];
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        parent::handleError($response, $exception);

        // Translate specific error, when starting date is too early
        if ($this->hasErrors() && str_contains($this->getErrorString(), 'must be greater then'))
            $this->addErrorMessage(ResourceInterface::DEPARTURE_DATE, 'parking-departure-too-early', 'Deze datum is te vroeg, hij moet minstens 24 uur in de toekomst liggen.');
    }
}