<?php
/**
 * User: Roeland Werring
 * Date: 3/7/13
 * Time: 8:48 PM
 *
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class AutoTypenLijstClient extends RollsAbstractSoapRequest
{
    protected $arguments = [
        ResourceInterface::CONSTRUCTION_DATE => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '2012-03-01',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::CONSTRUCTION_DATE_MONTH => [
            'rules'   => 'number',
            'example' => '03',
        ],
        ResourceInterface::CONSTRUCTION_DATE_YEAR => [
            'rules'   => 'number',
            'example' => '2012',
        ],
        ResourceInterface::MODEL_ID          => [
            'rules'   => 'integer | required',
            'example' => 1798,
        ],
    ];

    protected $cacheDays = false;

    public function __construct()
    {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.typen_auto_function' ) );
        unset( $this->xml->Functie->Parameters->Bouwdatum );
    }

    public function getResult()
    {
        $result =  $this->extractResult( 'Typen', 'Type' );
        $retArray = [];
        foreach($result as $res) {
            $retArray[$res->Id.""] = $res;
            $res->Label = "{$res->Naam} ({$res->Aantaldeuren} deurs, {$res->Vermogen} kW)";
            $res->name = $res->Id;
        }
        return $retArray;
    }

    public function setParams( Array $params )
    {
        if (isset($params[ResourceInterface::CONSTRUCTION_DATE] )) {
            $bouwdatum = $params[ResourceInterface::CONSTRUCTION_DATE] ;
        }else if(isset($params[ResourceInterface::CONSTRUCTION_DATE_MONTH]) && isset($params[ResourceInterface::CONSTRUCTION_DATE_YEAR])) {
            $bouwdatum = $params[ResourceInterface::CONSTRUCTION_DATE_YEAR] . str_pad($params[ResourceInterface::CONSTRUCTION_DATE_MONTH], 2, "0", STR_PAD_LEFT).'01';
        } else {
            $this->setErrorString('No valid construction date, or year/month combination');
            return;
        }
        //!!!!VOLGORDE NIET VERANDEREN OF HOERIGE ROLLS BEGRIJPT HET NIET
        $this->deleteModel();
        $this->setBouwdatum( $bouwdatum );
        $this->setModel( $params[ResourceInterface::MODEL_ID] );
        $this->deleteKoetswerk();
        $this->deleteTransmissie();
        $this->deleteBrandstof();
    }

    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setBouwdatum( $bouwdatum )
    {
        $this->xml->Functie->Parameters->Bouwdatum              = $bouwdatum;
        $this->xml->Functie->Parameters->Bouwdatum['type']      = "datum";
        $this->xml->Functie->Parameters->Bouwdatum['verplicht'] = "ja";
        $this->xml->Functie->Parameters->Bouwdatum['direction'] = "in";
    }

    public function setModel( $model )
    {
        $this->xml->Functie->Parameters->Model              = $model;
        $this->xml->Functie->Parameters->Model['type']      = "long";
        $this->xml->Functie->Parameters->Model['verplicht'] = "ja";
        $this->xml->Functie->Parameters->Model['direction'] = "in";
    }

    public function setKoetswerk( $koetswerk )
    {
        $this->xml->Functie->Parameters->Koetswerk = $koetswerk;
    }

    public function setBrandstof( $brandstof )
    {
        $this->xml->Functie->Parameters->Brandstof = $brandstof;
    }

    public function setTransmissie( $transmissie )
    {
        $this->xml->Functie->Parameters->Transmissie = $transmissie;
    }

    public function deleteBouwdatum()
    {
        unset( $this->xml->Functie->Parameters->Bouwdatum );
    }

    public function deleteModel()
    {
        unset( $this->xml->Functie->Parameters->Model );
    }

    public function deleteKoetswerk()
    {
        unset( $this->xml->Functie->Parameters->Koetswerk );
    }

    public function deleteBrandstof()
    {
        unset( $this->xml->Functie->Parameters->Brandstof );
    }

    public function deleteTransmissie()
    {
        unset( $this->xml->Functie->Parameters->Transmissie );
    }

}