<?php
/**
 * TODO:change surface by infofolio or bag database webservice
 *
 *
 * User: Roeland Werring
 * Date: 10/03/15
 * Time: 15:38
 *
 */

namespace App\Resources\Moneyview\Methods\Impl\LegalExpenses;

use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Resources\Moneyview\Methods\MoneyviewAbstractSoapRequest;
use Config;

class PremiumClient extends MoneyviewAbstractSoapRequest
{
    protected $insuredAmount;

    protected $arguments = [
        //        ResourceInterface::BIRTHDATE                  => [
        //            'rules'   => self::VALIDATION_REQUIRED_DATE,
        //            'example' => '1988-11-09 (yyyy-mm-dd)',
        //            'filter'  => 'filterNumber'
        //        ],
        ResourceInterface::PERSON_SINGLE              => [
            'rules'         => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'legalexpensesinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::PERSON_SINGLE
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ]
        ],
        ResourceInterface::CALCULATION_INSURED_AMOUNT => [
            'rules' => 'integer'
        ],
        //0, 25000, 100.000, 200.000 en onbeperk
        ResourceInterface::BOAT_COVERAGE              => [
            'rules'   => 'in:0,25000,100000,200000,onbeperkt',
            'default' => 0
        ],
        //this one should be not shown
        ResourceInterface::TRAFFIC_COVERAGE           => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'legalexpensesinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::TRAFFIC_COVERAGE
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'default'       => 'alle verkeersdeelnemers'
        ],
        ResourceInterface::INSURE_CONSUMER            => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter'  => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::INSURE_INCOME              => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter'  => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::INSURE_CAPITAL             => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter'  => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::INSURE_HOUSING             => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter'  => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::INSURE_TRAFFIC             => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter'  => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::HOUSE_OWNER                => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter'  => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::BIRTHDATE                  => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber',
            'default' => '19700101'
        ],
        ResourceInterface::ENABLE_COMPOSER            => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'default' => 'false'
        ],
        ResourceInterface::IDS                        => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
            'default' => []
        ],
    ];

    protected $cacheDays = 30;

    protected $outputFields = [
        ResourceInterface::COVERAGE_DAMAGE_REDRESS,
        ResourceInterface::COVERAGE_CONSUMER,
        ResourceInterface::COVERAGE_INCOME,
        ResourceInterface::COVERAGE_TRAFFIC,
        ResourceInterface::COVERAGE_HOUSING,
        ResourceInterface::COVERAGE_YACHT,
        ResourceInterface::COVERAGE_TAX_LAW,
        ResourceInterface::COVERAGE_LEASE,
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_INITIAL,
        ResourceInterface::OWN_RISK,
        ResourceInterface::COVERAGE_AMOUNT,
        ResourceInterface::INSURE_CONSUMER,
        ResourceInterface::INSURE_INCOME,
        ResourceInterface::INSURE_TRAFFIC,
        ResourceInterface::INSURE_TAX_LAW,
        ResourceInterface::INSURE_DAMAGE_REDRESS,
        ResourceInterface::INSURE_HOUSING,
        ResourceInterface::INSURE_YACHT,
        ResourceInterface::INSURE_CONSUMER_DAMAGE_REDRESS_HOUSING,
        ResourceInterface::INSURE_CONSUMER_DAMAGE_REDRESS_HOUSING_INCOME_TRAFFIC_YACHT,
        ResourceInterface::INSURE_CONSUMER_DAMAGE_REDRESS_HOUSING_TRAFFIC_YACHT,
        ResourceInterface::INSURE_CONSUMER_DAMAGE_REDRESS_INCOME_YACHT,
        ResourceInterface::INSURE_CONSUMER_DAMAGE_REDRESS_TRAFFIC_YACHT,
        ResourceInterface::INSURE_CONSUMER_DAMAGE_REDRESS_YACHT,
        ResourceInterface::INSURE_CONSUMER_HOUSING,
        ResourceInterface::INSURE_CONSUMER_HOUSING_YACHT,
        ResourceInterface::INSURE_DAMAGE_REDRESS_TRAFFIC_YACHT,
        ResourceInterface::INSURE_TRAFFIC_YACHT,
        ResourceInterface::PRICE_SUB_TOTAL,
        ResourceInterface::PRICE_SURCHARGES,
        ResourceInterface::PRICE_INSURANCE_TAX,
    ];


    public function __construct()
    {
        parent::__construct('RECHTSBIJSTAND', self::TASK_PROCESS_ONE);
        $this->documentRequest      = true;
        $this->strictStandardFields = false;
        $this->choiceLists          = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.choicelist'));
        $this->defaultParams        = [
            self::BEREKENING_MY_KEY => ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.settings.code')),
            self::PAY_TERM_KEY      => 'Maand',
            self::ASSUR_TAX_KEY     => self::ASSUR_TAX,
        ];
    }

    public function setParams(Array $params)
    {
        $this->insuredAmount = $params[ResourceInterface::CALCULATION_INSURED_AMOUNT];
        //setdefaults
        $serviceParams = [
            'berekening_ingangsdatum'                                         => $this->getNow(),
            'persoon_geboortedatum'                                           => $params[ResourceInterface::BIRTHDATE],
            'persoon_postcode'                                                => '1012',
            'persoon_pc_letters'                                              => 'AA',
            'Persoon_Huisnr'                                                  => '35',
            'Berekening_Er'                                                   => '50',
            $this->choiceLists[ResourceInterface::PERSON_SINGLE]              => $params[ResourceInterface::PERSON_SINGLE],
            'Verzekering_Basisdekking'                                        => 'Ja',
            'Verzekering_Consument'                                           => $params[ResourceInterface::INSURE_CONSUMER],
            'Verzekering_Inkomen'                                             => $params[ResourceInterface::INSURE_INCOME],
            'Verzekering_Vermogen'                                            => $params[ResourceInterface::INSURE_CAPITAL],
            'Verzekering_Verkeer'                                             => $params[ResourceInterface::INSURE_TRAFFIC],
            'Verzekering_Wonen'                                               => $params[ResourceInterface::INSURE_HOUSING],
            $this->choiceLists[ResourceInterface::TRAFFIC_COVERAGE]           => $params[ResourceInterface::TRAFFIC_COVERAGE],
            'PERSOON_MOTORRIJTUIGEN_AANTAL'                                   => $params[ResourceInterface::INSURE_TRAFFIC] == 'ja' ? '1' : '0',
            $this->choiceLists[ResourceInterface::CALCULATION_INSURED_AMOUNT] => $params[ResourceInterface::CALCULATION_INSURED_AMOUNT],
            //slaan voertuigen ff helemaal over
            'Verzekering_Vaartuig'                                            => ($params[ResourceInterface::BOAT_COVERAGE] > 0) ? 'ja' : 'nee',
            'vaartuig_cataloguswaarde'                                        => $params[ResourceInterface::BOAT_COVERAGE],
            'PERSOON_EIGENWONING'                                             => $params[ResourceInterface::HOUSE_OWNER],
            'berekening_nulpremies'                                           => ($this->debug ? 'Ja' : 'Nee'),
            'berekening_ER_afwijking_waarde'                                  => '0',
            'berekening_ER_afwijking_type'                                    => 'Close',
        ];
        parent::setParams($serviceParams);
    }

    /**
     * @param $params
     *
     * @return string
     */
    protected function setDefault($key, $params, $default)
    {
        return isset($params[$key]) ? $params[$key] : $default;
    }

    /**
     * Filter op erzekerd bedrag
     */
    public function getResult()
    {
        if($this->debug){
            return parent::getResult();
        }

        $result    = parent::getResult();
        $returnArr = [];
        foreach($result as $res){
            if($res['VERZEKERDBEDRAG'] >= $this->insuredAmount){
                $returnArr[] = $res;
            }
        }
        return $returnArr;
    }


    //EUG = Extra Uitgebreide dekking
    //AR = All Risk Dekkin

}
