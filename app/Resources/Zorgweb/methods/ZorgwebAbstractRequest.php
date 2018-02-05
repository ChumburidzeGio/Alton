<?php
namespace App\Resources\Zorgweb\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\BasicAuthRequest;
use Config;


/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */
class ZorgwebAbstractRequest extends BasicAuthRequest
{
    public function __construct($methodUrl, $typeRequest = 'get')
    {
        $url = ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.url'));
        $url .= $methodUrl;
        $this->basicAuthService = [
            'type_request' => $typeRequest,
            'method_url'   => $url,
            'username'     => ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.username')),
            'password'     => ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.password'))
        ];
    }


    protected function setMethodUrl($url) {
        $this->basicAuthService['method_url'] = ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.url')).$url;
    }

}