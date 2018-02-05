<?php

namespace App\Resources\Travel\Methods;

use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class OrderOptions extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        if(isset($this->params[ResourceInterface::ORDER_ID])){
            $order = $this->getOrderByOrderId($this->params[ResourceInterface::ORDER_ID]);

            if ($this->hasErrors()) {
                return;
            }

            //Necessary params for product call
            $productParams = [];
            $productParams[ResourceInterface::DESTINATION_ARRIVAL_DATE] = $order[ResourceInterface::DESTINATION_ARRIVAL_DATE];
            $productParams[ResourceInterface::DESTINATION_DEPARTURE_DATE] = $order[ResourceInterface::DESTINATION_DEPARTURE_DATE];
            $productParams[ResourceInterface::DESTINATION_GOOGLE_PLACE_ID] = $order[ResourceInterface::DESTINATION_GOOGLE_PLACE_ID];
            $productParams[ResourceInterface::ENABLED] = 1;
            $productParams[ResourceInterface::NUMBER_OF_CARS] = $order[ResourceInterface::NUMBER_OF_CARS];
            $productParams[ResourceInterface::NUMBER_OF_PERSONS] = $order[ResourceInterface::NUMBER_OF_PERSONS];
            $productParams[ResourceInterface::WEBSITE] = $order[ResourceInterface::WEBSITE];
            $productParams['__id'] = $order[ResourceInterface::PRODUCT_ID];
            $productParams['visible'] = 1;


            $productResult = ResourceHelper::callResource2('product.travel', $productParams);
            $options = isset($productResult[0], $productResult[0]['options'])? $productResult[0]['options'] : [];
            $availableOptions = isset($productResult[0], $productResult[0]['available_options'])? $productResult[0]['available_options'] : [];
            $options = $this->putOrderId($options, $this->params[ResourceInterface::ORDER_ID]);
            foreach ($availableOptions as $availableOption){
                $found = $this->findInOptions($options, $availableOption);

                if($found !== false){
                    $options[$found]['available'] = 1;
                    if(isset($this->params[ResourceInterface::__ID])){
                        $ids = is_array($this->params[ResourceInterface::__ID]) ? $this->params[ResourceInterface::__ID] : explode(',', $this->params[ResourceInterface::__ID]);
                        if(!in_array($found,$ids)){
                            unset($options[$found]);
                        }
                    }
                }
            }
            foreach ($options as $key => $value){
                $options[$key]['id'] = intval($options[$key]['id']);
            }
            $this->result = $options;
        }elseif(isset($this->params['target'])){
            //This is a reference call so just return the options from the order
            $order = $this->getOrderById($this->params['target']);
            $ids = explode(',', $order['options'] );
            $optionResult = ResourceHelper::callResource2('options.travel', ['__id' => $ids]);
            $this->result = $optionResult;
        }else{
            $this->result = [];
        }
    }

    protected function findInOptions($options, $option_id)
    {
        foreach ($options as $key => $option_data){
            if($option_data['id'] === $option_id){
                return $key;
            }
        }
        return false;
    }

    protected function putOrderId($options, $order_id)
    {
        foreach ($options as $key => $option_data){
            $options[$key][ResourceInterface::ORDER_ID] = $this->params[ResourceInterface::ORDER_ID];
        }
        return $options;
    }
}