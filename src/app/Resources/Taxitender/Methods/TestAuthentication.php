<?php


namespace App\Resources\Taxitender\Methods;


use App\Resources\Taxitender\TaxitenderAbstractRequest;

class TestAuthentication extends TaxitenderAbstractRequest
{
    protected $resultKeyname = null;

    protected $externalToResultMapping = [
        'responseCode' => 'responseCode',
        'response'     => 'response',
    ];

    public function __construct()
    {
        parent::__construct('testAuthentication');
    }
}