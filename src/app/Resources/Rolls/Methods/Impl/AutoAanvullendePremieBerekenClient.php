<?php
/**
 * (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 17-aug-2010
 * Time: 0:19:25
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class AutoAanvullendePremieBerekenClient extends RollsAbstractSoapRequest
{

    protected $cacheDays = 30;

    protected $arguments = [
//        ResourceInterface::BIRTHDATE            => [
//            'rules'   => self::VALIDATION_REQUIRED_DATE,
//            'example' => '1988-11-09 (yyyy-mm-dd)',
//            'filter'  => 'filterNumber'
//        ],
//        ResourceInterface::DRIVERS_LICENSE_AGE  => [
//            'rules'   => 'required | integer',
//            'example' => '19',
//        ],
//        ResourceInterface::COVERAGE             => [
//            'rules'   => 'in:bc,vc,wa',
//            'example' => 'bc, vc or wa',
//        ],
//        ResourceInterface::OWN_RISK             => [
//            'rules'   => 'required | in:0,150,300,999',
//            'example' => '0,150,300,999',
//        ],
//        ResourceInterface::MILEAGE              => [
//            'rules'   => 'required | in:7500,10000,12000,15000,20000,25000,30000,90000',
//            'example' => '7500,10000,12000,15000,20000,25000,30000,90000',
//        ],
//        ResourceInterface::YEARS_WITHOUT_DAMAGE => [
//            'rules'   => 'required | number',
//            'example' => '10',
//        ],
//        ResourceInterface::POSTAL_CODE          => [
//            'rules'   => self::VALIDATION_REQUIRED_POSTAL_CODE,
//            'example' => '8014EH',
//            'filter'  => 'filterToUppercase'
//        ],
//        ResourceInterface::TYPE_ID              => [
//            'rules'   => 'number',
//            'example' => '84654',
//        ],
//        ResourceInterface::LICENSEPLATE         => [
//            'rules'   => self::VALIDATION_LICENSEPLATE,
//            'example' => '35-JDR-8',
//            'filter'  => 'filterAlfaNumber'
//        ],
//        ResourceInterface::CONSTRUCTION_DATE    => [
//            'rules'   => self::VALIDATION_DATE,
//            'example' => '2009-04-01',
//            'filter'  => 'filterNumber'
//        ],
//        ResourceInterface::HOUSE_NUMBER         => [
//            'rules'   => 'integer',
//            'example' => '21'
//        ],
//        ResourceInterface::IDS                  => [
//            'rules'   => 'array',
//            'example' => '[213,345345,2342,12341234,1234]',
//            'default' => '[]'
//        ]

    ];

    protected $outputFields = [
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_INITIAL,
        ResourceInterface::OWN_RISK,
        ResourceInterface::TOTAL_RATING,
        ResourceInterface::RATINGS,
        ResourceInterface::COVERAGE,
        ResourceInterface::PRICE_FEE,
    ];


    public function __construct()
    {
        parent::__construct();
        $this->documentRequest = true;
        $this->init(((app()->configure('resource_rolls')) ? '' : config('resource_rolls.functions.aanvullendepremie_auto_function')));
    }

    public function getResult()
    {
        return $this->getMotorizedPremieResult('car_option_list');
    }


    public function setParams(Array $params)
    {



    }



}