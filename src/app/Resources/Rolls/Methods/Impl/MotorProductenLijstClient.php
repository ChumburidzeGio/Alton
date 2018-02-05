<?php
/**
 * Date: 13-okt-2010
 * Time: 23:23:06
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;

class MotorProductenLijstClient extends RollsAbstractSoapRequest
{
    protected $cacheDays = 7;

    public function __construct()
    {
        parent::__construct();
        $this->init( Config::get( 'resource_rolls.functions.producten_motor_function' ) );
    }


    public function getResult()
    {
        return $this->extractResult( 'Producten', 'Product' );
    }
}

