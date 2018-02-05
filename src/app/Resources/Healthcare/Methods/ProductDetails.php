<?php

namespace App\Resources\Healthcare\Methods;


use App\Helpers\DocumentHelper;
use App\Helpers\Healthcare2018Helper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use DB;
use Mockery\CountValidator\Exception;

class ProductDetails extends HealthcareAbstractRequest
{
    protected $biggerIsBetterFields = [
        'waardering',
        'max_vergoedingspercentage',
        'max_bedrag_per_consult_behandeling',
        'max_aantal_behandelingen_p_jr',
        'volledige_vergoeding',
        'gemiddeld_gecontracteerde_tarieven',
        'marktconform_bedrag',
        'tarief_gecontracteerde_zorg',
        'max_bedrag_p_jr',
        'max_aantal_uren',
        'max_tarief_zitting_per_kwartier',
        'percentage_techniekkosten',
        'max_bedrag_tarief',
        'max_bedrag_techniekkosten',
        'max_bedrag_enkelvoudig',
        'max_bedrag_meervoudig',

        // Unknown?
        'beperking_codes',
    ];

    protected $smallerIsBetterFields = [
        'eigen_bijdrage_p_zitting',
        'min_leeftijd',
        'eigen_bijdrage_p_uur',
        'min_sterkte',
    ];

    protected $order = [
        'huisarts_en_ziekenhuiszorg',
        'therapieen',
        'alternatieve_geneeswijzen',
        'hulpmiddelen_w_o_brillen_en_lenzen',
        'orthodontie',
        'tandheelkundige_hulp_tot_18_jaar',
        'tandheelkundige_hulp_vanaf_18_jaar',
        'zwangerschap_en_bevalling',
        'hulp_in_het_buitenland',
        'geneesmiddelen',
        'cursussen_en_extra_vergoedingen',
        'ziekenvervoer',
        'verpleging_verzorging_en_begeleiding',
    ];

    public function executeFunction()
    {
        $conditions = array_only($this->params, [ResourceInterface::WEBSITE, ResourceInterface::USER]);

        $product = head(ResourceHelper::callResource2(
            'product.healthcare2018',
            [
                ResourceInterface::PRODUCT_ID => $this->params[ResourceInterface::PRODUCT_ID],
                OptionsListener::OPTION_NO_PROPAGATION => true,
            ] + $conditions
        ));

        if (!$product) {
            $this->setPrettyErrorString('Unknown product_id.');
            return;
        }

        // IAK base products have to add a '1', for their "own risk" differences
        $product_ids[] = starts_with($product['base_id'], 'SV') ? $product['base_id'] .'1' : $product['base_id'];
        $product_ids   = array_merge($product_ids, explode(',', $product['child_source_ids']));

        $coverages = ResourceHelper::callResource2(
            'coverages.healthcare2018',
            [
                '__id' => $product_ids,
            ] + $conditions
        );

        $filterKeys = array_get($this->params, ResourceInterface::FILTER_KEYS, []);
        if (is_string($filterKeys))
            $filterKeys = explode(',', $filterKeys);

        $mergedCoverage = [];

        foreach ($coverages as $coverage) {
            if (preg_match("/^{$product['base_id']}.*/msi", $coverage['__id'])) {
                $mergedCoverage = $this->mergeRecursive($mergedCoverage, $coverage[ResourceInterface::COVERAGE], $filterKeys, true);
            }
        }

        foreach ($coverages as $coverage) {
            if (!preg_match("/^{$product['base_id']}.*/msi", $coverage['__id'])) {
                $mergedCoverage = $this->mergeRecursive($mergedCoverage, $coverage[ResourceInterface::COVERAGE], $filterKeys, false);
            }
        }

        $mergedCoverage = $this->orderCoverages($mergedCoverage);

        $this->result = [
            ResourceInterface::PRODUCT_ID => $product['product_id'],
            ResourceInterface::COVERAGE => $mergedCoverage,
        ];
    }

    /**
     * @param $array
     * @return array
     */
    public function orderCoverages($array)
    {
        $ordered = array();

        foreach ($this->order as $key) {
            if (array_key_exists($key, $array)) {
                $ordered[$key] = $array[$key];
                unset($array[$key]);
            }
        }

        $coverages = $ordered + $array;

        return $coverages;
    }

    public function mergeRecursive($item1, $item2, $filterKeys = [], $isBase = false)
    {
        if (isset($item2['details_summary'])) {
            if ($isBase) {
                $item2['base_summary'] = $item2['details_summary'];
            }
            else
                $item2['add_summary'] = $item2['details_summary'];
            unset($item2['details_summary']);
        }

        foreach ($item2 as $k => $v) {
            if (is_array($v)) {
                $item1[$k] = $this->mergeRecursive(isset($item1[$k]) ? $item1[$k] : [], $v, $filterKeys, $isBase);

                if (array_get($this->params, ResourceInterface::REQUIRE_DESCRIPTION)) {
                    // Subitem has no description, and is a leaf (aka, has no sub-arrays)
                    if (!isset($item1[$k]['description']) && count($item1[$k]) == count($item1[$k], COUNT_RECURSIVE)) {
                        unset($item1[$k]);
                    }
                }
            }
            else if ($filterKeys && !in_array($k, $filterKeys)) {
                continue;
            }
            else if (!isset($item1[$k]) || is_string($v)) {
                $item1[$k] = $v;
            }
            else if (is_float($v) || is_int($v)) {
                if (in_array($k, $this->biggerIsBetterFields))
                    $item1[$k] = max($item1[$k], $v);
                else if (in_array($k, $this->smallerIsBetterFields))
                    $item1[$k] = min($item1[$k], $v);
                else
                {
                    if ($item1[$k] !== $v) {
                        //throw new \Exception('Cannot compare field `'. $k .'`.');
                        //dd($k, $item1, $item2);
                        // Always overwrite for now...
                        $item1[$k] = $v;
                    }
                }
            }
        }
        return $item1;
    }
}