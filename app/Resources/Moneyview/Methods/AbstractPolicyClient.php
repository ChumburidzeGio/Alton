<?php
/**
 * User: Roeland Werring
 * Date: 13/04/15
 * Time: 15:12
 *
 */

namespace App\Resources\Moneyview\Methods;

use App\Interfaces\ResourceInterface;
use Config;

class AbstractPolicyClient extends MoneyviewAbstractSoapRequest
{
    protected $cacheDays = false;

    protected $arguments = [
        ResourceInterface::ID => [
            'rules' => 'required | number',
            'example' => '9314'
        ],
        ResourceInterface::PRODUCT_SPEC => [
            'rules' => 'string',
        ],
    ];

    protected $moneyviewModuleName = '';
    protected $serviceName = '';

    public function __construct()
    {
        parent::__construct($this->moneyviewModuleName, self::TASK_COVERAGE);
        $this->populateRequest = true;
    }

    public function setParams(Array $params)
    {
        $this->strictStandardFields = false;
        if (($prod = $this->getProductNameById($params[ResourceInterface::ID])) === FALSE) {
            $this->setErrorString('ID not valid');
            return [];
        }
        $paramXml = $this->xmlField('local', $this->replaceSpecialChars($prod[ResourceInterface::COMP_NAME]));
        $specific = $this->replaceSpecialChars($prod[ResourceInterface::SPEC_NAME]);
        if ($specific != 'default') {
            $paramXml .= $this->xmlField('specific', $specific);
        }
        if (!empty($params[ResourceInterface::PRODUCT_SPEC]))
            $paramXml .= $this->xmlField('productspec', $params[ResourceInterface::PRODUCT_SPEC]);
        $dekking = $this->xmlField('dekking', $paramXml);
        $this->fillVar('PARAMS', $dekking, true);
    }

    private function getProductNameById($id)
    {
        $res = $this->internalRequest($this->getRequestType(), 'products');
        foreach ($res as $row) {
            if ($row[ResourceInterface::RESOURCE_ID] == $id) {
                return $row;
            }
        }
        return false;
    }


    public function getResult()
    {
        return $this->getPolicyResult();
    }

    private function replaceSpecialChars($inputString)
    {
        $replaceArray = ["&" => "&amp;"];
        $str = $inputString;
        foreach($replaceArray as $key => $val) {
            $str = str_replace($key, $val, $str);
        }
        return $str;
    }


}