<?php
namespace App\Resources\Healthcarech\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Healthcarech\AbstractKnipRequest;

class InsuranceCompanies extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'id'           => ResourceInterface::ID,
        'name'         => ResourceInterface::LABEL,
        'imageUrl.url' => ResourceInterface::IMAGE,
    ];
    protected $resultTransformations = [];

    public function __construct()
    {
        parent::__construct('komparu/insuranceCompanies');
    }

    protected function mapExternalToResult(array $rawResult)
    {
        if( ! isset($rawResult['insuranceCompanies'])){
            $this->setErrorString('Missing `insuranceCompanies` structure.');
            return [];
        }
        return parent::mapExternalToResult($rawResult['insuranceCompanies']);
    }
}