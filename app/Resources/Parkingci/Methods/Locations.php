<?php

namespace App\Resources\Parkingci\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkingci\ParkingciAbstractRequest;

class Locations extends ParkingciAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'park_id'                     => ResourceInterface::LOCATION_ID,
        'name'                        => ResourceInterface::NAME,
        'info'                        => ResourceInterface::DESCRIPTION,
        'info_en'                     => ResourceInterface::DESCRIPTION_EN,
        'info_fr'                     => ResourceInterface::DESCRIPTION_FR,
        'info_de'                     => ResourceInterface::DESCRIPTION_DE,
        'image'                       => ResourceInterface::BRAND_LOGO,
        'airport_id'                  => ResourceInterface::AIRPORT_ID,
        'service_id'                  => ResourceInterface::SERVICE,
        'service'                     => ResourceInterface::SERVICE_NAME,
        'location'                    => ResourceInterface::LOCATION,
        'distance'                    => ResourceInterface::DISTANCE,
        'time'                        => ResourceInterface::TIME,
        'destination'                 => ResourceInterface::DESTINATION,
        'rating'                      => ResourceInterface::RATING,
        'voorwaarden'                 => ResourceInterface::CONDITIONS,
        'official'                    => ResourceInterface::OFFICIAL,
        'source'                      => ResourceInterface::SOURCE,
        'active'                      => ResourceInterface::ENABLED,
        'image1'                      => 'image1',
        'image2'                      => 'image2',
        'image3'                      => 'image3',
        'image4'                      => 'image4',
        'image5'                      => 'image5',
        'image6'                      => 'image6',
        'image7'                      => 'image7',
        'image8'                      => 'image8',
        'image9'                      => 'image9',
        'image10'                     => 'image10',
        'map_image'                   => ResourceInterface::MAP_IMAGE,
        'eco_points'                  => ResourceInterface::ECO_POINTS,
        'company.name'                => ResourceInterface::COMP_NAME,
        'company.id'                  => ResourceInterface::COMP_ID,
        'company.image'               => ResourceInterface::COMP_IMAGE,
        'company.title'               => ResourceInterface::COMP_TITLE,
        'email1'                      => ResourceInterface::EMAIL1,
        'email2'                      => ResourceInterface::EMAIL2,
        'email_from'                  => ResourceInterface::EMAIL_FROM,
        'mail'                        => ResourceInterface::MAIL,
        'mail_en'                     => ResourceInterface::MAIL_EN,
        'mail_fr'                     => ResourceInterface::MAIL_FR,
        'mail_de'                     => ResourceInterface::MAIL_DE,
        'parkeeroptie.options.option' => ResourceInterface::OPTIONS,
        'category'                    => ResourceInterface::CATEGORY,
        'prod_id'                     => ResourceInterface::RESOURCE__ID,
        'type'                        => ResourceInterface::TYPE,
    ];
    protected $resultTransformations = [
        ResourceInterface::BRAND_LOGO => 'tweakImage',
        ResourceInterface::OFFICIAL   => 'castToBool',
        ResourceInterface::ENABLED    => 'castToBool',
        'image1'                      => 'tweakImage',
        'image2'                      => 'tweakImage',
        'image3'                      => 'tweakImage',
        'image4'                      => 'tweakImage',
        'image5'                      => 'tweakImage',
        'image6'                      => 'tweakImage',
        'image7'                      => 'tweakImage',
        'image8'                      => 'tweakImage',
        'image9'                      => 'tweakImage',
        'image10'                     => 'tweakImage',
    ];

    protected $resultKeyname = 'parkeerbeheerder';

    public function __construct()
    {
        parent::__construct('parkeerbeheerder/false');
    }

    protected function getDefaultParams()
    {
        return [
            'show_sources' => '1', // Magickal parameter to get some more 'sensitive' data back like 'source'
        ];
    }

    protected function tweakImage($img)
    {
        if($img != '' && !stristr($img, 'userfiles/')){
            $img = 'userfiles/parking/' . $img;
        }
        return (trim($img) !== '') ? ('//static.komparu.com/' . $img) : null;
    }
}