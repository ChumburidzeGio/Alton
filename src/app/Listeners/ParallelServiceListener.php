<?php

namespace App\Listeners\Resources2;

use App;
use App\Exception\ResourceError;
use ArrayObject;
use Config;
use GuzzleHttp\Client;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Request;

class ParallelServiceListener
{
    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.process.parallel', [$this, 'process']);
    }

    /**
     * Parallel external service processing. According to the received data, build the batch requests
     * and execute them with guzzle's pool functionality. Finally merge and return the output.
     *
     * @param ArrayObject $resources
     * @param ArrayObject $inputs
     * @param ArrayObject $data
     * @param array $headers
     * @param $action
     * @param App\Models\Resource $motherResource
     *
     * @param bool $useAlias
     *
     * @throws ResourceError
     */
    public static function process(ArrayObject $resources, ArrayObject $inputs, ArrayObject $data, array $headers, $action, App\Models\Resource $motherResource, $useAlias = false)
    {
        //Calculate the route for resources
        $routeName = sprintf('resource.data.%s', $action);
        /** @var Router $router */
        $router = app(Router::class);
        $route  = $router->getRoutes()->getByName($routeName);
        $method = $route ? current($route->methods()) : 'GET';
        $client = new Client([
            'defaults' => ['headers' => $headers]
        ]);

        $requests = static::prepareRequests($client, $method, $resources, $inputs, $routeName);

        $successes = [];
        $failures  = [];
        //Prepare the responses according to the prepared requests. Results will be in
        //successes and error codes will be in failures
        $responses = static::prepareResponses($client, $requests, $successes, $failures);


        try{
            cw('Starting threads: `' . implode('`, `', array_keys($responses)) . '`');

            foreach($responses as $resourceName => $response){
                cws('Parallel thread `' . $resourceName . '`');
            }

            foreach($responses as $resourceName => $response){
                $response->wait();
            }
        }catch(\Exception $ex){

            //We have failures! Merge them
            $exceptionMessages = [];
            foreach($failures as $resourceName => $messageJson){
                $messages = json_decode($messageJson, true);
                if( ! isset($messages['errors'])){
                    if (isset($messages['code'])) {
                        $exceptionMessages[$resourceName] = [
                            'single_resource' => $resourceName,
                            'code'            => $messages['code'],
                            'field'           => isset($messages['field']) ? $messages['field'] : "unknown",
                            'message'         => isset($messages['description']) ? $messages['description'] : "unknown",
                        ];
                    }
                    continue;
                }
                foreach($messages['errors'] as $message){
                    $code = isset($message['code']) ? $message['code'] : (isset($message['field']) ? $message['field'] : "unknown");
                    if(isset($exceptionMessages[$code])){
                        $exceptionMessages[$code]['single_resource'] .= ',' . $resourceName;
                    }else{
                        $exceptionMessages[$code] = [
                            'single_resource' => $resourceName,
                            'code'            => $code,
                            'field'           => isset($message['field']) ? $message['field'] : "unknown",
                            'message'         => isset($message['message']) ? $message['message'] : "unknown",
                        ];
                    }
                }
            }
            $exceptionMessagesValues = array_values($exceptionMessages);
            if(empty($exceptionMessagesValues)){

                Log::error('ParallelServiceError: ' . $ex->getMessage() . '| ' . Request::fullUrl() . ' | ' . $ex->getTraceAsString());
            }else{
                Log::error('ParallelServiceError: ' . json_encode($exceptionMessages) . ' (' . $ex->getMessage() . '| ' . Request::fullUrl() . ')');
            }
            cw($exceptionMessages);
            /**
             * If as many requests as failures, everything failed and basically we are one big faillure. Throw it.
             */
            if(count($requests) == count($failures)){
                throw new ResourceError($motherResource, $inputs->getArrayCopy(), $exceptionMessages);
            }
        }

        //Merge the results into the data!
        $outData = [];
        foreach($resources as $alias => $resource){
            if(isset($successes[$alias])){
                $outKey = $useAlias ? $alias : $resource->name;
                if( ! isset($outData[$outKey])){
                    $outData[$outKey] = [];
                }
                $outData[$outKey] = array_merge($outData[$outKey], $successes[$alias]);
            }
        }

        $data->exchangeArray($outData);
    }

    /** Creates guzzle requests from the given resources and input.
     *
     * @param Client $client
     * @param $method
     * @param ArrayObject $resources
     * @param ArrayObject $inputs
     * @param $routeName
     *
     * @return array
     */
    private static function prepareRequests(Client $client, $method, ArrayObject $resources, ArrayObject $inputs, $routeName)
    {
        $requests = [];
        //Start assembling the requests based on the inputs
        $counter = 1;
        foreach($inputs as $resourceName => $resourceInput){
            $resource = $resources->offsetGet($resourceName);
            $url      = URL::route($routeName, $resource->name);
            $timeout  = isset($resourceInput['__timeout']) ? $resourceInput['__timeout'] : (! is_null($resources[$resourceName]['timeout']) ? $resources[$resourceName]['timeout'] : 29);

            /*
             * Pass on test param if the app is running in test mode
             */
            if(Config::get('TEST_MODE')){
                $resourceInput['_test'] = 1;
            }

            $params = http_build_query($resourceInput);

            $uri = $url . '?' . $params;
            cw("Thread URI #" . $counter);
            cw($uri);
            $counter ++;
            $requests[$resourceName] = $client->createRequest($method, $uri, ['future' => true, 'connect_timeout' => 2, 'timeout' => $timeout]);
        }
        return $requests;
    }

    /** Given requests, it prepares the responses while also adding the handlers for
     * asyncronously dealing with errors. You need to pass two arrays so you can get
     * the data back eventually.
     *
     * @param $client
     * @param $requests
     * @param $successes
     * @param $failures
     *
     * @return array
     */
    private static function prepareResponses($client, $requests, &$successes, &$failures)
    {
        $responses = [];
        //Functions to handle the responses


        //Here the pool is not used for more granular control over error control
        foreach($requests as $resourceName => $request){
            $successHandler           = function ($response) use (&$successes, $resourceName) {
                $successes[$resourceName] = $response->json();
                cw('Finishing thread `' . $resourceName . '`');
                cwe('Parallel thread `' . $resourceName . '`');
            };
            $failureHandler           = function ($response) use (&$failures, $resourceName) {
                $failures[$resourceName] = is_null($response->getResponse()) ? 'is null' : $response->getResponse()->getBody()->getContents();

                cw('Finishing thread `' . $resourceName . '`');
                cw('Parallel request failure for resource: ' . $resourceName);
                cwe('Parallel thread `' . $resourceName . '`');
            };
            $responses[$resourceName] = $client->send($request);
            //Put the handlers in the response
            $responses[$resourceName]->then($successHandler, $failureHandler);
        }
        return $responses;
    }

    /**
     * Break on resource inseveral and send it of
     *
     * @param App\Models\Resource $resource The resource you want to split up
     * @param $batchInputs
     * associative array with inputs. The size of this array will the amount of threads
     *
     * @return array all data together
     */
    public static function batch(App\Models\Resource $resource, $batchInputs)
    {
        $count     = 0;
        $resources = [];
        $params    = [];
        foreach($batchInputs as $batchInput){
            $resourceName             = $resource->name . '@' . $count;
            $params[$resourceName]    = $batchInput;
            $resources[$resourceName] = $resource;
            $count ++;
        }
        $headers['X-Auth-Token']  = Request::header('X-Auth-Token');
        $headers['X-Auth-Domain'] = Request::header('X-Auth-Domain');
        $data                     = new ArrayObject();
        ParallelServiceListener::process(new ArrayObject($resources), new ArrayObject($params), $data, $headers, 'index', $resource, true);
        return $data->getArrayCopy();
    }

}