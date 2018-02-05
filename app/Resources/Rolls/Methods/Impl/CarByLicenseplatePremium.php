<?php

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\MappedHttpMethodRequest;
use Illuminate\Support\Facades\Config;

class CarByLicenseplatePremium extends MappedHttpMethodRequest
{
    public $cacheDays = false; // Is cached through Resource2 cache
    public $resource2Request = true;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        // Names
        'BrandName'                     => ResourceInterface::BRAND_NAME,
        'ModelName'                     => ResourceInterface::MODEL_NAME,
        'Types.0.TypeDescriptionLong'   => ResourceInterface::TYPE_NAME,
        // Rolls IDs
        'MakeID'                        => ResourceInterface::BRAND_ID,
        'ModelID'                       => ResourceInterface::MODEL_ID,
        'Types.0.TypeID'                => ResourceInterface::TYPE_ID,
        // Details
        'DatePart1'                     => ResourceInterface::CONSTRUCTION_DATE,
        'ConstructionYear'              => ResourceInterface::CONSTRUCTION_DATE_YEAR,
        'ConstructionMonth'             => ResourceInterface::CONSTRUCTION_DATE_MONTH,
        'Types.0.VehicleType'           => ResourceInterface::VEHICLE_TYPE,
        'Types.0.SecuritySystemClass'   => ResourceInterface::SECURITY_CLASS,
        'Types.0.CurrentValue'          => ResourceInterface::DAILY_VALUE,
        'Types.0.ConsumerPrice'         => ResourceInterface::REPLACEMENT_VALUE,
        'RegistrationFuelType1ID'            => ResourceInterface::PRIMARY_FUEL_TYPE_ID,
        'RegistrationFuelType1Description'   => ResourceInterface::PRIMARY_FUEL_TYPE_NAME,
        'RegistrationFuelType2ID'            => ResourceInterface::SECONDARY_FUEL_TYPE_ID,
        'RegistrationFuelType2Description'   => ResourceInterface::SECONDARY_FUEL_TYPE_NAME,
        // Extra car data
        'Types.0.OriginalFuelTypeID'    => ResourceInterface::CATALOG_FUEL_TYPE_ID,
        'Types.0.OriginalFuelTypeDescription' => ResourceInterface::CATALOG_FUEL_TYPE_NAME,
        'Types.0.Doors'                 => ResourceInterface::AMOUNT_OF_DOORS,
        'Types.0.Seats'                 => ResourceInterface::AMOUNT_OF_SEATS,
        'Types.0.BodyType'              => ResourceInterface::BODY_TYPE,
        'Types.0.BodyDescription'       => ResourceInterface::BODY_TYPE_DESCRIPTION,
        'Types.0.Traction'              => ResourceInterface::DRIVE_TYPE,
        'Types.0.Transmission'          => ResourceInterface::TRANSMISSION_TYPE,
        'Types.0.Turbo'                 => ResourceInterface::TURBO,
        'Types.0.BpmValue'              => ResourceInterface::BPM_VALUE,
        'Types.0.Vat'                   => ResourceInterface::PRICE_VAT,
        'Types.0.NetPrice'              => ResourceInterface::NET_VALUE,
        'Types.0.Weight'                => ResourceInterface::WEIGHT,
        'Types.0.EnginePower'           => ResourceInterface::POWER,
        'Types.0.Acceleration'          => ResourceInterface::ACCELERATION,
        'Types.0.Cylinders'             => ResourceInterface::CYLINDERS,
        'Types.0.TopSpeed'              => ResourceInterface::TOP_SPEED,
        'Types.0.EngineSize'            => ResourceInterface::CYLINDER_VOLUME,
        'Color'                         => ResourceInterface::COLOR,
        'Color2'                        => ResourceInterface::SECOND_COLOR,
        'Types.0.Energylabel'           => ResourceInterface::ENERGY_LABEL,
        'Types.0.Co2Emissions'          => ResourceInterface::CO2_EMISSION,
        // Extra 'van' data
        'Types.0.MaxLoadingWeight'      => ResourceInterface::LOAD_CAPACITY,

