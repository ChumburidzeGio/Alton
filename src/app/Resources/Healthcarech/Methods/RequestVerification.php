<?php
namespace App\Resources\Healthcarech\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Healthcarech\AbstractKnipRequest;

class RequestVerification extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [
        ResourceInterface::ACCOUNT_ID  => 'id',
        ResourceInterface::KEY => 'privateKey',
    ];
    protected $externalToResultMapping = [];
    protected $resultTransformations = [];


    public function __construct()
    {
        parent::__construct('komparu/account/verify', self::METHOD_GET);
    }
}