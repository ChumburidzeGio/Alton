<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Zorgweb\Methods;

use App\Interfaces\ResourceInterface;
use Config, Log;

class GenerateForm extends ZorgwebAbstractRequest
{

    //this form assumes ALL PERSONS ARE AT ONE POLICY

    protected $cacheDays = false;
    protected $dumpFields;
    protected $resourceFieldMapping = [
        ResourceInterface::TYPE               => [
            'zorgwebName' => ['soort'],
            'filter'      => 'convertType'
        ],
        ResourceInterface::LABEL              => [
            'zorgwebName' => ['kopje', 'vraag']
        ],
        ResourceInterface::VALID              => [
            'zorgwebName' => ['geldig']
        ],
        ResourceInterface::REQUIRED           => [
            'zorgwebName' => ['verplicht']
        ],
        ResourceInterface::OPTIONS            => [
            'zorgwebName' => ['opties'],
        ],
        ResourceInterface::NAME               => [
            'zorgwebName' => ['property'],
            'filter'      => 'encodeChars'
        ],
        ResourceInterface::VALIDATION_KEY     => [
            'zorgwebName' => ['validatieMessageKey']
        ],
        ResourceInterface::VALIDATION_MESSAGE => [
            'zorgwebName' => ['validatieMessage']
        ],
        ResourceInterface::DESCRIPTION        => [
            'zorgwebName' => ['uitleg']
        ],
        ResourceInterface::PLACEHOLDER        => [
            'zorgwebName' => ['hint']
        ],
        ResourceInterface::DEFAULT_VALUE      => [
            'zorgwebName' => ['waarde']
        ],
        ResourceInterface::RESOURCE__ID       => [
            'zorgwebName' => ['abstracteVraagId']
        ],
    ];
    protected $typeMap = [
        'HEADING'       => 'heading',
        'SUBHEADING'    => 'subheading',
        'REEKS'         => 'wysiwyg',
        'VALIDATIE'     => 'validation',
        'MEERKEUZE'     => 'dropdown',
        'GESLACHT'      => 'radio',
        'OPEN'          => 'string',
        'BSN'           => 'bsn',
        'GEBOORTEDATUM' => 'date',
        'GETAL'         => 'integer',
        'JANEE'         => 'radiowide',
        'TELNR'         => 'phonenumber',
        'VASTE_WAARDE'  => 'fixed',
        'EMAIL'         => 'email',
        'DATUM'         => 'date',
        'AUTO_IBAN'     => 'iban',
        'VERKLARING'    => 'accept',
        'MEDEDELING'    => 'notice',
        'TUSSENVOEGSEL' => 'string',
        'POSTCODE'      => 'postalcode',
    ];


    const MAX_CHILD = 8;

    const DEFAULT_CONTRACT = 'DEFAULT';

    const VALID_INPUT = ['aanvrager', 'partner', 'kinderen'];

    const ORDER_MAPPING = [
        'aanvrager.'   => 20000000,
        'partner.'     => 40000000,
        'kinderen.0.'  => 60000000,
        'kinderen.1.'  => 80000000,
        'kinderen.2.'  => 100000000,
        'kinderen.3.'  => 120000000,
        'kinderen.4.'  => 140000000,
        'kinderen.5.'  => 160000000,
        'kinderen.6.'  => 180000000,
        'kinderen.7.'  => 200000000,
        'kinderen.8.'  => 220000000,
        'kinderen.9.'  => 240000000,
        'hoofdadres.'  => 260000000,
        'verzekering.' => 280000000,
    ];

    const VALID_FIELDS = ['geslacht', 'geboortedatum', 'overige.gewenstEigenRisico', 'aanTeVragenPakketId'];

    public function __construct($year = null)
    {
        if( ! $year){
            $year = ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year'));
        }

        parent::__construct('/aanvraagformulier/voor_advies_' . $year . '/', 'post_json_no_auth');
        $this->resource2Request     = true;
        $this->strictStandardFields = false;
    }


    public function setParams(Array $params)
    {
        $this->dumpFields = isset($params[ResourceInterface::DUMP_FIELDS]) ? $params[ResourceInterface::DUMP_FIELDS] : 0;
        $filteredParams   = (array_only($params, self::VALID_INPUT));
        unset($params[ResourceInterface::DUMP_FIELDS]);
        //if no geslacht, no partner results. FU Zorgweb
        $formParams = [];
        foreach(self::VALID_INPUT as $cat){
            if( ! isset($params[$cat])){
                continue;
            }
            if(str_contains($cat, 'kinderen')){
                for($kind = 0; $kind < 10; $kind ++){
                    if( ! isset($params[$cat][$kind])){
                        continue;
                    }
                    foreach(self::VALID_FIELDS as $field){
                        if(array_has($params, $cat . '.' . $kind . '.' . $field)){
                            array_set($formParams, $cat . '.' . $kind . '.' . $field, array_get($params, $cat . '.' . $kind . '.' . $field));
                        }
                    }
                }
            }
            foreach(self::VALID_FIELDS as $field){
                if(array_has($params, $cat . '.' . $field)){
                    array_set($formParams, $cat . '.' . $field, array_get($params, $cat . '.' . $field));
                }
            }
        }
        if(isset($formParams['partner']) && empty($formParams['partner']['geslacht'])){
            $formParams['partner']['geslacht'] = 'VROUW';
        }


        $familyCompositionParams['verzekerden'] = $formParams;
        $params                                 = array_dot($filteredParams);
        $familyCompositionParams['waardes']     = [];
        foreach($params as $key => $val){
            if(str_contains($key, ['aanTeVragenPakketId', 'gewenstEigenRisico'])){
                continue;
            }
            $familyCompositionParams['waardes'][] = ["property" => $key, "waarde" => $val];
        }
        parent::setParams($familyCompositionParams);
    }


