<?php
namespace App\Resources\Healthcarech\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Healthcarech\AbstractKnipRequest;

class TransferContracts extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [
        ResourceInterface::KEY        => 'privateKey',
        ResourceInterface::IDS        => 'insuranceCompanies',
        ResourceInterface::SIGNATURE  => 'signature',
    ];
    protected $externalToResultMapping = [];
    protected $resultTransformations = [];

    public function setParams(array $params)
    {
        // now possible to send empty contract
//        if ((!isset($params[ResourceInterface::IDS])) || !count($params[ResourceInterface::IDS])) {
//            $this->addErrorMessage(ResourceInterface::IDS,'resource.knip.error.IDS','Sie haben mindestens eine Versicherung zu wählen!','input');
//            return;
//        }
        parent::setParams($params);
    }

    public function __construct()
    {
        parent::__construct('komparu/account/{account_id}/add/knip', self::METHOD_POST);
    }

}