<?php

namespace App\Resources\Rolls\Methods\Impl;

use App\Exception\InvalidResourceInput;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;

class CarByLicenseplate extends MappedHttpMethodRequest
{
    public $cacheDays = false; // Is cached through Resource2 cache
    public $resource2Request = true;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'Make'                  => ResourceInterface::BRAND_NAME,
        'Model'                 => ResourceInterface::MODEL_NAME,
        'TypeDescriptionLong'   => ResourceInterface::TYPE_NAME,
        ResourceInterface::CONSTRUCTION_DATE => ResourceInterface::CONSTRUCTION_DATE,
        'FuelTypeDescription'   => [ResourceInterface::FUEL_TYPE_ID, ResourceInterface::FUEL_TYPE_NAME],
        'VehicleType'           => ResourceInterface::VEHICLE_TYPE,
        'SecuritySystemClass'   => ResourceInterface::SECURITY_CLASS_ID,
        'CurrentValue'          => ResourceInterface::DAILY_VALUE,
        'ConsumerPrice'         => ResourceInterface::REPLACEMENT_VALUE,
        'BrandLogo'             => ResourceInterface::BRAND_LOGO,
        'BrandLogoThumb'        => ResourceInterface::BRAND_LOGO_THUMB,
        'PhotoFront'            => ResourceInterface::PHOTO_FRONT,
        'PhotoFrontThumb'       => ResourceInterface::PHOTO_FRONT_THUMB,
        'PhotoRear'             => ResourceInterface::PHOTO_REAR,
        'PhotoRearThumb'        => ResourceInterface::PHOTO_REAR_THUMB,
        'PhotoInterior'         => ResourceInterface::PHOTO_INTERIOR,
        'PhotoInteriorThumb'    => ResourceInterface::PHOTO_INTERIOR_THUMB,
        ResourceInterface::ADVISE => ResourceInterface::ADVISE,
    ];
    protected $resultTransformations = [
        ResourceInterface::BRAND_LOGO           => 'prefixLogoPath',
        ResourceInterface::BRAND_LOGO_THUMB     => 'prefixLogoPath',
        ResourceInterface::PHOTO_FRONT          => 'prefixPhotoPath',
        ResourceInterface::PHOTO_FRONT_THUMB    => 'prefixPhotoPath',
        ResourceInterface::PHOTO_REAR           => 'prefixPhotoPath',
        ResourceInterface::PHOTO_REAR_THUMB     => 'prefixPhotoPath',
        ResourceInterface::PHOTO_INTERIOR       => 'prefixPhotoPath',
        ResourceInterface::PHOTO_INTERIOR_THUMB => 'prefixPhotoPath',
        ResourceInterface::CONSTRUCTION_DATE    => 'getConstructionDate',
        ResourceInterface::ADVISE               => 'getCoverageAdvice',
    ];
    protected $clearUnmapped = true;

    public function __construct()
    {
        $url = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.vehicle_api_url')) .'licenseplates/basic/{licenseplate}';
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

    protected function getConstructionDate($value, $data)
    {
        return $data['@unmapped']['ConstructionYear'] .'-'. str_pad($data['@unmapped']['ConstructionMonth'], 2, "0", STR_PAD_LEFT) . '-01';
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

    public function getResult()
    {
        if ($this->result === null)
        {
            $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'rolls.licenseplate.notfound', 'Kenteken onbekend.');
            return;
        }
        return parent::getResult();
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        if($response && (string)$response->getBody() === "Er zijn (nog) geen types gekoppeld aan dit kenteken"){
            $resource = Resource::where('name','licenseplate.carinsurance')->first();
            throw new InvalidResourceInput($resource, ['licenseplate' => [0 => \Lang::get('errors.carinsurance.error.licenseplate_not_found')]], [], $response->getBody());
        }

        parent::handleError($response, $exception);
    }
}