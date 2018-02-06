<?php
/**
 * User: Roeland Werring
 * Date: 3/7/13
 * Time: 8:48 PM
 *
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class MotorTypenLijstClient extends RollsAbstractSoapRequest
{
    protected $arguments = [
        ResourceInterface::CONSTRUCTION_DATE => [
            'rules'   => 'required',
            'example' => '2012-03-01'
        ],
        ResourceInterface::MODEL_ID          => [
            'rules'   => 'integer | required',
            'example' => 1798,
        ],
        ResourceInterface::BRAND_ID          => [
            'rules'   => 'integer | required',
            'example' => 1,
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.typen_motor_function' ) );

    }

    public function getResult()
    {
        return $this->extractResult( 'Typen', 'Type' );
    }

    public function setParams( Array $params )
    {
        //!!!!VOLGORDE NIET VERANDEREN OF HOERIGE ROLLS BEGRIJPT HET NIET
        //        $this->deleteModel();
        $this->setBouwdatum( ResourceFilterHelper::regexpInt( $params[ResourceInterface::CONSTRUCTION_DATE] ) ) ;
        $this->setModel( $params[ResourceInterface::MODEL_ID] );
        $this->setMerk( $params[ResourceInterface::BRAND_ID] );
        $this->setSoort( 1 );

    }

    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setMerk( $merk )
    {
        $this->xml->Functie->Parameters->Merk = $merk;
    }

    public function setSoort( $soort )
    {
        $this->xml->Functie->Parameters->Soort = $soort;
    }

    public function setBouwdatum( $bouwdatum )
    {
        $this->xml->Functie->Parameters->Bouwdatum = $bouwdatum;
    }

    public function setModel( $model )
    {
        $this->xml->Functie->Parameters->Model = $model;
    }

    public function deleteMerk()
    {
        unset( $this->xml->Functie->Parameters->Merk );
    }

    public function deleteSoort()
    {
        unset( $this->xml->Functie->Parameters->Soort );
    }

    public function deleteBouwdatum()
    {
        unset( $this->xml->Functie->Parameters->Bouwdatum );
    }

    public function deleteModel()
    {
        unset( $this->xml->Functie->Parameters->Model );
    }

}