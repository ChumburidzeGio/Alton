<?php
namespace App\Resources\Parkandfly\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\ParkandflyAbstractRequest;

class GetReservation extends ParkandflyAbstractRequest
{
    protected $cacheDays = false;

    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'id'                => ResourceInterface::ORDER_ID,
        'user.firstName'    => ResourceInterface::FIRST_NAME,
        'user.lastName'     => ResourceInterface::LAST_NAME,
        'user.email'        => ResourceInterface::EMAIL,
        'user.vehicle'      => ResourceInterface::LICENSEPLATE,
        'arrival.planned'   => ResourceInterface::ARRIVAL_DATE,
        'departure.planned' => ResourceInterface::DEPARTURE_DATE,
        'location.id'       => ResourceInterface::LOCATION_ID,
        'accessCode'        => ResourceInterface::RESERVATION_CODE,
        //'phone'                 => ResourceInterface::PHONE, // Not currently present
        '_empty_value_'     => ResourceInterface::OPTIONS,
        'externalId'        => ResourceInterface::EXTERNAL_ID,
        'description'       => ResourceInterface::INTERNAL_REMARKS,
    ];
    protected $resultTransformations = [
        ResourceInterface::ORDER_ID     => 'prefixOrderId',
        ResourceInterface::OPTIONS      => 'formatOptions',
        ResourceInterface::LOCATION_ID  => 'castToString',
        ResourceInterface::ARRIVAL_DATE => 'formatResultDateTime',
        ResourceInterface::DEPARTURE_DATE => 'formatResultDateTime',
    ];

    public function __construct()
    {
        parent::__construct('users/{user_id}/{user_hash}/reservations/{order_id}');
    }

    public function formatOptions($value)
    {
        return []; // No options present
    }
}