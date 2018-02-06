<?php
namespace App\Resources\Parkandfly\Methods;

use App\Resources\Parkandfly\ParkandflyAbstractRequest;

class FindUserByEmail extends ParkandflyAbstractRequest
{
    protected $cacheDays = false;

    protected $inputToExternalMapping = [];

    public function __construct()
    {
        parent::__construct('users/email/{email}', self::METHOD_GET);
    }
}