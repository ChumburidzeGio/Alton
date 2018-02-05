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

class AutoModellenLijstClient extends RollsAbstractSoapRequest
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
        ResourceInterface::BRAND_ID          => [
            'rules'   => 'number | required',
            'example' => 1798
        ],
    ];


    public function __construct()
    {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.modellen_auto_function' ) );
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
        $this->setBouwdatum($bouwdatum);
        $this->setMerk( $params['brand_id'] );
    }

    public function getResult()
    {
        return $this->extractResult( 'Modellen', 'Model' );
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
