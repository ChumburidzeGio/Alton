<?php

namespace App\Resources\Blaudirekt\Methods\Privateliabilityde;

use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Resources\Blaudirekt\BlaudirektAbstractRequest;
use Illuminate\Support\Facades\Config;
use DB, Exception;

class ContractPrivateliabilityde extends BlaudirektAbstractRequest
{
    protected $requestParams;

    protected $inputTransformations = [
        ResourceInterface::BIRTHDATE  => 'convertDate',
        ResourceInterface::START_DATE => 'convertDate',
        ResourceInterface::RANGE      => 'convertRange',
    ];

    protected $product = null;

    protected $localOrder = null;

    protected $httpBodyEncoding = self::DATA_ENCODING_JSON;

    protected $httpResultEncoding = self::DATA_ENCODING_TEXT;

    protected $httpMethod = self::METHOD_POST;

    protected $dataResult = false;

    protected $defaultParams = [
        'ajax'                        => 1,
        'Vertrag_Kunde_nationalitaet' => 'de', //country code
        'Vertrag_zahlweise'           => 1,
        'Vertrag_zahlart'             => 'lastschrift', //payment method
        'Vertrag_Konto_abweichend'    => false,
        'agreement'                   => [
            "0" => true
        ],
        'Rechner_job'                 => 'abschluss',
    ];

    protected $inputToExternalMapping = [
        ResourceInterface::SALUTATION                 => 'Vertrag_Kunde_anrede',
        ResourceInterface::TITLE                      => 'Vertrag_Kunde_titel',
        ResourceInterface::FIRST_NAME                 => 'Vertrag_Kunde_vorname_person',
        ResourceInterface::LAST_NAME                  => 'Vertrag_Kunde_nachname_person',
        ResourceInterface::POSTAL_ADDRESS_STREET      => 'Vertrag_Kunde_strasse',
        ResourceInterface::POSTAL_ADDRESS_POSTAL_CODE => 'Vertrag_Kunde_plz',
        ResourceInterface::POSTAL_ADDRESS_CITY        => 'Vertrag_Kunde_ort',
        ResourceInterface::PROFESSION                 => 'Vertrag_Kunde_beruf_person',
        ResourceInterface::PHONE_MOBILE               => 'Vertrag_Kunde_mobil_privat',
        ResourceInterface::PHONE_LANDLINE             => 'Vertrag_Kunde_telefon_privat',
        ResourceInterface::FAX                        => 'Vertrag_Kunde_fax_privat',
        ResourceInterface::EMAIL                      => 'Vertrag_Kunde_email_privat',
        ResourceInterface::BIRTHDATE                  => 'Vertrag_Kunde_geburtsdatum',
        ResourceInterface::START_DATE                 => 'Vertrag_beginn',

        ResourceInterface::BANK_ACCOUNT_IBAN => 'Vertrag_Konto_iban',
        ResourceInterface::COMMENTS          => 'Vertrag_vertragsnotiz',
    ];

    public function __construct($requestParams = [])
    {
        parent::__construct('bd/privathaftpflicht/');
        $this->defaultParams['vermittler']  = Config::get('resource_blaudirekt.settings.broker_id');
        $this->defaultParams['Job_vertrag'] = Config::get('resource_blaudirekt.settings.Job_vertrag');
    }

    public function executeFunction()
    {
        $this->createOrderLocally();
        parent::executeFunction();


        // check for errors
        if(str_contains($this->result, 'Fehlercode')){
            preg_match('/Fehlercode: (.{6})/', $this->result, $matches);
            if(isset($matches[1])){
                $this->addErrorMessage(null, "blaudirekt.error", "Unknown error from Blaudirekt: " . $matches[1]);
                return;
            }
        }

        if( ! isset($this->responseHeaders['X-Dio-Contract-Id'], $this->responseHeaders['X-Dio-Customer-Id'])){
            $this->addErrorMessage(null, "blaudirekt.error", "No contract id or customer id in reply");
            return;
        }
        $this->updateOrder([
            ResourceInterface::STATUS      => ['COMPLETED'],
            ResourceInterface::CONTRACT_ID => current($this->responseHeaders['X-Dio-Contract-Id']),
            ResourceInterface::CUSTOMER_ID => current($this->responseHeaders['X-Dio-Customer-Id']),

        ]);

        $knipStatusCode = $this->createOrderInKnip();

        $this->updateOrder([
            ResourceInterface::KNIP_HTTP_CODE => $knipStatusCode
        ]);


        $this->result = [
            'status' => 'success',
            'order'  => $this->localOrder,
        ];
    }


    public function createOrderInKnip()
    {
        $knipData = [
            'product_ids' => $this->product[ResourceInterface::__ID],
            'company'     => [
                'name'    => 'blaudirekt',
                'knip_id' => 'privathaftpflichtversicherung'
            ],
            'price'       => array_get($this->product, ResourceInterface::PREMIUM_GROSS),
            'order_id'    => array_get($this->localOrder, ResourceInterface::__ID),
            'hash'        => Config::get('resource_blaudirekt.settings.knip_hash'),
        ];

        try{
            ResourceHelper::callResource2('set_additional_insurances.knip', $knipData, RestListener::ACTION_STORE);

            return 201;
        }catch(Exception $e){
        }

        return 404;
    }

    public function createOrderLocally()
    {
        $requestParams = $this->inputParams;

        $productId = array_get($requestParams, ResourceInterface::PRODUCT_ID);

        $this->product = $this->getProductById($productId);

        $orderData = [
            ResourceInterface::USER       => array_get($requestParams, ResourceInterface::USER),
            ResourceInterface::WEBSITE    => array_get($requestParams, ResourceInterface::WEBSITE),
            ResourceInterface::IP         => array_get($requestParams, ResourceInterface::IP),
            ResourceInterface::SESSION_ID => array_get($requestParams, ResourceInterface::SESSION_ID),
            ResourceInterface::PRODUCT_ID => $productId,
            ResourceInterface::SESSION    => array_get($requestParams, ResourceInterface::SESSION),
            ResourceInterface::STATUS     => ['PENDING'],
            ResourceInterface::REQUEST    => $requestParams,
            ResourceInterface::PRODUCT    => $this->product,
        ];

        $this->localOrder = ResourceHelper::callResource2('order_privateliabilityde.blaudirekt', $orderData, RestListener::ACTION_STORE);
    }

    public function updateOrder($data)
    {
        $this->localOrder = ResourceHelper::callResource2('order_privateliabilityde.blaudirekt', $data, RestListener::ACTION_UPDATE, $this->localOrder[ResourceInterface::__ID]);
    }

    public function getProductById($id)
    {
        $product = DB::connection('mysql_product')->table('product_privateliabilityde_blaudirekt as pp')->where(ResourceInterface::__ID, $id)->first();

        return (array) $product;
    }


}