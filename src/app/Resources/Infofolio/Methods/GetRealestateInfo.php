<?php
/**
 * User: Roeland Werring
 * Date: 09/02/17
 * Time: 12:03
 *
 */

namespace App\Resources\Infofolio\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Infofolio\AbstractInfofolioRequest;

class GetRealestateInfo extends AbstractInfofolioRequest
{
    public function __construct()
    {
        parent::__construct('realestateinfo');

    }

    public function setParams(Array $params)
    {
        if (!isset($params['zipcode'])) {
            $this->setErrorString("not valid params");
        }
        parent::setParams($params);
    }


    public function getResult()
    {
        //
        $resultKey = 'GetRealEstateObjectsResult.RealEstateObject.Attributes.ObjectAttributeBase';
        if( ! isset($this->result['GetRealEstateObjectsResult']) || count($this->result['GetRealEstateObjectsResult']) == 0 || ! array_has($this->result, $resultKey)){
            $this->result = [];
            return [];
        }
        $this->result = array_get($this->result, $resultKey);

        $returnArr = [];
        foreach($this->result as $result){
            if( ! isset($result["Name"]) || ! isset($result["Value"])){
                continue;
            }
            $returnArr[$result["Name"]] = $result["Value"];
        }

        $this->result                                       = $returnArr;

        if(!empty($returnArr))
        {
            $this->result[ResourceInterface::INFOFOLIO] = true;
        }

        $this->result[ResourceInterface::HOUSE_IS_MONUMENT] = isset($this->result['monumentaanduiding_code']) ? 1 : 0;

        //
        $this->result[ResourceInterface::LIVING_AREA_TOTAL_ROUNDED] = array_get($this->result, 'gebruiksoppervlakte', 0);
        $this->result[ResourceInterface::CONTENTS_ESTIMATE_ROUNDED] = $this->roundToArray(array_get($this->result, 'inboedelwaarde_basis_indicatie', 0), [100000, 150000, 200000]);
        $this->result[ResourceInterface::PARCEL_SIZE_ROUNDED]       = $this->roundToArray(array_get($this->result, 'grondoppervlakte_bijgebouwen', 0), [90, 140, 190, 300, 400]);
        $this->result[ResourceInterface::PARCEL_QUESTION]           = array_get($this->result, 'grondoppervlakte_bijgebouwen', 0) ? 1 : 0;
        return $this->result;
    }


    private function roundToArray($input, $array)
    {
        foreach($array as $val){
            if($input > $val){
                continue;
            }
            return $val;
        }
        return $array[count($array) - 1];

    }
}
