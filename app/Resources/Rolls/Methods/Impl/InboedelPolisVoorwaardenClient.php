<?php
/**
 * User: Roeland Werring
 * Date: 6/3/13
 * Time: 1:28 PM
 *
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class InboedelPolisVoorwaardenClient extends RollsAbstractSoapRequest
{

    protected $arguments = [
        'product_id' => [
            'rules'     => 'required | number',
            'example'  => '6084'
        ],
    ];
//
    public function __construct()
    {
        $this->init( Config::get( 'resource_rolls.functions.polis_inboedel_function' ) );
    }


//    public function getResult()
//    {
//        return $this->extractResult('Rubrieken','Rubriek',parent::getResult()->Polisuittreksel);
//    }

    public function setParams( Array $params )
    {
        $this->setPolisProductid( $params['product_id'] );
        $this->deleteAanvullingen();
    }

    /**
     * Auto generated functions from XML file 1.0
     *(C) 2015 Komparu.com
     */

    public function setPolisProductid($par) {
        $this->xml->Functie->Parameters->Polis->Productid = $par;
    }

    public function setPolisAanvullingenAanvullingid($par) {
        $this->xml->Functie->Parameters->Polis->Aanvullingen->Aanvullingid = $par;
    }

    public function deletePolisProductid() {
        unset($this->xml->Functie->Parameters->Polis->Productid );
    }

    public function deletePolisAanvullingenAanvullingid() {
        unset($this->xml->Functie->Parameters->Polis->Aanvullingen->Aanvullingid );
    }





}

