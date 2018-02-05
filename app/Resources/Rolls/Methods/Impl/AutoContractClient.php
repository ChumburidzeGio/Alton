<?php
/**
 * User: Roeland Werring
 * Date: 3/7/13
 * Time: 8:48 PM
 *
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use App\Resources\BasicDummyRequest;
use Config;


class AutoContractClient extends BasicDummyRequest
{
    public $resource2Request = true;

    protected $arguments = [
        ResourceInterface::GENDER                                => [
            'rules' => 'in:male,female'
        ],
        ResourceInterface::INITIALS                              => [
            'rules' => 'string',
        ],
        ResourceInterface::INSERTION                             => [
            'rules' => 'string',
        ],
        ResourceInterface::LAST_NAME                             => [
            'rules' => 'string',
        ],
        ResourceInterface::EMAIL                                 => [
            'rules' => self::VALIDATION_REQUIRED_EMAIL,
        ],
        ResourceInterface::BIRTHDATE                             => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        //        ResourceInterface::IDENTIFICATION_COUNTRY_CODE            => [
        //            'rules' => self::VALIDATION_REQUIRED_COUNTRY_CODE,
        //        ],
        //        ResourceInterface::IDENTIFICATION_TYPE                    => [
        //            'rules'  => 'required | in:passport,driving_licence,dutch_id_card,european_id_card',
        //            'filter' => 'filterUpperCaseFirst'
        //        ],
        //        ResourceInterface::IDENTIFICATION_NUMBER                  => [
        //            'rules'  => 'required | string',
        //            'filter' => 'filterAlfaNumber'
        //        ],

        ResourceInterface::START_DATE                            => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::BUSINESS                              => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::BUSINESS_TAX                          => [
            'rules' => 'in:true,false,nvt',
        ],
        ResourceInterface::BANK_ACCOUNT_IBAN                     => [
            'rules' => 'required | iban',
        ],
        ResourceInterface::CAR_REPORTING_CODE                    => [
            'rules' => 'string',
        ],
        ResourceInterface::PAYMENT_PERIOD                        => [
            'rules' => 'required | in:1,3,12', // 1 month, 1 year
        ],
        ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT           => [
            'rules' => AbstractMethodRequest::VALIDATION_REQUIRED_BOOLEAN,
        ],
        ResourceInterface::IS_CAR_OWNER                          => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_OWNER_BIRTHDATE                   => [
            'rules' => self::VALIDATION_DATE,
        ],
        ResourceInterface::CAR_OWNER_GENDER                      => [
            'rules' => 'in:male,female'
        ],
        ResourceInterface::CAR_OWNER_HOUSE_NUMBER                => [
            'rules' => 'number'
        ],
        ResourceInterface::CAR_OWNER_HOUSE_NUMBER_SUFFIX         => [
            'rules' => 'string'
        ],
        ResourceInterface::CAR_OWNER_INITIALS                    => [
            'rules' => 'string'
        ],
        ResourceInterface::CAR_OWNER_INSERTION                   => [
            'rules' => 'string'
        ],
        ResourceInterface::CAR_OWNER_LAST_NAME                   => [
            'rules' => 'string'
        ],
        ResourceInterface::CAR_OWNER_POSTAL_CODE                 => [
            'rules' => AbstractMethodRequest::VALIDATION_POSTAL_CODE,
        ],
        ResourceInterface::CAR_OWNER_RELATION                    => [
            'rules' => 'in:partner,child',
        ],
        ResourceInterface::CAR_OWNER_SAME_ADDRESS                => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_OWNER_YEARS_WITHOUT_DAMAGE        => [
            'rules' => 'number',
        ],
        ResourceInterface::CAR_LICENSE_SUSPENSION_HISTORY        => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_LICENSE_SUSPENSION_REASON         => [
            'rules' => 'string',
        ],
        ResourceInterface::CAR_LICENSE_SUSPENSION_YEAR           => [
            'rules' => 'number',
        ],
        ResourceInterface::CAR_LICENSE_SUSPENSION_DURATION       => [
            'rules' => 'number',
        ],
        ResourceInterface::CAR_PHYSICAL_DISABILITIES             => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_PHYSICAL_DISABILITIES_NOTED       => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_CRIMINAL_PAST                     => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_CRIMINAL_PAST_YEAR                => [
            'rules' => 'number',
        ],
        ResourceInterface::CAR_CRIMINAL_PAST_INFO                => [
            'rules' => 'string',
        ],
        ResourceInterface::CAR_MOTOR_VEHICLE_DAMAGE              => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_MOTOR_VEHICLE_DAMAGE_INFO         => [
            'rules' => 'schema:CarinsuranceDamageInfoSubForm',
        ],
        ResourceInterface::CAR_INSURANCE_REFUSED                 => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_INSURANCE_REFUSED_YEAR            => [
            'rules' => 'number',
        ],
        ResourceInterface::CAR_INSURANCE_REFUSED_INFO            => [
            'rules' => 'string',
        ],
        ResourceInterface::CAR_INSURANCE_WITHDRAWAL              => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_INSURANCE_WITHDRAWAL_YEAR         => [
            'rules' => 'number',
        ],
        ResourceInterface::CAR_INSURANCE_WITHDRAWAL_INFO         => [
            'rules' => 'string',
        ],
        ResourceInterface::CAR_INSURANCE_SPECIAL_CONDITIONS      => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_INSURANCE_SPECIAL_CONDITIONS_YEAR => [
            'rules' => 'number',
        ],
        ResourceInterface::CAR_INSURANCE_SPECIAL_CONDITIONS_INFO => [
            'rules' => 'string',
        ],
        ResourceInterface::AGREE_POLICY_CONDITIONS               => [
            'rules' => AbstractMethodRequest::VALIDATION_REQUIRED_BOOLEAN,
        ],
        ResourceInterface::AGREE_DIGITAL_DISPATCH               => [
            'rules' => AbstractMethodRequest::VALIDATION_REQUIRED_BOOLEAN,
        ],
        ResourceInterface::CAR_INSURANCE_MEASURE              => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_INSURANCE_MEASURE_DURATION            => [
            'rules' => 'string',
        ],
        ResourceInterface::CAR_INSURANCE_MEASURE_INFO            => [
            'rules' => 'string',
        ],
        ResourceInterface::CAR_INSURANCE_BANKRUPT              => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CAR_INSURANCE_OTHER_INFO            => [
            'rules' => 'string',
        ],
    ];

    public function __construct()
    {
        $this->funnelRequest = true;
    }

    public function setParams(Array $params)
    {
        // Dirty backward compatibility hack. 'lastname' => 'last_name'
        if (isset($params['lastname']))
            $params[ResourceInterface::LAST_NAME] = $params['lastname'];

        if (!isset($params[ResourceInterface::BUSINESS]) || !ResourceFilterHelper::filterBooleanToInt($params[ResourceInterface::BUSINESS]))
        {
            // Personal details only required when not a Business
            foreach ([ResourceInterface::GENDER, ResourceInterface::INITIALS, ResourceInterface::LAST_NAME] as $field) {
                if (empty($params[$field]))
                    $this->addErrorMessage($field, 'rolls.contract.custom.field_required', 'This field is required to be set.', 'input');
            }
        }

        return parent::setParams($params);
    }
}