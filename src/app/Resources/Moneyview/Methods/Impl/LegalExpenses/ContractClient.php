<?php
/**
 * User: Roeland Werring
 * Date: 3/7/13
 * Time: 8:48 PM
 *
 */

namespace App\Resources\Moneyview\Methods\Impl\LegalExpenses;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use App\Resources\BasicDummyRequest;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class ContractClient extends BasicDummyRequest
{
    protected $arguments = [
        ResourceInterface::GENDER                                => [
            'rules' => 'required | in:male,female'
        ],
        ResourceInterface::INITIALS                              => [
            'rules' => 'required | string',
        ],
        ResourceInterface::INSERTION                             => [
            'rules' => 'string',
        ],
        ResourceInterface::LAST_NAME                             => [
            'rules' => 'required | string',
        ],
        ResourceInterface::EMAIL                                 => [
            'rules' => AbstractMethodRequest::VALIDATION_REQUIRED_EMAIL,
        ],
        ResourceInterface::PHONE                            => [
            'rules'  => 'required | phonenumber',
            'filter' => 'filterNumber'
        ],
        ResourceInterface::BIRTHDATE                             => [
            'rules' => AbstractMethodRequest::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::AGREE_POLICY_CONDITIONS               => [
            'rules' => AbstractMethodRequest::VALIDATION_REQUIRED_BOOLEAN,
        ],
        ResourceInterface::HOUSE_NUMBER                => [
            'rules' => 'number'
        ],
        ResourceInterface::HOUSE_NUMBER_SUFFIX         => [
            'rules' => 'string'
        ],
        ResourceInterface::POSTAL_CODE                 => [
            'rules' => AbstractMethodRequest::VALIDATION_POSTAL_CODE,
        ],
        ResourceInterface::START_DATE                            => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::BANK_ACCOUNT_IBAN                     => [
            'rules' => 'required | iban',
        ],
        ResourceInterface::PAYMENT_PERIOD                        => [
            'rules' => 'required | in:1,12', // 1 month, 1 year
        ],
        ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT           => [
            'rules' => AbstractMethodRequest::VALIDATION_REQUIRED_BOOLEAN,
        ],
        ResourceInterface::AGREE_POLICY_CONDITIONS               => [
            'rules' => AbstractMethodRequest::VALIDATION_REQUIRED_BOOLEAN,
        ],
        ResourceInterface::INSURANCE_DIRECT_HELP                     => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::INSURANCE_LEGAL_CONFLICT                     => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CRIMINAL_PAST                     => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::CRIMINAL_PAST_INFO                => [
            'rules' => 'string',
        ],
        ResourceInterface::INSURANCE_REFUSED                 => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::INSURANCE_REFUSED_INFO            => [
            'rules' => 'string',
        ],
        ResourceInterface::INSURANCE_LEGAL_HISTORY                 => [
            'rules' => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::INSURANCE_LEGAL_HISTORY_INFO            => [
            'rules' => 'string',
        ],
    ];

    public function __construct()
    {
        $this->funnelRequest = true;
    }

}