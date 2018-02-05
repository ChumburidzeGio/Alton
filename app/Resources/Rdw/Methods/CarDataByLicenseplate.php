<?php
namespace App\Resources\Rdw\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Rdw\RdwAbstractRequest;

/**
 * Class CarDataByLicenseplate
 *
 * Source: Open Data RDW: Gekentekende_voertuigen
 * Specs: https://dev.socrata.com/foundry/opendata.rdw.nl/m9d7-ebf2
 * Source RDW spec: https://www.rdw.nl/SiteCollectionDocuments/Over%20RDW/Naslagwerk/Beschrijving%20dataset%20Voertuigen%20v3.0.pdf
 *
 * @package App\Resources\Rdw\Methods
 *
 */
class CarDataByLicenseplate extends RdwAbstractRequest
{
    protected $cacheDays = 30;

    protected $inputTransformations = [
        ResourceInterface::LICENSEPLATE => 'filterLicenseplate',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::LICENSEPLATE => 'kenteken',
    ];
    protected $externalToResultMapping = [
        'kenteken' => ResourceInterface::LICENSEPLATE,
        'merk' => ResourceInterface::BRAND_NAME,
        'handelsbenaming' => ResourceInterface::MODEL_NAME,
        'type' => ResourceInterface::TYPE_NAME,
        'voertuigsoort' => ResourceInterface::VEHICLE_TYPE,
        'inrichting' => ResourceInterface::BODY_TYPE,
        'catalogusprijs' => ResourceInterface::REPLACEMENT_VALUE,
        'massa_ledig_voertuig' => ResourceInterface::WEIGHT,
        'aantal_deuren' => ResourceInterface::AMOUNT_OF_DOORS,
        'aantal_zitplaatsen' => ResourceInterface::AMOUNT_OF_SEATS,
        'cilinderinhoud' => ResourceInterface::CYLINDER_VOLUME,
        'aantal_cilinders' => ResourceInterface::CYLINDERS,
        'eerste_kleur' => ResourceInterface::COLOR,
        'datum_eerste_toelating' => ResourceInterface::CONSTRUCTION_DATE,
        'laadvermogen' => ResourceInterface::LOAD_CAPACITY,
    ];
    protected $resultTransformations = [
        ResourceInterface::CONSTRUCTION_DATE => 'formatResultDate',
    ];

    public function __construct()
    {
        parent::__construct('resource/m9d7-ebf2.json');
    }

    public function getResult()
    {
        $result = parent::getResult();

        if (count($result) == 0) {
            $this->addErrorMessage(
                ResourceInterface::LICENSEPLATE,
                'rdw.licenseplate.unknown',
                'Kenteken onbekend.'
            );
            return;
        }
        if (count($result) > 1) {
            $this->addErrorMessage(
                ResourceInterface::LICENSEPLATE,
                'rdw.licenseplate.toomany',
                'Auto van kenteken niet determineerbaar.'
            );
            return;
        }

        return $result[0];
    }
}