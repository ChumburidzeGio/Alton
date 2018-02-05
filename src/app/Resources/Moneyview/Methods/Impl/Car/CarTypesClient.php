<?php

namespace App\Resources\Moneyview\Methods\Impl\Car;

use App\Interfaces\ResourceInterface;
use App\Resources\Moneyview\Methods\MoneyviewAbstractSoapRequest;
use Illuminate\Support\Facades\Config;

class CarTypesClient extends MoneyviewAbstractSoapRequest
{
    protected $arguments = [
         ResourceInterface::CONSTRUCTION_DATE => [
            'rules'   => 'date | required',
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber',
            'default' => '',
            'description' => 'autogegevens_bouwjaar - Bouwdatum voertuig',
        ],
        ResourceInterface::FUEL_TYPE_NAME => [
            'rules'         => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'carinsurance.moneyview',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::FUEL_TYPE_NAME,
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'description' => 'autogegevens_brandstof',
        ],
        ResourceInterface::BRAND_NAME => [
            'rules'       => 'string | required',
            'description' => 'autogegevens_merk',
        ],
        ResourceInterface::MODEL_NAME => [
            'rules'       => 'string | required',
            'description' => 'autogegevens_type',
        ],
    ];

    protected $outputFields = [
        //ResourceInterface::BRAND_NAME =>
    ];

    protected $cacheDays = false; //TODO: set to something when done developing
    protected $choiceLists;

    public function __construct()
    {
        parent::__construct('', self::TASK_LOOKUP);
        $this->choiceLists     = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.choicelist'));
        $this->defaultParams = [
            self::TASK_KEY          => self::TASK_PROCESS_TWO,
            self::GLOBAL_KEY        => 'Auto',
            self::FIELD_KEY         => 'uitvoering_keuzelijst',
        ];
    }

    public function setParams(Array $params)
    {
        $serviceParams = [
            'autogegevens_brandstof' => $params[ResourceInterface::FUEL_TYPE_NAME],
            'autogegevens_bouwjaar' => $params[ResourceInterface::CONSTRUCTION_DATE],
            'autogegevens_merk' => $params[ResourceInterface::BRAND_NAME],
            'autogegevens_type' => $params[ResourceInterface::MODEL_NAME],
        ];
        parent::setParams($serviceParams);
    }
}
