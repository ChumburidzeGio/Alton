<?php
namespace App\Resources\Parkandfly\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\ParkandflyAbstractRequest;
use App\Resources\Parkandfly\Parking;

class UpdateReservation extends ParkandflyAbstractRequest
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
        'reservation.id'         => ResourceInterface::ORDER_ID,
        'reservation.accessCode' => ResourceInterface::RESERVATION_CODE,
    ];
    protected $resultTransformations = [
        ResourceInterface::ORDER_ID   => 'prefixOrderId',
    ];

    protected $externalUserParams = [
        // User data, mapped to FindUserByEmail / CreateUser
        ResourceInterface::EMAIL,
        ResourceInterface::FIRST_NAME,
        ResourceInterface::LAST_NAME,
        ResourceInterface::PHONE,
    ];


    protected $userParams;
    protected $userResults;

    public function __construct()
    {
        parent::__construct('users/{user_id}/{user_hash}/reservations/{order_id}', self::METHOD_POST);
    }

    protected function getDefaultParams()
    {
        return [
            'plannedArrival' => null,
            'plannedDeparture' => null,
            'locationId' => null,
            'registration' => null,
            'externalId' => null,
            'description' => null,
            // The parameters below are not mapped currently
            'vehicleId' => null,
            'sendMail' => null,
            'anonymous' => null,
        ];
    }

    public function setParams(Array $params)
    {
        $this->userParams = array_only($params, $this->externalUserParams);

        parent::setParams($params);
    }

    public function executeFunction()
    {
        // If we got any user data, fire off a separate request for that.
        if (!empty($this->inputParams[ResourceInterface::LOCATION_ID]))
        {
            $getReservation = new GetReservation();
            $getReservation->setParams([Parking::ORDER_ID => $this->inputParams[Parking::ORDER_ID]]);
            if ($getReservation->getErrorString())
            {
                $this->setErrorString('Get reservation: '. $getReservation->getErrorString());
                return;
            }
            $getReservation->executeFunction();
            if ($getReservation->getErrorString())
            {
                $this->setErrorString('Get reservation: '. $getReservation->getErrorString());
                return;
            }
            $reservation = $getReservation->getResult();

            if ((int)$reservation[Parking::LOCATION_ID] != (int)$this->inputParams[ResourceInterface::LOCATION_ID])
            {
                $this->setErrorString('Cannot change the location of a reservation through Park & Fly. Cancel and create a new reservation instead.');
                return;
            }
        }

        if ($this->userParams != [])
        {
            $userParams = $this->userParams + [Parking::ORDER_ID => $this->inputParams[Parking::ORDER_ID]];

            $updateUser = new UpdateUser();
            $updateUser->setParams($userParams);

            if ($updateUser->getErrorString())
            {
                $this->setErrorString('Update user: '. $updateUser->getErrorString());
                return;
            }
            $updateUser->executeFunction();
            if ($updateUser->getErrorString())
            {
                $this->setErrorString('Update user: '. $updateUser->getErrorString());
                return;
            }
            $this->userResults = $updateUser->getResult();
            if ($updateUser->getErrorString())
            {
                $this->setErrorString('Update user: '. $updateUser->getErrorString());
                return;
            }
        }

        parent::executeFunction();
    }
}