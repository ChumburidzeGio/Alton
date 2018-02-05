<?php
/**
 * User: Roeland Werring
 * Date: 3/7/13
 * Time: 8:48 PM
 *
 */
namespace App\Resources\Rolls\Methods\Impl;


use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;

class AutoMerkenLijstClient extends RollsAbstractSoapRequest {

    public function __construct() {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.merken_auto_function' ) );
        unset($this->xml->Functie->Parameters->Bouwdatum);
    }

    public function getResult()
    {
        return $this->extractResult('Merken','Merk');
    }


    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setBouwdatum($bouwdatum) {
        $this->xml->Functie->Parameters->Bouwdatum = $bouwdatum;
    }

    public function deleteBouwdatum() {
        unset($this->xml->Functie->Parameters->Bouwdatum);
    }




}