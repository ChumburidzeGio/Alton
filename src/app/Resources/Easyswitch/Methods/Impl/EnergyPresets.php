<?php
namespace App\Resources\Easyswitch\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;

/**
 * Basic Dummy request
 *
 * User: Roeland Werring
 * Date: 16/04/15
 * Time: 11:51
 *
 */
class EnergyPresets extends AbstractMethodRequest
{
    protected $cacheDays = false;

    private $params = [];
    // we use resource param validation
    public $resource2Request = true;

    //dummy function
    public function setParams(Array $params)
    {
        $this->params = $params;
        return;
    }

    public function getResult()
    {
        $presetName  = $this->params[ResourceInterface::NAME];
        $data= [];

        switch($presetName){
            case 'single':
                $data = [
                    'electricity_usage_high' => 750,
                    'electricity_usage_low' => 750,
                    'electricity_usage' => 1500,
                    'gas_usage' => 1000
                ];
                break;
            case 'duo':
                $data = [
                    'electricity_usage_high' => 1300,
                    'electricity_usage_low' => 1300,
                    'electricity_usage' => 2600,
                    'gas_usage' => 1100
                ];
                break;
            case 'family':
                $data = [
                    'electricity_usage_high' => 1750,
                    'electricity_usage_low' => 1750,
                    'electricity_usage' => 3500,
                    'gas_usage' => 1500
                ];
                break;
            case 'family_medium':
                $data = [
                    'electricity_usage_high' => 2250,
                    'electricity_usage_low' => 2250,
                    'electricity_usage' => 4500,
                    'gas_usage' => 1700
                ];
                break;
            case 'family_large':
                $data = [
                    'electricity_usage_high' => 2250,
                    'electricity_usage_low' => 2250,
                    'electricity_usage' => 5100,
                    'gas_usage' => 1800
                ];
                break;
        }
        if (isset($this->params[ResourceInterface::ELECTRICITY_USAGE_DISABLED]) && $this->params[ResourceInterface::ELECTRICITY_USAGE_DISABLED] === '1'){
            $data['electricity_usage_high'] = 0;
            $data['electricity_usage_low'] = 0;
            $data['electricity_usage'] = 0;
        }
        if (isset($this->params[ResourceInterface::GAS_USAGE_DISABLED]) && $this->params[ResourceInterface::GAS_USAGE_DISABLED] === '1'){
            $data['gas_usage'] = 0;
        }
        if ((isset($this->params[ResourceInterface::ELECTRICITY_USAGE_DISABLED]) && $this->params[ResourceInterface::ELECTRICITY_USAGE_DISABLED] === '1') &&
            (isset($this->params[ResourceInterface::GAS_USAGE_DISABLED]) && $this->params[ResourceInterface::GAS_USAGE_DISABLED] === '1'))
        {
            $this->setErrorString("Either gas or electricity should be enabled");
            return [];
        }
        return $data;
    }

    public function executeFunction()
    {
        //do nothing
    }
}
