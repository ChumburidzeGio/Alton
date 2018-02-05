<?php

namespace App\Resources\Buckaroo\Methods;

use App\Interfaces\ResourceInterface;
use App\Models\Website;
use App\Resources\AbstractMethodRequest;

use LinkORB\Buckaroo;
use LinkORB\Buckaroo\SOAP;
use stdClass;

/**
 * User: Roeland Werring
 * Date: 08/07/15
 * Time: 11:29
 *
 */
class Payment extends AbstractMethodRequest
{
    protected $cacheDays = false;
    protected $params = [];
    protected $website = null;

    protected $arguments = [
        ResourceInterface::KEY               => [
            'rules' => 'required',
        ],
        ResourceInterface::PRODUCT_TYPE      => [
            'rules' => 'required',
        ],
        ResourceInterface::AMOUNT            => [
            'rules' => 'required | number',
        ],
        ResourceInterface::CURRENCY          => [
            'rules' => self::VALIDATION_REQUIRED_CURRENCY,
        ],
        ResourceInterface::RETURN_URL_OK     => [
            'rules' => 'required',
        ],
        ResourceInterface::RETURN_URL_CANCEL => [
            'rules' => 'required',
        ],
        ResourceInterface::RETURN_URL_ERROR  => [
            'rules' => 'required',
        ],
        ResourceInterface::RETURN_URL_REJECT => [
            'rules' => 'required',
        ],
        ResourceInterface::BANK_ACCOUNT_BIC  => [
            'rules' => self::VALIDATION_REQUIRED_BANK,
        ],
        ResourceInterface::IP                => [
            'rules' => 'required',
        ],
        ResourceInterface::WEBSITE_ID                => [
            'rules' => 'required | number',
        ],
        ResourceInterface::PARAM1                => [
            'rules' => 'string',
        ],
        ResourceInterface::PARAM2                => [
            'rules' => 'string',
        ],
    ];

    public function setParams(Array $params)
    {
        $this->params = $params;
        $this->website = Website::find($this->params[ResourceInterface::WEBSITE_ID]);
        if (!$this->website) {
            $this->setErrorString('Website not found! '.$this->params[ResourceInterface::WEBSITE_ID]);
            return;
        }
    }

    public function executeFunction()
    {
    }

    /**
     * Get results of request
     * @return mixed
     */
    public function getResult()
    {
        $req = new Buckaroo\Request($this->params[ResourceInterface::KEY]);
        $req->loadPem(app_path() . '/keys/buckaroo_' . $this->params[ResourceInterface::PRODUCT_TYPE] . '.pem');

        // Create the message body (actual request)
        $TransactionRequest           = new SOAP\Body();
        $TransactionRequest->Currency = $this->params[ResourceInterface::CURRENCY];;
        $TransactionRequest->AmountDebit     = $this->params[ResourceInterface::AMOUNT];
        $TransactionRequest->Invoice         = $this->website->name;
        $TransactionRequest->Description     = 'Verificatie betaling '.$this->website->name;
        $TransactionRequest->ReturnURL       = $this->params[ResourceInterface::RETURN_URL_OK];
        $TransactionRequest->ReturnURLCancel = $this->params[ResourceInterface::RETURN_URL_CANCEL];
        $TransactionRequest->ReturnURLError  = $this->params[ResourceInterface::RETURN_URL_ERROR];
        $TransactionRequest->ReturnURLReject    = $this->params[ResourceInterface::RETURN_URL_REJECT];

        if (isset($this->params[ResourceInterface::PARAM1])) {
            $TransactionRequest->AdditionalParameters    = array();
            $AdditionalParameter =  new stdClass();
            $AdditionalParameter->Name = ResourceInterface::PARAM1;
            $AdditionalParameter->_ = $this->params[ResourceInterface::PARAM1];
            $TransactionRequest->AdditionalParameters[]= $AdditionalParameter;
            if (isset($this->params[ResourceInterface::PARAM2])) {
                $AdditionalParameter =  new stdClass();
                $AdditionalParameter->Name = ResourceInterface::PARAM2;
                $AdditionalParameter->_ = $this->params[ResourceInterface::PARAM2];
                $TransactionRequest->AdditionalParameters[]= $AdditionalParameter;
            }
        }
        $TransactionRequest->StartRecurrent  = false;

        // Specify which service / action we are calling
        $TransactionRequest->Services          = new SOAP\Services();
        $TransactionRequest->Services->Service = new SOAP\Service('ideal', 'Pay', 2);
        // Add parameters for this service
        $TransactionRequest->Services->Service->RequestParameter = new SOAP\RequestParameter('issuer', $this->params[ResourceInterface::BANK_ACCOUNT_BIC]);
        // Optionally pass the client ip-address for logging
        $TransactionRequest->ClientIP = new SOAP\IPAddress($this->params[ResourceInterface::IP]);

        // Send the request to Buckaroo, and retrieve the response
        $soap_response = $req->sendRequest($TransactionRequest, 'transaction');

        // Display the response:
        $xml = new \SimpleXMLElement($soap_response['response']);
        // $xml->registerXPathNamespace("s", "http://schemas.xmlsoap.org/soap/envelope/");
        $body   = $xml->xpath("//s:Body");
        $result = json_decode(json_encode($body[0]->TransactionResponse), true);
        return [ResourceInterface::KEY         => $result['Key'],
                ResourceInterface::PAYMENT_KEY => $result['PaymentKey'],
                ResourceInterface::URL         => isset($result['RequiredAction'], $result['RequiredAction']['RedirectURL']) ? $result['RequiredAction']['RedirectURL'] : ''
        ];
    }

}
