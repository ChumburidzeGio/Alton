<?php
/**
 * User: Roeland Werring
 * Date: 09/02/17
 * Time: 12:03
 *
 */

namespace App\Resources\Infofolio\Methods;

use App\Resources\Infofolio\AbstractInfofolioRequest;

class CheckAddress extends AbstractInfofolioRequest {
    public function __construct() {
        parent::__construct('addressinfo');

    }

    public function getResult()
    {
        return $this->result;
//        //
//        $resultKey = 'GetRealEstateObjectsResult.RealEstateObject.Attributes.ObjectAttributeBase';
//        if ( !isset($this->result['GetRealEstateObjectsResult'])
//             || count($this->result['GetRealEstateObjectsResult']) == 0
//             || !array_has($this->result, $resultKey)){
//            $this->result = [];
//            return [];
//        }
//        $this->result  = array_get($this->result,$resultKey);
//        return parent::getResult();
    }
}
