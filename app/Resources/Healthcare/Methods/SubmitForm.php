<?php

namespace App\Resources\Healthcare\Methods;

use App\Helpers\Healthcare2018Helper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Resources\Healthcare\BasicAuthRequest;
use Config, Cache, Log, Event;


class SubmitForm extends BasicAuthRequest
{
    const KOMPARU_CONTRACT_NUMBER = '70121';
    const MENZIS_COMPANY_ID = '202736';



    protected $cacheDays = false;

    const VALID_INPUT = ['aanvrager', 'partner', 'kinderen', 'verzekeringsgegevens', 'hoofdadres', 'verzekerden', 'zorgvragenMap', 'waardes','aanbieder'];

    public function __construct()
    {
        // The actual method we are calling is determined in setParams()
        $this->basicAuthService = [
            'type_request' => 'post_json_no_auth',
            'method_url'   => ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.url')) . '/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/',
            'username'     => ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.username')),
            'password'     => ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.password'))
        ];

        $this->funnelRequest        = true;
        $this->resource2Request     = true;
        $this->strictStandardFields = false;
        $this->defaultParamsFilter  = ['kinderen'];
    }

    public function setParams(Array $params)
    {
        // Filter only allowed
        $params = (array_only($params, self::VALID_INPUT));
        // Apply some generic input processing
        $params = $this->processZorgwebFormInputs($params);

        if ($this->hasErrors())
            return;

        parent::setParams($params);
    }
}