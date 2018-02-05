<?php
namespace App\Resources;

use App\Interfaces\ResourceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ParseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;


class HttpMethodRequest extends AbstractMethodRequest
{
    const METHOD_NONE = 'none';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_HEAD = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';

    const AUTH_NONE = 'none';
    const AUTH_CUSTOM = 'custom';
    const AUTH_HTTP_BASIC = 'http_basic';

    const DATA_ENCODING_JSON = 'json';
    const DATA_ENCODING_XML = 'xml';
    const DATA_ENCODING_URLENCODED = 'urlencoded';
    const DATA_ENCODING_TEXT = 'text';

    const HTTP_STATUS_CLASS_INFORMATIONAL = 1;
    const HTTP_STATUS_CLASS_SUCCESS = 2;
    const HTTP_STATUS_CLASS_REDIRECTION = 3;
    const HTTP_STATUS_CLASS_CLIENT_ERROR = 4;
    const HTTP_STATUS_CLASS_SERVER_ERROR = 5;

    protected $httpMethod = self::METHOD_GET;
    protected $httpHeaders = [];
    protected $httpBodyEncoding = self::DATA_ENCODING_URLENCODED;
    protected $httpResultEncoding = self::DATA_ENCODING_JSON;
    protected $url = '';

    protected $authMethod = self::AUTH_NONE;
    protected $authData = [];

    protected $logErrors = false;
    protected $ignoreErrors = false;

    protected $params = [];
    protected $result = null;

    public $resource2Request = true;
    protected $cacheDays = false;

    public function __construct($url = '')
    {
        $this->url = $url;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }

    protected function getHttpClient()
    {
        return new Client();
    }

    protected function applyAuthentication(array $httpOptions)
    {
        if ($this->authMethod == self::AUTH_HTTP_BASIC)
        {
            $httpOptions['auth'] = [$this->authData['username'], $this->authData['password']];
        }

        return $httpOptions;
    }

    protected function applyParams(array $httpOptions)
    {
        switch ($this->httpMethod)
        {
            case self::METHOD_NONE:
                break;
            case self::METHOD_GET:
            case self::METHOD_DELETE:
            case self::METHOD_HEAD:
            case self::METHOD_OPTIONS:
                if (isset($httpOptions['query']))
                    $httpOptions['query'] = array_merge($httpOptions['query'], $this->params);
                else
                    $httpOptions['query'] = $this->params;
                break;
            case self::METHOD_PUT:
            case self::METHOD_POST:
                $httpOptions = $this->setBody($httpOptions, $this->params);
                break;
        }

        return $httpOptions;
    }

    protected function setBody(array $httpOptions, $data)
    {
        switch($this->httpBodyEncoding) {
            case self::DATA_ENCODING_JSON:
                $httpOptions['headers']['Accept'] = 'application/json';
                $httpOptions['json'] = $data;
                break;
            case self::DATA_ENCODING_URLENCODED:
                $httpOptions['body'] = array_merge(array_get($httpOptions, 'body', []), $data);
                break;
            default:
                $httpOptions['body'] = (string) $data;
        }
        return $httpOptions;
    }

    protected function getDefaultHttpOptions()
    {
        return [];
    }

    protected function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function executeFunction()
    {
        $client = $this->getHttpClient();

        $httpOptions = $this->getDefaultHttpOptions();
        $httpOptions = $this->applyAuthentication($httpOptions);
        $httpOptions = $this->applyParams($httpOptions);

        $reqId = 'httpmethod_req_'. uniqid();
        try
        {

            $response = $client->request($this->httpMethod, $this->getUrl(), $httpOptions);

            if ($this->debug()) {
                cwe($reqId);
                cw('HTTP Response: ' . (string)$response);
            }
        }
        catch (RequestException $e)
        {
            if ($this->debug()) {
                cw('HTTP Response (' . $e->getMessage() . '): ' . (string)$e->getResponse());
                cwe($reqId);
            }

            if ($e->getResponse())
                $this->handleError($e->getResponse(), $e);
            else
                $this->handleError(null, $e);
            return;
        }

        if (floor($response->getStatusCode() / 100) != self::HTTP_STATUS_CLASS_SUCCESS)
        {
            $this->handleError($response);
            return;
        }

        $this->result = $this->parseResponse($response);
    }

    public function getResult()
    {
        return $this->result;
    }

    protected function parseResponse(Response $response, $ignoreException = false)
    {
        try
        {
            if ($this->httpResultEncoding == self::DATA_ENCODING_JSON)
                return (array)json_decode($response->getBody()->getContents());
            else if ($this->httpResultEncoding == self::DATA_ENCODING_XML)
                return new SimpleXMLElement($response->getBody()->getContents());
        }
        catch (ParseException $e)
        {
            if (!$ignoreException)
                $this->handleError($response, $e);
            return null;
        }

        return $response->getBody()->getContents();
    }

    protected function parseErrorResponse(ResponseInterface $response)
    {
        return $this->parseResponse($response, true);
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        $rawBody = $response ? '"'.(string)$response->getBody().'"' : '-none-';

        $errorData = $response ? $this->parseErrorResponse($response) : null;

        $errorMessage = $exception ? $exception->getMessage() : 'Unknown error';

        $body = $errorData ? json_encode($errorData, true) : $rawBody;

        $this->setErrorString($errorMessage .'. Body: '. $body);
    }

    public function setErrorString($errorString)
    {
        if ($this->logErrors)
            Log::warning($errorString);

        if ($this->ignoreErrors)
            $this->result = [ResourceInterface::SUCCESS => 'ok'];
        else
            parent::setErrorString($errorString);
    }
}
