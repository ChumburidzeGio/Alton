<?php
namespace App\Resources\General\Methods;

use App\Helpers\DocumentHelper;
use App\Helpers\ProductSettingsHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Komparu\Value\ValueInterface;

class GetCompanies extends AbstractMethodRequest
{
    protected $cacheDays = false;

    const FILTERS = 'filters';          // mapping to document package filter
    const CONDITIONS = 'conditions';    // mapping to document package conditions

    public $resource2Request = true;

    protected $params = [];
    protected $result = [];

    public function setParams(Array $params)
    {
        // product type
        $this->params[ResourceInterface::PRODUCT_TYPE] = $params[ResourceInterface::PRODUCT_TYPE];
        unset($params[ResourceInterface::PRODUCT_TYPE]);

        // conditions
        $this->params[self::CONDITIONS] = array_intersect_key($params, [ResourceInterface::WEBSITE => 0, ResourceInterface::USER => 0]);
        unset($params[ResourceInterface::WEBSITE], $params[ResourceInterface::USER]);

        // filters
        $this->params[self::FILTERS] = $params;
    }

    public function parkingRequest()
    {
        $result         = DocumentHelper::get('product', 'parking2', [
            'filters'    => $this->params[self::FILTERS] + ['active' => 1, 'enabled' => 1],
            'conditions' => $this->params[self::CONDITIONS],
            'limit'      => ValueInterface::INFINITE,
            'visible'    => 'brand_logo'
        ]);
        $this->result   = [];
        $companiesAdded = [];

        foreach($result->documents()->toArray() as $document){
            if( ! array_get($document, 'brand_logo') || in_array(array_get($document, 'brand_logo'), $companiesAdded)){
                continue;
            }
            $companiesAdded[] = array_get($document, 'brand_logo');
            $this->result[]   = ['image' => array_get($document, 'brand_logo')];
        }
    }

    public function defaultRequest(){
        $result         = DocumentHelper::get('product', $this->params[ResourceInterface::PRODUCT_TYPE], [
            'filters'    => $this->params[self::FILTERS] + ['active' => 1, 'enabled' => 1],
            'conditions' => $this->params[self::CONDITIONS],
            'limit'      => ValueInterface::INFINITE,
            'visible'    => 'company.image,company.name'
        ]);
        $this->result   = [];
        $companiesAdded = [];
        foreach($result->documents()->toArray() as $document){
            if(in_array(array_get($document, 'company.name'), $companiesAdded)){
                continue;
            }
            $companiesAdded[] = array_get($document, 'company.name');
            if( ! array_get($document, 'company.image')){
                continue;
            }
            $this->result[] = ['image' => array_get($document, 'company.image'), 'name' => array_get($document, 'company.name')];
        }
    }

    public function healthcareRequest()
    {
        $this->result = array_values(array_map(function ($company) {
            return [
                'image' => $company['company.image'],
                'name'  => $company['title'],
            ];
        }, array_filter(ProductSettingsHelper::getSettings('healthcare2'), function ($company) {
            return $company['active'] and $company['enabled'];
        })));
    }

    public function executeFunction()
    {
        switch($this->params[ResourceInterface::PRODUCT_TYPE]){
            case 'parking2':
                $this->parkingRequest();
                break;
            case 'healthcare2':
                $this->healthcareRequest();
                break;
            default:
                $this->defaultRequest();
                break;
        }
    }

    public function getResult()
    {
        return $this->result;
    }
}