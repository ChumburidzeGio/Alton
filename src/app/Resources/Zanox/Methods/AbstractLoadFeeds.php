<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 22:09
 *
 */

namespace App\Resources\Zanox\Methods;

use App, Log;
use App\Interfaces\ResourceInterface;
use App\Models\Product;
use App\Models\ProductType;
use App\Resources\AbstractMethodRequest;
use DOMDocument;
use Komparu\Value\ValueInterface;
use SimpleXMLElement;
use XMLReader;

class AbstractLoadFeeds extends AbstractMethodRequest
{

    protected $cacheDays = false;
    protected $productType = ""; //SimOnly1
    protected $linkTypes = ['xml_zanox', 'xml_zanox_combi'];
    protected $feedProducts = [];
    protected $processFields = [];
    protected $classUrl = '';
    protected $resultArr = [];
    protected $incorrectData = [];

    //TODO: REMOVE
    private $providerId = null; //marksspencer,mango,omoda,sarenza,asos,zalando,vandenassem

    private $dumpFields = false;
    protected $skipDowload = false;
    private $fields = [];

    protected $limit = ValueInterface::INFINITE;

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
                $this->loadXmlAndProcess($product);
            }
            return $this->resultArr;
        }

        //load all feeds
        $this->feedProducts = Product::where(['product_type_id' => $productType->id, 'active' => 1])->whereIn('link_type', $this->linkTypes)->get();
        foreach ($this->feedProducts as $feedProduct) {
            $this->loadXmlAndProcess($feedProduct);
        }

        //return $this->removeDuplicates($this->resultArr, $this->productType);
        if ($this->dumpFields) {
            return $this->fields;
        }

        return $this->resultArr;

    }


    private function processFeed(Product $feedProduct, SimpleXMLElement $data)
    {
        //cw($feedProduct->name);

        //dump all cols
        if ($this->dumpFields) {
            $xml = simplexml_load_string($data->asXML(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $array = json_decode(json_encode((array)$xml), true);
            foreach (array_keys($array) as $key) {
                if (!in_array($key, $this->fields)) {
                    $this->fields[] = $key;
                }
            }
            return;
        }


        if ($feedProduct->link_type == 'xml_zanox_combi') {
            //hierdynamische declaratie
            //hier this->classUrl
            $combiFeedClass = App::make($this->classUrl . "\\ProcessCombiFeed");
            $xml = simplexml_load_string($data->asXML(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $array = json_decode(json_encode((array)$xml), true);
            $combiFeedClass->setData($array);

            $arrayData = $combiFeedClass->process();

            $arrayData[ResourceInterface::RESOURCE_ID] = $feedProduct->link_type . '_' . $feedProduct->uid . (!empty($arrayData['recordHash']) ? $arrayData['recordHash'] : $arrayData['offerId']);
            $arrayData[ResourceInterface::RESOURCE_NAME] = $this->serviceproviderName;
            $this->resultArr[] = $arrayData;

            return;
        }


        $filtername = ucfirst($feedProduct->uid);
        if (empty($data->title) && empty($data->description) && empty($data->url)) {
            return;
        }

        $providerProcess = App::make($this->classUrl . "\\Providers\\" . $filtername);
        $providerProcess->set_data($data);

        if (is_null($providerProcess->get_data())) {
            return;
        }

        if ($providerProcess->is_excluded() === true) {
            return;
        }

        foreach ($this->processFields as $item) {
            $providerProcess->{"process_$item"}();
        }


        $arrayData = $providerProcess->get_data();
        $arrayData[ResourceInterface::PROVIDER_ID] = $feedProduct->uid;
        //hack for network
        $arrayData[ResourceInterface::NETWORK] = $feedProduct->text;
        //recordHash
        $arrayData[ResourceInterface::RESOURCE_ID] = $feedProduct->link_type . '_' . $feedProduct->uid . (!empty($data->recordHash) ? $data->recordHash : '');
        $arrayData[ResourceInterface::RESOURCE_NAME] = $this->serviceproviderName;



        if (!array_key_exists('error', $arrayData)) {
            $this->resultArr[] = $arrayData;
        } else {
            $this->incorrectData[] = $arrayData;
            print_r($arrayData['error']);
        }
    }

    private function postProcessing()
    {}

    private function loadXmlAndProcess(Product $feedProduct)
    {
        $tmpFile = storage_path() . '/' . $this->productType . '_' . $feedProduct->uid . '.xml';
        if (!$this->skipDowload) {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            try {
                $fopen = fopen($feedProduct->partner_link, 'r');
            } catch (\Exception $e) {
                Log::error($feedProduct->name . ': could not load feed! ' . $feedProduct->partner_link);
                echo $feedProduct->name . ': could not load feed! ' . $feedProduct->partner_link . PHP_EOL;
                return;
            }
            if (!$fopen) {
                Log::error($feedProduct->name . ': could not load feed! ' . $feedProduct->partner_link);
                echo $feedProduct->name . ': could not load feed! ' . $feedProduct->partner_link . PHP_EOL;
                return;
            }
            file_put_contents($tmpFile, $fopen);
        }

        $size = filesize($tmpFile);
        if ($size > 0) {
            $unset = false;
            $z = new XMLReader;
            $z->open($tmpFile);
            $doc = new DOMDocument;
            while ($z->read() && $z->name !== 'record') {
                ;
            }
            $i = 0;
            while ($z->name === 'record') {
                if ($i > $this->limit) {
                    $this->postProcessing();
                    return;
                }
                $node = simplexml_import_dom($doc->importNode($z->expand(), true));
                $this->processFeed($feedProduct, $node);
                $z->next('record');

                $i++;
            }


            if ($i < 2) {
                $unset = true;
            }
        } else {
            $unset = true;
        }
        // als er geen of minder dan 2 records zijn gevonden, zorg er dan voor dat de huidige records niet uit de database worden verwijderd.
        if ($unset === true) {
            //TODO: do not disable entries of this feed.
            //            $controller->unset_feed($insurance);
        }
    }
}