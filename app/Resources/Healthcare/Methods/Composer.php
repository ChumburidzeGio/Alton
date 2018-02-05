<?php

namespace App\Resources\Healthcare\Methods;


use App\Helpers\IAKHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Healthcare\Healthcare;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class Composer extends HealthcareAbstractRequest
{
    /*
     * fysiotherapie > 18 jaar      435526
     * alternatieve geneeswijzen    2301548
     * brillen en lenzen            435390
     * tandarts > 18 jaar           435812
     * orthodontie < 18 jaar        435764
     * buitenland                   1337
     */

    private $summaryOrder = [
        'Fysiotherapie vanaf 18 jaar' => 'Fysiotherapie >18 jaar',
        'Behandelingen alternatief genezer' => 'Alternatieve geneeswijzen',
        'Brillen' => 'Brillen en lenzen',
        'Vullingen' => 'Tandarts >18 jaar',
        'Orthodontie tot 18 jaar' => 'Orthodontie <18 jaar',
        'Dekkingsgebied' => 'Buitenland'
    ];

    private $coverage_types = [
        'base'        => [],
        'aanvullend'  => [435526, 2301548, 435390, 1337, 435812, 435764],
        'buitenland'  => [1337],
        'ortho'       => [],
        'alternatief' => [],
        'therapie'    => [],
        'gezinsplanning' => [],
        'tand'        => [435812, 435764],
    ];

    public function executeFunction()
    {
        $conditions = array_only($this->params, [ResourceInterface::WEBSITE, ResourceInterface::USER]);

        //Call the base composer to get the composer for the insurance provider
        $composer = array_get(ResourceHelper::callResource2('composer.healthcare2018', ['__id' => $this->params['provider_id']] + $conditions), 0);

        if (!$composer) {
            $this->result = [];

            return;
        }

        // IAK 'combo's should be in 'aanvullend'
        if ($composer['__id'] == IAKHelper::COMPANY_ID) {

            $additionalIakProducts = isset($composer['aanvullend']) ? $composer['aanvullend'] : [];
            $comboIakProducts = isset($composer['combo']) ? $composer['combo'] : [];

            foreach ($comboIakProducts as $k => $v)
                $comboIakProducts[$k]['type'] = 'aanvullend';

            $composer['aanvullend'] = array_merge($additionalIakProducts, $comboIakProducts);
            $composer['combo'] = [];
        }

        $ids          = [];
        $toppings_ids = [];
        foreach (array_keys($this->coverage_types + ['combo' => []]) as $coverage_type) {
            if ($composer[$coverage_type]) {
                foreach ($composer[$coverage_type] as $nr => $product) {
                    $product_id = $this->getProductId($composer, $coverage_type, $product);

                    $ids[] = $product_id;

                    if (isset($product['details']))
                        foreach ($product['details'] as $toppingsType)
                            foreach ($toppingsType['toppings'] as $topping)
                                $toppings_ids[] = $topping['id'];

                    $product['type_label'] = array_get(IndexProduct::$typeLabelMap, $product['type'], 'Verzekering');
                    $composer[$coverage_type][$nr] = $product;
                }
            }
        }

        if (!empty($this->params[ResourceInterface::COLLECTIVITY_ID])) {
            $cols = ResourceHelper::callResource2('collectivity.healthcare2018', ['__id' => $this->params[ResourceInterface::COLLECTIVITY_ID]] + $conditions);
            if (!$cols) {
                $this->setErrorString('Unknown collectivity ID.');

                return;
            }
            $this->params[ResourceInterface::COLLECTIVITY_GROUP_ID] = [head($cols)[ResourceInterface::COLLECTIVITY_GROUP_ID], head($cols)[ResourceInterface::COLLECTIVITY_GROUP_ID_IAK]];
        }

        //3. Get the premiums for the ids we found
        $premiums = ResourceHelper::callResource2('premium.healthcare2018', [
            'product_id'                       => array_merge($ids, $toppings_ids),
            ResourceInterface::BIRTHDATE       => array_get($this->params, ResourceInterface::BIRTHDATE),
            ResourceInterface::COLLECTIVITY_ID => array_get($this->params, ResourceInterface::COLLECTIVITY_GROUP_ID, Healthcare::PREMIUM_GROUP_ID_KOMPARU_AFFILIATE),
            '_limit'                           => 99999,
        ] + $conditions);


        if (array_get($this->params, ResourceInterface::PAYMENT_PERIOD, 1) == 12) {
            $price_field = 'price_12m';
            $discount_field = function ($item) {return $item['discount_12m'];};
            $multiplier = 12;
        } else {
            $price_field = 'price_default';
            $multiplier = 1;
            $discount_field = function(){return 0;};
        }

        $price_field = function ($item) use ($price_field, $multiplier) {
            return  $item[$price_field] * $multiplier;
        };
        $discount = function($item) use ($discount_field, $multiplier) {
            return $item['own_risk_amount'] * $multiplier + $discount_field($item);
        };

        $premiumIds = array_fill_keys(array_pluck($premiums, ResourceInterface::PRODUCT_ID), true);

        //4. Go through composer and enrich with prices from premium
        //Assemble the products
        foreach (array_keys($this->coverage_types) as $coverage_type) {
            if ($composer[$coverage_type]) {
                foreach ($composer[$coverage_type] as &$product) {
                    $product_id = $this->getProductId($composer, $coverage_type, $product);
                    if(isset($product[ResourceInterface::ID])){
                        //For frontend consistency between IAK and non IAK products,
                        //cast the product id to string
                        $product[ResourceInterface::ID] = strval($product[ResourceInterface::ID]);
                    }
                    $price = $this->getPriceFromPremium($premiums, $product_id, $price_field);
                    if ($coverage_type === 'base') {
                        $product['own_risk'] = $this->getOwnRisks($premiums, $product_id, $price_field, $discount);
                    }
                    if ($price !== false) {
                        //Found a price
                        $product['price'] = $price;
                    } else {
                        $product = false;
                        array_splice($ids, array_search($product_id, $ids), 1);
                        continue;
                    }

                    // Add topping prices & sorting
                    if (isset($product['details'])) {
                        foreach ($product['details'] as $detailNr => $toppingsType) {
                            $product['details'][$detailNr]['price'] = 0;
                            foreach ($toppingsType['toppings'] as $toppingNr => $topping) {
                                $price = $this->getPriceFromPremium($premiums, $topping['id'], $price_field);
                                $product['details'][$detailNr]['toppings'][$toppingNr]['topping'] = true;
                                if ($price !== false) {
                                    $product['details'][$detailNr]['toppings'][$toppingNr]['price'] = $price;
                                }
                                else {
                                    unset($product['details'][$detailNr]['toppings'][$toppingNr]);
                                }
                            }
                            // Sort toppings by price (could be wrong, but seems ok so far!)
                            $product['details'][$detailNr]['toppings'] = array_values(array_sort($product['details'][$detailNr]['toppings'], function ($topping) {
                                return $topping['price'];
                            }));
                            $product['details'][$detailNr]['has_toppings'] = count($product['details'][$detailNr]['toppings']) ? 1 : 0;
                        }
                    }

                    // Remove all pcs that contain any product we do not have prices for
                    if (isset($product['pcs'])) {
                        foreach ($product['pcs'] as $pc => $x) {
                            $pcIds = explode(',', $pc);
                            foreach ($pcIds as $pcId) {
                                if (!isset($premiumIds[$pcId])) {
                                    unset($product['pcs'][$pc]);
                                    break;
                                }
                            }
                        }
                    }
                }

                $coverages = array_values(array_filter($composer[$coverage_type]));
                usort($coverages, function ($c1, $c2) {
                    return $c1['price'] - $c2['price'];
                });
                $composer[$coverage_type] = $coverages;
            }
        }

        //Get the provider information
        $provider            = array_get(ResourceHelper::callResource2('company.healthcare2018', ['resource_id' => $this->params['provider_id']] + $conditions), 0);
        $composer['company'] = $provider;

        //Add the summaries
        $composer            = $this->getSummaries($ids, $composer);

        //Order the summaries if the iak user is present but NOT for IAK provider
        if(isset($this->params[ResourceInterface::USER]) && $this->params[ResourceInterface::USER] == IAKHelper::USER_ID && $composer['__id'] != IAKHelper::COMPANY_ID){
            $composer = $this->sortSummariesForIAK($composer);
        }

        //Find the first 'Vergoedingenoverzicht' PDF
        $pdfs = ResourceHelper::callResource2('pdfs.healthcare2018', [ResourceInterface::PRODUCT_ID => $ids, ResourceInterface::TYPE => 'Vergoedingenoverzicht'] + $conditions);
        $composer[ResourceInterface::COVERAGES_PDF] = array_get($pdfs, '0.'. ResourceInterface::URL);

        $this->result = $composer;
    }

    public function getPriceFromPremium($premiums, $product_id, $price_field)
    {
        $discovered = [];
        foreach ($premiums as $premium) {
            if ($premium['product_id'] == $product_id) {
                $discovered[] = $premium;
            }
        }

        if (count($discovered) > 1) {
            //We have multiple prices use the own_risk value to group them
            usort($discovered, function ($a, $b) {
                return $a['own_risk'] < $b['own_risk'] ? -1 : 1;
            });

            return $price_field(head($discovered));

        } elseif (count($discovered) === 1) {
            return $price_field($discovered[0]);
        }

        return false;
    }

    public function getOwnRisks($premiums, $product_id, $price_field, $discount)
    {
        $discovered = [];
        foreach ($premiums as $premium) {
            if ($premium['product_id'] === $product_id) {
                $discovered[] = $premium;
            }
        }

        if (count($discovered) > 0) {
            usort($discovered, function ($a, $b) {
                return $a['own_risk'] < $b['own_risk'] ? -1 : 1;
            });

            $ownrisks = array_map(function ($item) use ($price_field, $discount) {
                return ['__id' => $item['own_risk'], 'name' => $item['own_risk'], 'discount' => $discount($item), 'price_default' => $price_field($item)];
            }, $discovered);

            return $ownrisks;
        }

        return false;
    }

    /**
     * @param $composer
     * @param $coverage_type
     * @param $product
     *
     * @return string
     */
    private function getProductId($composer, $coverage_type, $product)
    {
        $prefix     = ($composer['__id'] != IAKHelper::COMPANY_ID)
            ? ($coverage_type === 'base' ? 'H' : 'A') : '';
        $product_id = $prefix . $product['id'];


        return $product_id;
    }

    /**
     * @param $ids
     * @param $composer
     *
     * @return array
     */
    private function getSummaries($ids, $composer)
    {
        /** @var Builder $query */
        $query = DB::connection('mysql_product')->table('product_summaries_healthcare2018');

        $cost_types = DB::connection('mysql_product')->table('cost_types_healthcare2018')->get();
        $cost_types = Collection::make($cost_types)->keyBy('__id');

        foreach (array_unique(call_user_func_array('array_merge', $this->coverage_types)) as $cost_type_id) {
            foreach ($ids as $product_id) {
                $query->orWhere(function (Builder $or) use ($cost_type_id, $product_id) {
                    $or
                        ->where('cost_type_id', $cost_type_id)
                        ->where('product_id', $product_id);
                });
            }
        }

        $summaries = $query->get()->toArray();

        $summaries = array_reduce($summaries, function ($summaries, $s) use($cost_types){
            $cost_type = $cost_types->get($s->cost_type_id);
            $summaries[$s->product_id][$s->cost_type_id] = [
                'product_summary' => $s->product_summary,
                'label'           => $s->label,
                'name'            => isset($cost_type->name)? $cost_type->name : null
            ];

            return $summaries;
        }, []);

        foreach ($this->coverage_types as $coverage_type => $coverage_ids) {
            if (!isset($composer[$coverage_type])) {
                continue;
            }

            foreach ($composer[$coverage_type] as &$product) {
                $product_id = $this->getProductId($composer, $coverage_type, $product);

                $product_summaries = array_only(array_get($summaries, $product_id, []), $coverage_ids);

                if ($product_summaries) {
                    $product['details'] = array_merge(array_get($product, 'details', []), array_values($product_summaries));
                }
            }
        }

        return $composer;
    }

    private function sortSummariesForIAK($composer)
    {
        foreach ($composer['aanvullend'] as $key => $additionalProduct){
            $details = [];
            foreach ($this->summaryOrder as $name => $iakLabel) {

                //Try to find the data item the name refers to
                $foundInDetails = null;
                foreach ($additionalProduct['details'] as $detailItem){
                    if($detailItem['name'] === $name){
                        $foundInDetails = $detailItem;
                    }
                }

                if($foundInDetails){
                    //You have an item in the details data
                    //Use the label that iak wants and the info from the data item
                    $details[] = [
                        'product_summary' => $foundInDetails['product_summary'],
                        'label' => $foundInDetails['label'],
                        'name' => $iakLabel
                    ];
                }else{
                    //You do not have the item for the summary in the data
                    //Just add an empty item for that
                    $details[] = [
                        'product_summary' => 'Geen vergoeding',
                        'label' => 'Geen vergoeding',
                        'name' => $iakLabel
                    ];

                }
            }
            $composer['aanvullend'][$key]['details'] = $details;
        }
        return $composer;
    }
}