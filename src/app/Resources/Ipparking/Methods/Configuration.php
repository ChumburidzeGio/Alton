<?php
/**
 * User: Roeland Werring
 * Date: 25/09/15
 * Time: 10:38
 * 
 */

namespace App\Resources\Ipparking\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Ipparking\AbstractParkingRequest;

class Configuration extends AbstractParkingRequest
{


    public function __construct()
    {
        $this->method = 'GetConfiguration';
        parent::__construct();
    }

}