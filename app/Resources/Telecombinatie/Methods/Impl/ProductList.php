<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Telecombinatie\Methods\Impl;


use App\Interfaces\ResourceInterface;
use App\Resources\Telecombinatie\Methods\TelecombinatieAbstractRequest;

class ProductList extends TelecombinatieAbstractRequest
{
    protected $cacheDays = false;

    private $list = false;
    private $providerList = null;
    private $networkList = null;

    protected $arguments = [
        ResourceInterface::PAGE => [
            'rules'   => 'number',
            'example' => '1',
        ]
    ];

    const PRODUCT_TYPE = 'simonly3';

    public function __construct()
    {
        parent::__construct('/api/content/propositions', 'post_json');
    }

    public function setParams(Array $params)
    {
        if( ! isset ($params[ResourceInterface::PAGE])){
            $this->list = true;
            parent::setParams(['page' => '0']);
            return;
        }
        parent::setParams($params);
    }

    public function getResult()
    {

        // list all
        if($this->list){
            $pageCount = (int) parent::getResult()['pageCount'];
            $resultArr = [];
            for($page = 0; $page < $pageCount; $page ++){
                $resultArr = array_merge($resultArr, $this->internalRequest(self::PRODUCT_TYPE, 'products', ['page' => $page]));
                echo "page nr: ".$page. " of total ".$pageCount.PHP_EOL;
            }
            return $resultArr;
        }

        //get all pages :)
        $result     = parent::getResult();
        $resultArr  = [];
        $this->meta = ['page_count' => $result['pageCount']];
        foreach($result['propositions'] as $res){

            $content = $this->convertFields($res['content']);
            if(!($content[ResourceInterface::PRICE_DEFAULT] > 0 )){
                continue;
            }

            if($content[ResourceInterface::SIM_ONLY] == false){
                continue;
            }

            if (strpos($content[ResourceInterface::PROVIDER_NAME],'Business')!==false) {
                continue;
            }
            //commmissions
            $content[ResourceInterface::COMMISSION_TOTAL]   = $res['totalBonus'];
            $content[ResourceInterface::COMMISSION_PARTNER] = $res['totalBonus'] * 0.8;


            unset($content[ResourceInterface::SIM_ONLY]);

            $content[ResourceInterface::RESOURCE_ID]          = $res[ResourceInterface::ID];
            $content[ResourceInterface::RESOURCE_NAME]        = $this->serviceproviderName;
            $content[ResourceInterface::URL]                  = "#";
            $content[ResourceInterface::NETWORK]              = $this->getNetworkName($content[ResourceInterface::NETWORK_CODE]);
            $content[ResourceInterface::PROVIDER_DESCRIPTION] = $this->getProviderDescription($content[ResourceInterface::PROVIDER_ID]);

            $content[ResourceInterface::CONDITION_NAME]  = 'http://code.komparu.com/userfiles/conditions/simonly/' . $content[ResourceInterface::PROVIDER_NAME] . 'AlgemeneVoorwaardenParticulier.pdf';
            $content[ResourceInterface::CONDITION_LABEL] = $content[ResourceInterface::PROVIDER_NAME] . ' voorwaarden.pdf';

            unset($content[ResourceInterface::NETWORK_CODE]);
            //unset($content[ResourceInterface::PROVIDER_CODE]);
            unset($content[ResourceInterface::SIM_ONLY]);

            if (!isset($content[ResourceInterface::INTERNET_TYPE])) {
                //$content[ResourceInterface::INTERNET_TYPE] = ResourceInterface::NA;
                $content[ResourceInterface::INTERNET_TYPE] = '3G';
            }

            $resultArr[] = $content;
        }
        return $this->removeDuplicates($resultArr, self::PRODUCT_TYPE);
    }

    private function getNetworkName($code)
    {
        //temporaryhack
        if(in_array($code, ['TMOB', 'BEN'])){
            return "T-Mobile";
        }
        if(in_array($code, ['YOU', 'TEL', 'KPN'])){
            return "KPN";
        }
        if(in_array($code, ['VOD', 'ZIG'])){
            return "Vodafone";
        }
        if(in_array($code, ['TL2'])){
            return "Tele2";
        }

        if($this->networkList == null){
            $this->networkList = $this->internalRequest(self::PRODUCT_TYPE, 'networks', []);
        }
        foreach($this->networkList as $provider){
            if($provider['code'] == $code){
                return $provider['description'];
            }
        }
        return 'unknown';
    }


    private function getProviderDescription($code)
    {
        if($this->providerList == null){
            $this->providerList = $this->internalRequest(self::PRODUCT_TYPE, 'providers', []);
        }
        return $this->providerList[$code]['description'];
    }

}