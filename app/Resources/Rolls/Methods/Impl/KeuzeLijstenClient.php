<?php

/**
 * (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 17-aug-2010
 * Time: 0:19:25
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class KeuzeLijstenClient extends RollsAbstractSoapRequest
{
    protected $cacheDays = 7;

    protected $arguments = [
        ResourceInterface::OPTIONLIST => [
            'rules'     => 'required',
            'example'  => '',
        ],
        ResourceInterface::OFFICE_ID => [
            'rules'     => 'string',
        ],
    ];



    public function getResult()
    {
        $result   = parent::getResult();
        $retArray = [ ];
        foreach ($result->Keuzelijsten->children() as $name => $nodeEntry) {
            foreach ($nodeEntry as $nodeEntry2) {
                $elId      = 0;
                $elname    = '';
                $elDefault = false;
                foreach ($nodeEntry2->children() as $name3 => $nodeEntry3) {
                    if ($name3 == 'Id') {
                        $elId = "" . $nodeEntry3;
                    }
                    if ($name3 == 'Omschrijving') {
                        $elname = "" . $nodeEntry3;
                    }
                    if ($name3 == 'Default') {
                        $elDefault = true;
                    }
                }
                $retArray[$name][$elId] = [ 'name' => $elname, 'default' => $elDefault ];
            }
        }
        return $retArray;
    }

    public function __construct()
    {
        parent::__construct();
        $lists                              = config( 'resource_rolls.lists' );
        $this->arguments['list']['example'] = array_keys( $lists );

    }

    public function setParams( Array $params )
    {
        $lists    = config( 'resource_rolls.lists' );
        $function = $lists[$params['list']];
        $this->init( $function );
        $this->deleteParameterTree('Keuzelijsten');
        if (isset($params[ResourceInterface::OFFICE_ID]))
            $this->officeId = (int)$params[ResourceInterface::OFFICE_ID];
    }

}