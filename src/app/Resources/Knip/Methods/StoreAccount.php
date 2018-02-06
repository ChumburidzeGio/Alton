<?php
namespace App\Resources\Knip\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Knip\AbstractKnipRequest;

class StoreAccount extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
    ];
    protected $inputToExternalMapping = [

        ResourceInterface::FIRST_NAME => 'firstName',
        ResourceInterface::LAST_NAME => 'lastName',
        ResourceInterface::PHONE => 'phone',
        ResourceInterface::EMAIL => 'email',
        ResourceInterface::BIRTHDATE => 'birthday',
        ResourceInterface::STREET => 'street',
        ResourceInterface::HOUSE_NUMBER => 'streetNumber',
        ResourceInterface::POSTAL_CODE => 'postCode',
        ResourceInterface::CITY => 'city',
        ResourceInterface::GENDER => 'gender',
        ResourceInterface::COUNTRY_NAME => 'country',
        ResourceInterface::SESSION_ID => 'sid'
    ];
    protected $externalToResultMapping = [
        'id'         => ResourceInterface::ACCOUNT_ID,
        'streetNumber' => ResourceInterface::HOUSE_NUMBER,
        'firstName'=> ResourceInterface::FIRST_NAME,
        'lastName'=> ResourceInterface::LAST_NAME,
        'email'=> ResourceInterface::EMAIL,
        'birthday'=> ResourceInterface::BIRTHDATE,
        'street'=> ResourceInterface::STREET,
        'postCode'=> ResourceInterface::POSTAL_CODE,
        'city'=> ResourceInterface::CITY,
        'country'=> 'country',
        'gender' => ResourceInterface::GENDER,
        'phone' => ResourceInterface::PHONE,
        'applications' => 'applications'
    ];
    protected $resultTransformations = [];

    public function getResult()
    {
        $this->result = $this->result['data'];
        return parent::getResult();
    }

    public function __construct()
    {
        parent::__construct('accounts', self::METHOD_POST);
    }
}