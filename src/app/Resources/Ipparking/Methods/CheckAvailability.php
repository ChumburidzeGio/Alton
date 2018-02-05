<?php
/**
 * User: Roeland Werring
 * Date: 25/09/15
 * Time: 10:38
 *
 */

namespace App\Resources\Ipparking\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Ipparking\AbstractParkingRequest;

class CheckAvailability extends AbstractParkingRequest
{
    protected $arguments = [
        ResourceInterface::ARRIVAL_DATE  => [
            'rules'   => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::DEPARTURE_DATE  => [
            'rules'   => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::LOCATION_ID  => [
            'rules'   => 'required | string',
            'example' => '4d8111cc-da2f-e211-aa49-68b599c2cc67'
        ],
        ResourceInterface::NUMBER_OF_SPOTS  => [
            'rules'   => 'number',
            'example' => '3',
            'default' => 1
        ],
    ];

    public function __construct()
    {
        $this->method = 'CheckAvailability';
        parent::__construct();
    }

}