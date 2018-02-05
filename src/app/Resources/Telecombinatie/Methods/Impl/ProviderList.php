<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Telecombinatie\Methods\Impl;


use App\Interfaces\ResourceInterface;
use App\Resources\Telecombinatie\Methods\TelecombinatieAbstractRequest;

class ProviderList extends TelecombinatieAbstractRequest
{
    protected $cacheDays = false;
    
    private $list = false;

    protected $arguments = [
        ResourceInterface::CODE => [
            'rules'   => 'regex:/[A-Z]/',
            'example' => 'ZIGGO',
            'filter'  => 'filterToUppercase'
        ],
        ResourceInterface::ADD_NO_CHOICE => [
            'rules' => self::VALIDATION_BOOLEAN,
            'default' => 'false'
        ]
    ];

    private $nochoice = false;

    public function __construct()
    {
        parent::__construct('/api/content/providers');
         $this->strictStandardFields = false;
    }

    public function setParams(Array $params)
    {

        if($params['add_no_choice'] == 'true'){
            $this->nochoice = true;
        }

        if (!isset ($params[ResourceInterface::CODE])) {
            $this->list = true;
            parent::setParams($params);
            return;
        }
        $this->basicAuthService['method_url'] = $this->basicAuthService['method_url'].'/'.$params[ResourceInterface::CODE];
        parent::setParams($params);
    }

    /**
     * Request list, and enrich this with individual request data;
     * @return array
     */
    public function getResult() {
        if (!$this->list) {
            return $this->convertFields(parent::getResult());
        }
        $result = parent::getResult();
        $returnArr = [];
        foreach ($result as $res) {
            $returnArr[$res['code']] = $this->internalRequest('simonly3','providers', ['code' => $res['code']]);
        }
        if ($this->nochoice) {
            array_unshift($returnArr, ['name'=> 'geen', 'description' => 'Geen', 'active' => true, 'code' => -1]);
        }
        return $returnArr;
    }


}