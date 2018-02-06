<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Easyswitch\Methods\Impl;


use App\Interfaces\ResourceInterface;
use App\Resources\Easyswitch\Methods\EasyswitchAbstractRequest;

class EnergyProductList extends EasyswitchAbstractRequest
{
    protected $arguments = [
        ResourceInterface::ADD_NO_CHOICE => [
            'rules' => self::VALIDATION_BOOLEAN,
            'default' => 'false'
        ]
    ];

    private $nochoice = false;


    protected $cacheDays = 30;

    public function __construct()
    {
        parent::__construct('/leveranciers/');
    }

    public function setParams(Array $params)
    {
        if($params['add_no_choice'] == 'true'){
            $this->nochoice = true;
        }

    }

    public function getResult()
    {
        $data = parent::getResult();
        if ($this->nochoice) {
            array_unshift($data, ['id' => '-1', 'naam'=> 'geen', 'alias' => 'Geen (i.v.m. verhuizing)']);
        }
//        foreach($data as $key => &$val){
//            //standard commsions
//            $val[ResourceInterface::COMMISSION_TOTAL] = 28;
//            $val[ResourceInterface::COMMISSION_PARTNER] = 22;
//
//        }
        return $data;
    }

}