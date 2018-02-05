<?php
namespace App\Resources\Quickparking\Methods;

use App\Resources\Quickparking\Parking;
use App\Resources\Quickparking\QuickparkingAbstractRequest;


class Options extends QuickparkingAbstractRequest
{
    const RESULT_NO_ABERRANT_BILLING = 20;

    public function __construct(\SoapClient $soapClient = null)
    {
        parent::__construct('getLabelServices7', $soapClient);
    }

    public function setParams(Array $params)
    {
        if (isset($params[Parking::LOCATION_ID]))
            $params['labelID'] = (int)$params[Parking::LOCATION_ID];

        $defaultParams = [
            'labelID' => 0,
            'incomingDate' => '',
            'outgoingDate' => '',
            'email' => '',
            'referralid' => 0,
            'selectedServices' => '',
            'language' => self::LANGUAGE_CODE_DUTCH,
            'accountid' => 0,
            'couponcode' => '',
        ];
        $params = array_merge($defaultParams, $params);

        $params['incomingDate'] = $this->formatDateTime($params['incomingDate']);
        $params['outgoingDate'] = $this->formatDateTime($params['outgoingDate']);

        return parent::setParams($params);
    }

    public function getResult()
    {
        $numberOfDays = (new \DateTime($this->params['outgoingDate']))->diff(new \DateTime($this->params['incomingDate']))->days + 1;
        if ($numberOfDays <= 1) // Must be more than one day?
            $numberOfDays = 0;

        $methodResult = parent::getResult()['getLabelServices7Result'];
        if (!isset($methodResult['Services']['ResultService']))
            return [];

        $options = [];
        foreach ($methodResult['Services']['ResultService'] as $option)
        {
            $option[Parking::IS_UNAVAILABLE] = !$option['available'];
            $option[Parking::PRICE_ACTUAL] = ((float)$option['price_daily'] * ($numberOfDays + 1)) + (float)$option['price_onetime'];

            $options[] = $option;
        }

        return $options;
    }
}