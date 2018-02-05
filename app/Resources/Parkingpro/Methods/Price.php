<?php
namespace App\Resources\Parkingpro\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkingpro\ParkingproAbstractRequest;

class Price extends ParkingproAbstractRequest
{
    protected $inputTransformations = [
        ResourceInterface::ARRIVAL_DATE     => 'formatDateTime',
        ResourceInterface::DEPARTURE_DATE   => 'formatDateTime',
        ResourceInterface::OPTIONS          => 'formatOptionsCommaSeparated',
    ];

    protected $inputToExternalMapping = [
        ResourceInterface::PARKING_ID       => 'parkingId',
        ResourceInterface::LOCATION_ID      => 'locationId',
        ResourceInterface::ARRIVAL_DATE     => 'parkingDate',
        ResourceInterface::DEPARTURE_DATE   => 'returnDate',
        ResourceInterface::OPTIONS          => 'reservationOptions',
    ];
    protected $externalToResultMapping = [
        'totalWithTax'  => ResourceInterface::PRICE_ACTUAL,
        'isUnavailable' => ResourceInterface::IS_UNAVAILABLE,
        'totalOptions'  => ResourceInterface::PRICE_OPTIONS,
        'lines'         => ResourceInterface::PRODUCT_OPTIONS,
    ];
    protected $resultTransformations = [
        ResourceInterface::PRODUCT_OPTIONS => 'filterOptions',
    ];

    public function __construct()
    {
        parent::__construct('price');
    }

    public function getDefaultParams()
    {
        return [
            'locationId' => null,
            'parkingDate' => null,
            'returnDate' => null,
            'passengerCount' => null,
            'reservationOptions' => [],     // Optional
            'discountCodes' => [],          // Optional
            'reservationId' => null,        // Optional
        ];
    }

    public function filterOptions($value)
    {
        $options = [];
        foreach ($value as $item) {
            if ($item['isOption']) {
                $options[] = [
                    ResourceInterface::OPTION_ID => $item['id'],
                    ResourceInterface::NAME => $item['title'],
                    ResourceInterface::PRICE_ACTUAL => $item['amount'],
                ];
            }
        }

        return $options;
    }
}