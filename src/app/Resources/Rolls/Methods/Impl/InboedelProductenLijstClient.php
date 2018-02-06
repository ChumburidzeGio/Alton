<?php
/**
 * Date: 13-okt-2010
 * Time: 23:23:06
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;

class InboedelProductenLijstClient extends RollsAbstractSoapRequest
{

    public function __construct()
    {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.producten_inboedel_function' ) );
        $this->officeId= 8824;
    }


    public function getResult()
    {
        return $this->extractResult( 'Producten', 'Product' );
    }
}

