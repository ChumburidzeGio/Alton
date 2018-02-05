<?php
namespace App\Resources\MeeusCCS\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\MeeusCCS\MeeusCCSAbstractRequest;
use Illuminate\Support\Facades\Config;

/**
 * Get carinsurance product information, including possible coverage combinations, possible own risk values and possible mileage values.
 */
class GetProductInfo extends MeeusCCSAbstractRequest
{
    protected $cacheDays = 30;

    protected $methodName = 'InitieerPolis';

    public $inputToExternalMapping = [
        ResourceInterface::PRODUCT_ID => 'CommercieelProductnummer',
    ];
    public $externalToResultMapping = false;

    public function __construct()
    {
        parent::__construct('data/meeus_ccs_wsdls/'. ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.wsdl_environment')) .'_InitieerService.wsdl');
    }

    protected function getDefaultParams()
    {
        return [
            'Pakketonderdeel' => 'Nee',
            'SoortPolisVersie' => 'Polis',
            'TransactieType' => 'Invp',
            'InternPolisnummerMantel' => '',
        ];
    }

    public function getResult()
    {
        $result = parent::getResult();

        // Make filters & parameter info easily accessible by name
        if (isset($result['mijcommproductfilter']))
        {
            foreach ($result['mijcommproductfilter'] as $nr => $filter) {
                // Lists with one option are not an array in XML, make them arrays here
                if (isset($filter['tabelfilter']) && count($filter['tabelfilter']) > 0 && !isset($filter['tabelfilter'][0]))
                    $filter['tabelfilter'] = [$filter['tabelfilter']];

                $result['mijcommproductfilter'][$filter['labelnaam']][] = $filter;
                unset($result['mijcommproductfilter'][$nr]);
            }
        }
        if (isset($result['mijcommprodparameter']))
        {
            foreach ($result['mijcommprodparameter'] as $nr => $parameter) {
                $result['mijcommprodparameter'][$parameter['parameternaam']] = $parameter;
                unset($result['mijcommprodparameter'][$nr]);
            }
        }

        return $result;
    }
}