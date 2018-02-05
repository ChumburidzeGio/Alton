<?php
/**
 * User: Roeland Werring
 * Date: 6/3/13
 * Time: 1:28 PM
 *
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class AllePolisVoorwaardenClient extends AbstractMethodRequest
{
    private $coverages = ['wa', 'bc', 'vc'];
    private $id;


    protected $arguments = [
        ResourceInterface::ID       => [
            'rules'   => 'required | integer',
            'example' => '9314'
        ],
    ];

    protected $cacheDays = 1;


    public function __construct()
    {
        $this->populateRequest = true;
    }


    public function executeFunction()
    {

    }


    public function getResult()
    {
        $result = [];
        foreach ($this->coverages as $coverage) {
           $result[$coverage] = $this->internalRequest($this->getRequestType(),'coveragepolicy',[ResourceInterface::ID => $this->id, ResourceInterface::COVERAGE => $coverage]);
        }
        return $result;
    }

    public function setParams( Array $params )
    {
        //return all conditions
        $this->strictStandardFields = false;
        $this->id = $params[ResourceInterface::ID];
    }
}

