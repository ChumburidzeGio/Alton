<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Zorgweb\Methods;

use App\Helpers\DocumentHelper;
use App\Helpers\IAKHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\BasicAuthRequest;
use Config, Cache, Log;
use Komparu\Input\Contract\Validator;

class PostForm extends ZorgwebAbstractRequest
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


    private $pushIt = false;


    public function __construct()
    {
        //   cw('/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/');
        parent::__construct('/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/', 'post_json_no_auth');
        $this->resource2Request     = true;
        $this->strictStandardFields = false;
    }

    public function setParams(Array $params)
    {
        if(isset($params['push']) && $params['push']){
            $this->pushIt = true;
            $this->setMethodUrl('/aanvraag/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/');
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

        if(isset($params['push'])){
            unset($params['push']);
        }



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
        if(isset($params[ResourceInterface::COLLECTIVITY])){
            $params['verzekerden']['collectiviteitId'] = $params[ResourceInterface::COLLECTIVITY];
            $params['aanbieder']                       = ['collectiviteit' => ['naam' => $tussenpersoonNaam, 'code' => '', 'contractNummer' => $params[ResourceInterface::COLLECTIVITY]], 'tussenpersoon' => $contractNummer];
            unset($params[ResourceInterface::COLLECTIVITY]);
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


        
        cw('Zorgweb Contract Params');
        cw($params);
        parent::setParams($params);

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

}