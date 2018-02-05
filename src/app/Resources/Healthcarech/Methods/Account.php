<?php
namespace App\Resources\Healthcarech\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Healthcarech\AbstractKnipRequest;

class Account extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
        ResourceInterface::ACCIDENT => 'castToInt',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::FIRST_NAME          => 'firstname',
        ResourceInterface::LAST_NAME           => 'lastname',
        ResourceInterface::BIRTHDATE           => 'birthday',
        ResourceInterface::GENDER              => 'gender',
        ResourceInterface::POSTAL_CODE         => 'postcode',
        ResourceInterface::CITY                => 'city',
        ResourceInterface::STREET              => 'street',
        ResourceInterface::HOUSE_NUMBER        => 'streetNr',
        ResourceInterface::PHONE               => 'phone',
        ResourceInterface::EMAIL               => 'email',
        ResourceInterface::PAYMENT_CYCLE       => 'paymentCycle',
        ResourceInterface::PAYMENT_METHOD      => 'paymentMethod',
        ResourceInterface::BANK_ACCOUNT_NAME   => 'paymentDetails.owner',
        ResourceInterface::BANK_ACCOUNT_IBAN   => 'paymentDetails.iban',
        ResourceInterface::BANK_ACCOUNT_BIC    => 'paymentDetails.bic',
        ResourceInterface::CURRENT_PROVIDER_ID => 'currentHealthInsuranceCompanyId',
        ResourceInterface::NEW_PROVIDER_ID     => 'newHealthInsuranceCompanyId',
        ResourceInterface::FRANCHISE           => 'franchise',
        ResourceInterface::MODEL_ID            => 'modell',
        ResourceInterface::ACCIDENT            => 'unfall',
        ResourceInterface::TENANT              => 'tenant',
        ResourceInterface::IP                  => 'ipAddress',
        ResourceInterface::PRICE               => 'policyPrice',
        ResourceInterface::POLICY_NUMBER       => 'policyNumber',
        ResourceInterface::BAG_ID              => 'bagNumber',
    ];
    protected $externalToResultMapping = [
        'id'         => ResourceInterface::ACCOUNT_ID,
        'privateKey' => ResourceInterface::KEY,
    ];
    protected $resultTransformations = [];

    public function setParams(array $params)
    {
        if(( ! isset($params[ResourceInterface::CURRENT_PROVIDER_ID])) || ($params[ResourceInterface::CURRENT_PROVIDER_ID] == - 1)){
            $this->addErrorMessage(ResourceInterface::CURRENT_PROVIDER_ID, 'resource.knip.error.current_provider_id', 'Bitte wählen Sie Ihre aktuelle Versicherung', 'input');
            return;
        }
        parent::setParams($params);
    }

    public function __construct()
    {
        parent::__construct('komparu/account', self::METHOD_POST);
    }
}