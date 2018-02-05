<?php
namespace App\Resources\Knip\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Knip\AbstractKnipRequest;

class IndexAccount extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::HASH          => 'hash',
        ResourceInterface::ACCOUNT_ID           => 'id',
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
    ];
    protected $resultTransformations = [];

    public function getResult()
    {
        $this->result = [$this->result['data']];
        return parent::getResult();
    }

    public function __construct()
    {
        //TODO: change __id to account_id when Knip is ready with the endpoints
        parent::__construct('accounts/{hash}', self::METHOD_GET);
    }
}