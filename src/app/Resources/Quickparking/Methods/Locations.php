<?php
namespace App\Resources\Quickparking\Methods;

use App\Resources\Quickparking\QuickparkingAbstractRequest;

class Locations extends QuickparkingAbstractRequest
{
    const RESULT_CODE_SUCCESS = 10;

    public function __construct(\SoapClient $soapClient = null)
    {
        parent::__construct('getLabelLocations', $soapClient);
    }

    public function getResult()
    {
        $locations = [];
        foreach (parent::getResult()['getLabelLocationsResult']['labels']['ResultLabel'] as $location)
        {
            $locations[] = $location;
        }

        return $locations;
    }
}