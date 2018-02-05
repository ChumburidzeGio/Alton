<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 * 
 */

namespace App\Resources\Telecombinatie\Methods\Impl;

use App\Resources\Telecombinatie\Methods\TelecombinatieAbstractRequest;

class NetworkList extends TelecombinatieAbstractRequest {
    protected $cacheDays = 30;

    public function __construct() {
        parent::__construct('/api/content/networks');
    }

}