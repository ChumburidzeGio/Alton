<?php

namespace App\Resources\Travel\Methods;


use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class GetParkingciLocations extends TravelWrapperAbstractRequest
{
    public $airportCodeToOrder = [
        // Germany
        'DUS' => 2, // Dusseldorf
        'FRA' => 2, // Frankfurt am Main
        'CGN' => 2, // Cologne
        'FRH' => 2, // Frankfurt Hahn
        'NRN' => 2, // Weeze
        // Belgium
        'BRU' => 3, // Brussel
        'CRL' => 3, // Charleroi
    ];

    public function executeFunction()
    {
        $this->result = $this->getParkingCiAirports();
    }

    protected function getParkingCiAirports()
    {
        // Get all airports, those are our locations (reuse the airport ID directly as internal ID, for now)
        $airports = array_map(function ($airport) {
            $airport[ResourceInterface::__ID] = $airport[ResourceInterface::AIRPORT_ID];
            $airport[ResourceInterface::ENABLED] = true;
            return $airport;
        }, $this->internalRequest('parkingci', 'airports'));

        // Get all parkings, so we can deactivate locations without any products
        $locationAirportIds = array_filter(array_map(function ($location) {
            return $location[ResourceInterface::ENABLED] ? (int)$location[ResourceInterface::AIRPORT_ID] : null;
        }, array_get(ResourceHelper::callResource1('parkingci', 'locations', ['skipcache' => true]), 'result')));
        foreach ($airports as $nr => $airport) {
            if (!in_array($airport[ResourceInterface::AIRPORT_ID], $locationAirportIds)) {
                $airports[$nr][ResourceInterface::ENABLED] = false;
            }
        }

        // Sort by country, then name
        usort($airports, function ($a, $b) {
            $aCountry = array_get($this->airportCodeToOrder, $a[ResourceInterface::AIRPORT_CODE], 0);
            $bCountry = array_get($this->airportCodeToOrder, $b[ResourceInterface::AIRPORT_CODE], 0);

            return $aCountry === $bCountry ? strcmp($a[ResourceInterface::NAME], $b[ResourceInterface::NAME]) : $aCountry - $bCountry;
        });
        foreach ($airports as $nr => $airport)
            $airports[$nr][ResourceInterface::ORDER_NR] = $nr + 1;

        return $airports;
    }
}