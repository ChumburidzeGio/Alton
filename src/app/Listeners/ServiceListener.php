<?php

namespace App\Listeners\Resources2;

use App, Cache;
use App\Helpers\CacheHelper;
use App\Helpers\ContractHelper;
use App\Helpers\OfferHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Support\Facades\Event;
use Komparu\Value\Type;

class ServiceListener
{
    private static $cacheKey;


    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.process.input', [$this, 'processInputs']);
        $events->listen('resource.process.input', [$this, 'processId']);
        $events->listen('resource.process', [$this, 'process']);
    }

    /**
     * General external service processing. First check if there is an existing client
     * for this resource name and method. Then call this client with the input data and
     * return the output.
     *
     * @param Resource|Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     * @param $action
     * @param null $id
     *
     * @throws App\Exception\ResourceError
     * @throws App\Exception\ServiceError
     */
    public static function process(App\Models\Resource $resource, ArrayObject $input, ArrayObject $data, $action)
    {
        if (isset($input[OptionsListener::OPTION_BYPASS])) {
            return;
        }

        // Services are only the resources that are not REST. Because these REST ones have
        // data in the Document package. We are only interested in external data sources here.
        if($resource->act_as == Resource::ACT_AS_REST || $resource->act_as == Resource::ACT_AS_ELOQUENT_REST || $resource->act_as == Resource::ACT_AS_SERVICE_REST){
            return;
        }

        if(($cachedResult = CacheHelper::processCache($resource, $input)) !== false){
            $data->exchangeArray($cachedResult);
            return;
        }


        $result = null;

        /**
         * Special cases
         */
        switch ($resource->getServiceMethodName()) {
            case 'contract':
                //contract funnels
                if ($action == 'store') {
                    $result = ContractHelper::process($resource, $input->getArrayCopy());
                } else if ($action == 'index') {
                    $result = ContractHelper::retrieve($resource, $input->getArrayCopy());
                }
                break;
            case 'offer':
                $result = OfferHelper::process($resource, $input->getArrayCopy());
                break;
            default:
                if (in_array($action, ['index', 'store', 'show'])) {
                    $result = self::callService($resource, $input->getArrayCopy());
                }
        }

        if (!is_null($resource['cache_lifetime'])) {
            if (!(strpos(php_sapi_name(), 'cli') !== false))
                header("X-Komparu-Cache-Control: " . $resource['cache_lifetime']);
        }

        if(is_array($result)){
            // Fill the data with result
            CacheHelper::storeCache($resource, $input, $result);
            $data->exchangeArray($result);
        }
        Event::fire('external.service.process.after', [$resource, $input, $data, $action]);
    }

    public static function callService(Resource $resource, Array $input)
    {
        if ($resource->hasBehaviour(Resource::BEHAVIOUR_DUMMY)) {
            //do nothing;
            return;
        }
        if (!($resource->hasBehaviour(Resource::BEHAVIOUR_SERVICE_NO_PROPAGATION)) && !empty($input[OptionsListener::OPTION_NO_PROPAGATION])) {
            return;
        }
        // If there is no service with that name, we can't do anything here.
        if( ! app('resource.' . $resource->getServiceName())){
            return null;
        }

        /** @var ResourceInterface $client */
        $client = app('resource.' . $resource->getServiceName());

        // Get the result from the service client
        $result = call_user_func_array([$client, $resource->getServiceMethodName()], [
            'params' => $input,
            'path'   => $resource->getServiceName() . '/' . $resource->getServiceMethodName(),
        ]);

        /*
         * Throw input error;
         */
        if(isset($result['error_messages'])){
            throw new App\Exception\ResourceError($resource, $input, $result['error_messages']);
        }

        if(isset($result['pretty_error'])){
            //We have a user facing message to show so throw a pretty exception
            if(isset($input['debug']) && $input['debug'] && isset($result['error'])){
                throw new App\Exception\ServiceError($resource, $input, $result['pretty_error'] . ' --- ' .$result['error']);
            }
            throw new App\Exception\PrettyServiceError($resource, $input, $result['pretty_error']);
        }

        if(isset($result['error'])){
            throw new App\Exception\ServiceError($resource, $input, $result['error']);
        }
        // Fallback
        if(isset($result['result'])){
            $result = $result['result'];
        }

        return $result;
    }

    public static function processId(Resource $resource, ArrayObject $input, $action, $id)
    {
        if(!$resource->hasBehaviour(Resource::BEHAVIOUR_SERVICE_NO_PROPAGATION)){
            return;
        }
        if($action !== 'show'){
            return;
        }
        if(!isset($input[ResourceInterface::__ID])  && $id !== null){
            $input->offsetSet(ResourceInterface::__ID, $id);
        }

    }

    /**
     * This basically process the inputs. Right now it only splits an input on , if it is an ARR.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function processInputs(Resource $resource, ArrayObject $input)
    {
        if($resource->act_as == Resource::ACT_AS_REST){
            return;
        }
        foreach($resource->fields as $field){
            if($field->type != Type::ARR){
                continue;
            }
            if( ! $input->offsetExists($field->name)){
                continue;
            }
            $value = $input->offsetGet($field->name);
            if(is_array(($value))){
                continue;
            }
            $input->offsetSet($field->name, explode(',', $value));
        }
    }
}