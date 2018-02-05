<?php

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\AbstractMethodRequest;
use Config;


class AutoEigenRisicosClient extends AbstractMethodRequest
{
    public $cacheDays = false;
    public $resource2Request = true;

    protected $coverageSort = [
        ResourceValue::CAR_COVERAGE_MINIMUM => 0,
        ResourceValue::CAR_COVERAGE_LIMITED => 1,
        ResourceValue::CAR_COVERAGE_COMPLETE => 2,
    ];

    protected $params = [
        ResourceInterface::RESOURCE__ID => 0,
    ];
    protected $result;

    public function setParams(Array $params)
    {
        $this->params = array_only($params, array_keys($this->params));
    }

    public function executeFunction()
    {
        $lists = $this->internalRequest('carinsurance', 'list', ['list' => 'car_option_list'], true);

        if ($this->resultHasError($lists)) {
            $this->setErrorString('List error: '. json_encode($lists));
            return;
        }
        $ownRisks = array_keys($lists[ResourceInterface::OWN_RISK]);

        $premiums = $this->internalRequest('carinsurance', 'premium', [
            ResourceInterface::LICENSEPLATE => '89-RVX-7',
            ResourceInterface::BIRTHDATE => '1980-01-01',
            ResourceInterface::DRIVERS_LICENSE_AGE => '10',
            ResourceInterface::MILEAGE => 30000,
            ResourceInterface::YEARS_WITHOUT_DAMAGE => 10,
            ResourceInterface::POSTAL_CODE => '2024TJ',
            ResourceInterface::HOUSE_NUMBER => '12',
            ResourceInterface::COVERAGE => 'all',
            ResourceInterface::PAYMENT_PERIOD => 1,
            ResourceInterface::IDS => [$this->params[ResourceInterface::RESOURCE__ID]],
            ResourceInterface::OWN_RISK => implode(',',$ownRisks),
        ], true);

        if ($this->resultHasError($premiums)) {
            $this->setErrorString('Premium error: '. json_encode($premiums));
            return;
        }

        $result = [];
        $coverages = [];
        foreach ($premiums as $premium) {
            $result[] = [
                ResourceInterface::COVERAGE => $premium[ResourceInterface::COVERAGE],
                ResourceInterface::OWN_RISK => $premium[ResourceInterface::OWN_RISK],
                ResourceInterface::DEFAULT_VALUE => empty($coverages[$premium[ResourceInterface::COVERAGE]]), // First one is always '-1' aka default
            ];

            $coverages[$premium[ResourceInterface::COVERAGE]] = true;
        }

        $s = $this->coverageSort;
        usort($result, function ($a, $b) use ($s) {
            $cov = $s[$a[ResourceInterface::COVERAGE]] - $s[$b[ResourceInterface::COVERAGE]];
            return $cov == 0 ? $a[ResourceInterface::OWN_RISK] - $b[ResourceInterface::OWN_RISK] : $cov;
        });

        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }
}