    public function getResult()
    {
        $data = $this->result;
        if($this->dumpFields){
            $vragen = $data['vragen'];
            return $vragen;
        }
        $vragen    = $data['vragen'];
        $converted = $this->convertFields($vragen);
        return $converted;
    }

    protected function convertFields($vragen)
    {
        $return         = [];
        $currentSection = 'aanvrager';
        $sectionCounter = [];
        foreach($vragen as $vraag){
            //hacks
            if(isset($vraag['kopje'])){
                $vraag['soort'] = $vraag['kopje'] == 'Overige vragen' ? 'SUBHEADING' : 'HEADING';
            }
            if(array_get($vraag, 'abstracteVraagId') == 'eindverklaring' && isset($vraag['uitleg'])){
                $description = $vraag['uitleg'];
                if(preg_match('/\/\/www.([a-zA-Z\-]+).nl\//', $description, $matches)){
                    $vraag['abstracteVraagId'] = 'eindverklaring-' . $matches[1];
                }
            }

            if(array_get($vraag, 'abstracteVraagId') == 'postcode'){
                $vraag['soort'] = 'POSTCODE';
            }


            //till here

            $question = [];
            foreach($vraag as $fieldName => $value){
                $transField = $this->getField($fieldName, $value, $vraag);
                if($transField['name'] == 'skip'){
                    continue;
                }
                $question[$transField['name']] = $transField['value'];
            }
            //check current section.
            if($question[ResourceInterface::TYPE] == 'heading'){
                // Gegevens (\w+)
                if(preg_match('/Gegevens (\w+)/', $question[ResourceInterface::LABEL], $matches)){
                    $currentSection                            = $matches[1] . '.';
                    $question[ResourceInterface::RESOURCE__ID] = 'heading';
                }elseif(preg_match('/Kind (\d)/', $question[ResourceInterface::LABEL], $matches)){
                    $currentSection                            = 'kinderen.' . ($matches[1] - 1) . '.';
                    $question[ResourceInterface::RESOURCE__ID] = 'heading';
                }elseif($question[ResourceInterface::LABEL] == 'Adres'){
                    $currentSection                            = 'hoofdadres.';
                    $question[ResourceInterface::RESOURCE__ID] = 'heading';
                }
            }

            //logic to create fucking unique hash
            $question[ResourceInterface::RESOURCE__ID] = $currentSection . $question[ResourceInterface::RESOURCE__ID];

            //By default disable birthdates
            if(str_contains($question[ResourceInterface::RESOURCE__ID], 'geboortedatum')){
                $question[ResourceInterface::DISABLED] = true;
            }


            if(array_has(self::ORDER_MAPPING, $currentSection)){
                $baseOrder = array_get(self::ORDER_MAPPING, $currentSection);
                $mySection = $currentSection;
                if(str_contains($question[ResourceInterface::RESOURCE__ID], 'zorgvragen') && ! str_contains($question[ResourceInterface::RESOURCE__ID], 'verzekering.zorgvragen')){
                    $baseOrder += 10000000;
                    $mySection .= 'zorgvragenmap';
                }
                if( ! isset($sectionCounter[$mySection])){
                    $sectionCounter[$mySection] = 0;
                }
                $question[ResourceInterface::ORDER] = $baseOrder + $sectionCounter[$mySection];
                $sectionCounter[$mySection]         += 100000;
            }
            $return[] = $question;
        }
        return $return;
    }

    protected function getField($origName, $value, $vraag)
    {
        foreach($this->resourceFieldMapping as $key => $resourceField){
            if(in_array($origName, $resourceField['zorgwebName'])){
                $transField['name'] = $key;
                if(isset($resourceField['filter'])){
                    $value = $this->{$resourceField['filter']}($value);
                }
                $transField['value'] = $value;
                return $transField;
            }
        }
        cw('ERROR');
        cw($vraag);
        return ['name' => 'unknown', 'value' => 'unkown'];
    }

    protected function convertType($value)
    {
        if(isset($this->typeMap[$value])){
            return $this->typeMap[$value];
        }
        cw('ERROR');
        cw('not found type! ' . $value);
        return 'unknown';
    }

    protected function encodeOptions($value)
    {
        return json_encode($value);
    }

    private function encodeChars($value)
    {
        $res = str_replace('[', '.', $value);
        $res = str_replace(']', '', $res);
        return $res;
    }

    public function executeFunction()
    {
        //dd($this->params);
        parent::executeFunction();

    }
}