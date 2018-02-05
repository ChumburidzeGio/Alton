<?php

namespace App\Helpers;


use App\Exception\InvalidResourceInput;
use App\Exception\NotExistError;
use App\Exception\ResourceError;
use App\Exception\ServiceError;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\ServiceListener;
use App\Models\ProductType;
use App\Models\Resource;
use App\Models\Sale;
use App\Models\User;
use App\Models\Website;
use Event;
use Illuminate\Support\Facades\Log;
use Komparu\Document\Contract\Document;

class ContractHelper
{
    private static $ignoreList = ['contract.carinsurance', 'contract.vaninsurance'];

    //Put stuff here that you want handled straight by the Contract method of the service resource
    private static $skipList = ['contract.vvghealthcarech', 'contract.healthcare2018', 'contract.elipslife', 'contract.privateliabilityde'];

    const ORDER_DEFAULTS = ['__index', '__type', '__id', 'website_id', 'user_id', 'product_id', 'session_id', 'ip', 'session', 'status', 'product', 'test', 'error'];

    public static function retrieve($resource, Array $input)
    {
        $productType = $resource->productType->name;


        if( ! isset($input['id'])){
            throw new InvalidResourceInput($resource, ['id' => [0 => 'Order ID not set.']], $input, 'An order ID is required for contract retrieval.');
        }
        try{
            $document = DocumentHelper::show('order', $productType, $input['id']);
            $product  = $document->toArray();
            return json_decode($product['status'], true);
        }catch(\Exception $e){
            throw new ServiceError($resource, $input, 'Document not found: ' . $input['id']);
        }
    }

