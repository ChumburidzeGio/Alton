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

namespace App\Resources\Moneyview\Methods\Impl\Liability;

use App\Interfaces\ResourceInterface;
use App\Resources\Moneyview\Methods\MoneyviewAbstractSoapRequest;
use Config;

class PremiumClient extends MoneyviewAbstractSoapRequest
{
    private $insuredAmount = 0;
    private $ownRisk = 0;
    protected $arguments = [
        ResourceInterface::BIRTHDATE                  => [
            'rules'   => self::VALIDATION_REQUIRED_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::BIRTHDATE_PARTNER          => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::PERSONAL_CIRCUMSTANCES     => [
            'rules'         => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'liabilityinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => 'personal_circumstances'
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ]
        ],
        ResourceInterface::OWN_RISK_TYPE              => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'liabilityinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => 'own_risk_type'
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ]
        ],
        ResourceInterface::CALCULATION_INSURED_AMOUNT => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'liabilityinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::CALCULATION_INSURED_AMOUNT
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'default'       => 500000
        ],
        ResourceInterface::OWN_RISK_CHILDREN          => [
            'rules'   => 'in:0,150,999',
            'example' => 'only required to fill in when own risk type = kinderen',
        ],
        ResourceInterface::OWN_RISK_GENERAL           => [
            'rules'   => 'in:0,45,90,100,999',
            'example' => 'only required to fill in when own risk type = algemeen',
        ],


    ];

    protected $cacheDays = 30;

    protected $outputFields = [
        ResourceInterface::COVERAGE_AMOUNT,
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_INITIAL,
        ResourceInterface::OWN_RISK,
        ResourceInterface::COVERAGE_AMOUNT,
    ];

    public function __construct()
    {

        parent::__construct('AVP', self::TASK_PROCESS_ONE);
        $this->documentRequest = true;
        $this->choiceLists     = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.choicelist'));
        $this->defaultParams   = [
            self::BEREKENING_MY_KEY => ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.settings.code')),
            self::PAY_TERM_KEY      => self::PAY_TERM_MONTH,
            self::ASSUR_TAX_KEY     => self::ASSUR_TAX,
        ];
    }

    public function setParams(Array $params)
    {
        $this->insuredAmount = $params[ResourceInterface::CALCULATION_INSURED_AMOUNT];


        if (!isset($params[ResourceInterface::OWN_RISK_TYPE])) {
            $ownRiskType = (strpos( $params[ResourceInterface::PERSONAL_CIRCUMSTANCES],'met kinderen'))?'kinderen' :'algemeen';
        } else {
            $ownRiskType = $params[ResourceInterface::OWN_RISK_TYPE];
        }

        if ($ownRiskType == 'algemeen') {
            $this->ownRisk = isset($params[ResourceInterface::OWN_RISK_GENERAL])?$params[ResourceInterface::OWN_RISK_GENERAL]:0;
        }
        if ($ownRiskType == 'kinderen') {
            $this->ownRisk = isset($params[ResourceInterface::OWN_RISK_CHILDREN])?$params[ResourceInterface::OWN_RISK_CHILDREN]:0;
        }

        //setdefaults
        $serviceParams = [
            'berekening_ingangsdatum'                                         => $this->getNow(),
            // 'persoon_geboortedatum'                                 => $params[ResourceInterface::BIRTHDATE],
            'persoon_postcode'                                                => '3848',
            'persoon_pc_letters'                                              => 'DD',
            'Persoon_Huisnr'                                                  => '18',
            'Berekening_Er'                                                   => '50',
            $this->choiceLists[ResourceInterface::PERSONAL_CIRCUMSTANCES]     => $params[ResourceInterface::PERSONAL_CIRCUMSTANCES],
            $this->choiceLists[ResourceInterface::OWN_RISK_TYPE]              => $ownRiskType,
            'Persoon_Geboortedatum'                                           => $params[ResourceInterface::BIRTHDATE],
            'Persoon_Geboortedatum_Partner'                                   => ((strpos( $params[ResourceInterface::PERSONAL_CIRCUMSTANCES],'gezin')!== false) &&isset($params[ResourceInterface::BIRTHDATE_PARTNER])) ? $params[ResourceInterface::BIRTHDATE_PARTNER] : '',
            $this->choiceLists[ResourceInterface::CALCULATION_INSURED_AMOUNT] => $params[ResourceInterface::CALCULATION_INSURED_AMOUNT],
            'Berekening_Jagersrisico'                                        => 'Nee',
            'Berekening_Bezit_2E'                                        => 'Nee',
            'Berekening_Privegebruik_2E'                                        => 'Nee',
            'berekening_nulpremies'                                           => ($this->debug?'Ja':'Nee'),
        ];
        //dd($serviceParams);
        if($ownRiskType == 'algemeen'){
            $serviceParams[$this->choiceLists[ResourceInterface::OWN_RISK_GENERAL]] = $ownRiskType;
        }
        if($ownRiskType == 'kinderen'){
            $serviceParams[$this->choiceLists[ResourceInterface::OWN_RISK_CHILDREN]] = $ownRiskType;
        }



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
    public function getResult() {
        $result = parent::getResult();
        $returnArr= [];

        foreach ($result as $res) {
            if (!isset($res['VERZ_BEDRAG'])) {
                continue;
            }
            if (($res['VERZ_BEDRAG'] >= $this->insuredAmount) &&  ($res['EIGENRISICO_ALGEMEEN'] <= $this->ownRisk)) {
                  $returnArr[] = $res;
            }
        }

        return $returnArr;
    }


    //EUG = Extra Uitgebreide dekking
    //AR = All Risk Dekkin

}
