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

namespace App\Resources\Moneyview\Methods\Impl\ShorttermTravel;

use App\Interfaces\ResourceInterface;
use App\Resources\Moneyview\Methods\MoneyviewAbstractSoapRequest;
use Config;

class PremiumClient extends MoneyviewAbstractSoapRequest
{
    protected $arguments = [
        ResourceInterface::BIRTHDATE => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],
        ResourceInterface::BIRTHDATE_PARTNER => [
            'rules' => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],
        ResourceInterface::BIRTHDATE_CHILD_1 => [
            'rules' => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],

        ResourceInterface::BIRTHDATE_CHILD_2 => [
            'rules' => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],

        ResourceInterface::BIRTHDATE_CHILD_3 => [
            'rules' => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],

        ResourceInterface::BIRTHDATE_CHILD_4 => [
            'rules' => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],

        ResourceInterface::BIRTHDATE_CHILD_5 => [
            'rules' => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],
        ResourceInterface::EFFECTIVE_DATE => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
            'example' => '2015-11-09 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],
        ResourceInterface::END_DATE => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
            'example' => '2015-11-21 (yyyy-mm-dd)',
            'filter' => 'filterNumber'
        ],
        ResourceInterface::COVERAGE_AREA => [
            'rules' => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'shorttermtravelinsurance',
                'method' => 'list',
                'params' => [
                    'list' => ResourceInterface::COVERAGE_AREA
                ],
                'field' => ResourceInterface::SPEC_NAME
            ]
        ],

        ResourceInterface::TOTAL_LUGGAGE => [
            'rules' => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'shorttermtravelinsurance',
                'method' => 'list',
                'params' => [
                    'list' => ResourceInterface::TOTAL_LUGGAGE
                ],
                'field' => ResourceInterface::SPEC_NAME
            ]
        ],

        ResourceInterface::TOTAL_SCUBA_DIVING => [
            'rules' => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'shorttermtravelinsurance',
                'method' => 'list',
                'params' => [
                    'list' => ResourceInterface::TOTAL_SCUBA_DIVING
                ],
                'field' => ResourceInterface::SPEC_NAME
            ]
        ],

        ResourceInterface::TOTAL_CASH_CHEQUES => [
            'rules' => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'shorttermtravelinsurance',
                'method' => 'list',
                'params' => [
                    'list' => ResourceInterface::TOTAL_CASH_CHEQUES
                ],
                'field' => ResourceInterface::SPEC_NAME
            ]
        ],

        ResourceInterface::COVERAGE_WINTER_SPORTS => [
            'rules' => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter' => 'convertToDutchBool',
            'default' => 0

        ],
        ResourceInterface::COVERAGE_SCUBA_DIVING => [
            'rules' => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter' => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::COVERAGE_DANGEROUS_SPORTS => [
            'rules' => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter' => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::COVERAGE_HEALTHCARE => [
            'rules' => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter' => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::COVERAGE_ACCIDENTS => [
            'rules' => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter' => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::COVERAGE_DRIVERS_HELP => [
            'rules' => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter' => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::COVERAGE_BUSINESS_TRIPS => [
            'rules' => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter' => 'convertToDutchBool',
            'default' => 0
        ],
        ResourceInterface::COVERAGE_CANCELLATION => [
            'rules' => self::VALIDATION_BOOLEAN,
            'example' => 'true',
            'filter' => 'convertToDutchBool',
            'default' => 0
        ],
    ];

    protected $outputFields = [
        ResourceInterface::COVERAGE_AREA,
        ResourceInterface::COVERAGE_LUGGAGE,
        ResourceInterface::OWN_RISK,
        ResourceInterface::COVERAGE_SCUBA_DIVING,
        ResourceInterface::COVERAGE_CASH_CHEQUES,
        ResourceInterface::COVERAGE_CANCELLATION,
        ResourceInterface::COVERAGE_PERIOD,
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_INITIAL,
    ];


    protected $cacheDays = 30;


    public function __construct()
    {

        parent::__construct('reiskort', self::TASK_PROCESS_ONE);
        $this->documentRequest = true;
        $this->choiceLists = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.choicelist'));
        $this->defaultParams = [
            self::BEREKENING_MY_KEY => ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.settings.code')),
            self::PAY_TERM_KEY => self::PAY_TERM,
            self::ASSUR_TAX_KEY => self::ASSUR_TAX,
        ];
    }

    public function setParams(Array $params)
    {
        //setdefaults

        $serviceParams = [
            'berekening_ingangsdatum' => $params[ResourceInterface::EFFECTIVE_DATE],
            'Berekening_Rekendatum' => $params[ResourceInterface::EFFECTIVE_DATE],
            'Berekening_Boekingsdatum' => $params[ResourceInterface::EFFECTIVE_DATE],
            'Berekening_Einddatum' => $params[ResourceInterface::END_DATE],
            'persoon_postcode' => '1012',
            'Berekening_Er' => '50',
            'Persoon_Geboortedatum' => $params[ResourceInterface::BIRTHDATE],
            'Persoon_Geboortedatum_Partner' => isset($params[ResourceInterface::BIRTHDATE_PARTNER]) ? $params[ResourceInterface::BIRTHDATE_PARTNER] : '',
            'Persoon_Geboortedatum_Kind1' => isset($params[ResourceInterface::BIRTHDATE_CHILD_1]) ? $params[ResourceInterface::BIRTHDATE_CHILD_1] : '',
            'Persoon_Geboortedatum_Kind2' => isset($params[ResourceInterface::BIRTHDATE_CHILD_2]) ? $params[ResourceInterface::BIRTHDATE_CHILD_2] : '',
            'Persoon_Geboortedatum_Kind3' => isset($params[ResourceInterface::BIRTHDATE_CHILD_3]) ? $params[ResourceInterface::BIRTHDATE_CHILD_3] : '',
            'Persoon_Geboortedatum_Kind4' => isset($params[ResourceInterface::BIRTHDATE_CHILD_4]) ? $params[ResourceInterface::BIRTHDATE_CHILD_4] : '',
            'Persoon_Geboortedatum_Kind5' => isset($params[ResourceInterface::BIRTHDATE_CHILD_5]) ? $params[ResourceInterface::BIRTHDATE_CHILD_5] :'',
            $this->choiceLists[ResourceInterface::COVERAGE_AREA] => $params[ResourceInterface::COVERAGE_AREA],
            $this->choiceLists[ResourceInterface::TOTAL_LUGGAGE] => $params[ResourceInterface::TOTAL_LUGGAGE],
            $this->choiceLists[ResourceInterface::TOTAL_SCUBA_DIVING] => $params[ResourceInterface::TOTAL_SCUBA_DIVING],
            $this->choiceLists[ResourceInterface::TOTAL_CASH_CHEQUES] => $params[ResourceInterface::TOTAL_CASH_CHEQUES],

            'Dekking_Wintersport' => $params[ResourceInterface::COVERAGE_WINTER_SPORTS],
            'Dekking_Onderwatersport' => $params[ResourceInterface::COVERAGE_SCUBA_DIVING],
            'Dekking_Gevaarlijkesport' => $params[ResourceInterface::COVERAGE_DANGEROUS_SPORTS],
            'Dekking_Geneeskundig' => $params[ResourceInterface::COVERAGE_HEALTHCARE],
            'Dekking_Ongevallen' => $params[ResourceInterface::COVERAGE_ACCIDENTS],
            'Dekking_Automobilistenhulp' => $params[ResourceInterface::COVERAGE_DRIVERS_HELP],
            'Dekking_Zakenreis' => $params[ResourceInterface::COVERAGE_BUSINESS_TRIPS],
            'Dekking_Annulering' => $params[ResourceInterface::COVERAGE_CANCELLATION],


            //'berekening_nulpremies'                                       => 'JA',
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


}
