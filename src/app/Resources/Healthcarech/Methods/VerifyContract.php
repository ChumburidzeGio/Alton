<?php
namespace App\Resources\Healthcarech\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Healthcarech\AbstractKnipRequest;
use GuzzleHttp\Message\ResponseInterface;

class VerifyContract extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [
        ResourceInterface::ACCOUNT_ID => 'id',
        ResourceInterface::KEY        => 'privateKey',
        ResourceInterface::CODE       => 'vcode',
    ];
    protected $externalToResultMapping = [
        'isKnipCustomer' => ResourceInterface::IS_EXISTING_CUSTOMER

    ];
    protected $resultTransformations = [
        ResourceInterface::IS_EXISTING_CUSTOMER => 'convertToString',
    ];

    public function __construct()
    {
        parent::__construct('komparu/account/verify', self::METHOD_POST);
    }

    public function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        if ($response->getStatusCode() == '403') {
            $this->addErrorMessage(ResourceInterface::CODE,'resource.knip.error.code','Ungültiger Code!','input');
            return;
        }

        parent::handleError($response,$exception);
    }

    /**
     * This is because code cannot work well with booleans
     * @param $value
     *
     * @return string
     */
    public function convertToString($value)
    {
        return $value ? "yes" : "no";
    }

}