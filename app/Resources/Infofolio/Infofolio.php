<?php

namespace App\Resources\Infofolio;

use App\Resources\AbstractServiceRequest;

class Infofolio extends AbstractServiceRequest
{
    protected $serviceProvider = 'infofolio';


    protected $filterKeyMapping = [
        self::POSTAL_CODE         => 'zipcode',
        self::HOUSE_NUMBER        => 'houseNumber',
        self::HOUSE_NUMBER_SUFFIX => 'houseLetter',
    ];


    public $fieldMapping = [
        'gebruiksoppervlakte'          => self::LIVING_AREA_TOTAL,
        //todo!! not in interface yet
        'inhoud'                       => self::HOUSE_VOLUME_SOURCE,
        'bouwjaar'                     => self::CONSTRUCTION_DATE_YEAR,
        'grondoppervlakte_bijgebouwen' => self::PARCEL_SIZE,
        'monumentaanduiding_code'      => self::HOUSE_IS_MONUMENT,


        //to convert
        'eigendom_gebruik_indicatie'   => self::HOUSE_OWNER,
        'gebruiksfunctie'              => self::HOUSE_USAGE,
        'aantal_kamers_code'           => self::SLEEPHOBBYSTUDYWORK_ROOM_COUNT,
        'bouwstatus_code'              => self::HOUSE_IS_NEWLY_BUILT, //????
        'type_opstal'                  => self::HOUSE_TYPE,

        'soort_dak_code'                 => self::HOUSE_ROOF_MATERIAL,


        //
        'inboedelwaarde_basis_indicatie' => self::CONTENTS_ESTIMATE,
        'herbouwwaarde_indicatie'        => self::HOUSE_REBUILD,
        'grondoppervlakte_hoofdgebouw'   => self::SURFACE_AREA_MAIN_BUILDING,
        'aantal_bijgebouwen'             => self::NUMBER_OF_ADDITIONAL_BUILDINGS,
        'aantal_woonlagen'               => self::NUMBER_OF_FLOORS,
        'woz_waardeindicatie'            => self::VALUATION_OF_REAL_ESTATE,


        /**
         * Muren
         * Vloeren
         * Fundering
         * grondoppervlakte_bijgebouwen
         * nieuwbouw? wtf?
         */

    ];


    protected $filterMapping = [
        self::HOUSE_OWNER                    => 'filterInfoMVHouseOwner',
        self::HOUSE_USAGE                    => 'filterInfoMVHouseUsage',
        self::SLEEPHOBBYSTUDYWORK_ROOM_COUNT => 'filterInfoMVRoomCount',
        self::HOUSE_TYPE                     => 'filterInfoMVHouseType',
        self::HOUSE_ROOF_MATERIAL            => 'filterInfoMVRoofMaterial',
    ];


    /*
     *
     * Soort woning
    Heiwerk meeverzekeren
    Bouwaard vloeren
    Bouwaard muren
    Bouwaard dak
    Rieten dak
    Bouwjaar
    Oppervlakte woning
    Oppervlakte bijgebouwen

    Inhoud woning
     */


    protected $methodMapping = [
        'realestateinfo' => [
            'class'       => \App\Resources\Infofolio\Methods\GetRealestateInfo::class,
            'description' => 'Get realestate info based on housenumber and adress'
        ],
        'addressinfo'    => [
            'class'       => \App\Resources\Infofolio\Methods\CheckAddress::class,
            'description' => 'Get realestate info based on housenumber and adress'
        ]
    ];
}