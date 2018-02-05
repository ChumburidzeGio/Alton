<?php
/**
 * User: Roeland Werring
 * Date: 16/02/15
 * Time: 13:21
 *
 */

namespace App\Resources;
use App\Exception\NotExistError;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceRequestInterface;
use Illuminate\Support\Facades\App;
use Komparu\Input\Contract\Validator;


abstract class AbstractServiceRequest extends OutputMapper implements ResourceInterface
{

    /**
     * Debug: when turned on, cache is skipped and all values of the webservice are returned.
     * Debug can be enabled by adding debug = true in param list.
     * @var bool
     */
    protected $debug = false;


    /**
     * This is a mapping of the methods to the classes, with description. Example:
     *  protected $methodMapping = [
     *         'list'     => [
     * 'class'       => 'App\Resources\Rolls\Methods\Impl\KeuzeLijstenClient',
     * 'description' => 'Request list of choosable options'
     * ],
     *  ];
     */
    protected $methodMapping = [];


    /**
     * Some default mapping (mainly translations).
     * @var array
     */
    private $defaultFieldMapping = [
        'nee'       => false,
        'ja'        => true,
        'Nee'       => false,
        'Ja'        => true,
        'NEE'       => false,
        'JA'        => true,
        'Standaard' => 'default',
        'nvt'       => 'n/a'
    ];

    /**
     * This a mapping, to transfer fields in to generic col names.
     * Example: 'ProductNaam' => 'product_name'
     *
     * This mapping is applied on both keys and values of the results
     *
     * Mapping of the return fields to some standard cols.
     * @var array
     */
    protected $fieldMapping = [];


    /**
     * Some result values require a certain filter to be applied, for instance transfer
     * euro cents to euros (devide by 100). This mapping is to call the helper function from ResourceFilterHelper
     * that is applying this filter
     * @var array
     */
    protected $filterMapping = [];

    /**
     * Some resources are good as they are, we only want to translate the params to their service in order to use generic names
     * @var array
     */
    protected $filterKeyMapping = [];

    /**
     * Defines which webservice provider this is.
     */
    protected $serviceProvider = 'unknown';

    /**
     * @var string internal function used by resource (for instance 'reis');
     */
    protected $resourceModuleName = 'unknown';

    protected $validator;


    public function __construct()
    {
        $this->validator = new \Komparu\Input\Validation\SiriusValidator(new \Sirius\Validation\Validator(), new \Komparu\Input\Rule\RuleFactory(new \Komparu\Resolver\Resolver()));
    }

    /**
     * @param $method
     *
     * @return string
     */
    protected function returnError($message)
    {
        return ["error" => $message];
    }

    /**
     */
    public function info($typeInfo = false)
    {
        $args = [];
        foreach($this->methodMapping as $method => $properties){

            $client = App::make($properties['class']);
            if($client instanceof ResourceRequestInterface){
                if($typeInfo == 'document' && ! $client->isDocumentRequest()){
                    continue;
                }
                if($typeInfo == 'funnel' && ! $client->isFunnelRequest()){
                    continue;
                }

                if($typeInfo == 'populate' && ! $client->isPopulateRequest()){
                    continue;
                }
                $args[$method]['description'] = $properties['description'];
                $args[$method]['arguments']   = $client->arguments($this->validator);
                foreach($client->outputFields() as $outputfield){
                    $args[$method]['output'] [$outputfield] =
                        isset($this->outputTypeMapping[$outputfield])
                            ? $this->outputTypeMapping[$outputfield]
                            : 'string';
                }
            }
        }
        return $args;
    }

    /**
     * Make the actual call request
     *
     * @param $method
     * @param $args
     *
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        if( ! isset($this->methodMapping[$method])){
            throw new NotExistError($method);
        }

        /** @var AbstractMethodRequest $client */
        $client = App::make($this->methodMapping[$method]['class']);
        //0 = params, 1 = path
        $params = $args[0];
        $path   = $args[1];


        //call it
        $fieldMapping = $client->skipDefaultFields?$this->fieldMapping : array_merge($this->defaultFieldMapping, $this->fieldMapping);
        return $client->call($params, $path, $this->validator, $fieldMapping, $this->filterKeyMapping, $this->filterMapping, $this->serviceProvider());
    }

    public function serviceProvider()
    {
        return $this->serviceProvider;
    }
}