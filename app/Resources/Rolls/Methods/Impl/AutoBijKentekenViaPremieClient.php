<?php

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Illuminate\Support\Facades\Cache;


class AutoBijKentekenViaPremieClient extends RollsAbstractSoapRequest
{
    protected $cacheDays = false; // It is already in the cache...
    private $licenseplate = false;
    protected $arguments = [
        ResourceInterface::LICENSEPLATE => [
            'rules'     => self::VALIDATION_REQUIRED_LICENSEPLATE,
            'example'  => '35-jdr-8',
            'filter' => 'filterAlfaNumber'
        ],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function setParams( Array $params )
    {
        $this->licenseplate = $params[ResourceInterface::LICENSEPLATE] ;
    }

    public function executeFunction()
    {
        $this->result = Cache::get('rolls_viapremie_licenseplate-'. strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $this->licenseplate)));

        if (!$this->result)
            $this->setErrorString('This licenseplate is not in our Rolls-via-Premie cache.');
    }

    public function getResult()
    {
        return $this->result;
    }
}


