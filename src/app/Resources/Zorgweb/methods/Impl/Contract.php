<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Zorgweb\Methods;

use App\Helpers\DocumentHelper;
use App\Interfaces\ResourceInterface;
use Config;
use Komparu\Input\Contract\Validator;
use Log;

class Contract extends ZorgwebAbstractRequest
{
    const KomparuContractnummer = '70121';

    /**
     * Mapping of ID to Tussen persoon. Quick fix for passing on right tussen persoon for IAK
     * @var array
     */
    private $tussenPersoonMap = [
        4261 => ['id' => '003018849', 'name' => 'IAK'],
        79   => ['id' => '4323947', 'name' => 'Overstappen'],
        66   => ['id' => '4323947', 'name' => 'Easyswitch'],
    ];

    protected $cacheDays = false;

    protected $mapping = [
        'hoofdadres.postcode'   => ResourceInterface::POSTAL_CODE,
        'hoofdadres.huisnummer' => ResourceInterface::HOUSE_NUMBER,
        'hoofdadres.straat'     => ResourceInterface::STREET,
        'hoofdadres.woonplaats' => ResourceInterface::CITY,
    ];


    protected $arguments = [
        ResourceInterface::RESOURCE__ID    => [
            'rules'   => 'required | string',
            'example' => 'H52244094',
        ],
        ResourceInterface::__ID            => [
            'rules' => 'string'
        ],
        ResourceInterface::OWN_RISK        => [
            'rules'   => 'required | number',
            'example' => '385',
        ],
        ResourceInterface::COLLECTIVITY_ID => [
            'rules' => 'number',
        ],
        'push'                             => [
            'rules'   => 'bool',
            'default' => 0
        ],
        'user_id'                          => [
            'rules'   => 'number',
            'default' => 0
        ]
    ];


    private $pushIt = false;


    public function __construct()
    {
        //   cw('/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/');
        parent::__construct('/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/', 'post_json_no_auth');
        $this->funnelRequest        = true;
        $this->strictStandardFields = false;
        $this->defaultParamsFilter  = ['kinderen'];
    }

    public function arguments(Validator $valitor = null)
    {
        parent::arguments($valitor);
        $arguments = $this->internalRequest('healthcare2', 'createfieldcache');
        foreach($this->mapping as $key => $val){
            if(isset($arguments[$key])){
                $arguments[$val] = $arguments[$key];
                unset($arguments[$key]);
            }
        }
        //take away some porn hacking
        foreach($arguments as &$argument){
            if(isset($argument['default'])){
                unset($argument['default']);
            }
        }

        return array_merge($arguments, $this->arguments);
    }

