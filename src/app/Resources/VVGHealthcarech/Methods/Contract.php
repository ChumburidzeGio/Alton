<?php

namespace App\Resources\VVGHealthcarech\Methods;


use App\Exception\PrettyServiceError;
use App\Exception\ServiceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Models\Sale;
use App\Models\User;
use App\Resources\VVGHealthcarech\VVGHealthcarechAbstractRequest;

class Contract extends VVGHealthcarechAbstractRequest
{

    public function setParams(Array $params)
    {
        parent::setParams($params);
    }

    public function executeFunction()
    {
        if(isset($this->params[ResourceInterface::HASH])){
            $account = ResourceHelper::callResource2('account.knip',['hash' => $this->params[ResourceInterface::HASH]]);
            $this->params[ResourceInterface::KNIP_ACCOUNT] = json_encode($account);
            //TODO: Assemble the questions from INPUT and fire a account_properties.knip

        }else{
            //We need to create the account with Knip and get a public key for saving
            //Do not send country as knip generates it from the phone prefix
            $accountData = [
                ResourceInterface::FIRST_NAME       =>      array_get($this->params, ResourceInterface::FIRST_NAME),
                ResourceInterface::LAST_NAME        =>      array_get($this->params, ResourceInterface::LAST_NAME),
                ResourceInterface::BIRTHDATE        =>      array_get($this->params, ResourceInterface::BIRTHDATE),
                ResourceInterface::GENDER           =>      array_get($this->params, ResourceInterface::GENDER),
                ResourceInterface::POSTAL_CODE      =>      array_get($this->params, ResourceInterface::POSTAL_CODE),
                ResourceInterface::CITY             =>      array_get($this->params, ResourceInterface::CITY),
                ResourceInterface::STREET           =>      array_get($this->params, ResourceInterface::STREET),
                ResourceInterface::HOUSE_NUMBER     =>      array_get($this->params, ResourceInterface::HOUSE_NUMBER),
                ResourceInterface::EMAIL            =>      array_get($this->params, ResourceInterface::EMAIL),
                ResourceInterface::PHONE            =>      array_get($this->params, ResourceInterface::PHONE),
                ResourceInterface::SESSION_ID       =>      array_get($this->params, ResourceInterface::SESSION_ID),
            ];

            try{
                $account = ResourceHelper::callResource2('account.knip', $accountData, RestListener::ACTION_STORE);
                $this->params['hash'] = head($account['applications'])['hash'];
            }catch (\Exception $ex){
                //Existing account return status:redirect
                //TODO: Put the call to knip that triggers the email to client here
                $this->result = ['status' => 'redirect'];
                return;
            }
        }

        //Fetch the product data
        $data            = ResourceHelper::callResource2('product.' . 'vvghealthcarech', [
            ResourceInterface::__ID => $this->params['product_id'],
            ResourceInterface::BIRTHDATE => array_get($this->params, ResourceInterface::BIRTHDATE),
            ResourceInterface::GENDER => array_get($this->params, ResourceInterface::GENDER),
            ResourceInterface::CALCULATION_FRANCHISE => array_get($this->params, ResourceInterface::CALCULATION_FRANCHISE),
            ResourceInterface::ACCIDENT => array_get($this->params, ResourceInterface::ACCIDENT),
            ResourceInterface::CALCULATION_CONTRACT_DURATION => array_get($this->params, ResourceInterface::CALCULATION_CONTRACT_DURATION, 1),
        ]);

        $productData = $data[0];
        if(empty($productData)){
            $resource = Resource::where(['name' => 'product.vvghealthcarech'])->firstOrFail();
            throw new PrettyServiceError($resource,[],'Could not find product.');
        }


        //Assemble the Order Data
        $orderData = [
            ResourceInterface::USER_ID          =>      array_get($this->params, ResourceInterface::USER_ID),
            ResourceInterface::WEBSITE_ID       =>      array_get($this->params, ResourceInterface::WEBSITE_ID),
            ResourceInterface::STATUS           =>      array_get($this->params, ResourceInterface::STATUS),
            ResourceInterface::IP               =>      array_get($this->params, ResourceInterface::IP),
            ResourceInterface::SESSION_ID       =>      array_get($this->params, ResourceInterface::SESSION_ID),
            ResourceInterface::PRODUCT_ID       =>      array_get($this->params, ResourceInterface::PRODUCT_ID),
            ResourceInterface::PRODUCT          =>      $productData,
            ResourceInterface::SESSION          =>      array_get($this->params, ResourceInterface::SESSION),
            ResourceInterface::FIRST_NAME       =>      array_get($this->params, ResourceInterface::FIRST_NAME),
            ResourceInterface::LAST_NAME        =>      array_get($this->params, ResourceInterface::LAST_NAME),
            ResourceInterface::BIRTHDATE        =>      array_get($this->params, ResourceInterface::BIRTHDATE),
            ResourceInterface::GENDER           =>      array_get($this->params, ResourceInterface::GENDER),
            ResourceInterface::POSTAL_CODE      =>      array_get($this->params, ResourceInterface::POSTAL_CODE),
            ResourceInterface::CITY             =>      array_get($this->params, ResourceInterface::CITY),
            ResourceInterface::STREET           =>      array_get($this->params, ResourceInterface::STREET),
            ResourceInterface::HOUSE_NUMBER     =>      array_get($this->params, ResourceInterface::HOUSE_NUMBER),
            ResourceInterface::EMAIL            =>      array_get($this->params, ResourceInterface::EMAIL),
            ResourceInterface::PHONE            =>      array_get($this->params, ResourceInterface::PHONE),
            ResourceInterface::HASH            =>      array_get($this->params, ResourceInterface::HASH),
            ResourceInterface::STATUS           =>      ['COMPLETED']
        ];

        //Get the existing order if it is there
        $existingOrder = DocumentHelper::get('order', 'vvghealthcarech', [
            'filters' => [
                ResourceInterface::SESSION_ID => $this->params['session_id'],
                ResourceInterface::STATUS => json_encode(['DRAFT']),
            ]
        ])->documents()->toArray();

        if(!empty($existingOrder)){
            //Update the order
            $order = DocumentHelper::update('order', 'vvghealthcarech', $existingOrder[0][ResourceInterface::__ID], $orderData)->product();
        }else{
            //Create a new one since there is no existing
            $order = DocumentHelper::insert('order', 'vvghealthcarech', $orderData)->product();
        }

        $knipData = [];
        $knipData['product_ids'] = array_pluck($productData['sub_products'], 'product_id');
        $knipData['company'] = [
            'name' => $productData['company']['name'],
            'knip_id' => $productData['knip_id']
        ];
        $knipData['price'] = $productData['price_actual'];
        $knipData['order_id'] = $order['__id'];
        $knipData['hash'] = array_get($this->params, ResourceInterface::HASH);

        //Push the sale to knip
        $knipResult = ResourceHelper::callResource2('set_additional_insurances.knip', $knipData, RestListener::ACTION_STORE);

        //Return the order as result
        $this->result = $order;
    }

    public function sendEmails($resource, $orderId, $input){
        $websiteId = array_get($input,'website_id');
        $userId = array_get($input,'user_id');
        $productType = $resource->productType->name;


        //notify email
        Event::fire('email.notify', [$productType, 'order.status.success', $orderId, $websiteId, $userId]);

        //extra notification fired if needed for sending policy email
        Event::fire('email.notify', [$productType, 'order.status.success2', $orderId, $websiteId, $userId]);

        return $input;
    }

    public function setSale($resource, $product, $orderId, $userId, $input)
    {
        /**
         * Insert sale
         */

        $user = User::findOrFail($userId);
        $clickId = array_get($input, 'click_id');
        $productType = $resource->productType->name;

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
                $sku = $input['product_id'];
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

            return $sale->toArray();
        }catch(\Exception $e){
            throw new ServiceError($resource, $input, 'Sale count not be created ' . $e);
        }
    }

}