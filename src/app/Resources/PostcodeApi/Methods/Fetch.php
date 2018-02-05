<?php

namespace App\Resources\PostcodeApi\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Config;

class Fetch extends AbstractMethodRequest
{

    private $headers = [];
    public $resource2Request = true;
    public $cacheDays = false;


    public function __construct()
    {
        $this->headers[] = 'X-Api-Key: ' . ((app()->configure('resource_postcodeapi')) ? '' : config('resource_postcodeapi.settings.apikey'));
    }

    public function setParams(Array $params)
    {
        $this->params = ['postcode' => $params[ResourceInterface::POSTAL_CODE], 'number' => $params[ResourceInterface::HOUSE_NUMBER]];
    }

    public function executeFunction()
    {
        // De URL naar de API call
        $url  = ((app()->configure('resource_postcodeapi')) ? '' : config('resource_postcodeapi.settings.url')) . http_build_query($this->params);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        // Indien de server geen TLS ondersteunt kun je met
        // onderstaande optie een onveilige verbinding forceren.
        // Meestal is dit probleem te herkennen aan een lege response.
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        // De ruwe JSON response
        $response = curl_exec($curl);

        // Gebruik json_decode() om de response naar een PHP array te converteren
        $this->result = json_decode($response, true);
        curl_close($curl);
    }


    public function getResult()
    {
        //init, same way as easyswitch addres
        $returnArr[ResourceInterface::STREET]      = "";
        $returnArr[ResourceInterface::CITY]        = "";
        $returnArr[ResourceInterface::CONNECTIONS] = [];
        $returnArr[ResourceInterface::SUFFIXES]    = [];
        if( ! isset($this->result['_embedded'], $this->result['_embedded']['addresses']) || ! is_array($this->result['_embedded']['addresses']) || ! count($this->result['_embedded']['addresses'])){
            return $returnArr;
        }
        $addressArrLetter = [];
        $addressArrNumber = [];
        foreach($this->result['_embedded']['addresses'] as $address){
            if($returnArr[ResourceInterface::STREET] == ""){
                $returnArr[ResourceInterface::STREET] = $address['street'];
            }
            if($returnArr[ResourceInterface::CITY] == ""){
                $returnArr[ResourceInterface::CITY] = array_get($address, 'city.label');
            }
            $letter              = (is_null($address['letter']) && is_null($address['addition'])) ? null : strtoupper($address['letter'].$address['addition']);
            if (is_numeric($letter)) {
                $addressArrNumber[$letter.""] = $returnArr[ResourceInterface::STREET] . ' ' . $address['number'] . ' ' . $letter;
            } else {
                $addressArrLetter[$letter] = $returnArr[ResourceInterface::STREET] . ' ' . $address['number'] . ' ' . $letter;
            }

        }

        ksort($addressArrNumber);
        ksort($addressArrLetter);
        $addressArr = $addressArrNumber + $addressArrLetter;
        if (count($addressArr) <= 1) {
            return $returnArr;
        }
        foreach($addressArr as $key => $value){
            $key                                          = ($key === "") ? null : $key;
            $returnArr[ResourceInterface::CONNECTIONS] [] = $key;
            $returnArr[ResourceInterface::SUFFIXES][]     = ['name' => $key, 'label' => $value];
        }
        return $returnArr;

    }
}