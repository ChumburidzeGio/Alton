<?php
namespace App\Resources\Parkingpro\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkingpro\ParkingproAbstractRequest;

class CreateReservation extends ParkingproAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
        ResourceInterface::ARRIVAL_DATE     => 'formatDateTime',
        ResourceInterface::DEPARTURE_DATE   => 'formatDateTime',
        ResourceInterface::OPTIONS          => 'formatOptions',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::PARKING_ID     => 'parkingId',
        ResourceInterface::LOCATION_ID    => 'locationId',
        ResourceInterface::ARRIVAL_DATE   => 'parkingDate',
        ResourceInterface::DEPARTURE_DATE => 'returnDate',
        ResourceInterface::LICENSEPLATE   => 'carLicensePlate',
        ResourceInterface::LAST_NAME      => 'lastName',
        ResourceInterface::FIRST_NAME     => 'firstName',
        ResourceInterface::EMAIL          => 'email',
        ResourceInterface::PHONE          => 'phone',
        ResourceInterface::OPTIONS        => 'appliedOptions',
        ResourceInterface::RETURN_FLIGHT_NUMBER => 'arrivalFlightNumber',
        ResourceInterface::NUMBER_OF_PERSONS => 'numberOfPersons',
        ResourceInterface::EXTERNAL_ID    => 'externalId',
        ResourceInterface::INTERNAL_REMARKS => 'internalRemarks',
        ResourceInterface::CUSTOMER_REMARKS => 'customerRemarks',
        ResourceInterface::PAYMENT_AMOUNT => ['payment.amount', 'totalAmountWithTax'],
    ];
    protected $externalToResultMapping = [
        'id'                => ResourceInterface::ORDER_ID,
        'reservationCode'   => ResourceInterface::RESERVATION_CODE,
    ];


    public function __construct()
    {
        parent::__construct('reservation', self::METHOD_POST);
    }

    public function getDefaultParams()
    {
        return [
            'options' => [
                'skipSendEmail' => true,
                'paymentLinkRedirectUrl' => null,
            ],
            'payment' => null,                  // Optional
            /*[
                'amount' => 0,
                'date' => '2016-08-10T09 01 14.486Z',   // Optional
                'method' => 'string',                       // Optional
                'transactionId' => 'string',                // Optional
                'description' => 'string'                   // Optional
            ],*/
            'returnDate' => null,
            'parkingDate' => null,
            'arrivalFlightNumber' => null,      // Optional
            'departureFlightNumber' => null,    // Optional
            'numberOfPersons' => null,          // Optional
            'externalId' => null,               // Optional
            'customerRemarks' => null,          // Optional
            'internalRemarks' => null,          // Optional
            'totalAmountWithTax' => null,       // Optional
            'carLicensePlate' => null,
            'carDescription' => null,           // Optional
            'locationId' => null,
            'companyName' => null,              // Optional
            'firstName' => null,
            'lastName' => null,
            'phone' => null,
            'email' => null,
            'invoiceEmail' => null,             // Optional
            'appliedCouponCode' => null,        // Optional
            'appliedOptions' => [],             // Array of strings - Optional
        ];
    }

    protected function mapInputToExternal(array $inputParams, array $params, $unsetNullValues = true, $unsetEmptyArrays = true)
    {
        $params = parent::mapInputToExternal($inputParams, $params, $unsetNullValues, $unsetEmptyArrays);
        if (isset($params['payment']['amount']))
            $params['payment']['date'] = $this->formatDateTime('now');
        return $params;
    }
}