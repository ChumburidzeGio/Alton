<?php
namespace App\Resources\Parkandfly\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\ParkandflyAbstractRequest;

class CreateReservationViaUser extends ParkandflyAbstractRequest
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
    protected $userData;

    public function __construct()
    {
        parent::__construct('users/{user_id}/{user_hash}/reservations', self::METHOD_PUT);
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
        if (!empty($this->userParams))
        {
            $findUser = new FindUserByEmail();
            $findUser->setParams($this->userParams);
            $findUser->executeFunction();

            if (empty($findUser->getErrorString()))
            {
                $this->userData = $findUser->getResult();
                $this->params['user_id'] = $this->userData['user']['id'];
                $this->params['user_hash'] = $this->userData['user']['hash'];

                // See if the current user data is
                $userDataChanged = false;
                foreach ($this->externalUserParams as $paramName)
                {
                    if (!isset($this->userParams[$paramName]))
                        continue;

                    if (!isset($this->userData['user'][$paramName]) || $this->userParams[$paramName] != $this->userData['user'][$paramName])
                    {
                        $userDataChanged = true;
                        break;
                    }
                }

                if ($userDataChanged)
                {
                    $updateUser = new UpdateUser();
                    $updateUser->setParams($this->userParams + ['user_id' => $this->userData['user']['id'], 'user_hash' => $this->userData['user']['hash']]);
                    $updateUser->executeFunction();
                    $updateUser->getResult();

                    if ($updateUser->hasErrors())
                    {
                        $this->setErrorString('Update user error: '. $updateUser->getErrorString());
                        return;
                    }
                }
            }
            else if (str_contains($findUser->getErrorString(), 'No user specified or user not found.'))
            {
                $createUser = new CreateUser();
                $createUser->setParams($this->userParams);
                $createUser->executeFunction();
                $createUserResult = $createUser->getResult();

                if (empty($createUser->getErrorString()))
                {
                    $this->userData = $createUserResult;
                    $this->params['user_id'] = $this->userData['user']['id'];
                    $this->params['user_hash'] = $this->userData['user']['hash'];
                }
            }
            else
            {
                $this->setErrorString($findUser->getErrorString());
                return;
            }
        }
        $this->params = $this->applyPathParams($this->params);

        parent::executeFunction();
    }

    protected function prefixOrderId($value)
    {
        if (isset($this->userData, $this->userData['user']['id']))
            return $this->userData['user']['id'] .'|'. $this->userData['user']['hash'] .'|'. $value;
        else
            return parent::prefixOrderId($value);
    }
}