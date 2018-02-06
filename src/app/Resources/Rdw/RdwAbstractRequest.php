<?php
namespace App\Resources\Rdw;

use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;


class RdwAbstractRequest extends MappedHttpMethodRequest
{
    public $resource2Request = true;

    protected $httpBodyEncoding = self::DATA_ENCODING_JSON;
    protected $httpResultEncoding = self::DATA_ENCODING_JSON;

    public function __construct($methodPath = '', $httpMethod = self::METHOD_GET)
    {
        parent::__construct(((app()->configure('resource_rdw')) ? '' : config('resource_rdw.settings.url')) . $methodPath);
        $this->httpMethod = $httpMethod;
    }

    public function applyAuthentication(array $httpOptions)
    {
        $httpOptions['headers']['X-App-Token'] = ((app()->configure('resource_rdw')) ? '' : config('resource_rdw.settings.apikey'));

        return parent::applyAuthentication($httpOptions);
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        if (!$response && $exception)
        {
            $this->setErrorString('Connection error: '. $exception->getMessage());
            return;
        }
        else if ($response && $response->json())
        {
            $errorData = $response->json();
            if (is_array($errorData) && isset($errorData['error'], $errorData['message']))
            {
                $this->setErrorString('Service reports: `'. $errorData['message'] .'`');
                return;
            }
            else if ($exception)
            {
                $this->setErrorString('Service connection error: `'. $exception->getMessage() .'`');
                return;
            }
        }

        $this->setErrorString('Unknown error.');
    }

    public function filterLicenseplate($licenseplate)
    {
        return strtoupper(preg_replace('~[^0-9a-z]~i', '', $licenseplate));
    }

    public function formatResultDate($resultDateTime)
    {
        // Weird non-traditional combo, not recognized by the standard DateTime contstructor
        $dateTime = \DateTime::createFromFormat('d/m/Y', $resultDateTime);

        if (!$dateTime)
            return null;

        return $dateTime->format('Y-m-d');
    }
}