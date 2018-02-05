<?php

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;

class CarByBrandModelTypeID extends AbstractMethodRequest
{
    public $cacheDays = false;
    public $resource2Request = true;

    protected $params = [];
    protected $result;

    public function setParams(Array $params)
    {
        if (isset($params[ResourceInterface::CONSTRUCTION_DATE])) {
            $params[ResourceInterface::CONSTRUCTION_DATE_YEAR] = date('Y', strtotime($params[ResourceInterface::CONSTRUCTION_DATE]));
            $params[ResourceInterface::CONSTRUCTION_DATE_MONTH] = (int)date('m', strtotime($params[ResourceInterface::CONSTRUCTION_DATE]));
        }
        if (!isset($params[ResourceInterface::CONSTRUCTION_DATE]))
            $params[ResourceInterface::CONSTRUCTION_DATE] = $params[ResourceInterface::CONSTRUCTION_DATE_YEAR] .'-'. $params[ResourceInterface::CONSTRUCTION_DATE_MONTH] .'-01';

        $this->params = $params;
    }

    public function executeFunction()
    {
        $rollsData = [
            ResourceInterface::CONSTRUCTION_DATE_YEAR => $this->params[ResourceInterface::CONSTRUCTION_DATE_YEAR],
            ResourceInterface::CONSTRUCTION_DATE_MONTH => $this->params[ResourceInterface::CONSTRUCTION_DATE_MONTH],
            ResourceInterface::CONSTRUCTION_DATE => $this->params[ResourceInterface::CONSTRUCTION_DATE],
        ];

        if (isset($this->params[ResourceInterface::BRAND_ID])) {
            $brands = $this->internalRequest('carinsurance', 'brands', [
                ResourceInterface::CONSTRUCTION_DATE_YEAR => $this->params[ResourceInterface::CONSTRUCTION_DATE_YEAR],
                ResourceInterface::CONSTRUCTION_DATE_MONTH => $this->params[ResourceInterface::CONSTRUCTION_DATE_MONTH],
            ]);
            foreach ($brands as $brand) {
                if ($brand['name'] == $this->params[ResourceInterface::BRAND_ID]) {
                    $rollsData[ResourceInterface::BRAND_NAME] = $brand['title'];
                    break;
                }
            }
            if (!isset($rollsData[ResourceInterface::BRAND_NAME]))
                $this->setErrorString('Brand ID `'. $this->params[ResourceInterface::BRAND_ID] .'` is unknown.');
        }

        if (isset($this->params[ResourceInterface::MODEL_ID])) {
            $models = $this->internalRequest('carinsurance', 'models', [
                ResourceInterface::CONSTRUCTION_DATE_YEAR => $this->params[ResourceInterface::CONSTRUCTION_DATE_YEAR],
                ResourceInterface::CONSTRUCTION_DATE_MONTH => $this->params[ResourceInterface::CONSTRUCTION_DATE_MONTH],
                ResourceInterface::BRAND_ID => $this->params[ResourceInterface::BRAND_ID],
            ]);
            foreach ($models as $model) {
                if ($model['name'] == $this->params[ResourceInterface::MODEL_ID]) {
                    $rollsData[ResourceInterface::MODEL_NAME] = $model['title'];
                    break;
                }
            }
            if (!isset($rollsData[ResourceInterface::MODEL_NAME]))
                $this->setErrorString('Model ID `'. $this->params[ResourceInterface::MODEL_ID] .'` in combination with brand id `'. $this->params[ResourceInterface::BRAND_ID] .'` is unknown.');
        }

        if (isset($this->params[ResourceInterface::MODEL_ID], $this->params[ResourceInterface::TYPE_ID])) {
            $types = $this->internalRequest('carinsurance', 'types', [
                ResourceInterface::CONSTRUCTION_DATE_YEAR => $this->params[ResourceInterface::CONSTRUCTION_DATE_YEAR],
                ResourceInterface::CONSTRUCTION_DATE_MONTH => $this->params[ResourceInterface::CONSTRUCTION_DATE_MONTH],
                ResourceInterface::MODEL_ID => $this->params[ResourceInterface::MODEL_ID],
            ]);
            foreach ($types as $type) {
                if ($type['name'] == $this->params[ResourceInterface::TYPE_ID]) {

                    $typeData = array_except($type, [
                        ResourceInterface::NAME,
                        ResourceInterface::LABEL,
                        ResourceInterface::RESOURCE_ID,
                        ResourceInterface::TITLE,
                    ]);

                    $rollsData += [
                        ResourceInterface::TYPE_NAME => $type['title'],
                    ] + $typeData;
                    break;
                }
            }
            if (!isset($rollsData[ResourceInterface::TYPE_NAME]))
                $this->setErrorString('Type ID `'. $this->params[ResourceInterface::TYPE_ID] .'` in combination with model id `'. $this->params[ResourceInterface::MODEL_ID] .'` is unknown.');
        }

        $this->result = $rollsData;
    }

    public function getResult()
    {
        return $this->result;
    }
}