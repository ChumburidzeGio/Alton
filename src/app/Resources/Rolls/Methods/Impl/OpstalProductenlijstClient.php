<?php

namespace App\Resources\Rolls\Methods\Impl;

use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;

class OpstalProductenLijstClient extends RollsAbstractSoapRequest
{

    public function __construct()
    {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.producten_opstal_function' ) );

        // Set office ID to Lancyr Kantoorid
        // TODO: Let this be determined by user/website
        $this->officeId = 8824;
    }


    public function getResult()
    {
        return $this->extractResult( 'Producten', 'Product' );
    }
}

