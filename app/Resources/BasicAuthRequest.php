<?php
namespace App\Resources;

use App\Interfaces\ResourceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use Log;

/**
 * Basic Authentication request
 *
 * User: Roeland Werring
 * Date: 16/04/15
 * Time: 11:51
 *
 */

// *************** DEPRECATED! USE HttpMethodRequest *************** //

class BasicAuthRequest extends AbstractMethodRequest
{
    protected $basicAuthService = ['type_request' => '', 'method_url' => '', 'username' => '', 'password' => '', 'headers' => []];
    protected $result;
    protected $convertMultiArr = false;
    protected $ignoreErrors = false;

    protected $params = [];

    /**
     * @var ResponseInterface
     */
    protected $response;

    protected static $GET_NO_AUTH = "get_no_auth";

    protected static $POST_JSON_NO_AUTH = "post_json_no_auth";

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    protected function getHttpClient()
    {
        return new Client();
    }

    public function executeFunction()
    {
//        $this->result = $this->params;
//        return;
        if ($this->convertMultiArr) {
            $multiArr = [];
            foreach ($this->params as $key => $value) {
                array_set($multiArr, $key, $value);
            }
            $this->params= $multiArr;
        }

        //Guzzle client
        $client = $this->getHttpClient();

        switch($this->basicAuthService['type_request']){
            case "none":
                return;
                break;
            case "get":
                try{
                    $this->response = $client->get($this->basicAuthService['method_url'], [
                        'auth'  => [$this->basicAuthService['username'], $this->basicAuthService['password']],
                        'query' => $this->params
                    ]);
                }catch(RequestException $e){
                    $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    return;
                }
                break;
            case self::$GET_NO_AUTH:
                try{
                    $options  = [
                        'query' => $this->params,
                    ];
                    if (!empty($this->basicAuthService['headers'])) {
                        $options['headers'] = $this->basicAuthService['headers'];
                    }
                    $this->response = $client->get($this->basicAuthService['method_url'], $options);
                }catch(RequestException $e){
                    if ($e->getResponse()){
                        $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    }
                    return;
                }
                break;
            case 'get_json_no_auth':
                try{
                    $options  = [
                        'headers' => [
                            'Accept' => 'application/json',
                        ],
                        'json'    => $this->params
                    ];
                    if (!empty($this->basicAuthService['headers'])) {
                        $options['headers'] = $options['headers'] + $this->basicAuthService['headers'];
                    }
                    $this->response = $client->get($this->basicAuthService['method_url'], $options);
                }catch(RequestException $e){
                    if ($e->getResponse()){
                        $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    }
                    return;
                }
                break;
            case "put_no_auth":
                try{
                    $options  = [
                        'headers' => [
                            'Accept' => 'application/json',
                        ],
                        'json'    => $this->params
                    ];
                    if (!empty($this->basicAuthService['headers'])) {
                        $options['headers'] = array_merge($options['headers'],$this->basicAuthService['headers']);
                    }

                    $this->response = $client->put($this->basicAuthService['method_url'], $options);
                }catch(RequestException $e){
                    $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    return;
                }
                break;
            case "put_form_no_auth":
                try{
                    $options  = [
                        'body'    => $this->params
                    ];
                    if (!empty($this->basicAuthService['headers'])) {
                        $options['headers'] = $this->basicAuthService['headers'];
                    }

                    $this->response = $client->put($this->basicAuthService['method_url'], $options);
                }catch(RequestException $e){
                    $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    return;
                }
                break;
            case "post":
                try{
                    $this->response = $client->post($this->basicAuthService['method_url'], [
                        'auth' => [$this->basicAuthService['username'], $this->basicAuthService['password']],
                        'body' => $this->params
                    ]);
                }catch(RequestException $e){
                    $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    return;
                }
                break;
            case "post_no_auth":
                try{
                    $options  = [
                        'body' => $this->params,
                    ];
                    if (!empty($this->basicAuthService['headers'])) {
                        $options['headers'] = $this->basicAuthService['headers'];
                    }

                    $this->response = $client->post($this->basicAuthService['method_url'], $options);
                }catch(RequestException $e){
                    $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    return;
                }
                break;
            case "post_json":
                try{
                    $this->response = $client->post($this->basicAuthService['method_url'], [
                        'auth' => [$this->basicAuthService['username'], $this->basicAuthService['password']],
                        'json' => $this->params
                    ]);
                }catch(RequestException $e){
                    $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    return;
                }
                break;
            case self::$POST_JSON_NO_AUTH:
                try{
                    $options  = [
                        'headers' => [
                            'Accept' => 'application/json',
                        ],
                        'json'    => $this->params
                    ];
                    if (!empty($this->basicAuthService['headers'])) {
                        $options['headers'] = array_merge($options['headers'],$this->basicAuthService['headers']);
                    }
//dd($this->basicAuthService['method_url']);
                    $this->response = $client->post($this->basicAuthService['method_url'], $options);
                }catch(RequestException $e){
                    $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
                    return;
                }
                break;
            case "delete_no_auth":
                try{
                    $options  = [
                        'body' => $this->params,
                    ];
                    if (!empty($this->basicAuthService['headers'])) {
                        $options['headers'] = $this->basicAuthService['headers'];
                    }

                    $this->response = $client->delete($this->basicAuthService['method_url'], $options);
                }catch(RequestException $e){
                    $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents()), $e);
                    return;
                }
                break;
            default:
                $this->result = ['error' => 'Unsupported method ' . $this->basicAuthService['type_request']];
                return;
        }

        //dd($this->response->getBody());

        //dd($this->response->getBody()->getContents());
        if(in_array($this->response->getStatusCode(), [500, 400, 415])){
            $this->handleError($this->parseResponseError($this->response->getBody()->getContents()));
            return;
        }

        $this->result = json_decode($this->response->getBody()->getContents(), true);
        return;
    }

    /**
     * @param $e
     */
    private function handleError($error)
    {
        if ($this->ignoreErrors) {
            Log::warning($error);
            $this->result =  [ResourceInterface::SUCCESS => 'ok'];
            return;
        }
        $this->setErrorString($error);
    }
}
