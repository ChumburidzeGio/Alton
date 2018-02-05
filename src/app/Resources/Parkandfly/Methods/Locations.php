<?php
namespace App\Resources\Parkandfly\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\ParkandflyAbstractRequest;


class Locations extends ParkandflyAbstractRequest
{
	protected $cacheDays = false;

    protected $externalToResultMapping = [
        'id'                => ResourceInterface::LOCATION_ID,
        'name'              => ResourceInterface::NAME,
        'description'       => ResourceInterface::DESCRIPTION,
    ];
    protected $resultTransformations = [
        ResourceInterface::LOCATION_ID    => 'castToString',
    ];

	public function __construct()
    {
        parent::__construct('locations');
    }

    public function getResult()
    {
        $locations = [];
        foreach (parent::getResult() as $key => $location)
        {
            // Filter out all locations that do not have rates
            // (usually those are airports themselves)
            if (!$location['@unmapped']['hasRates'])
                continue;
            $locations[] = $location;
        }

        return $locations;
    }
}