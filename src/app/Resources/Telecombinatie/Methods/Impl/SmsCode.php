<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 * 
 */

namespace App\Resources\Telecombinatie\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Telecombinatie\Methods\TelecombinatieAbstractRequest;

class SmsCode extends TelecombinatieAbstractRequest {
    protected $cacheDays = false;

    protected $arguments = [
        ResourceInterface::PHONE => [
            'rules'   => 'string | required',
            'example' => '0619440590',
        ]
    ];


    public function __construct() {
        parent::__construct('/api/tokensms','post_json','smsretention');
    }

    public function setParams(Array $params) {
        $paramArr = ['uniqueCustomerId' => $params[ResourceInterface::PHONE]];
        parent::setParams($paramArr);

    }
}