    public function setParams(Array $params)
    {
        if(isset($params['push']) && $params['push']){
            $this->pushIt = true;
            $this->setMethodUrl('/aanvraag/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/');
        }

        if(isset($params['zorgvragenMap.zorgvragen_Menzis_digitalepolis']) && $params['zorgvragenMap.zorgvragen_Menzis_digitalepolis']){
            $params['zorgvragenMap.zorgvragen_Menzis_digitalepolis'] = 'Ja, ik wil graag gebruik maken van de digitale polis.';
        } else {
            unset($params['zorgvragenMap.zorgvragen_Menzis_digitalepolis']);
        }

        if(isset($params['zorgvragenMap.zorgvragen_Anderzorg_digitalepolis']) && $params['zorgvragenMap.zorgvragen_Anderzorg_digitalepolis']){
            $params['zorgvragenMap.zorgvragen_Anderzorg_digitalepolis'] = 'Ja, ik ga akkoord.';
        } else {
            unset($params['zorgvragenMap.zorgvragen_Anderzorg_digitalepolis']);
//            $this->setErrorString(['zorgvragenMap.zorgvragen_Anderzorg_digitalepolis' => 'Bij deze verzekeraar is het accepteren van een digitale zorgpolis verplicht']);
//            return;
        }


        /**
         * prefix all burger service nrs with 0 till 8 characters
         */
        foreach($params as $key => $value){
            if(str_contains($key, 'burgerservicenummer')){
                $params[$key] = str_pad($value, 8, "0", STR_PAD_LEFT);;
            }
        }

        /**
         * limit tussenvoegse
         */
        foreach($params as $key => $value){
            if(str_contains($key, 'tussenvoegsel')){
                if( ! $value || trim($value) == '' || trim($value) == 'false'){
                    unset($params[$key]);
                }else{
                    $params[$key] = substr($value, 0, 10);
                }
            }
        }

        foreach($params as $key => $value){
            if(str_contains($key, 'achternaam')){
                if( ! preg_match("/^[a-zA-Z\s.-]+$/", $value)){
                    $this->setErrorString([$key => 'Ongeldige invoer']);
                    return;
                }
            }
        }

        /**
         * hack for multiple products
         */
        $ownIds = [];
        if(isset($params[ResourceInterface::__ID]) && str_contains($params[ResourceInterface::__ID], ',')){
            //we have multiple products. FUCK. Need to find the package Id's one by one.
            //dd(explode(',', $params[ResourceInterface::__ID]));
            foreach(explode(',', $params[ResourceInterface::__ID]) as $id){
                $options = ['visible' => 'resource.id'];
                if(isset($params['user_id'])){
                    $options['conditions'] = ['user' => $params['user_id']];
                }
                $doc = DocumentHelper::show('product', 'healthcare2', $id, $options, true);
                if($doc && $doc->data()){
                    $ownIds[] = array_get($doc->data(), 'resource.id');
                }
            }
        }

        if(isset($params['push'])){
            unset($params['push']);
        }

        $this->basicAuthService['method_url'] .= $params[ResourceInterface::RESOURCE__ID];
        unset($params[ResourceInterface::RESOURCE__ID]);
        if(isset($params[ResourceInterface::HOUSE_NUMBER_SUFFIX])){
            $params[ResourceInterface::HOUSE_NUMBER] .= ' ' . $params[ResourceInterface::HOUSE_NUMBER_SUFFIX];
            unset($params[ResourceInterface::HOUSE_NUMBER_SUFFIX]);
        }

        $params['verzekeringsgegevens.incassoWijze'] = 'INCASSO';

        /**
         * eigen risico
         */

        $zorgwebParamArr['overige']['gewenstEigenRisico'] = "" . $params[ResourceInterface::OWN_RISK];
        unset($params[ResourceInterface::OWN_RISK]);


        $contractNummer    = self::KomparuContractnummer;
        $tussenpersoonNaam = 'Komparu';
        if(isset($params['user_id'])){
            if(isset($this->tussenPersoonMap[$params['user_id']])){
                $contractNummer    = $this->tussenPersoonMap[$params['user_id']]['id'];
                $tussenpersoonNaam = $this->tussenPersoonMap[$params['user_id']]['name'];
                unset($params['user_id']);
            }
            unset($params['user_id']);
        }

        //collectivity ID
        if(isset($params[ResourceInterface::COLLECTIVITY_ID])){
            $zorgwebParamArr['verzekerden']['collectiviteitId'] = $params[ResourceInterface::COLLECTIVITY_ID];
            $zorgwebParamArr['aanbieder']                       = ['collectiviteit' => ['naam' => $tussenpersoonNaam, 'code' => '', 'contractNummer' => $params[ResourceInterface::COLLECTIVITY_ID]], 'tussenpersoon' => $contractNummer];
            unset($params[ResourceInterface::COLLECTIVITY_ID]);
        }


        $ownIdCounter = 0;
        /**
         * Add the meta data for
         */
        $zorgwebParamArr['verzekerden']['aanvrager'] = [
            "geslacht"      => array_get($params, 'aanvrager.geslacht'),
            "geboortedatum" => array_get($params, 'aanvrager.geboortedatum'),
        ];
        if(isset($ownIds[$ownIdCounter])){
            $zorgwebParamArr['verzekerden']['aanvrager']['aanTeVragenPakketId'] = $ownIds[$ownIdCounter];
        }
        $ownIdCounter ++;

        //partner
        if(array_has($params, 'partner.geboortedatum')){
            $zorgwebParamArr['verzekerden']['partner'] = [
                "geslacht"      => array_get($params, 'partner.geslacht'),
                "geboortedatum" => array_get($params, 'partner.geboortedatum'),
            ];
            if(isset($ownIds[$ownIdCounter])){
                $zorgwebParamArr['verzekerden']['partner']['aanTeVragenPakketId'] = $ownIds[$ownIdCounter];
            }
            $ownIdCounter ++;
        }


        /**
         * fix phone numbers
         */
        if(array_has($params, 'hoofdadres.telefoonnummer')){
            $phoneNumber = array_get($params, 'hoofdadres.telefoonnummer');
            $phoneNumber = preg_replace('/\s+/', '', $phoneNumber);
            $phoneNumber = str_replace('+31','0', $phoneNumber);
            if (!is_numeric($phoneNumber) || (strlen($phoneNumber) != 10) || substr($phoneNumber, 0, 1) != '0') {
                $this->setErrorString(['hoofdadres.telefoonnummer' => 'Ongeldige invoer']);
                return;
            }
            $params['hoofdadres.telefoonnummer'] = $phoneNumber;
        }




        $zorgwebParamArr['waardes'] = [];

        /**
         * copy values
         */

        foreach($params as $key => $val){
            if(str_contains($key, 'kinderen')){
                continue;
            }
            $zorgwebParamArr['waardes'][] = ["property" => $key, "waarde" => $val];
        }
        //kinderen
        for($kind = 0; $kind < 5; $kind ++){
            if(array_has($params, 'kinderen.' . $kind . '.geboortedatum')){
                $kindParams = [
                    "geslacht"      => array_get($params, 'kinderen.' . $kind . '.geslacht'),
                    "geboortedatum" => array_get($params, 'kinderen.' . $kind . '.geboortedatum'),
                ];
                if(isset($ownIds[$ownIdCounter])){
                    $kindParams['aanTeVragenPakketId'] = $ownIds[$ownIdCounter];
                }
                $ownIdCounter ++;
                $zorgwebParamArr['verzekerden']['kinderen'][] = $kindParams;
                foreach(['geslacht', 'geboortedatum', 'voorletters', 'achternaam', 'burgerservicenummer'] as $field){
                    $zorgwebParamArr['waardes'][] = ["property" => "kinderen[" . $kind . "]." . $field, "waarde" => array_get($params, 'kinderen.' . $kind . '.' . $field)];
                }
                if (array_has($params,'kinderen.' . $kind . '.zorgvragenMap')) {
                    $map = array_get($params,'kinderen.' . $kind . '.zorgvragenMap');
                    foreach($map as $key => $value) {
                        $zorgwebParamArr['waardes'][] = ["property" => "kinderen[" . $kind . "].zorgvragenMap." . $key, "waarde" => $value];
                    }
                }
            }
        }
        cw('Zorgweb Contract Params');
        cw($zorgwebParamArr);
        parent::setParams($zorgwebParamArr);

    }

