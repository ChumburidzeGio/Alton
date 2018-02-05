<?php
namespace App\Resources\Moneyview\Methods\Impl\Car;

use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\Moneyview\Methods\AbstractPolicyClient;

class PolicyClient extends AbstractPolicyClient
{
    protected $moneyviewModuleName = 'Auto';

    protected $arguments = [
        ResourceInterface::ID => [
            'rules' => 'required | number',
            'example' => '9314'
        ],
        ResourceInterface::COVERAGE => [
            'rules' => 'string',
        ],
    ];

    protected $coverageToExternalCode = [
        ResourceValue::CAR_COVERAGE_MINIMUM => 'WA',
        ResourceValue::CAR_COVERAGE_LIMITED => 'WABC',
        ResourceValue::CAR_COVERAGE_COMPLETE => 'WAVC',
    ];

    public function setParams(Array $params)
    {
        if (isset($params[ResourceInterface::COVERAGE], $this->coverageToExternalCode[$params[ResourceInterface::COVERAGE]]))
            $params[ResourceInterface::PRODUCT_SPEC] = $this->coverageToExternalCode[$params[ResourceInterface::COVERAGE]];

        return parent::setParams($params);
    }
}
