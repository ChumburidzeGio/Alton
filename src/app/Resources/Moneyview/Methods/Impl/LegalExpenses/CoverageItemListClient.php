<?php

namespace App\Resources\Moneyview\Methods\Impl\LegalExpenses;

use App\Helpers\DocumentHelper;
use App\Interfaces\ResourceInterface;
use Config;
use Komparu\Value\ValueInterface;

class CoverageItemListClient extends PremiumExtendedClient
{
    protected $outputFields = [];

    protected $coverageNames = [
        'DEKKING_CONSUMENT'         => ResourceInterface::COVERAGE_CONSUMER,
        'DEKKING_INKOMEN'           => ResourceInterface::COVERAGE_INCOME,
        'DEKKING_VERKEER'           => ResourceInterface::COVERAGE_TRAFFIC,
        'DEKKING_WONEN'             => ResourceInterface::COVERAGE_HOUSING,

        'DEKKING_SCHEIDINGSMEDIATION'     => ResourceInterface::COVERAGE_DIVORCE_MEDIATION,
        'DEKKING_FISCAAL_EN_VERMOGEN'     => ResourceInterface::COVERAGE_TAXES_AND_CAPITAL,
        'DEKKING_PERSONEN_EN_FAMILIERECHT' => ResourceInterface::COVERAGE_FAMILY_LAW,
        'DEKKING_ARBEID'                => ResourceInterface::COVERAGE_WORK,
        'DEKKING_MEDISCH'               => ResourceInterface::COVERAGE_MEDICAL,
        'DEKKING_BURENRECHT'            => ResourceInterface::COVERAGE_NEIGHBOUR_DISPUTES,
        'DEKKING_EIGENWONING'           => ResourceInterface::COVERAGE_HOUSING_OWNED_HOUSE,
        'DEKKING_VERH_EIGENWONING'      => ResourceInterface::COVERAGE_HOUSING_FOR_RENT,
        'DEKKING_VERH_WOONEENH'         => ResourceInterface::COVERAGE_HOUSING_RENTED_LIVINGUNITS,
        'DEKKING_VERH_BEDREENH'         => ResourceInterface::COVERAGE_HOUSING_RENTED_WORKUNITS,
        'DEKKING_MOTORRIJTUIG_ONGEVAL'  => ResourceInterface::COVERAGE_TRAFFIC_ROADVEHICLE_ACCIDENT,
        'DEKKING_MOTORRIJTUIG_OVERIG'   => ResourceInterface::COVERAGE_TRAFFIC_ROADVEHICLE_OTHER,
        'DEKKING_PLEZIERVAARTUIG_ONGEVAL' => ResourceInterface::COVERAGE_TRAFFIC_WATERVEHICLE_ACCIDENT,
        'DEKKING_PLEZIERVAARTUIG_OVERIG' => ResourceInterface::COVERAGE_TRAFFIC_WATERVEHICLE_OTHER,
        'DEKKING_VAKWONING_NL'          => ResourceInterface::COVERAGE_HOUSING_VACATIONHOME_NL,
        'DEKKING_VAKWONING_BUITENL'     => ResourceInterface::COVERAGE_HOUSING_VACATIONHOME_OTHER,
    ];


    protected $requestedCoverages = [];

    protected $extraArguments = [
        ResourceInterface::PRODUCT_ID => [
            'rules'   => 'string',
            'description' => 'Product ID',
        ],
        ResourceInterface::USER => [
            'rules'   => 'string',
            'description' => 'User ID',
        ],
        ResourceInterface::WEBSITE => [
            'rules'   => 'string',
            'description' => 'Website ID',
        ],
    ];

    protected $product;
    protected $coveragesConfiguration;

    public function __construct()
    {
        $this->arguments += $this->extraArguments;
        parent::__construct();
    }