        // Extra ownership data
        'DatePart2'                     => ResourceInterface::NEW_OWNER_DATE,
        'MeldCode'                      => ResourceInterface::CAR_REPORTING_CODE,
        'Import'                        => ResourceInterface::IMPORTED_CAR,
        // Images
        'BrandLogo'                     => ResourceInterface::BRAND_LOGO,
        'BrandLogoThumb'                => ResourceInterface::BRAND_LOGO_THUMB,
        'Types.0.PhotoFront'            => ResourceInterface::PHOTO_FRONT,
        'Types.0.PhotoFrontThumb'       => ResourceInterface::PHOTO_FRONT_THUMB,
        'Types.0.PhotoRear'             => ResourceInterface::PHOTO_REAR,
        'Types.0.PhotoRearThumb'        => ResourceInterface::PHOTO_REAR_THUMB,
        'Types.0.PhotoInterior'         => ResourceInterface::PHOTO_INTERIOR,
        'Types.0.PhotoInteriorThumb'    => ResourceInterface::PHOTO_INTERIOR_THUMB,
        ResourceInterface::ADVISE       => ResourceInterface::ADVISE,
    ];
    protected $resultTransformations = [
        ResourceInterface::FUEL_TYPE_ID         => 'mergeFuelTypeIDs',
        ResourceInterface::FUEL_TYPE_NAME       => 'mergeFuelTypeNames',
        ResourceInterface::BRAND_LOGO           => 'prefixLogoPath',
        ResourceInterface::BRAND_LOGO_THUMB     => 'prefixLogoPath',
        ResourceInterface::PHOTO_FRONT          => 'prefixPhotoPath',
        ResourceInterface::PHOTO_FRONT_THUMB    => 'prefixPhotoPath',
        ResourceInterface::PHOTO_REAR           => 'prefixPhotoPath',
        ResourceInterface::PHOTO_REAR_THUMB     => 'prefixPhotoPath',
        ResourceInterface::PHOTO_INTERIOR       => 'prefixPhotoPath',
        ResourceInterface::PHOTO_INTERIOR_THUMB => 'prefixPhotoPath',
        ResourceInterface::CONSTRUCTION_DATE    => 'convertDate',
        ResourceInterface::NEW_OWNER_DATE       => 'convertDate',
        ResourceInterface::ADVISE               => 'getCoverageAdvice',
    ];
    protected $clearUnmapped = false;

    public function __construct()
    {
        $url = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.vehicle_api_url')) .'licenseplates/premium/{licenseplate}';
        parent::__construct($url);
    }

    protected function applyAuthentication(array $httpOptions)
    {
        $httpOptions['headers']['ClientID'] = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.vehicle_api_clientid'));
        $httpOptions['headers']['ClientSecret'] = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.vehicle_api_secret'));
        $httpOptions['headers']['Accept-Language'] = 'nl-NL';

        return $httpOptions;
    }

    protected function prefixLogoPath($image, $data)
    {
        if (empty($image))
            return null;
        return $data['@unmapped']['PathLogo'] . $image;
    }

    protected function prefixPhotoPath($image, $data)
    {
        if (empty($image))
            return null;
        return $data['@unmapped']['PathPhoto'] . $image;
    }

    protected function convertDate($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    protected function mergeFuelTypeIDs($value, $data)
    {
        if (!empty($data[ResourceInterface::SECONDARY_FUEL_TYPE_ID]))
            return $data[ResourceInterface::PRIMARY_FUEL_TYPE_ID] .'/'. $data[ResourceInterface::SECONDARY_FUEL_TYPE_ID];
        else
            return $data[ResourceInterface::PRIMARY_FUEL_TYPE_ID];
    }

    protected function mergeFuelTypeNames($value, $data)
    {
        if (!empty($data[ResourceInterface::SECONDARY_FUEL_TYPE_NAME]))
            return $data[ResourceInterface::PRIMARY_FUEL_TYPE_NAME] .' / '. $data[ResourceInterface::SECONDARY_FUEL_TYPE_NAME];
        else
            return $data[ResourceInterface::PRIMARY_FUEL_TYPE_NAME];
    }

    /**
     * Coverage advice based on the construction date
     * cars younger than 6 years : WA + Volledig Casco (vc)
     * cars between 6 and 8 years old: WA + Beperkt Casco (bc)
     * cars older than 8 years: WA (wa)
     *
     * @return string
     */
    protected function getCoverageAdvice($advise, $data)
    {
        $date  = strtotime($data[ResourceInterface::CONSTRUCTION_DATE]);
        $diff  = abs(time() - $date);
        $years = floor($diff / (365 * 60 * 60 * 24));
        if($years < 6){
            return 'vc';
        }
        if($years <= 8){
            return 'bc';
        }
        return 'wa';
    }

    public function executeFunction()
    {
        cw('executing premium Licenseplate call!');
        parent::executeFunction();
        if (str_contains($this->getErrorString(), 'Er zijn (nog) geen types gekoppeld aan dit kenteken')) {
            $this->clearErrors();
            $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'rolls.licenseplate.notfound', 'Kenteken onbekend.');
        }

        // Rolls now returns error 500 & 'An error has occurred.' when a car is unknown :/
        if (str_contains($this->getErrorString(), 'An error has occurred.')) {
            $this->clearErrors();
            $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'rolls.licenseplate.notfound', 'Kenteken onbekend.');
        }
    }

    public function getResult()
    {
        if ($this->result === null)
        {
            $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'rolls.licenseplate.notfound', 'Kenteken onbekend.');
            return;
        }
        return parent::getResult();
    }
}