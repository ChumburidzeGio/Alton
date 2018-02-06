<?php
namespace App\Resources\Parkandfly\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\ParkandflyAbstractRequest;

class CreateUser extends ParkandflyAbstractRequest
{
    protected $cacheDays = false;

    protected $inputToExternalMapping = [
        ResourceInterface::LAST_NAME      => 'lastName',
        ResourceInterface::FIRST_NAME     => 'firstName',
        ResourceInterface::EMAIL          => 'email',
        ResourceInterface::PHONE          => 'phone',
    ];

    public function __construct()
    {
        parent::__construct('users', self::METHOD_PUT);
    }

    protected function getDefaultParams()
    {
        return [
            'email' => null,
            'firstName' => null,
            'lastName' => null,
            'phone' => null,
            // The parameters below are not mapped currently
            'title' => null,
            'password' => null,
            'companyName' => null,
            'address' => null,
            'city' => null,
            'zipcode' => null,
            'country' => null,
            'customFields' => null,
            'vehicles[0][registration]' => null,
        ];
    }

    public function getResult()
    {
        return parent::getResult();
    }
}