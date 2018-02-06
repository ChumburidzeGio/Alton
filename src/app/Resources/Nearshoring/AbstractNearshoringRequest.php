<?php
/**
 * User: Roeland Werring
 * Date: 18/05/15
 * Time: 16:49
 *
 */

namespace App\Resources\Nearshoring;


use App\Resources\AbstractMethodRequest;

class AbstractNearshoringRequest extends AbstractMethodRequest
{

    protected $cacheDays = false;

    protected $method;
    protected $params;

    public function __construct()
    {
    }


    public function executeFunction()
    {
        $this->soapClient = new NearshoringSoapClient();
        $this->strictStandardFields = false;
    }

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        $result =  json_decode(json_encode($this->soapClient->executeMethod($this->method, $this->params)), true);
        if (isset($result['AddSelectedInsuranceResult'], $result['AddSelectedInsuranceResult']['IsValid'], $result['AddSelectedInsuranceResult']['ErrorMessage'], $result['AddSelectedInsuranceResult']['Code'])) {
            if (isset( $result['AddSelectedInsuranceResult']['InsuranceRequestID']) && $result['AddSelectedInsuranceResult']['IsValid']){
                return ['iak_id' => $result['AddSelectedInsuranceResult']['InsuranceRequestID']];
            }
            $this->setErrorString($result['AddSelectedInsuranceResult']['ErrorMessage']);
            $return['error'] = $result['AddSelectedInsuranceResult']['ErrorMessage'];
            $return['code'] = $result['AddSelectedInsuranceResult']['Code'];
            return $return;
        }
        $return['error'] = 'Uknown soap error';
        return $return;
        
    }
}