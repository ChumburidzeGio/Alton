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

namespace App\Resources\Moneyview\Methods\Impl\Contents;

use App\Interfaces\ResourceInterface;
use App\Resources\Moneyview\Methods\MoneyviewAbstractSoapRequest;
use Config;

class PremiumClient extends MoneyviewAbstractSoapRequest
{
    protected $cacheDays = 30;

    protected $arguments = [
        ResourceInterface::BIRTHDATE              => [
            'rules'   => self::VALIDATION_REQUIRED_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::POSTAL_CODE            => [
            'rules'   => self::VALIDATION_REQUIRED_POSTAL_CODE,
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
                'resource' => 'contentsinsurance',
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
                'resource' => 'contentsinsurance',
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
                'resource' => 'contentsinsurance',
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
                'resource' => 'contentsinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::OUTSIDE
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'default'       => 'steen'
        ],
        ResourceInterface::ROOMS                  => [
            'rules'   => 'required | choice:1=1,2=2,3=3,4=4,5=5,6=6,7=7,8=8,9=9,10=meer dan 9',
            'example' => '2'
        ],
        ResourceInterface::OWNER                  => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter'  => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::SECURITY               => [
            'rules'         => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'contentsinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::SECURITY
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ]
        ],
        ResourceInterface::SURFACE                => [
            'rules'   => 'required | choice:90=tot en met 90m2,140=91m2-140m2,190=141m2-190m2,300=191m2-300m2,400=meer dan 400m2',
            'example' => '250. Estimation, consider dropdown box with Independer values: <= 90, 90-140, 141-190, 191-300, > 300',
        ],
        ResourceInterface::MONTHLY_NET_INCOME     => [
            'rules'   => 'required | choice:1000=tot en met euro 1000,2000=euro1001-euro2000,3000=euro2001-euro3000,4850=3001-4850,5500=meer dan 4850',
            'example' => '2000. Estimation, consider dropdown box with Independer values: (<1000, 1000-2000, 2001-3000, 3001-3000, 4001-4850, >4851)'
        ],

    ];

    protected $outputFields = [
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::COVERAGE,
        ResourceInterface::PRICE_INITIAL,
        ResourceInterface::OWN_RISK,
        ResourceInterface::UNDERINSURANCE,
    ];


    public function __construct()
    {
        $this->documentRequest = true;
        parent::__construct('Inboedel', self::TASK_PROCESS_TWO);
        $this->choiceLists   = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.choicelist'));
        $this->defaultParams = [
            self::BEREKENING_MY_KEY             => ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.settings.code')),
            self::PAY_TERM_KEY                  => self::PAY_TERM_MONTH,
            self::ASSUR_TAX_KEY                 => self::ASSUR_TAX,
            self::CALCULATION_FORM_KEY          => self::CALCULATION_FORM,
            self::CONTENT_VALUE_ESTITMATION_KEY => self::CONTENT_VALUE_ESTITMATION,
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
            'berekening_ingangsdatum'                                   => $this->getNow(),
            'persoon_geboortedatum'                                     => $params[ResourceInterface::BIRTHDATE],
            'persoon_postcode'                                          => $postalCodeNumbers,
            'persoon_pc_letters'                                        => $postalCodeChars,
            'Persoon_Huisnr'                                            => $params[ResourceInterface::HOUSE_NUMBER],
            'persoonlijke_omstandigheden'                               => $params[ResourceInterface::PERSONAL_CIRCUMSTANCES],
            $this->choiceLists[ResourceInterface::TYPE]                 => $params[ResourceInterface::TYPE],
            $this->choiceLists[ResourceInterface::TYPE_OF_CONSTRUCTION] => $params[ResourceInterface::TYPE_OF_CONSTRUCTION],
            $this->choiceLists[ResourceInterface::OUTSIDE]              => $params[ResourceInterface::OUTSIDE],
            'WHS_Kamers'                                                => $params[ResourceInterface::ROOMS],
            'WHS_Eigenaar'                                              => $params[ResourceInterface::OWNER],
            'WHS_BEVEILIGING'                                           => $params[ResourceInterface::SECURITY],
            'Whs_Glas'                                                  => 'geen',
            'WHS_Opp'                                                   => $params[ResourceInterface::SURFACE],
            'Whs_Woz'                                                   => '225000',
            //            $this->choiceLists[ResourceInterface::OWN_RISK]             => $params[ResourceInterface::OWN_RISK],
            'Persoon_Inkomen'                                           => $params[ResourceInterface::MONTHLY_NET_INCOME],
            'berekening_nulpremies'                                     => ($this->debug ? 'Ja' : 'Nee'),
            'berekening_ER_afwijking_waarde'                            => '0',
            'berekening_ER_afwijking_type'                              => 'Close',
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
        if($this->debug){
            return $results;
        }
        $return = [];
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

}
