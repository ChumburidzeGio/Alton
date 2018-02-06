<?php
/**
 * Created by PhpStorm.
 * User: kostya
 * Date: 05/10/2016
 * Time: 12:23
 */

namespace App\Resources\Parkingci\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkingci\ParkingciAbstractRequest;

class Airports extends ParkingciAbstractRequest
{

    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'id'                => ResourceInterface::AIRPORT_ID,
        'name'              => ResourceInterface::NAME,
        'description'       => ResourceInterface::DESCRIPTION,
        'logo'              => ResourceInterface::BRAND_LOGO,
        'iata'              => ResourceInterface::AIRPORT_CODE,
        'category'          => ResourceInterface::CATEGORY,
    ];
    protected $resultTransformations = [
        ResourceInterface::NAME => 'trimString',
    ];

    protected $resultKeyname = 'airport';

    public function __construct()
    {
        parent::__construct('airports');
    }

    protected function trimString($string)
    {
        return trim($string);
    }
}