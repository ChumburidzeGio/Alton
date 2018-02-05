<?php
namespace App\Resources\Knip\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Knip\AbstractKnipRequest;

class SetSelectedAdditionalInsurance extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::PRODUCT_ID => 'product_id',
        ResourceInterface::COMPANY__ID => 'company_id',
        ResourceInterface::PRICE => 'price'
    ];
    protected $externalToResultMapping = [
    ];
    protected $resultTransformations = [];

    public function setParams(array $params)
    {
        parent::setParams($params);
    }

    public function getResult()
    {
        return [];
    }

    public function __construct()
    {
        parent::__construct('accounts/{hash}/additional-health-insurances', self::METHOD_POST);
    }
}