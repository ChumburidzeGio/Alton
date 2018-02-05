<?php
/**
 * RollsSoapClient (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 10-aug-2010
 * Time: 18:52:59
 */


namespace App\Resources\Moneyview\Methods;

use SoapClient, Config;

class MoneyviewSoapClient extends SoapClient
{
    public function __construct( )
    {
        parent::__construct( Config::get( 'resource_moneyview.settings.wsdl' ) );
    }

}
