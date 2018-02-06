<?php
/**
 * User: Roeland Werring
 * Date: 3/7/13
 * Time: 8:10 PM
 *
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;

class MotorModellenLijstClient extends RollsAbstractSoapRequest
{

    protected $arguments = [
        ResourceInterface::CONSTRUCTION_DATE => [
            'rules'   => self::VALIDATION_REQUIRED_DATE,
            'example' => '2012-03-01',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::BRAND_ID          => [
            'rules'   => 'integer | required',
            'example' => 173
        ]
    ];

    public function __construct()
    {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.modellen_motor_function' ) );
    }

    public function setParams( Array $params )
    {
        $this->setBouwdatum( $params[ResourceInterface::CONSTRUCTION_DATE] );
        $this->setMerk( $params[ResourceInterface::BRAND_ID] );
    }

    public function getResult()
    {
        return $this->extractResult('Modellen','Model');
    }


    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setBouwdatum( $bouwdatum )
    {
        $this->xml->Functie->Parameters->Bouwdatum = $bouwdatum;
    }

    public function setMerk( $merk )
    {
        $this->xml->Functie->Parameters->Merk = $merk;
    }

    public function deleteBouwdatum()
    {
        unset( $this->xml->Functie->Parameters->Bouwdatum );
    }

    public function deleteMerk()
    {
        unset( $this->xml->Functie->Parameters->Merk );
    }

}
