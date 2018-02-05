<?php
namespace App\Resources\Isa\Methods;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Isa\IsaAbstractRequest;

/**
 * Class CarDataByLicenseplate
 *
 * Source: ABZ Audascan V8 (via ISA system and CCS webservice)
 */
class CarDataByLicenseplate extends IsaAbstractRequest
{
    protected $cacheDays = false;

    protected $methodName = 'VoertuiggegevensISA';

    public $externalToResultMapping = [
        'merk' => ResourceInterface::BRAND_NAME,
        'model' => ResourceInterface::MODEL_NAME,
        'type' => ResourceInterface::TYPE_NAME,
        'automaat' => ResourceInterface::TRANSMISSION_TYPE,
        'bedragbpm' => ResourceInterface::BPM_VALUE,
        'bedragbtw' => ResourceInterface::PRICE_VAT,
        'bouwjaar' => ResourceInterface::CONSTRUCTION_DATE_YEAR,
        'bouwmaand' => ResourceInterface::CONSTRUCTION_DATE_MONTH,
        'afgiftedatumdeel1' => ResourceInterface::CONSTRUCTION_DATE,
        'brandstofcode' => ResourceInterface::FUEL_TYPE_ID,
        'catalogusprijsexclusiefbtw' => ResourceInterface::NET_VALUE,
        'catalogusprijsinclusiefbtw' => ResourceInterface::REPLACEMENT_VALUE,
        'cataloguswaarderolls' => null,
        'cylinderinhoud' => ResourceInterface::CYLINDER_VOLUME,
        'dagwaarde' => null,//ResourceInterface::DAILY_VALUE,
        'dagwaardeinclusiefbtw' => ResourceInterface::DAILY_VALUE,
        'dagwaarderolls' => null,
        'gewicht' => ResourceInterface::WEIGHT,
        'gewichtrolls' => null,
        'isagebruik' => null,
        'kleurcode' => ResourceInterface::COLOR,
        'laadvermogen' => ResourceInterface::LOAD_CAPACITY,
        'motorvermogen' => ResourceInterface::POWER,
        'objectcode' => ResourceInterface::BODY_TYPE,
        'soortaandrijving' => ResourceInterface::DRIVE_TYPE,
        'turbocode' => ResourceInterface::TURBO,
        'verzekerdbedragcasco' => null,
        'aantaldeuren' => ResourceInterface::AMOUNT_OF_DOORS,
        'segment' => ResourceInterface::CATEGORY,
    ];

    // List obtained from document "HA_Koppeling_ISA_Voertuiggegevens_10.0.pdf"
    protected $segmentIdToSegmentName = [
        '100' => 'Mini',
        '101' => 'Klein',
        '102' => 'Kleine middenklasse',
        '103' => 'Middenklasse',
        '104' => 'Hogere middenklasse',
        '105' => 'Groot',
        '106' => 'Sportieve modellen',
        '107' => 'Sportwagens',
        '108' => 'Supersport',
        '109' => 'Luxe',
        '110' => 'Mini-MPV',
        '111' => 'Midi-MPV',
        '112' => 'MPV',
        '113' => 'Open, Cabrio/Roadster',
        '114' => 'Midi-SUV',
        '115' => 'Terreinwagens',
        '116' => 'SUV',
    ];

    public function setParams(array $params)
    {
        $this->inputParams = $params;

        $this->params = [
            'Kenteken' => isset($params[ResourceInterface::LICENSEPLATE]) ? ResourceFilterHelper::filterAlfaNumber($params[ResourceInterface::LICENSEPLATE]) : null,
            'Meldcode' => '0000',
            'Commercieelproductnummer' => '4025',
            'Pakketonderdeel' => 'N',
        ];
    }

    public function executeFunction()
    {
        parent::executeFunction();

        if (str_contains($this->getErrorString(), 'Geen gegevens gevonden met opgegeven kenteken.')) {
            $this->clearErrors();

            $this->addErrorMessage(
                ResourceInterface::LICENSEPLATE,
                'isa.licenseplate.unknown',
                'Kenteken onbekend.'
            );
        }
    }

    public function getResult()
    {
        if (!isset($this->result['any'])) {
            $this->setErrorString('XML not present in response.');
            return [];
        }

        try {
            $xml = new \SimpleXMLElement('<data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . $this->result['any'] . '</data>');
            $carData = json_decode(json_encode($xml), true);

            // Remove XML->PHP array artifacts (empty arrays for empty elements)
            foreach ($carData as $key => $value) {
                if ($value === [])
                    $carData[$key] = null;
            }
        }
        catch (\Exception $e)
        {
            $this->setErrorString('Error `'. $e->getMessage() .'` for XML: `'. $this->result['any'] .'`');
            return [];
        }

        // Map external names to internal known names
        $mappedResult = [];
        $unmappedResult = $carData;
        foreach ($carData as $key => $value) {
            if (isset($this->externalToResultMapping[$key])) {
                if (is_numeric($value))
                    $value = (float)$value;

                $mappedResult[$this->externalToResultMapping[$key]] = $value;
                unset($unmappedResult[$key]);
            }
        }

        if (isset($mappedResult[ResourceInterface::TURBO])) {
            $mappedResult[ResourceInterface::TURBO] = $mappedResult[ResourceInterface::TURBO] === 'J';
        }
        $mappedResult[ResourceInterface::CATEGORY_NAME] = array_get($this->segmentIdToSegmentName, $mappedResult[ResourceInterface::CATEGORY]);

        $mappedResult['@unmapped'] = $unmappedResult;

        return $mappedResult;
    }
}