<?php

namespace App\Resources\Moneyview\Methods\Impl\Car;

use App\Exception\ResourceError;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\Moneyview\Methods\MoneyviewAbstractSoapRequest;

class CarInsuranceAbstractClient extends MoneyviewAbstractSoapRequest
{
    protected $coverageToExternalCode = [
        ResourceValue::CAR_COVERAGE_ALL => 'Alles',
        ResourceValue::CAR_COVERAGE_MINIMUM => 'WA',
        ResourceValue::CAR_COVERAGE_LIMITED => 'BC',
        ResourceValue::CAR_COVERAGE_COMPLETE => 'VC',
    ];
    protected $externalVormCodeToCoverage = [
        'WA' => ResourceValue::CAR_COVERAGE_MINIMUM,
        'WABC' => ResourceValue::CAR_COVERAGE_LIMITED,
        'WAVC' => ResourceValue::CAR_COVERAGE_COMPLETE,
    ];
    protected $genderToExternalCode = [
        ResourceValue::MALE => 'Man',
        ResourceValue::FEMALE => 'Vrouw',
    ];
    protected $paymentPeriodToCode = [
        1 => 'Maand',
        4 => 'Kwartaal',
        6 => 'Half jaar',
        12 => 'Jaar',
    ];
    protected $rdwFuelToMoneyViewFuel = [
        'Benzine' => 'Benzine',
        'Elektriciteit' => 'Elektrisch', // Only one that is different
        'Diesel' => 'Diesel',
        'CNG' => 'CNG',
        'Alcohol' => 'Alcohol',
        'LNG' => 'LNG',
        'Waterstof' => 'Waterstof',
    ];
    protected $rdwBodyTypeToCarroserieVorm = [
        'stationwagen' => 'Stationwagon',
        'hatchback' => 'Hatchback',
        'cabriolet' => 'Cabriolet',
        'MPV' => 'MPV',
        'sedan' => 'Sedan',
        'coupe' => 'Coupé',
        'bus' => 'Bus',
        'pick-up truck' => 'Pick-Up',
        'terrein voertuig' => 'Terrainwagen',
        // Van
        'gesloten opbouw' => 'Bus',
    ];

    protected $rollsBodyTypeToCarroserieVorm = [
        'Other' => null,
        'ChassisCabin' => null,
        'Chassis' => null,
        'Sedan' => 'Sedan',
        'Hatchback' => 'Hatchback',
        'LiftBack' => 'Hatchback',
        'Coupe' => 'Coupé',
        'ConvertibleHardtop' => 'Cabriolet',
        'ConvertibleSoftTop' => 'Cabriolet',
        'Targa' => 'Cabriolet',
        'TerrainHardTop' => 'Terreinwagen',
        'TerrainSofttop' => 'Terreinwagen',
        'Station' => 'Stationwagon',
        'Bus' => 'Bus',
        'Deliverance' => null,
        'Van' => null,
        'PickUp' => 'Pick-Up',
        'MPV' => 'MPV',
        'SUV' => null,
    ];
    protected $rollsTransmissionTypeToMoneyview = [
        'H' => 'Handgeschakelde bak',
        'A' => 'Automatische transmissie',
        'S' => 'Automatische transmissie', // 'S' = 'Semi-Automatic transmission'... which is NOT "Variabele transmissie"
    ];
    protected $rollsDriveTypeToMoneyview = [
        'V' => 'Voor',
        'A' => 'Achter',
        'V+A' => '4WD',
        '4x4' => '4WD',
    ];
    protected $rollsDeprecatedFuelTypeToMoneyview = [
        'Other' => null,
        'Petrol' => 'Benzine',
        'GasOil' => 'Diesel',
        'Electric' => 'Elektrisch',
        'HybridPetrol' => 'Hybride/Benzine',
        'HybridGasOil' => 'Hybride/Diesel',
        'LPG' => 'LPG',
        'CNG' => null,
        'Hydrogen' => null,
        'Alcohol' => 'Alcohol',
        'Cryogenic' => null,
        'HybridLPG' => 'LPG',
    ];
    protected $rollsFuelTypeIDToMoneyview = [
        'A' => 'LPG', // Aardgas (CNG Compressed Natural Gas)
        'B' => 'Benzine',
        'C' => 'LPG', // Cryogeen
        'D' => 'Diesel',
        'E' => 'Elektrisch',
        'F' => 'Diesel', // Biodiesel
        'G' => 'LPG',
        'H' => 'Hybride/Benzine', // Hybride (unknown what type)
        'I' => 'Hybride/Diesel',
        'J' => 'Hybride/Benzine',
        'K' => 'LPG', // Hybride / LPG
        'O' => null, // Olie
        'U' => null, // Butagas
        'W' => null, // Waterstof
        'X' => 'Alcohol',
        'Z' => null, // Anders
    ];
    protected $rollsDualFuelTypeIDToMoneyview = [
        'B/E' => 'Hybride/Benzine',
        'D/E' => 'Hybride/Diesel',
        'E/B' => 'Hybride/Benzine',
        'E/D' => 'Hybride/Diesel',
    ];


