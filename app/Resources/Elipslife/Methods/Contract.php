<?php

namespace App\Resources\Elipslife\Methods;

use App\Exception\InvalidResourceInput;
use App\Helpers\ElipslifeHelper;
use App\Resources\Elipslife\AbstractElipslifeRequest;
use App\Interfaces\ResourceInterface;
use App\Helpers\ResourceHelper;
use App\Listeners\Resources2\RestListener;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use PhpSpec\Exception\Exception;
use Psr\Log\InvalidArgumentException;
use Whoops\Exception\ErrorException;

class Contract extends AbstractElipslifeRequest
{
    public $params     = [];
    protected $productData = [];
    protected $orderData   = [];
    protected $knipStatusCode  = null;
    static $session = [];


    public function setParams(array $params)
    {
        parent::setParams($params);
    }

    public function executeFunction()
    {
        //Load session data from params into a dotted arr
        $this->loadSession();

        //Validate with Bmi_data that the weight/height is allowed
        //TODO: Validate BMI and instruct where to navigate.
        $this->validateHeightAndWeight();

        //Get product/premium
        $this->getProduct();

        //Create local order
        $this->createPendingLocalOrder();

        //Create knip order and get a statuscode
        $knipStatusCode = $this->createKnipOrder();

        //Update order:
        $this->updateCurrentOrder([
            ResourceInterface::STATUS => ['COMPLETED']
        ]);

        $this->result = [
            'status' => ($knipStatusCode === 201) ? 'success' : $knipStatusCode,
            'product' => $this->productData,
            'knipStatusCode' => $knipStatusCode, //TODO: Remove this
            'orderData' => $this->orderData,
        ];
    }

    /**
     * @return \ArrayObject|bool
     * @throws \Exception
     */
    public function validateHeightAndWeight()
    {
        $gender = $this->session('start.product.elipslife.gender', null);
        $weight = array_get($this->params,ResourceInterface::WEIGHT, null);
        $height = array_get($this->params,ResourceInterface::HEIGHT, null);
        $smoker = array_get($this->params,ResourceInterface::SMOKER, null);

        $listingTypes = ElipslifeHelper::getBmiListingTypes($gender, $smoker);

        $bmiListings = ResourceHelper::callResource2('bmi_data.elipslife', [
            ResourceInterface::WEIGHT => $weight,
            ResourceInterface::HEIGHT => $height,
            ResourceInterface::TYPE   => $listingTypes
        ], RestListener::ACTION_INDEX);

        //No BmiListings, then you are not good enough :)
       if(count($bmiListings)===0)
       {
           //Nothing yet, but we should complain about soimthing
       }
       return $bmiListings;
    }



    /**
     * Creates a order locally with us with a pending status.
     */
    public function createPendingLocalOrder()
    {
        //order data
        $orderData = [
            ResourceInterface::USER       => array_get($this->params, ResourceInterface::USER),
            ResourceInterface::WEBSITE    => array_get($this->params, ResourceInterface::WEBSITE),
            ResourceInterface::IP         => array_get($this->params, ResourceInterface::IP),
            ResourceInterface::SESSION_ID => array_get($this->params, ResourceInterface::SESSION_ID),
            ResourceInterface::PRODUCT_ID => array_get($this->productData, ResourceInterface::__ID),
            ResourceInterface::SESSION    => array_get($this->params, ResourceInterface::SESSION),
            ResourceInterface::STATUS     => ['PENDING'],
            ResourceInterface::REQUEST    => $this->params,
        ];


        //Create order:
        $this->orderData = ResourceHelper::callResource2('order.elipslife', $orderData, RestListener::ACTION_STORE);
    }


    /**
     * Create an order remotely with knip
     * @return int 201 on success | 500 on error/exception
     */
    public function createKnipOrder()
    {
        //Assemble knip data
        $knipData = [
            ResourceInterface::PRODUCT_IDS => array_get($this->productData,ResourceInterface::__ID),
            ResourceInterface::ORDER_ID    => array_get($this->orderData, ResourceInterface::__ID),
            ResourceInterface::PRICE => array_get($this->productData, ResourceInterface::PRICE),
            ResourceInterface::HASH  => ((app()->configure('resource_elipslife')) ? '' : config('resource_elipslife.settings.knip_hash')),

            ResourceInterface::COMPANY => [
                 ResourceInterface::NAME  => 'knip',
                 ResourceInterface::KNIP_ID => 'elipslife'
            ],
        ];

        try
        {
            ResourceHelper::callResource2('set_additional_insurances.knip', $knipData, RestListener::ACTION_STORE);
            return 201;
        }
        catch (Exception $e) {
            dd($e);
        }

        return 500;
    }

    /**
     * @param array $data
     */
    public function updateCurrentOrder(Array $data)
    {
        $this->orderData = ResourceHelper::callResource2('order.elipslife', $data, RestListener::ACTION_UPDATE, $this->orderData[ResourceInterface::__ID]);
    }

    /**
     *
     */
    public function getProduct()
    {
        $productParams = [
            ResourceInterface::BIRTHDATE => array_get($this->params, ResourceInterface::BIRTHDATE, $this->session('start.product.elipslife.birthdate')),
            ResourceInterface::GENDER => array_get($this->params, ResourceInterface::GENDER, $this->session('start.product.elipslife.gender')),
            ResourceInterface::SMOKER => array_get($this->params, ResourceInterface::SMOKER, $this->session('start.product.elipslife.smoker')),
            ResourceInterface::COVERAGE_AMOUNT => array_get($this->params, ResourceInterface::COVERAGE, $this->session('elipslife-terminsurance.product.elipslife.coverage_amount')),
            ResourceInterface::YEARS_INSURED   => array_get($this->params, ResourceInterface::YEARS_INSURED, $this->session('elipslife-terminsurance.product.elipslife.years_insured', 20)),
        ];

        $this->productData = head(ResourceHelper::callResource2('product.elipslife', $productParams, RestListener::ACTION_INDEX));
    }

    public function loadSession()
    {
        return static::$session = Arr::dot(json_decode(array_get($this->params, ResourceInterface::SESSION), true));
    }

    /**
     * @param null $index
     * @param null $default
     * @return array|mixed|null
     */
    public function session($index = null, $default = null)
    {
        if(array_key_exists($index, static::$session)){
            return static::$session[$index];
        }
        return (!is_null($default)) ? $default : null;
    }
}