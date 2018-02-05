<?php
namespace App\Resources\Knip\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Knip\AbstractKnipRequest;

class CreateAccountKey extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::PHONE => 'phone',
        ResourceInterface::EMAIL => 'email',
    ];
    protected $externalToResultMapping = [
        'hash' => ResourceInterface::HASH,
        'validUntil'=> ResourceInterface::VALID,
    ];
    protected $resultTransformations = [];

    public function getResult()
    {
        $this->result = $this->result['data'];
        return parent::getResult();
    }

    public function __construct()
    {
        parent::__construct('account-api-keys', self::METHOD_POST);
    }
}