    public function fetchCarRdwData($params)
    {
        $licenseplateData = $this->internalRequest('rdw', 'licenseplate', [
            ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
        ]);

        if ($this->debug && $licenseplateData == [])
            $this->setErrorString('Licenseplate not found at RDW.');
        else if ($this->debug && $this->resultHasError($licenseplateData))
            $this->setErrorString('RDW error: '. json_encode($licenseplateData));

        if ($this->resultHasError($licenseplateData) || $licenseplateData == []) {
            return $params;
        }

        if (isset($this->rdwBodyTypeToCarroserieVorm[$licenseplateData[ResourceInterface::BODY_TYPE]]))
            $licenseplateData[ResourceInterface::BODY_TYPE] = $this->rdwBodyTypeToCarroserieVorm[$licenseplateData[ResourceInterface::BODY_TYPE]];

        return array_merge($params, $licenseplateData);
    }

    public function fetchCarRdwEngineData($params)
    {
        // Get fuel + engine power data
        $licenseplateFuelData = $this->internalRequest('rdw', 'licenseplate.fuel', [
            ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
        ], true);

        if (!$this->resultHasError($licenseplateFuelData) && $licenseplateFuelData != [])
        {
            $licenseplateFuelData['@unmapped'] = array_merge($params['@unmapped'], $licenseplateFuelData['@unmapped']);
            $params = array_merge($params, $licenseplateFuelData);

            if (isset($this->rdwFuelToMoneyViewFuel[$params[ResourceInterface::FUEL_TYPE_NAME]]))
                $params[ResourceInterface::FUEL_TYPE_NAME] = $this->rdwFuelToMoneyViewFuel[$params[ResourceInterface::FUEL_TYPE_NAME]];
            else
                $params[ResourceInterface::FUEL_TYPE_NAME] = '';
        }

        return $params;
    }

    public function fetchCarRollsBasicData($params)
    {
        $valueData = $this->internalRequest('carinsurance', 'licenseplate_basic', [
            ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
        ], true);

        if ($this->debug && $this->resultHasError($valueData))
            $this->setErrorString('Rolls error: '. json_encode($valueData));

        if ($this->resultHasError($valueData))
            return $params;

        // Merge data from first model (old Rolls data)
        if ($valueData != [] && isset($valueData['types'][0]))
        {
            $valueData[ResourceInterface::DAILY_VALUE] = $valueData['types'][0][ResourceInterface::DAILY_VALUE];
            if (empty($params[ResourceInterface::REPLACEMENT_VALUE]))
                $valueData[ResourceInterface::REPLACEMENT_VALUE] = $valueData['types'][0][ResourceInterface::REPLACEMENT_VALUE];
            $valueData[ResourceInterface::TYPE_NAME] = $valueData['types'][0]['title'];

            $valueData[ResourceInterface::SECURITY_CLASS_ID] = (int)str_replace('Klasse ', '', $valueData['types'][0][ResourceInterface::SECURITY_CLASS_ID]);
        }

        $secNr = isset($valueData[ResourceInterface::SECURITY_CLASS_ID]) ? (int)$valueData[ResourceInterface::SECURITY_CLASS_ID] : 0;
        if ($secNr >= 1 && $secNr <= 5)
            $valueData[ResourceInterface::SECURITY] = 'SCM'. $secNr;
        else
            $valueData[ResourceInterface::SECURITY] = 'geen';

        if (empty($valueData[ResourceInterface::REPLACEMENT_VALUE]))
            unset($valueData[ResourceInterface::REPLACEMENT_VALUE]);
        if (!isset($valueData[ResourceInterface::DAILY_VALUE]))
            unset($valueData[ResourceInterface::DAILY_VALUE]);

        return array_merge($params, $valueData);
    }

