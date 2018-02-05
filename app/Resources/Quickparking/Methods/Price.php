<?php
namespace App\Resources\Quickparking\Methods;

use App\Resources\Quickparking\Parking;
use App\Resources\Quickparking\QuickparkingAbstractRequest;

class Price extends QuickparkingAbstractRequest
{
    protected $cacheDays = false;

    const STATUS_CODE_INVALID_INPUT = 20;
    const STATUS_CODE_DATABASE_CONNECTION_ERROR = 100;

    const REQUIRED_SESSION_ID_DEFAULT = 'no_session';

    protected $hasInputErrorNotAvailable = false;

    public function __construct(\SoapClient $soapClient = null)
    {
        parent::__construct('checkBookingV5', $soapClient);
    }

    public function setParams(Array $params)
    {
        if (isset($params[Parking::LOCATION_ID]))
            $params['label'] = (int)$params[Parking::LOCATION_ID];

        $defaultParams = [
            'sessionID' => '',
            'incomingDate' => '',
            'outgoingDate' => '',
            'label' => 0,
            'serviceids' => '',
            'reservationID' => '',
            'accountid' => 0,
            'referralid' => 0,
            'referrertourl' => '',
            'referrerfromurl' => '',
            'email' => '',
            'couponcode' => '',
            'debugInformation' => '',
            'priceAdditionFactor' => 0.0,
            'priceDiscountFactor' => 0.0,
        ];
        $params = array_merge($defaultParams, $params);

        $params['incomingDate'] = $this->formatDateTime($params['incomingDate']);
        $params['outgoingDate'] = $this->formatDateTime($params['outgoingDate']);

        if ($params['sessionID'] === '')
            $params['sessionID'] = self::REQUIRED_SESSION_ID_DEFAULT;

        return parent::setParams($params);
    }

    public function executeFunction()
    {
        $this->hasInputErrorNotAvailable = false;
        parent::executeFunction();

        if ($this->result['checkBookingV5Result']['status'] == self::STATUS_CODE_INVALID_INPUT
            && str_contains($this->result['checkBookingV5Result']['technicalMessage'], 'niet beschikbaar'))
        {
            // A combination of services are not available on this location, or the total parking is not
            // available and/or full
            // (this is not an input error, because we cannot predict which options cannot combine)
            $this->setErrorString(null);
            $this->hasInputErrorNotAvailable = true;
        }
    }

    public function getResult()
    {
        if ($this->hasInputErrorNotAvailable)
        {
            return [
                Parking::IS_UNAVAILABLE => true,
                'price' => 0,
                'price_withoutservices' => 0,
                Parking::PRICE_OPTIONS => 0,
                'labelID' => (int)$this->params['label'],
                'message' => $this->result['checkBookingV5Result']['technicalMessage'],
            ];
        }

        $price = parent::getResult()['checkBookingV5Result'];

        $price[Parking::IS_UNAVAILABLE] = str_contains('niet beschikbaar', $price['message']);
        $price['labelID'] = (int)$price['labelID'];
        $price[Parking::PRICE_OPTIONS] = (float)$price['price'] - (float)$price['price_withoutservices'];

        return $price;
    }
}