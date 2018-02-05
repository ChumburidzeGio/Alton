<?php
namespace App\Resources\Parkandfly\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\ParkandflyAbstractRequest;
use GuzzleHttp\Message\ResponseInterface;

class CreateReservation extends ParkandflyAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
        ResourceInterface::ARRIVAL_DATE   => 'formatDateTime',
        ResourceInterface::DEPARTURE_DATE => 'formatDateTime',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::LOCATION_ID    => 'locationId',
        ResourceInterface::ARRIVAL_DATE   => 'plannedArrival',
        ResourceInterface::DEPARTURE_DATE => 'plannedDeparture',
        ResourceInterface::LICENSEPLATE   => 'registration',
        ResourceInterface::LAST_NAME      => 'lastName',
        ResourceInterface::FIRST_NAME     => 'firstName',
        ResourceInterface::EMAIL          => 'email',
        ResourceInterface::PHONE          => 'phone',
        ResourceInterface::EXTERNAL_ID    => 'externalId',
        ResourceInterface::INTERNAL_REMARKS => 'description',
    ];
    protected $externalToResultMapping = [
        'id'         => ResourceInterface::ORDER_ID,
        'accessCode' => ResourceInterface::RESERVATION_CODE,
    ];
    protected $resultTransformations = [
        ResourceInterface::ORDER_ID   => 'prefixOrderId',
    ];

    public function __construct()
    {
        parent::__construct('reservations', self::METHOD_PUT);
    }

    protected function getDefaultParams()
    {
        return [
            'plannedArrival' => null,
            'plannedDeparture' => null,
            'locationId' => null,
            'registration' => null,
            'email' => null,
            'firstName' => null,
            'lastName' => null,
            'phone' => null,
            'externalId' => null,
            'description' => null,
            // The parameters below are not mapped currently
            'password' => null,
            'title' => null,
            'vehicleId' => null,
            'sendMail' => null,
            'anonymous' => null,
        ];
    }

    public function parseResponse(ResponseInterface $response, $ignoreException = false)
    {
        $result = parent::parseResponse($response, $ignoreException);
        if (isset($result['reservation']))
            $result = $result['reservation'];

        return $result;
    }

    public function executeFunction()
    {
        parent::executeFunction();

        // Park and Fly currently has some API issues where calls can fail (due to some silly race/lock). We can retry in one second.
        // (for safety we will only do it once)
        if ($this->hasErrors() && $this->receivedEmptyError) {
            $this->clearErrors();
            $this->result = null;
            sleep(1);
            parent::executeFunction();
        }
    }
}