    public function fetchCarRollsPremiumData($params)
    {
        try {
            $valueData = ResourceHelper::callResource2('licenseplate_premium.carinsurance', [
                ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
            ]);
        }
        catch (ResourceError $e) {
            $this->setErrorString('Rolls resource error: '. json_encode($e->getMessages()));
            return $params;
        }
        catch (\Exception $e) {
            $this->setErrorString('Rolls error: '. $e);
            return $params;
        }

        if ($this->debug && $this->resultHasError($valueData))
            $this->setErrorString('Rolls error: '. json_encode($valueData));

        if ($this->resultHasError($valueData))
            return $params;

        if ($valueData[ResourceInterface::VEHICLE_TYPE] != 'Passengercar')
            $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'car-is-not-passengercar', 'Car is a `'.$valueData[ResourceInterface::VEHICLE_TYPE].'`, not a `Passengercar`.');

        if (isset($this->rollsBodyTypeToCarroserieVorm[$valueData[ResourceInterface::BODY_TYPE]]))
            $valueData[ResourceInterface::BODY_TYPE] = $this->rollsBodyTypeToCarroserieVorm[$valueData[ResourceInterface::BODY_TYPE]];
        if (isset($this->rollsTransmissionTypeToMoneyview[$valueData[ResourceInterface::TRANSMISSION_TYPE]]))
            $valueData[ResourceInterface::TRANSMISSION_TYPE] = $this->rollsTransmissionTypeToMoneyview[$valueData[ResourceInterface::TRANSMISSION_TYPE]];
        if (isset($this->rollsDriveTypeToMoneyview[$valueData[ResourceInterface::DRIVE_TYPE]]))
            $valueData[ResourceInterface::DRIVE_TYPE] = $this->rollsDriveTypeToMoneyview[$valueData[ResourceInterface::DRIVE_TYPE]];

        // Note: Fuel ID -> Fuel NAME
        // We do -not- use the catalog fuel id.
        // First see if we have a 'dual' fuel type that we can map ('Electro / Benzine')
        if (isset($this->rollsDualFuelTypeIDToMoneyview[$valueData[ResourceInterface::FUEL_TYPE_ID]]))
            $valueData[ResourceInterface::FUEL_TYPE_NAME] = $this->rollsDualFuelTypeIDToMoneyview[$valueData[ResourceInterface::FUEL_TYPE_ID]];
        // Then see if we have a simple fuel type that we can map (just one type)
        else if (isset($this->rollsFuelTypeIDToMoneyview[$valueData[ResourceInterface::FUEL_TYPE_ID]]))
            $valueData[ResourceInterface::FUEL_TYPE_NAME] = $this->rollsFuelTypeIDToMoneyview[$valueData[ResourceInterface::FUEL_TYPE_ID]];
        // Fall back to using the 'primary' fuel type only
        else if (isset($this->rollsFuelTypeIDToMoneyview[$valueData[ResourceInterface::PRIMARY_FUEL_TYPE_ID]]))
            $valueData[ResourceInterface::FUEL_TYPE_NAME] = $this->rollsFuelTypeIDToMoneyview[$valueData[ResourceInterface::PRIMARY_FUEL_TYPE_ID]];
        // Fall back to using the 'secondary' fuel type only
        else if (isset($this->rollsFuelTypeIDToMoneyview[$valueData[ResourceInterface::SECONDARY_FUEL_TYPE_ID]]))
            $valueData[ResourceInterface::FUEL_TYPE_NAME] = $this->rollsFuelTypeIDToMoneyview[$valueData[ResourceInterface::SECONDARY_FUEL_TYPE_ID]];

        unset($valueData[ResourceInterface::PRICE_VAT]);

        return array_merge($params, $valueData);
    }
}
