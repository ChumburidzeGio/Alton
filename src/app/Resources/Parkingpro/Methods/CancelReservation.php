<?php
namespace App\Resources\Parkingpro\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkingpro\ParkingproAbstractRequest;

class CancelReservation extends ParkingproAbstractRequest
{
    protected $cacheDays = false;

    protected $inputToExternalMapping = [
        ResourceInterface::PARKING_ID       => 'parkingId',
    ];
    protected $externalToResultMapping = [
        'id' => ResourceInterface::ORDER_ID,
    ];

    public function __construct()
    {
        parent::__construct('reservation/{order_id}/cancel', self::METHOD_PUT);
    }

    public function getDefaultParams()
    {
        return [
            'options' => [
                'skipSendEmail' => true,
            ],
            'cancelledDate' => $this->formatDateTime('now'),
        ];
    }

}