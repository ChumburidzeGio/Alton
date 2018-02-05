<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Telecombinatie\Methods\Impl;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Telecombinatie\Methods\TelecombinatieAbstractRequest;
use DateTime;

class Contract extends TelecombinatieAbstractRequest
{
    protected $cacheDays = false;

    protected $arguments = [
        ResourceInterface::RENEWAL                                => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 'false'
        ],
        ResourceInterface::RENEWAL_TYPE                           => [
            'rules'   => 'in:sms,sign',
            'default' => 'sms'
        ],
        ResourceInterface::TOKEN                                  => [
            'rules'   => 'string',
            'example' => 'This token should be retrieved through smscode request'
        ],
        ResourceInterface::RESOURCE__ID                           => [
            'rules' => 'string | required',
        ],
        ResourceInterface::WEBSITE_ID                             => [
            'rules' => 'string | required',
        ],
        ResourceInterface::SIMCARD_TYPE                           => [
            'rules'   => 'in:nano,normal',
            'example' => 'only for new sim only'
        ],
        ResourceInterface::GENDER                                 => [
            'rules' => 'required | in:male,female'
        ],
        ResourceInterface::INITIALS                               => [
            'rules' => 'required | string',
        ],
        ResourceInterface::INSERTION                              => [
            'rules' => 'string',
        ],
        ResourceInterface::LAST_NAME                              => [
            'rules' => 'required | string',
        ],
        ResourceInterface::EMAIL                                  => [
            'rules' => self::VALIDATION_REQUIRED_EMAIL,
        ],
        ResourceInterface::BIRTHDATE                              => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::IDENTIFICATION_COUNTRY_CODE            => [
            'rules' => self::VALIDATION_REQUIRED_COUNTRY_CODE,
        ],
        ResourceInterface::IDENTIFICATION_TYPE                    => [
            'rules'  => 'required | in:passport,driving_licence,dutch_id_card,european_id_card',
            'filter' => 'filterUpperCaseFirst'
        ],
        ResourceInterface::IDENTIFICATION_NUMBER                  => [
            'rules'  => 'required | string',
            'filter' => 'filterAlfaNumber'
        ],
        ResourceInterface::EXPIRATION_DATE                        => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::BIRTHDATE                              => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::PHONE                                  => [
            'rules' => 'required | string',
        ],
        ResourceInterface::STREET                                 => [
            'rules' => 'required | string',
        ],
        ResourceInterface::HOUSE_NUMBER                           => [
            'rules'   => 'required | number',
            'example' => '21'
        ],
        ResourceInterface::SUFFIX                                 => [
            'rules'   => 'string',
            'example' => 'a'
        ],
        ResourceInterface::POSTAL_CODE                            => [
            'rules'   => self::VALIDATION_REQUIRED_POSTAL_CODE,
            'example' => '8014EH',
            'filter'  => 'filterToUppercase'
        ],
        ResourceInterface::CITY                                   => [
            'rules' => 'required | string',
        ],
        ResourceInterface::COUNTRY_CODE                           => [
            'rules' => self::VALIDATION_REQUIRED_COUNTRY_CODE,
        ],
        ResourceInterface::BANK_ACCOUNT_IBAN                      => [
            'rules'   => 'iban | required',
            'example' => 'only for aquasition',
            'filter'  => 'filterToUppercase,removeWhitespace'
        ],
        ResourceInterface::BANK_ACCOUNT_ACCOUNT_HOLDER_NAME       => [
            'rules'   => 'string | required',
            'example' => 'only for aquasition'
        ],
        //only needed for aquisition
        ResourceInterface::NUMBER_PORTABILITY                     => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 'false'
        ],
        ResourceInterface::NUMBER_PORTABILITY_PREPAY              => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 'false'
        ],
        ResourceInterface::NUMBER_PORTABILITY_CURRENT_PHONE       => [
            'rules' => 'phonenumber',
        ],
        ResourceInterface::NUMBER_PORTABILITY_CURRENT_SIM         => [
            'rules' => 'string',
        ],
        ResourceInterface::NUMBER_PORTABILITY_PREFERED_START_DATE => [
            'rules' => 'date',
        ],
        ResourceInterface::NUMBER_PORTABILITY_SERVICE_PROVIDER    => [
            'rules'           => self::VALIDATION_EXTERNAL_CHOICE,
            'external_choice' => [
                'resource' => 'simonly3',
                'method'   => 'providers',
                'params'   => ['add_no_choice' => true],
                'key'      => ResourceInterface::CODE,
                'val'      => ResourceInterface::NAME,
            ],
            'default'         => '-1'
        ],
        ResourceInterface::AGREE                                  => [
            'rules'  => self::VALIDATION_REQUIRED_BOOLEAN,
            'filter' => 'filterBooleanToInt'
        ],
        ResourceInterface::PAYMENT_KEY                            => [
            'rules' => 'string',
        ],
        ResourceInterface::STATUS                                 => [
            'rules' => 'string',
        ],
        ResourceInterface::SKIP_PAYMENT                           => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ]

    ];

    private $skipPayment = false;

    protected $outputFields = [

        ResourceInterface::SUCCESS,
        ResourceInterface::DESCRIPTION,
        ResourceInterface::ORDER_ID,
    ];

    public function __construct()
    {
        parent::__construct('/api/lead', 'post_json', 'mos');
        $this->funnelRequest = true;
    }

    public function setParams(Array $params)
    {
        $error = [];
        if( ! $params[ResourceInterface::RENEWAL] && $params[ResourceInterface::STATUS] != 'ok'){
            $error[ResourceInterface::STATUS] = "De status van deze server is niet ok: " . $params[ResourceInterface::STATUS];
        }
        if( ! ResourceFilterHelper::strToBool($params[ResourceInterface::AGREE])){
            $error[ResourceInterface::AGREE] = "U bent verplicht in te stemmen met de voorwaarden.";
        }

        if(($expdate = date_create_from_format('Y-m-d', $params[ResourceInterface::EXPIRATION_DATE])) === false){
            $error[ResourceInterface::EXPIRATION_DATE] = "Ongeldige vervaldatum.";
        }else{
            if(new DateTime() >= $expdate){
                $error[ResourceInterface::EXPIRATION_DATE] = "Vervaldatum identificatie kan niet in het verleden zijn.";
            }
        }
        if($params[ResourceInterface::SKIP_PAYMENT]){
            $this->skipPayment = true;
        }

        $idNumber = $params[ResourceInterface::IDENTIFICATION_NUMBER];
        //drivers license chck
        if((strtolower($params[ResourceInterface::IDENTIFICATION_TYPE]) == 'driving_licence') && ((strlen($idNumber) != 10) || ( ! preg_match("/^[0-9]+$/", $idNumber)))){
            $error[ResourceInterface::IDENTIFICATION_NUMBER] = "Een rijbewijs moet uit tien cijfers bestaan.";
        }else{
            if((strtolower($params[ResourceInterface::IDENTIFICATION_TYPE]) != 'driving_licence') && strlen($idNumber) != 9){
                $error[ResourceInterface::IDENTIFICATION_NUMBER] = "Het legitimatienummer moet uit negen karakters bestaan.";
            }
        }
        if(count($error) > 0){
            $this->setErrorString($error);
            return;
        }
        $paramArr                  = [];
        $paramArr['PropositionId'] = $params[ResourceInterface::RESOURCE__ID];
        $paramArr['Reference']     = $params[ResourceInterface::WEBSITE_ID];
        $paramArr['LeadType']      = ResourceFilterHelper::strToBool($params[ResourceInterface::RENEWAL]) ? 'SimOnlyRenewal' : 'SimOnly';
        $paramArr['MobileNumber']  = $params[ResourceInterface::PHONE];
        if(ResourceFilterHelper::strToBool($params[ResourceInterface::RENEWAL])){
            $paramArr['NumberPortability'] = false;
            //only aquasitie
            $paramArr['PortingInformation']          = null;
            $paymentInformation                      = [];
            $paymentInformation['PaymentMethodType'] = 'Nvt';
            $paramArr['SIMCardType']                 = null;
        }else{
            $params[ResourceInterface::RENEWAL_TYPE] = 'sign';
            $paramArr['NumberPortability']           = ResourceInterface::NUMBER_PORTABILITY;

            if(ResourceFilterHelper::strToBool($params[ResourceInterface::NUMBER_PORTABILITY])){
                $portingInfo                          = [];
                $portingInfo['PrePay']                = $params[ResourceInterface::NUMBER_PORTABILITY_PREPAY];
                $portingInfo['CustomerNumberNetwork'] = "";
                $portingInfo['ServiceProvider']       = $params[ResourceInterface::NUMBER_PORTABILITY_SERVICE_PROVIDER];
                $portingInfo['CurrentMobileNumber']   = $params[ResourceInterface::NUMBER_PORTABILITY_CURRENT_PHONE];
                $portingInfo['CurrentSIMNumber']      = $params[ResourceInterface::NUMBER_PORTABILITY_CURRENT_SIM];
                $portingInfo['DateNumberPortability'] = isset($params[ResourceInterface::NUMBER_PORTABILITY_PREFERED_START_DATE]) ? $params[ResourceInterface::NUMBER_PORTABILITY_PREFERED_START_DATE] : date("Y-m-d", strtotime('+2 weeks'));

                $paramArr['PortingInformation'] = $portingInfo;
            }
            $paymentInformation                      = [];
            $paymentInformation['PaymentMethodType'] = 'iDeal';
            if( ! $this->skipPayment){
                $paymentInformation['PaymentReference'] = $params[ResourceInterface::PAYMENT_KEY];
            }
            $paramArr['SIMCardType'] = $params[ResourceInterface::SIMCARD_TYPE];

        }
        $paramArr['Customer']                = [];
        $paramArr['Customer']['Gender']      = $params[ResourceInterface::GENDER];
        $paramArr['Customer']['Initials']    = $params[ResourceInterface::INITIALS];
        $paramArr['Customer']['LastName']    = $params[ResourceInterface::LAST_NAME];
        $paramArr['Customer']['MiddleName']  = isset($params[ResourceInterface::INSERTION]) ? $params[ResourceInterface::INSERTION] : "";
        $paramArr['Customer']['DateOfBirth'] = $params[ResourceInterface::BIRTHDATE];
        $paramArr['Customer']['Email']       = $params[ResourceInterface::EMAIL];


        $paramArr['Customer']['IdentificationInformation']                       = [];
        $paramArr['Customer']['IdentificationInformation']['IdentificationType'] = $params[ResourceInterface::IDENTIFICATION_TYPE];


        $paramArr['Customer']['IdentificationInformation']['IdentificationNumber'] = $params[ResourceInterface::IDENTIFICATION_NUMBER];


        ////"ExpiryDate":"2015-12-31T00:00:00", hoe gaat het


        $paramArr['Customer']['IdentificationInformation']['ExpiryDate'] = $params[ResourceInterface::EXPIRATION_DATE] . 'T00:00:00';

        $paramArr['Customer']['IdentificationInformation']['IssuingCountryCode'] = $params[ResourceInterface::COUNTRY_CODE];

        $paramArr['Customer']['DeliveryInformation']                       = [];
        $paramArr['Customer']['DeliveryInformation']['DeliveryMethodType'] = 'PostNL';

        $paramArr['Customer']['Addresses']   = [];
        $adress['Street']                    = $params[ResourceInterface::STREET];
        $adress['Number']                    = $params[ResourceInterface::HOUSE_NUMBER];
        $adress['AdditionalNumber']          = isset($params[ResourceInterface::SUFFIX]) ? $params[ResourceInterface::SUFFIX] : null;
        $adress['PostalCode']                = $params[ResourceInterface::POSTAL_CODE];
        $adress['City']                      = $params[ResourceInterface::CITY];
        $adress['CountryCode']               = $params[ResourceInterface::COUNTRY_CODE];
        $adress['AddressType']               = 'post';
        $paramArr['Customer']['Addresses'][] = $adress;


        $paymentInformation['BankAccountNumber'] = $params[ResourceInterface::BANK_ACCOUNT_IBAN];
        if( ! ResourceFilterHelper::isValidIBAN($params[ResourceInterface::BANK_ACCOUNT_IBAN])){
            $this->setErrorString([ResourceInterface::BANK_ACCOUNT_IBAN => "Rekeningnummer is geen geldig IBAN nummer."]);
            return;
        }


        $paymentInformation['BankAccountHolderName'] = $params[ResourceInterface::BANK_ACCOUNT_ACCOUNT_HOLDER_NAME];


        $paramArr['Customer']["PaymentInformation"] = $paymentInformation;
        $paramArr['ExtraReferences']                = [];
        $ref                                        = ['Key' => 'affiliate_code', 'Value' => $params[ResourceInterface::WEBSITE_ID]];
        $paramArr['ExtraReferences'][]              = $ref;
        if($params[ResourceInterface::RENEWAL_TYPE] == 'sms'){
            if( ! isset($params[ResourceInterface::TOKEN])){
                $this->setErrorString([ResourceInterface::TOKEN => "Geen SMS token!"]);
                return;
            }
            $ref2                          = ['Key' => 'sms_retention_token', 'Value' => $params[ResourceInterface::TOKEN]];
            $paramArr['ExtraReferences'][] = $ref2;
        }
        parent::setParams($paramArr);
    }

    public function executeFunction()
    {
        if( ! $this->skipPayment){
            parent::executeFunction();
        }
    }


    public function getResult()
    {
        if($this->skipPayment){
            $this->meta = [ResourceInterface::ORDER_ID => '666666'];
            return [];
        }
        // list all
        $res                              = parent::getResult();
        $res[ResourceInterface::ORDER_ID] = null;
        $details                          = isset($res['details']) ? $res['details'] : [];
        foreach($details as $detail){
            if($detail['key'] == 'ReferenceNumber'){
                $res[ResourceInterface::ORDER_ID] = $detail['value'];
                break;
            }
        }
        unset($res['details']);
        $this->meta = [ResourceInterface::ORDER_ID => $res[ResourceInterface::ORDER_ID]];
        return $res;
    }

}