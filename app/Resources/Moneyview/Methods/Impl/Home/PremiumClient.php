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

namespace App\Resources\Moneyview\Methods\Impl\Home;

use App\Interfaces\ResourceInterface;
use App\Resources\Moneyview\Methods\MoneyviewAbstractSoapRequest;
use Config;

class PremiumClient extends MoneyviewAbstractSoapRequest
{
    protected $arguments = [
        ResourceInterface::BIRTHDATE              => [
            'rules'   => 'date | required',
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::POSTAL_CODE            => [
            'rules'   => 'postalcode | required',
            'example' => '8014EH',
            'filter'  => 'filterToUppercase'
        ],
        ResourceInterface::HOUSE_NUMBER           => [
            'rules'   => 'required | integer',
            'example' => '21'
        ],
        //        ResourceInterface::SUFFIX                 => [
        //            'rules'   => 'integer',
        //            'example' => '21',
        //            'filter'  => 'filterToUppercase'
        //        ],
        ResourceInterface::PERSONAL_CIRCUMSTANCES => [
            'rules'         => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'homeinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => 'personal_circumstances'
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ]
        ],
        ResourceInterface::TYPE                   => [
            'rules'         => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'homeinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::TYPE
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ]
        ],
        ResourceInterface::TYPE_OF_CONSTRUCTION   => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'homeinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::TYPE_OF_CONSTRUCTION
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'default'       => 'steen/hard'
        ],
        ResourceInterface::OUTSIDE                => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'homeinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::OUTSIDE
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'default'       => 'steen'
        ],
        ResourceInterface::SURFACE                => [
            'rules'   => 'required | integer',
            'example' => '160',
        ],
        ResourceInterface::KITCHEN_VALUE          => [
            'rules'   => 'required | choice:7000=normaal,14000=hoogwaardig,21000=luxe',
            'example' => '7000. Estimation, consider dropdown box with values: Normaal => 7000, Hoogwaardig => 14000, Luxe => 21000',
        ],
        ResourceInterface::BATHROOM_VALUE         => [
            'rules'   => 'required | choice:4500=normaal,9500=hoogwaardig,15000=luxe',
            'example' => '9500. Estimation, consider dropdown box with values: Normaal => 4500, Hoogwaardig => 9500, Luxe => 15000',
        ],
        ResourceInterface::FINISH                 => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'homeinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::FINISH
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'default'       => 'normaal'
        ],
        ResourceInterface::FOUNDATION             => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'homeinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::FOUNDATION
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'default'       => false
        ],
        ResourceInterface::ROOMS                  => [
            'rules'   => 'required | choice:1=1,2=2,3=3,4=4,5=5,6=6,7=7,8=8,9=9,10=meer dan 9',
            'example' => '2'
        ],
        ResourceInterface::CONSTRUCTION_DATE      => [
            'rules'   => 'required | choice:1900=tot en met 1900,1940=1901-1940,1960=1941-1960,1990=1961-1990,2014=1991-2014,2015=vanaf 2015',
            'example' => '1988-11-09 (yyyy-mm-dd), consider using dropodown box with values: =< 1900, 1901-1940, 1941-1960, 1961-1990,1991-2015,>= 2015 ',
            'filter'  => 'filterNumberAddMonthDay'

        ],
        //dakbedekking
        //fundering
        //keuken
        //erker of serre
        //afwerking woonkamer
    ];

    protected $outputFields = [
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_INITIAL,
        ResourceInterface::COVERAGE,
        ResourceInterface::OWN_RISK
    ];

    protected $cacheDays = 30;


    public function __construct()
    {

        parent::__construct('opstal', self::TASK_PROCESS_TWO);
        $this->documentRequest = true;
        $this->choiceLists     = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.choicelist'));
        $this->defaultParams   = [
            self::BEREKENING_MY_KEY          => ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.settings.code')),
            self::PAY_TERM_KEY               => self::PAY_TERM_MONTH,
            self::ASSUR_TAX_KEY              => self::ASSUR_TAX,
            self::CALCULATION_FORM_KEY       => self::CALCULATION_FORM,
            self::HOME_VALUE_ESTITMATION_KEY => self::HOME_VALUE_ESTITMATION,
        ];
    }

    public function setParams(Array $params)
    {
        $postalCodeNumbers = substr(trim($params[ResourceInterface::POSTAL_CODE]), 0, 4);
        $postalCodeChars = '';
        if (preg_match('~[a-z]{2}~i', $params[ResourceInterface::POSTAL_CODE], $matches))
            $postalCodeChars = $matches[0];

        //setdefaults

        $serviceParams = [
            'berekening_ingangsdatum'                                     => $this->getNow(),
            'persoon_geboortedatum'                                       => $params[ResourceInterface::BIRTHDATE],
            'persoon_postcode'                                            => $postalCodeNumbers,
            'persoon_pc_letters'                                          => $postalCodeChars,
            'Persoon_Huisnr'                                              => $params[ResourceInterface::HOUSE_NUMBER],
            $this->choiceLists[ResourceInterface::PERSONAL_CIRCUMSTANCES] => $params[ResourceInterface::PERSONAL_CIRCUMSTANCES],
            'Whs_Eigen_Bewoning'                                          => 'Ja',
            $this->choiceLists[ResourceInterface::TYPE]                   => $params[ResourceInterface::TYPE],
            $this->choiceLists[ResourceInterface::TYPE_OF_CONSTRUCTION]   => $params[ResourceInterface::TYPE_OF_CONSTRUCTION],
            $this->choiceLists[ResourceInterface::OUTSIDE]                => $params[ResourceInterface::OUTSIDE],
            'Whs_Woz'                                                     => 225000,
            'Whs_Opp'                                                     => $params[ResourceInterface::SURFACE],
            'Whs_M3_Woning'                                               => (3 * $params[ResourceInterface::SURFACE]),
            'Whs_Garage'                                                  => 'GEEN',
            'Whs_Berging'                                                 => 'GEEN',
            'Whs_Constructie'                                             => 'normaal',
            'Whs_Gevel'                                                   => 'normaal',
            'Whs_Keuken'                                                  => $params[ResourceInterface::KITCHEN_VALUE],
            'Whs_Badkamer'                                                => $params[ResourceInterface::BATHROOM_VALUE],
            $this->choiceLists[ResourceInterface::FINISH]                 => $params[ResourceInterface::FINISH],
            $this->choiceLists[ResourceInterface::FOUNDATION]             => $params[ResourceInterface::FOUNDATION],
            'Whs_Verd_Vloer'                                              => 'normaal',
            'WHS_Kamers'                                                  => $params[ResourceInterface::ROOMS],
            'Whs_Bestaand'                                                => 'Ja',
            'Whs_Bouwjaar'                                                => $params[ResourceInterface::CONSTRUCTION_DATE],
            'Whs_Glas'                                                    => 'Geen',
            'berekening_nulpremies'                                           => ($this->debug?'Ja':'Nee'),
            'berekening_ER_afwijking_waarde'                              => '0',
            'berekening_ER_afwijking_type'                                => 'Close',
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

    public function getResult()
    {
        $results = parent::getResult();
        if ($this->debug) {
            return $results;
        }
        $return  = [];
        foreach($results as $res){
            if(isset($res['NETTO_TERMIJNPREMIE_AR'])){
                $res[ResourceInterface::PRICE_DEFAULT] = $res['NETTO_TERMIJNPREMIE_AR'];
                $res[ResourceInterface::COVERAGE]      = ResourceInterface::COVERAGE_ALL_RISK;
                $return[]                              = $res;
                continue;
            }
            if(isset($res['NETTO_TERMIJNPREMIE_EUG'])){
                $res[ResourceInterface::PRICE_DEFAULT] = $res['NETTO_TERMIJNPREMIE_EUG'];
                $res[ResourceInterface::COVERAGE]      = ResourceInterface::COVERAGE_EXTENDED;
                $return[]                              = $res;
                continue;
            }
        }
        return $return;
    }


    //EUG = Extra Uitgebreide dekking
    //AR = All Risk Dekkin


}
