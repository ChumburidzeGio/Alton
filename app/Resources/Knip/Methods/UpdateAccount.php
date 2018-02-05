<?php
namespace App\Resources\Knip\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Knip\AbstractKnipRequest;

class UpdateAccount extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::HASH => 'hash',
        ResourceInterface::ACCOUNT_ID => 'id',
        ResourceInterface::FIRST_NAME => 'firstName',
        ResourceInterface::LAST_NAME => 'lastName',
        ResourceInterface::EMAIL => 'email',
        ResourceInterface::BIRTHDATE => 'birthday',
        ResourceInterface::STREET => 'street',
        ResourceInterface::HOUSE_NUMBER => 'streetNumber',
        ResourceInterface::POSTAL_CODE => 'postCode',
        ResourceInterface::CITY => 'city',
    ];
    protected $externalToResultMapping = [
        'id'         => ResourceInterface::ACCOUNT_ID,
        'firstName'=> ResourceInterface::FIRST_NAME,
        'lastName'=> ResourceInterface::LAST_NAME,
        'email'=> ResourceInterface::EMAIL,
        'birthday'=> ResourceInterface::BIRTHDATE,
        'street'=> ResourceInterface::STREET,
        'streetNumber' => ResourceInterface::HOUSE_NUMBER,
        'postCode'=> ResourceInterface::POSTAL_CODE,
        'city'=> ResourceInterface::CITY,
        'country'=> 'country',
        'gender' => ResourceInterface::GENDER,
        'phone' => ResourceInterface::PHONE,
    ];
    protected $resultTransformations = [];

    public function getResult()
    {
        $this->result = $this->result['data'];
        return parent::getResult();
    }

    public function __construct()
    {
        //TODO: change __id to account_id when Knip is ready with the endpoints
        parent::__construct('accounts/{__id}', self::METHOD_PUT);
    }
}