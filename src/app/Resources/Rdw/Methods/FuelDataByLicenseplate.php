<?php
namespace App\Resources\Rdw\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Rdw\RdwAbstractRequest;

/**
 * Class FuelDataByLicenseplate
 *
 * Source:Open Data RDW: Gekentekende_voertuigen_brandstof
 * Specs: https://dev.socrata.com/foundry/opendata.rdw.nl/8ys7-d773
 * Source RDW spec: https://www.rdw.nl/SiteCollectionDocuments/Over%20RDW/Naslagwerk/Beschrijving%20dataset%20Voertuigen%20v3.0.pdf
 *
 * @package App\Resources\Rdw\Methods
 *
 */
class FuelDataByLicenseplate extends RdwAbstractRequest
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
        'brandstof_omschrijving' => ResourceInterface::FUEL_TYPE_NAME,
        'nettomaximumvermogen' => ResourceInterface::POWER,
    ];
    protected $resultTransformations = [];

    public function __construct()
    {
        parent::__construct('resource/8ys7-d773.json');
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

        $result[0]['additional_fuels'] = [];

        foreach ($result as $k => $r)
        {
            if (isset($r['@unmapped']['nominaal_continu_maximumvermogen']) && !isset($r[ResourceInterface::POWER])) {
                $result[$k][ResourceInterface::POWER] = $r['@unmapped']['nominaal_continu_maximumvermogen'];
                unset($result[$k]['@unmapped']['nominaal_continu_maximumvermogen']);
            }
        }

        if (count($result) > 1) {
            // Multiple fuels: probably a Hybrid - shortcut: add all beyond first as 'extra' fuels

            $result[0]['additional_fuels'] = array_slice($result, 1);
        }

        return $result[0];
    }
}