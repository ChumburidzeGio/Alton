<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Zorgweb\Methods;

use App\Interfaces\ResourceInterface;
use Config, Cache, Log;


class SubmitForm extends ZorgwebAbstractRequest
{
    const KomparuContractnummer = '70121';
    const MENZIS_COMPANY_ID = "202736";

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

    const VALID_INPUT = ['aanvrager', 'partner', 'kinderen', 'verzekeringsgegevens', 'hoofdadres', 'verzekerden', 'zorgvragenMap'];

    private $initParams;
    private $pushIt = false;
    private $noPushing = false;

    private $jsonCall = '';


    public function __construct()
    {
        //   cw('/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/');
        parent::__construct('/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/', 'post_json_no_auth');
        $this->funnelRequest        = true;
        $this->resource2Request     = true;
        $this->strictStandardFields = false;
        $this->defaultParamsFilter  = ['kinderen'];
    }


    public function setParams(Array $params)
    {
        $this->initParams = $params;
        $this->noPushing = (array_get($params,'product_data.company.id') == self::MENZIS_COMPANY_ID);
        if(isset($params['push']) && $params['push']){
            $this->pushIt = true;
            $this->setMethodUrl('/aanvraag/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/');
        }


        //collectivity and contract stuff
        $contractNummer    = self::KomparuContractnummer;
        $tussenpersoonNaam = 'Komparu';
        if(isset($params[ResourceInterface::USER])){
            if(isset($this->tussenPersoonMap[$params[ResourceInterface::USER]])){
                $contractNummer    = $this->tussenPersoonMap[$params[ResourceInterface::USER]]['id'];
                $tussenpersoonNaam = $this->tussenPersoonMap[$params[ResourceInterface::USER]]['name'];
            }
        }


        $zorgwebParamArr['verzekerden'] = json_decode($params['resource_input'], true);
        if(isset($params[ResourceInterface::COLLECTIVITY_ID])){
            $zorgwebParamArr['verzekerden']['collectiviteitId'] = $params[ResourceInterface::COLLECTIVITY_ID];
            $zorgwebParamArr['aanbieder']                       = ['collectiviteit' => ['naam' => $tussenpersoonNaam, 'code' => '', 'contractNummer' => $params[ResourceInterface::COLLECTIVITY_ID]], 'tussenpersoon' => $contractNummer];
        }


        //filter all
        $params = (array_only($params, self::VALID_INPUT));


        // FIX INPUTS

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
         * fix phone numbers
         */
        if(array_has($params, 'hoofdadres.telefoonnummer')){
            $phoneNumber = array_get($params, 'hoofdadres.telefoonnummer');
            $phoneNumber = preg_replace('/\s+/', '', $phoneNumber);
            $phoneNumber = str_replace('+31', '0', $phoneNumber);
            if( ! is_numeric($phoneNumber) || (strlen($phoneNumber) != 10) || substr($phoneNumber, 0, 1) != '0'){
                $this->setErrorString(['hoofdadres.telefoonnummer' => 'Ongeldige invoer']);
                return;
            }
            array_set($params, 'hoofdadres.telefoonnummer', $phoneNumber);
        }


        // set the avars
        $params                     = array_dot($params);
        $zorgwebParamArr['waardes'] = [];
        foreach($params as $key => $val){
            $zorgwebParamArr['waardes'][] = ["property" => $key, "waarde" => $val];
        }
        $this->jsonCall = json_encode($zorgwebParamArr);
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
            if(isset($result['ongeldigeVragen'])){
                foreach($result['ongeldigeVragen'] as $key){
                    $inValid = $this->getVraag($result['vragen'], $key);
                    $this->addErrorMessage($key, 'zorgweb.' . $inValid["validatieMessageKey"], $inValid["validatieMessage"], 'input');
                }
                return ['error' => $this->getErrorString()];
            }

            //Menzis is blocked
            if ($this->noPushing) {
                return ['status' => ['success']];
            }
            if( ! $this->pushIt && isset($result['offerteAanvraagXml'])){
                $this->initParams['push'] = 1;
                return $this->internalRequest('healthcare', 'contract', $this->initParams);
            }
            if($this->pushIt && isset($result['aanvraagNummer']) && isset($result['verzondenXml'])){

                return ['status' => ['success'], 'request' => $this->jsonCall, 'xml' => $result['verzondenXml'], 'zorgweb_order_id' => $result['aanvraagNummer']];
            }
            return ['error' => $result];
        }
        if($this->getErrorString()){
            return ['error' => $this->getErrorString()];
        }
    }

    private function getVraag($vragen, $key)
    {
        foreach($vragen as $vraag){
            if(array_get($vraag, 'property') == $key){
                return $vraag;
            }
        }
        return null;
    }

}