    public function setParams(Array $params)
    {
        if (!isset($params[ResourceInterface::PRODUCT_ID])) {
            $this->setErrorString('Field `product_id` required.');
            return;
        }

        $documents = DocumentHelper::get('product', 'legalexpensesinsurance', [
            'filters' => ['__id' => $params[ResourceInterface::PRODUCT_ID], 'active' => 1, 'enabled' => 1],
            'conditions' => ['user' => array_get($params, ResourceInterface::USER), 'website' => array_get($params, ResourceInterface::WEBSITE)],
            'limit'      => ValueInterface::INFINITE,
        ]);

        $documents = $documents->toArray();
        if (!isset($documents['documents'][0])) {
            $this->setErrorString('Product with ID `'. $params[ResourceInterface::PRODUCT_ID] .'` not found.');
            return;
        }
        $this->product = $documents['documents'][0];
        if (!empty($this->product[ResourceInterface::COVERAGES_CONFIGURATION]))
            $this->coveragesConfiguration = json_decode($this->product[ResourceInterface::COVERAGES_CONFIGURATION], true);

        if (trim($params[ResourceInterface::SELECTED_COVERAGES]) === '')
            $params[ResourceInterface::SELECTED_COVERAGES] = [];
        if (!is_array($params[ResourceInterface::SELECTED_COVERAGES]))
            $params[ResourceInterface::SELECTED_COVERAGES] = explode(',', (string)$params[ResourceInterface::SELECTED_COVERAGES]);

        // No selected coverages = show all
        if (in_array('all', $params[ResourceInterface::SELECTED_COVERAGES])) {
            if ($this->coveragesConfiguration)
                $params[ResourceInterface::SELECTED_COVERAGES] = array_keys($this->coveragesConfiguration);
            else
                $params[ResourceInterface::SELECTED_COVERAGES] = str_replace('coverage_', 'insure_', array_values($this->coverageNames));
        }

        $this->requestedCoverages = $params[ResourceInterface::SELECTED_COVERAGES];

        parent::setParams($params);
    }

    /**
     * Filter op erzekerd bedrag
     */
    public function getResult()
    {
        $fullResults = parent::getResult();

        $result = [];
        foreach ($fullResults as $fullResult)
        {
            if ($fullResult[ResourceInterface::RESOURCE_PREMIUM_EXTENDED_ID] != $this->product[ResourceInterface::RESOURCE_PREMIUM_EXTENDED_ID])
                continue;

            $coverages = [];
            foreach ($this->coverageNames as $remoteName => $coverageName)
            {
                $baseNameRemote = str_replace('DEKKING_', '', $remoteName);
                $baseNameLocal = str_replace('coverage_', '', $coverageName);

                $coverage = [
                    ResourceInterface::NAME => 'insure_'. $baseNameLocal,
                    ResourceInterface::LABEL => ucfirst(str_replace('_', ' ', strtolower($baseNameRemote))),
                    ResourceInterface::COVERAGE => null,
                    ResourceInterface::PRICE => null,
                    ResourceInterface::TITLE => null,
                    ResourceInterface::DESCRIPTION => null,
                    ResourceInterface::IS_MANDATORY => false,
                    ResourceInterface::IS_PRESELECTED => false,
                ];

                $coverage[ResourceInterface::COVERAGE] = !empty($fullResult['DEKKING_'. $baseNameRemote]) && $fullResult['DEKKING_'. $baseNameRemote] == 'JA';
                // Prices are already mapped
                if (isset($fullResult['price_insure_'. $baseNameLocal]))
                    $coverage[ResourceInterface::PRICE] = $fullResult['price_insure_'. $baseNameLocal];
                if (isset($fullResult['OPM_'. $baseNameRemote]))
                    $coverage[ResourceInterface::TITLE] = $fullResult['OPM_'. $baseNameRemote];

                if (!$coverage[ResourceInterface::COVERAGE])
                    continue;

                if ($coverage[ResourceInterface::PRICE] === null)
                    continue;

                if ($this->coveragesConfiguration && !isset($this->coveragesConfiguration['insure_'. $baseNameLocal]))
                    continue;

                // Add overloaded data from product configuration data
                if (isset($this->coveragesConfiguration['insure_'. $baseNameLocal]))
                    $coverage = $this->coveragesConfiguration['insure_'. $baseNameLocal] + $coverage;

                $coverages[] = $coverage;
            }

            // Re-order coverage by configuration order
            if ($this->coveragesConfiguration)
            {
                $orderedCoverages = [];
                foreach (array_keys($this->coveragesConfiguration) as $coverageName) {
                    foreach ($coverages as $coverage) {
                        if ($coverage[ResourceInterface::NAME] == $coverageName) {
                            $orderedCoverages[] = $coverage;
                            break;
                        }
                    }
                }
                $coverages = $orderedCoverages;
            }

            $result[] = [
                ResourceInterface::TITLE => $fullResult['LOCAL'],
                ResourceInterface::SPEC_NAME => $fullResult['SPECIFIC'],
                ResourceInterface::COVERAGE => $coverages,
            ];
        }

        if (isset($result[0]))
            return $result[0][ResourceInterface::COVERAGE];
        else
            return [];
    }
}