    /**
     * Process the contract
     *
     * @param Resource $resource
     * @param array $input
     *
     * @return array
     * @throws ResourceError
     * @throws ServiceError
     */
    public static function process(Resource $resource, Array $input)
    {
        if( ! isset($input['user'], $input['website'])){
            throw new ServiceError($resource, $input, '`user` and `website` parameters are required for contract.');
        }

        //Fire the service directly for resources in skipList
        if( in_array($resource->name, self::$skipList)){
            $serviceResult = ServiceListener::callService($resource, $input);
            $result = $result = isset($serviceResult['status']) ? $serviceResult: ['status' => 'success'];
            return $result;
        }

        $userId    = $input['user'];
        $websiteId = $input['website'];

        /** @var ProductType $productTypeObj */
        $productType = $resource->productType->name;

        $productId = $input[ResourceInterface::PRODUCT_ID];


        if(isset($input['product_data'])){
            $product = $input['product_data'];
        }else{
            try{
                $productDocument = DocumentHelper::show('product', $productType, $productId, ['conditions' => ['user' => $userId, 'website' => $websiteId]], true);
            }catch(\Exception $e){

                try{
                    $data            = ResourceHelper::callResource2('product.' . $productType, [ResourceInterface::__ID => $productId, 'getproduct' => true, 'user' => $userId, 'website' => $websiteId]);
                    $productDocument = $data[0];
                }catch(\Exception $e){
                    throw new ServiceError($resource, $input, 'Document not found: ' . $productType . ' product with __id ' . $productId . ' (' . $e->getMessage() . ')');
                }
            }

            if( ! $productDocument){
                throw new ServiceError($resource, $input, 'Document not found: ' . $productType . ' product with __id ' . $productId);
            }
            $product = $productDocument instanceof Document ? $productDocument->toArray() : $productDocument;
        }


        //if send by external source, use that product info
        $productData = json_encode($product);


        // See if this product uses a different contract resource
        if(isset($product[ResourceInterface::CONTRACT_RESOURCE_NAME]) and ! empty($product[ResourceInterface::CONTRACT_RESOURCE_NAME])){
            $resourceServiceName = $resource->getServiceName();
            $resource = Resource::where('name', $product[ResourceInterface::CONTRACT_RESOURCE_NAME])->firstOrFail();

            // Insert session product data into contract
            // TODO: Make code properly pass product_data into contracts via $input
            $sessionData = json_decode($input['session'], true);
            if(isset($sessionData['product.' . $resourceServiceName])){
                foreach($sessionData['product.' . $resourceServiceName] as $key => $value){
                    if( ! isset($input[$key])){
                        $input[$key] = $value;
                    }
                }
            }
        }




        /**
         * in case there is a referal website, order should be on their name
         */
        if(isset($input['referal_website_id'])){
            $website   = Website::findOrFail($input['referal_website_id']);
            $websiteId = $website['id'];
            $userId    = $website['user_id'];
        }




        //set clickId
        $clickId = isset($input['referal_click_id']) ? $input['referal_click_id'] : (isset($input['click_id']) ? $input['click_id'] : null);

        $hash  = md5(rand());
        $order = [
            'user_id'    => $userId,
            'website_id' => $websiteId,
            'status'     => ['pending'],
            'ip'         => $input['ip'],
            'session_id' => $input['session_id'],
            'product_id' => $productId,
            'product'    => $productData,
            'session'    => $input['session'],
            'hash'       => $hash
        ];

        //Check if you have licenseplate in session
        if($resource->name == 'contract.carinsurance'){
            $sessionData = json_decode(urldecode($input['session']));
            if(isset($sessionData->{'product.carinsurance'}, $sessionData->{'product.carinsurance'}->licenseplate)){
                $licensePlate = $sessionData->{'product.carinsurance'}->licenseplate;
                $licenseData  = ResourceHelper::callResource2('licenseplate_basic.carinsurance', [ResourceInterface::LICENSEPLATE => $licensePlate]);
                if($licenseData){
                    //We have found the corresponding data, add it to the order...
                    $order['licenseplate_data'] = json_encode($licenseData);
                }
            }elseif(isset($sessionData->{'product.carinsurance'}, $sessionData->{'product.carinsurance'}->type_id)){
                //TODO: Hanldle the type id case
            }
        }

        // Insert the order
        // By that moment an order might already be created
        // with the “draft” status and the same session ID
        // probably must check
        $order = DocumentHelper::insert('order', $productType, $order);
        if( ! $order->success()){
            Log::error('Could not store order!!!!');

            Log::error($order);
            throw new ServiceError($resource, $input, 'Could not store order!!: ' . $productId);
        }

        /*
         * Call the service
         */
        $orderId                                               = $order->product()->id();
        $input['_forinternaluse'][ResourceInterface::ORDER_ID] = $orderId;



        /**
         * Some resources we want to ignore, legacy issues
         */
        $serviceResult = [];
        if( ! in_array($resource->name, self::$ignoreList)){
            try{
                $serviceResult = ServiceListener::callService($resource, $input);
                cw('Called contract resource ' . $resource->name);
            }catch(NotExistError $e){
                ;
                //Method does not exist, ignore this exception, no panic
                cw('Contract method not available :' . $e->getMessage());
            }catch(ResourceError $e){
                $result = ['status' => 'rejected', 'id' => $orderId, 'error_messages' => $e->getMessages()];
                DocumentHelper::update('order', $productType, $orderId, ['status' => json_encode($result)]);
                throw $e;
            }catch(ServiceError $e){
                $result = ['status' => 'error', 'id' => $orderId, 'error' => $e->getMessage()];
                DocumentHelper::update('order', $productType, $orderId, ['status' => json_encode($result)]);
                throw $e;
            }
        }



        $result = ['status' => 'success', 'id' => $orderId] + (array) $serviceResult;


        /**
         * Add results as column in Order if they are available.
         * Remove from result, unless specified in the contract resource as output field.
         */
        $outputFields = [];
        foreach($resource->fields as $field)
            if ($field->output)
                $outputFields[] = $field->name;
        $update      = array_except($order->product()->toArray(), self::ORDER_DEFAULTS);
        $updateArray = [];
        foreach(array_keys($update) as $updateKey){
            if( ! isset($result[$updateKey])){
                continue;
            }
            $updateArray[$updateKey] = $result[$updateKey];
            if (!in_array($updateKey, $outputFields))
                unset($result[$updateKey]);
        }
        $updateArray['status'] = json_encode($result);
        DocumentHelper::update('order', $productType, $orderId, $updateArray);


        //notify email
        Event::fire('email.notify', [$productType, 'order.status.success', $orderId, $websiteId, $userId]);

        //extra notification fired if needed for sending policy email
        Event::fire('email.notify', [$productType, 'order.status.success2', $orderId, $websiteId, $userId]);


        /**
         * Insert sale
         */

        $user = User::findOrFail($userId);

        $total   = array_get($product, 'commission.total');
        $partner = array_get($product, 'commission.partner');
        //        $total   = array_has($productData, 'commission.total') ? array_get($productData, 'commission.total') : array_get($product, 'commission.total');
        //        $partner = array_has($productData, 'commission.partner') ? array_get($productData, 'commission.partner') : array_get($product, 'commission.partner');
        if($total === null || $partner === null){
            $total      = 0;
            $commission = 0;
        }else{
            $commission = number_format(((float) $total - (float) $partner), 2);
            $total      = number_format((float) $partner, 2);
        }
        try{

            $sku = array_get($product, 'resource.id');
            if($sku === null){
                $sku = $productId;
            }

            $store_name = ! is_null(array_get($product, 'company.name')) ? array_get($product, 'company.name') : $productType;
            $sale       = [
                'user_id'        => $userId,
                'click_id'       => $clickId, // click_id
                'url_identifier' => $user['url_identifier'], // users.url_identifier
                'network'        => $productType . '-funnel',
                'order_uid'      => uniqid($orderId . '-'),
                'total'          => $total,
                'commission'     => $commission,
                'status'         => 'open',
                'sku'            => $sku,    // resource_id
                'store_name'     => $store_name,   // company name
                'product_name'   => array_get($product, 'title'),           // product name
                'lead_time'      => date('Y-m-d H:i:s'),
            ];
            $sale       = Sale::create($sale);
        }catch(\Exception $e){
            throw new ServiceError($resource, $input, 'Sale count not be created ' . $e);
        }

        return $result;
    }
}
