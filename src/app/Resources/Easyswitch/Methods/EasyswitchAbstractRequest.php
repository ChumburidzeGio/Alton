<?php
namespace App\Resources\Easyswitch\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\BasicAuthRequest;
use Config;


/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */
class EasyswitchAbstractRequest extends BasicAuthRequest
{
    protected $gasType = 'no_preference';
    protected $contractType = '';
    //Let op: indien de method POST wordt gebruikt richting onze API, moet de content-Type op
    //‘multipart/form-data’ staan.


    public function __construct($methodUrl, $typeRequest = 'get')
    {
        $url = ((app()->configure('resource_easyswitch')) ? '' : config('resource_easyswitch.settings.url'));
        $url .= ((app()->configure('resource_easyswitch')) ? '' : config('resource_easyswitch.settings.energy_sub'));
        $url .= $methodUrl;



        $this->basicAuthService = [
            'type_request' => $typeRequest,
            'method_url' => $url,
            'username' => ((app()->configure('resource_easyswitch')) ? '' : config('resource_easyswitch.settings.username')),
            'password' => ((app()->configure('resource_easyswitch')) ? '' : config('resource_easyswitch.settings.password'))
        ];
    }

    public function compareFilterParamKeys(Array $params)
    {
        $serviceParams = $params;


        $energyArr       = explode('-', $serviceParams[ResourceInterface::ENERGY_TYPE]);
        $electricityType = $energyArr[0];
        $this->gasType   = (count($energyArr) == 2) ? $energyArr[1] : $energyArr[0];

        unset($serviceParams[ResourceInterface::ENERGY_TYPE]);

        $serviceParams[ResourceInterface::TARIFF_TYPE] = $this->getTariffType($serviceParams[ResourceInterface::TARIFF_TYPE]);
        $serviceParams[ResourceInterface::ELECTRICITY_TYPE]         = $this->getElectricityType($electricityType);
        $serviceParams[ResourceInterface::CONTRACT_DURATION_MONHTS] = $this->getContractDuration($params, ResourceInterface::CONTRACT_DURATION_MONHTS);
        $serviceParams[ResourceInterface::BUSINESS]                 = (isset($serviceParams[ResourceInterface::BUSINESS]) && (($serviceParams[ResourceInterface::BUSINESS] == 'true') || (is_numeric($serviceParams[ResourceInterface::BUSINESS]) && $serviceParams[ResourceInterface::BUSINESS] == 1))) ? 'zakelijk' : 'consument';
        return parent::filterParamKeys($serviceParams);
    }



    public function getResult()
    {
        return $this->result['data'];
    }

    private function getTariffType($key)
    {
        switch($key){
            case ResourceInterface::FIXED:
                return "vast";
                break;
            case ResourceInterface::VARIABLE:
                return "variabel";
                break;
            default:
                return "";
        }
    }

    private function getElectricityType($key)
    {
        switch($key){
            case "green":
                return "true";
                break;
            case "grey":
                return "false";
                break;
            default:
                return "";
        }
    }

    private function getContractDuration($param, $key)
    {
        if( ! isset($param[$key])){
            return "-1";
        }

        switch($param[$key]){
            case "no_preference":
                return "-1";
                break;
            case "continuously":
                return "0";
                break;
            default:
                return $param[$key];
        }
    }
}