<?php
/**
 * RollsSoapClient (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 10-aug-2010
 * Time: 18:52:59
 */


namespace App\Resources\Rolls\Methods;

use Illuminate\Support\Facades\Config;
use SoapClient;

class RollsSoapClient extends SoapClient
{
    public function __construct( $type = 'webservice')
    {
        if ($type == 'lijsten') {
            $wsdl = 'resource_rolls.settings.rolls_lijstservicewsdl_url';
        } else {

            $wsdl = 'resource_rolls.settings.rolls_webservicewsdl_url';
        }
        parent::__construct( config( $wsdl ) );
    }

}
