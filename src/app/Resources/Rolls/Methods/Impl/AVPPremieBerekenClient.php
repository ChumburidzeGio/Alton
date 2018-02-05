<?php

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;
use Komparu\Value\ValueInterface as Value;


class AVPPremieBerekenClient extends RollsAbstractSoapRequest
{
    protected $cacheDays = false;
    public $resource2Request = true;

    //    protected $arguments = [
    //        ResourceInterface::BIRTHDATE          => [
    //            'type'     => Value::TYPE_STRING,
    //            'example'  => '1988-11-09 (yyyy-mm-dd)',
    //            'rules'    => 'required | date',
    //            'required' => true
    //        ],
    //        ResourceInterface::POSTAL_CODE        => [
    //            'type'     => Value::TYPE_STRING,
    //            'example'  => '8014EH',
    //            'rules'    => 'required | string',
    //            'required' => true
    //        ],
    //        ResourceInterface::FAMILY_COMPOSITION => [
    //            'type'     => Value::TYPE_INTEGER,
    //            'example'  => 'required | 8: see inboedelkeuzelijst->Gezinssamenstellingen',
    //            'rules'    => 'integer',
    //            'required' => true
    //        ],
    //        ResourceInterface::CALCULATION_OWN_RISK       => [
    //            'type'     => Value::TYPE_INTEGER,
    //            'example'  => 'Own riks',
    //            'rules'    => 'required | integer',
    //            'required' => true
    //        ],
    //    ];


    public function __construct()
    {
        parent::__construct();
        $this->init(((app()->configure('resource_rolls')) ? '' : config('resource_rolls.functions.premie_avp_function')));

        // Set office ID to Lancyr Kantoorid
        // TODO: Let this be determined by user/website
        $this->officeId = 8824;
    }

    public function setParams(Array $params)
    {
        $paramNode = $this->xml->Functie->Parameters;

        // Verzekeringnemer
        $paramNode->Verzekeringnemer->Geboortedatum       = str_replace('-', '', trim($params[ResourceInterface::BIRTHDATE]));
        $paramNode->Verzekeringnemer->Postcode            = $params[ResourceInterface::POSTAL_CODE];
        $paramNode->Verzekeringnemer->Gezinssamenstelling = $params[ResourceInterface::FAMILY_COMPOSITION];

        // Huidigepolis
        unset($paramNode->Huidigepolis);

        // Productselectie
        unset($paramNode->Productselectie);

        // Nieuwepolis
        $paramNode->Nieuwepolis->Ingangsdatum = $this->getNow();
        unset($paramNode->Nieuwepolis->Minimaledekking);
        unset($paramNode->Nieuwepolis->Aanvullingsoorten);
        unset($paramNode->Nieuwepolis->NP_Rubrieken);
        unset($paramNode->Nieuwepolis->Status);

        // Premieobjecten
        $paramNode->Premieobjecten->Premieobject->Betalingstermijn                 = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.termijn_maand'));
        $paramNode->Premieobjecten->Premieobject->Gewensteigenrisico               = $params[ResourceInterface::CALCULATION_OWN_RISK];
        $paramNode->Premieobjecten->Premieobject->Includeproductenzonderacceptatie = $this->rollsBool('true');
        $paramNode->Premieobjecten->Premieobject->Assurantiebelastingincl          = $this->rollsBool('true');
        $paramNode->Premieobjecten->Premieobject->Verzekerdesom                    = $params[ResourceInterface::CALCULATION_INSURED_AMOUNT];
        unset($paramNode->Premieobjecten->Premieobject->Premies);
        unset($paramNode->Premieobjecten->Premieobject->Combinaties);
        unset($paramNode->Premieobjecten->Premieobject->Contractsduur);
        unset($paramNode->Premieobjecten->Premieobject->VerzekerdeSom_Buitenhuisdekking);
        unset($paramNode->Premieobjecten->Premieobject->Poliskostenincl);
        unset($paramNode->Premieobjecten->Premieobject->PO_Productselectie);

        // Only specific products
        if(isset($params[ResourceInterface::RESOURCE][ResourceInterface::ID])){
            $this->addProductIdsFilter($params[ResourceInterface::RESOURCE][ResourceInterface::ID]);
        }
    }

    public function getResult()
    {
        $result = parent::getResult();
        return $this->extractResult('Premies', 'Premie', $result->Premieobjecten->Premieobject);
    }
}