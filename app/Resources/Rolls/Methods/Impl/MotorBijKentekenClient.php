<?php
/**
 * (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 17-aug-2010
 * Time: 0:19:25
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class MotorBijKentekenClient extends RollsAbstractSoapRequest
{

    protected $arguments = [
        ResourceInterface::LICENSEPLATE => [
            'rules'     => self::VALIDATION_REQUIRED_LICENSEPLATE,
            'example' => 'MG60XX',
            'filter' => 'filterAlfaNumber'
        ]
    ];


    public function __construct()
    {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.kenteken_motor_function' ) );
    }

    public function setParams( Array $params )
    {
        $this->setMerkenlijst( 0 );
        $this->setKenteken( $params[ResourceInterface::LICENSEPLATE] );
    }

    public function getResult()
    {
        return $this->getLincensePlateResult();
    }


    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setKenteken($kenteken) {
        $this->xml->Functie->Parameters->Kenteken = $kenteken;
    }

    public function setMerkenlijst($merkenlijst) {
        $this->xml->Functie->Parameters->Merkenlijst = $merkenlijst;
    }

    public function setSoortenlijst($soortenlijst) {
        $this->xml->Functie->Parameters->Soortenlijst = $soortenlijst;
    }

    public function deleteKenteken() {
        unset($this->xml->Functie->Parameters->Kenteken);
    }

    public function deleteMerkenlijst() {
        unset($this->xml->Functie->Parameters->Merkenlijst);
    }

    public function deleteSoortenlijst() {
        unset($this->xml->Functie->Parameters->Soortenlijst);
    }


}

 