    public function executeFunction()
    {
        parent::executeFunction();
        if(strpos($this->getErrorString(), "geen verzekering met id") !== false){
            Log::error($this->getErrorString());
            $this->setErrorString(null);
        }
    }

    public function getResult()
    {
        if($this->response){
            $result = (json_decode((string) $this->response->getBody(), true));
            if( ! $this->pushIt && isset($result['offerteAanvraagXml'])){
                $xml = $result['offerteAanvraagXml'];
                return ['status' => ['success'], 'xml' => $xml];
            }
            if($this->pushIt && isset($result['aanvraagNummer']) && isset($result['verzondenXml'])){
                return ['status' => ['success'], 'xml' => $result['verzondenXml'], 'zorgweb_order_id' => $result['aanvraagNummer']];
            }
            return ['error' => $result];
        }
        if($this->getErrorString()){
            return ['error' => $this->getErrorString()];
        }
    }

    public function call(Array $params, $path, Validator $validator, Array $fieldMapping, Array $filterKeyMapping, Array $filterMapping, $serviceproviderName)
    {
        // Fix: hacks for unknown messing up of input params from code?
        foreach ($this->mapping as $from => $to) {
            if (!isset($params[$to]) && array_get($params, $from)) {
                $params[$to] = array_get($params, $from);
            }
        }
        if (array_get($params, 'verzekeringsgegevens.ingangsdatum')) {
            array_set($params, 'verzekeringsgegevens.ingangsdatum', date('Y-m-d', strtotime(array_get($params, 'verzekeringsgegevens.ingangsdatum'))));
        }

        return parent::call($params, $path, $validator, $fieldMapping, $filterKeyMapping, $filterMapping, $serviceproviderName);
    }

}