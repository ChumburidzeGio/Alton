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
use App\Resources\BasicDummyRequest;
use DateTime;

class MobileContract extends BasicDummyRequest
{
    protected $cacheDays = false;

    protected $arguments = [
        ResourceInterface::RENEWAL                                => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ],
        ResourceInterface::RENEWAL_TYPE                           => [
            'rules'   => 'in:sms,sign',
            'default' => 'sign'
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
        ResourceInterface::EMAIL                                 => [
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
            'rules' => 'required | phonenumber',
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
            'filter' => 'filterToUppercase,removeWhitespace'
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


    ];

    protected $outputFields = [
        ResourceInterface::SUCCESS,
        ResourceInterface::ORDER_ID,
    ];

    public function setParams(Array $params)
    {
        $error = [];
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

        $idNumber = $params[ResourceInterface::IDENTIFICATION_NUMBER];
        //drivers license chck
        if((strtolower($params[ResourceInterface::IDENTIFICATION_TYPE]) == 'driving_licence') && ((strlen($idNumber) != 10) || ( ! preg_match("/^[0-9]+$/", $idNumber)))){
            $error[ResourceInterface::IDENTIFICATION_NUMBER] = "Een rijbewijs moet uit tien cijfers bestaan.";
        }else{
            if((strtolower($params[ResourceInterface::IDENTIFICATION_TYPE]) != 'driving_licence') && strlen($idNumber) != 9){
                $error[ResourceInterface::IDENTIFICATION_NUMBER] = "Het legitimatienummer moet uit negen karakters bestaan.";
            }
        }

        $params[ResourceInterface::BANK_ACCOUNT_IBAN];
        if( ! ResourceFilterHelper::isValidIBAN($params[ResourceInterface::BANK_ACCOUNT_IBAN])){
            $error[ResourceInterface::BANK_ACCOUNT_IBAN] = "Rekeningnummer is geen geldig IBAN nummer.";
            return;
        }

        if(count($error) > 0){
            $this->setErrorString($error);
            return;
        }
    }


    public function getResult()
    {
        $rand3                            = function () {
            return substr(str_shuffle("0123456789"), 0, 3);
        };
        $res[ResourceInterface::ORDER_ID] = $rand3() . '-' . $rand3() . '-' . $rand3();
        $res[ResourceInterface::SUCCESS] = 'ok';
        $this->meta = [ResourceInterface::ORDER_ID => $res[ResourceInterface::ORDER_ID], ResourceInterface::SUCCESS => 'Ok'];
        return $res;
    }

}