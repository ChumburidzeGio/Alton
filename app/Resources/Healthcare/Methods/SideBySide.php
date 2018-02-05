<?php

namespace App\Resources\Healthcare\Methods;


use App\Exception\PrettyServiceError;
use App\Helpers\IAKHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use DB;
use Illuminate\Support\Collection;

class SideBySide extends HealthcareAbstractRequest
{

    static $activeFilters = [
        '11498698_middelen_bij_adhd' => ['cost_type_id' => '11498698', 'label' => 'Middelen bij ADHD'],
        'coverage_area' => [],
        '2301548_behandelingen_alternatief_genezer' => ['cost_type_id' => '2301548', 'label' => 'Behandelingen alternatief genezer'],
        '435090_chiropractie' => ['cost_type_id' => '435090', 'label' => 'Chiropractie - alternatieve geneeswijzen'],
        '435156_anticonceptiva' => ['cost_type_id' => '435156', 'label' => 'Anticonceptiva'],
        '435352_zelfzorg_geneesmiddelen' => ['cost_type_id' => '435352', 'label' => 'Zelfzorg Geneesmiddelen'],
        '435356_pruiken' => ['cost_type_id' => '435356', 'label' => 'Pruiken'],
        '435380_hoortoestel' => ['cost_type_id' => '435380', 'label' => 'Hoortoestellen'],
        '435390_brillen' => ['cost_type_id' => '435390', 'label' => 'Brillen'],
        '435396_ooglaserbehandeling_en_lensimplantatie' => ['cost_type_id' => '435396', 'label' => 'Ooglaserbehandeling en lensimplantatie'],
        '435400_uitwendige_hulpmiddelen_te_gebruiken_bij_het_controlere' => ['cost_type_id' => '435400', 'label' => 'Hulpmiddelen diabetes'],
        '435436_steunzolen' => ['cost_type_id' => '435436', 'label' => 'Steunzolen'],
        '435474_plastische_cosmetische_chirurgie' => ['cost_type_id' => '435474', 'label' => 'Plastische / cosmetische chirurgie'],
        '435476_bovenooglidcorrectie' => ['cost_type_id' => '435476', 'label' => 'Bovenooglidcorrectie'],
        '435512_acnetherapie' => ['cost_type_id' => '435512', 'label' => 'Acnétherapie'],
        '435520_elektrische_laserepilatie' => ['cost_type_id' => '435520', 'label' => 'Elektrische / laserepilatie'],
        '435526_fysiotherapie_vanaf_18_jaar' => ['cost_type_id' => '435526', 'label' => 'Fysiotherapie vanaf 18 jaar'],
        '435548_podotherapie' => ['cost_type_id' => '435548', 'label' => 'Podotherapie'],
        '435560_stottertherapie' => ['cost_type_id' => '435560', 'label' => 'Stottertherapie'],
        '435664_sportarts' => ['cost_type_id' => '435664', 'label' => 'Sportarts'],
        '435676_kunstgebit' => ['cost_type_id' => '435676', 'label' => 'Tandarts kunstgebit'],
        '435692_kraamzorg_thuis_kraaminrichtinghotel' => ['cost_type_id' => '435692', 'label' => 'Kraamzorg'],
        '435762_implantatenimplantologie' => ['cost_type_id' => '435762', 'label' => 'Tandarts implantaten'],
        '435764_orthodontie_tot_18_jaar' => ['cost_type_id' => '435764', 'label' => 'Orthodontie tot 18 jaar'],
        '435786_orthodontie_vanaf_18_jaar' => ['cost_type_id' => '435786', 'label' => 'Orthodontie vanaf 18 jaar'],
        '435806_orthopedisch_schoeisel' => ['cost_type_id' => '435806', 'label' => 'Orthopedisch schoeisel'],
        '435812_vullingen' => ['cost_type_id' => '435812', 'label' => 'Tandarts vullingen'],
        '435818_kronen_en_bruggen' => ['cost_type_id' => '435818', 'label' => 'Tandarts kronen en bruggen'],
        '435832_pedicure' => ['cost_type_id' => '435832', 'label' => 'Pedicure'],
        'free_choice' => [],
    ];

    public function executeFunction()
    {
        if (!isset($this->params[ResourceInterface::PRODUCT_IDS])){
            $resource = Resource::where('name', 'side_by_side.healthcare2018')->firstOrFail();
            throw new PrettyServiceError($resource,$this->params,'Geen producten gevonden');
        }

        $conditions = array_only($this->params, [ResourceInterface::WEBSITE, ResourceInterface::USER]);

        $product_ids = explode(',', $this->params[ResourceInterface::PRODUCT_IDS]);
        if($product_ids){
            $this->result = [];

            //Get the products
            $products = ResourceHelper::callResource2('product.healthcare2018', [
                ResourceInterface::PRODUCT_ID => $product_ids,
                ResourceInterface::BIRTHDATE => $this->params[ResourceInterface::BIRTHDATE],
                ResourceInterface::OWN_RISK => $this->params[ResourceInterface::OWN_RISK],
                ResourceInterface::PAYMENT_PERIOD => $this->params[ResourceInterface::PAYMENT_PERIOD],
                ResourceInterface::COLLECTIVITY_ID => array_get($this->params, ResourceInterface::COLLECTIVITY_ID),
            ] + $conditions);

            $products = Collection::make($products)->keyBy('product_id');

            //Get the details of the products
            foreach ($product_ids as $product_id){
                $product = $products->get($product_id);
                //Get the details of the products
                $product_detail = ResourceHelper::callResource2('product_details.healthcare2018', [
                    'product_id' => $product_id,
                    'filter_keys' => 'description,waardering,label,base_summary,add_summary',
                    'require_description' => '1',
                ] + $conditions);

                $product['details'] = $product_detail['coverage'];

                if(isset($this->params['cost_types'], $this->params['user']) && intval($this->params['user']) === IAKHelper::USER_ID){
                    $selectedCostTypes = explode(',', $this->params['cost_types']);
                    $product['selection_coverage'] = [];
                    foreach ($selectedCostTypes as $selectedCostType){
                        if(isset(self::$activeFilters[$selectedCostType]) && !empty(self::$activeFilters[$selectedCostType])){
                            if(!empty($product['child_source_ids'])){
                                //This is a combo so go through the children and try to find a summary
                                $prefix = intval($product['company']['resource_id']) === IAKHelper::COMPANY_ID ? '': 'A';
                                $children = explode(',', $product['child_source_ids']);
                                foreach ($children as $childId){
                                    $summary = head(ResourceHelper::callResource2('product_summaries.healthcare2018', [
                                        'product_id' => $prefix . $childId,
                                        'cost_type_id' => intval(self::$activeFilters[$selectedCostType]['cost_type_id']),
                                    ] + $conditions));
                                    if($summary){
                                        $product['selection_coverage'][self::$activeFilters[$selectedCostType]['label']] = $summary['product_summary'];
                                    }
                                }
                            }else{
                                //This is just a base product. Try to find summaries for it.
                                $summary = head(ResourceHelper::callResource2('product_summaries.healthcare2018', [
                                    'product_id' => $product_id,
                                    'cost_type_id' => intval(self::$activeFilters[$selectedCostType]['cost_type_id']),
                                ] + $conditions));
                                if($summary){
                                    $product['selection_coverage'][self::$activeFilters[$selectedCostType]['label']] = $summary['product_summary'];
                                }
                            }

                        }
                    }

                }
                $this->result[] = $product;
            }
        }
    }

}