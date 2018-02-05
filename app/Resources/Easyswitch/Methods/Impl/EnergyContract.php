<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Easyswitch\Methods\Impl;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Website;
use App\Resources\Easyswitch\Methods\EasyswitchAbstractRequest;
use Config;

class EnergyContract extends EasyswitchAbstractRequest
{

    protected $cacheDays = false;

    //"sale_id": 349877,
    //"esos_order_id": "E-3JNXQS68ME",
    //"api_user": "komparu",
    //"persoon_geslacht": 1,
    //"persoon_initialen": "RH",
    //"persoon_tussenvoegsel": "",
    //"persoon_achternaam": "test",
    //"persoon_email": "roeland@werring.com",
    //"adres_straat": "Goyerkamp 21",
    //"adres_huisnummer": 21,
    //"adres_toevoeging": "",
    //"adres_postcode": "8014EH",
    //"adres_plaats": "Zwolle",
    //"product_id": 10735,
    //"product_naam": "Actie E.On 1 jaar stroom & Gas",
    //"leverancier_naam": "E.ON",
    //"leverancier_id": 14,
    protected $arguments = [
        ResourceInterface::BANK_ACCOUNT_PAYMENT_TYPE        => [
            'rules'   => 'choice:0=incasso,1=acceptgiro',
            'default' => '0'
        ],
        ResourceInterface::BANK_ACCOUNT_ACCOUNT_HOLDER_NAME => [
            'rules'  => 'required | string',
            'filter' => 'regexp_valid_name_chars'
        ],
        ResourceInterface::BANK_ACCOUNT_IBAN                => [
            'rules'  => 'required | iban',
            'filter' => 'filterToUppercase,removeWhitespace'
        ],
        ResourceInterface::INITIALS                         => [
            'rules'  => 'required | string',
            'filter' => 'filterToUppercaseStrip'
        ],
        ResourceInterface::INSERTION                        => [
            'rules' => 'string',
        ],
        ResourceInterface::LAST_NAME                        => [
            'rules'  => 'required | string',
            'filter' => 'regexp_valid_name_chars'
        ],
        ResourceInterface::EMAIL                            => [
            'rules' => self::VALIDATION_REQUIRED_EMAIL,
        ],
        ResourceInterface::GENDER                           => [
            'rules' => 'required | choice:m=male,v=female',
        ],
        ResourceInterface::BIRTHDATE                        => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::PHONE                            => [
            'rules'  => 'required | phonenumber',
            'filter' => 'filterNumber'
        ],
        ResourceInterface::IP                               => [
            'rules' => 'required | string',
        ],
        ResourceInterface::STREET                           => [
            'rules' => 'required | string',
        ],
        ResourceInterface::HOUSE_NUMBER                     => [
            'rules'   => 'required | number',
            'example' => '21'
        ],
        ResourceInterface::HOUSE_NUMBER_SUFFIX              => [
            'rules'   => 'string',
            'example' => 'a'
        ],
        ResourceInterface::POSTAL_CODE                      => [
            'rules'   => self::VALIDATION_REQUIRED_POSTAL_CODE,
            'example' => '8014EH',
            'filter'  => 'filterToUppercase'
        ],
        ResourceInterface::CITY                             => [
            'rules' => 'required | string',
        ],
        ResourceInterface::POSTAL_ADDRESS_OTHER             => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => '0'
        ],
        ///
        ResourceInterface::POSTAL_ADDRESS_SUFFIX            => [
            'rules' => 'string',
        ],
        ResourceInterface::POSTAL_ADDRESS_HOUSE_NUMBER      => [
            'rules'   => 'number',
            'example' => '21'
        ],
        ResourceInterface::POSTAL_ADDRESS_SUFFIX            => [
            'rules'   => 'string',
            'example' => 'a'
        ],
        ResourceInterface::POSTAL_ADDRESS_POSTAL_CODE       => [
            'rules'   => self::VALIDATION_POSTAL_CODE,
            'example' => '8014EH',
            'filter'  => 'filterToUppercase'
        ],
        ResourceInterface::POSTAL_ADDRESS_CITY              => [
            'rules' => 'string',
        ],
        ResourceInterface::REHOUSING                        => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 1,
            'filter'  => 'filterBooleanToInt'
        ],
        ResourceInterface::USE_HOUSE_FOR_WORK               => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 1,
            'filter'  => 'filterBooleanToInt'
        ],
        ResourceInterface::ASAP                             => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 1,
            'filter'  => 'filterBooleanToInt'
        ],
        ResourceInterface::START_DATE                       => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::AGREE                            => [
            'rules'  => self::VALIDATION_REQUIRED_BOOLEAN,
            'filter' => 'filterBooleanToInt'
        ],
        ResourceInterface::ELECTRICITY_USAGE_HIGH           => [
            'rules'   => 'required | number',
            'example' => '5400',
        ],
        ResourceInterface::ELECTRICITY_USAGE_LOW            => [
            'rules'   => 'number',
            'example' => '5400, only needed when double meter',
            'default' => '0'
        ],
        ResourceInterface::CONTRACT_ID                      => [
            'rules' => 'string | required',
        ],
        ResourceInterface::GAS_USAGE                        => [
            'rules'   => 'number | required',
            'example' => '5400',
        ],
        ResourceInterface::CURRENT_PROVIDER                 => [
            'rules'           => self::VALIDATION_EXTERNAL_CHOICE,
            'external_choice' => [
                'resource' => 'energy',
                'method'   => 'products',
                'params'   => ['add_no_choice' => true],
                'key'      => ResourceInterface::RESOURCE_ID,
                'val'      => ResourceInterface::TITLE,
            ],
            'default'         => '-1'
        ],
        ResourceInterface::BUSINESS                         => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0,
            'filter'  => 'filterBooleanToInt'
        ],
        ResourceInterface::COMPANY_REGISTRATION_NUMBER      => [
            'rules' => 'string',
        ],
        ResourceInterface::COMPANY_NAME                     => [
            'rules' => 'string',
        ],
        ResourceInterface::COMPANY_CONTACT_INITIALS         => [
            'rules'  => 'string',
            'filter' => 'filterToUppercaseStrip'
        ],
        ResourceInterface::COMPANY_CONTACT_INSERTION        => [
            'rules' => 'string',
        ],
        ResourceInterface::COMPANY_CONTACT_LASTNAME         => [
            'rules'  => 'string',
            'filter' => 'regexp_valid_name_chars'
        ],
        ResourceInterface::WEBSITE_ID                       => [
            'rules' => 'string | required',
        ],
        ResourceInterface::REF_URL                          => [
            'rules' => 'string',
        ],
    ];


    //"ref": null


    public function __construct()
    {
        parent::__construct('/contracten/', 'post');
        $this->funnelRequest        = true;
        $this->strictStandardFields = false;
    }


    public function filterParamKeys(Array $params)
    {
        $serviceParams = $params;

        //force test lastname when in  debug mode
        $serviceParams[ResourceInterface::LAST_NAME] = ((app()->configure('app')) ? '' : config('app.debug')) ? 'test' : $serviceParams[ResourceInterface::LAST_NAME];


        if( ! ResourceFilterHelper::isValidIBAN($params[ResourceInterface::BANK_ACCOUNT_IBAN])){
            $this->setErrorString([ResourceInterface::BANK_ACCOUNT_IBAN => "Rekeningnummer is geen geldig IBAN nummer: " . $params[ResourceInterface::BANK_ACCOUNT_IBAN]]);
            return [];
        }


        $serviceParams[ResourceInterface::BANK_ACCOUNT_BBAN] = 1; //overbodig
        $serviceParams[ResourceInterface::BANK_ACCOUNT_NAME] = ''; //overbodig

        if($serviceParams[ResourceInterface::ELECTRICITY_USAGE_HIGH] > 0){
            if($serviceParams[ResourceInterface::GAS_USAGE] > 0){
                $serviceParams[ResourceInterface::PRODUCT_TYPE] = 'combi';
            }else{
                $serviceParams[ResourceInterface::PRODUCT_TYPE] = 'stroom';
            }
        }else{
            $serviceParams[ResourceInterface::PRODUCT_TYPE] = 'gas';
        }

        $serviceParams[ResourceInterface::BUSINESS] = ($serviceParams[ResourceInterface::BUSINESS] == 1) ? 'zakelijk' : 'consument';

        if($serviceParams[ResourceInterface::BUSINESS] == 'zakelijk'){
            $serviceParams[ResourceInterface::COMPANY_CONTACT_INITIALS] = ResourceFilterHelper::filterToUppercaseStrip($serviceParams[ResourceInterface::INITIALS]);

            $serviceParams[ResourceInterface::COMPANY_CONTACT_LASTNAME] = $serviceParams[ResourceInterface::LAST_NAME];
            if(isset($serviceParams[ResourceInterface::INSERTION])){
                $serviceParams[ResourceInterface::COMPANY_CONTACT_INSERTION] = $serviceParams[ResourceInterface::INSERTION];
            }
        }

        $ids = explode('-', $serviceParams[ResourceInterface::CONTRACT_ID]);

        $serviceParams[ResourceInterface::PRODUCT_COMBI_ID]       = $ids[0];
        $serviceParams[ResourceInterface::PRODUCT_GAS_ID]         = $ids[1];
        $serviceParams[ResourceInterface::PRODUCT_ELECTRICITY_ID] = $ids[2];
        unset($serviceParams[ResourceInterface::CONTRACT_ID]);


        return parent::filterParamKeys($serviceParams);
    }

    public function setParams(Array $params)
    {
        if(isset($params['startdatum']) && ! empty($params['startdatum'])){
            $params['zsm'] = 0;
        }


        if($this->getErrorString()){
            return;
        }
        if( ! isset($params['akkoord']) || ( ! $params['akkoord'])){
            $this->setErrorString([ResourceInterface::AGREE => "U bent verplicht in te stemmen met de voorwaarden"]);
            return;
        }
        if( ! isset($params['verbruik_stroom_2']) || $params['verbruik_stroom_2'] == 0){
            $params['type_meter'] = 'enkel';
        }else{
            $params['type_meter'] = 'dubbel';
        }
        $params['http_ref'] = isset($params[ResourceInterface::REF_URL]) ? $params[ResourceInterface::REF_URL] : "na";
        $website            = Website::find($params[ResourceInterface::WEBSITE_ID]);
        unset($params[ResourceInterface::WEBSITE_ID]);
        if($website){
            $params['domain']        = preg_replace("(^https?:\/\/)", "", $website['url']);
            $params['api_user_data'] = ['partnerNaam' => $website['name'], 'partnerLogo' => $website['logo'], 'username' => $website->user->username, 'user' => $website->user->name];
            $params['ref']    = $website->user->username;
        }

        parent::setParams($params);
    }

}