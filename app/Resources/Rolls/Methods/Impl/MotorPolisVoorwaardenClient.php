<?php
/**
 * User: Roeland Werring
 * Date: 6/3/13
 * Time: 1:28 PM
 *
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class MotorPolisVoorwaardenClient extends RollsAbstractSoapRequest
{

    protected $arguments = [
        ResourceInterface::ID => [
            'rules'     => 'required | integer',
            'example'  => '9314'
        ],
        ResourceInterface::COVERAGE     => [
            'rules'     => 'required | in:bc,vc,wa',
            'example'  => 'bc, vc or wa',
        ]
    ];

    protected $cacheDays = 1;

    public function __construct()
    {
        $this->init( Config::get( 'resource_rolls.functions.polis_motor_function' ) );
        unset( $this->xml->Functie->Parameters->Bouwdatum );
    }


    public function getResult()
    {
        return $this->getPolisResult();
    }

    public function setParams( Array $params )
    {
        //return all conditions
        $this->strictStandardFields = false;

        $this->setPolisProductid( $params[ResourceInterface::ID] );
        $this->setPolisDekking( $params[ResourceInterface::COVERAGE] );
        $this->deleteAanvullingen();
    }

    /**
     * Motor generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setPolisProductid( $par )
    {
        $this->xml->Functie->Parameters->Polis->Productid = $par;
    }

    public function setPolisDekking( $par )
    {
        $this->xml->Functie->Parameters->Polis->Dekking = $par;
    }


    public function setPolisAanvullingenAanvullingid( $par )
    {
        $this->xml->Functie->Parameters->Polis->Aanvullingen->Aanvullingid = $par;
    }

    public function deletePolisProductid()
    {
        unset( $this->xml->Functie->Parameters->Polis->Productid );
    }

    public function deletePolisDekking()
    {
        unset( $this->xml->Functie->Parameters->Polis->Dekking );
    }

    public function deletePolisAanvullingenAanvullingid()
    {
        unset( $this->xml->Functie->Parameters->Polis->Aanvullingen->Aanvullingid );
    }

}

