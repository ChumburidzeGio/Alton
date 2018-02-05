<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 22:09
 *
 */

namespace App\Resources\Daisycon\Methods;

use App, Log;
use App\Interfaces\ResourceInterface;
use App\Models\Product;
use App\Models\ProductType;
use App\Resources\AbstractMethodRequest;
use Komparu\Value\ValueInterface;


class AbstractLoadFeeds extends AbstractMethodRequest
{
    protected $cacheDays = false;
    protected $productType = ""; //SimOnly1
    protected $linkTypes = ['xml_daisycon'];

    protected $processFields;

    private $providerId = null; //marksspencer,mango,omoda,sarenza,asos,zalando,vandenassem

    private $dumpFields = false;
    protected $skipDowload = false;
    private $fields = [];

    protected $limit = ValueInterface::INFINITE;

    protected $resultArr = [];

    protected $arguments = [
        ResourceInterface::PROVIDER_ID   => [
            'rules'   => 'string',
            'example' => 'telfort',
        ],
        ResourceInterface::DUMP_FIELDS   => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'function to dump all cols of the xml',
        ],
        ResourceInterface::SKIP_DOWNLOAD => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'example' => 'skip redownload of the xml file',
        ],
        ResourceInterface::LIMIT         => [
            'rules'   => 'integer',
            'example' => 'limit to amound of records',
        ],

    ];

    public function setParams(Array $params)
    {
        if (isset($params[ResourceInterface::PROVIDER_ID])) {
            $this->providerId = $params[ResourceInterface::PROVIDER_ID];
        }
        if (isset($params[ResourceInterface::DUMP_FIELDS]) && $params[ResourceInterface::DUMP_FIELDS]) {
            $this->dumpFields = true;
        }
        if (isset($params[ResourceInterface::SKIP_DOWNLOAD]) && $params[ResourceInterface::SKIP_DOWNLOAD]) {
            $this->skipDowload = true;
        }
        if (isset($params[ResourceInterface::LIMIT]) && $params[ResourceInterface::LIMIT]) {
            $this->limit = $params[ResourceInterface::LIMIT];
        }
    }


    public function __construct()
    {
    }

    public function executeFunction()
    {

    }


    public function getResult()
    {
        $productType = ProductType::where('name', $this->productType)->firstOrFail();

        //load one feed
        if ($this->providerId) {
            $product = Product::where(['product_type_id' => $productType->id, 'active' => 1, 'uid' => $this->providerId])->whereIn('link_type', $this->linkTypes)->first();
            if ($product) {
                $this->loadJsonAndProcess($product);
            }
            return $this->resultArr;
        }

        //load all feeds
        $feedProducts = Product::where(['product_type_id' => $productType->id, 'active' => 1])->whereIn('link_type', $this->linkTypes)->get();
        foreach ($feedProducts as $feedProduct) {
            $this->loadJsonAndProcess($feedProduct);
        }

        //return $this->removeDuplicates($this->resultArr, $this->productType);
        if ($this->dumpFields) {
            return $this->fields;
        }

        return $this->resultArr;
    }

    private function loadJsonAndProcess(Product $feedProduct) {
        $datafeed = json_decode(file_get_contents($feedProduct->partner_link), true);
        $datafeed = json_decode(json_encode($datafeed, JSON_NUMERIC_CHECK), true);
        if (!isset($datafeed['datafeed']['programs'][0]['products'])) {
            return;
        }
        $products = $datafeed['datafeed']['programs'][0]['products'];
        $filtername = ucfirst($feedProduct->uid);
        /** @var App\Resources\Daisycon\Methods\SimOnly\DefaultProvider $providerProcess */
        $providerProcess = App::make($this->classUrl . "\\Providers\\" . $filtername);
        foreach ($products as $product) {
            $providerProcess->set_data($product, $feedProduct->uid);
            foreach ($this->processFields as $item) {
                $providerProcess->{"process_$item"}();
            }
            $this->resultArr[] = $providerProcess->get_data();
        }
    }

}