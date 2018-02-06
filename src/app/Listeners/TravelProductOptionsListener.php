<?php

namespace App\Listeners\Resources2;

use Agent;
use App\Helpers\DocumentHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Events\Dispatcher;

class TravelProductOptionsListener
{

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.process.before', [$this, 'maintainDeleteData']);
        $events->listen('resource.product_options.travel.process.after', [$this, 'updateOptionsArrayInProduct']);
    }

    public function maintainDeleteData(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id)
    {
        if ($resource->name !== 'product_options.travel' || $action != 'destroy') {
            return;
        }
        //This is necessary in order to be able to remove the specified option from the product['options'] array
        $optionToBeDeleted = $this->getTravelDocumentById($id, 'product_options');
        $input->offsetSet(OptionsListener::OPTION_DELETED_DATA, $optionToBeDeleted->toArray());

    }

    public function updateOptionsArrayInProduct(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id){
        if ( $action === 'index' || $action === 'show') {
            return;
        }
        //Update the options array in the product with the (new) option coming in
        //Get the product from the product_id in the input
        switch ($action){
            case 'update':
                $product = $this->getTravelDocumentById($input['product_id'], 'product');
                if(isset($product['options']) && !is_null($product['options'])){
                    $product['options'] = $this->updateOptionsArray($product['options'], $input->getArrayCopy());
                    DocumentHelper::update('product', 'travel', $input['product_id'], $product->toArray());
                }
                break;
            case 'store':
                $product = $this->getTravelDocumentById($input['product_id'], 'product');
                if(!isset($product['options']) || is_null($product['options'])){
                    $product['options'] = [];
                }
                //Add the option that was created
                $optionToBeInserted[ResourceInterface::ID] = $input[ResourceInterface::OPTION_ID];
                $optionToBeInserted[ResourceInterface::NAME] = $input[ResourceInterface::NAME];
                $optionToBeInserted[ResourceInterface::COST] = $input[ResourceInterface::COST];
                $optionToBeInserted[ResourceInterface::DESCRIPTION] = isset($input[ResourceInterface::DESCRIPTION]) ? $input[ResourceInterface::DESCRIPTION]: null;
                $optionToBeInserted[ResourceInterface::REMOTE_ID] = isset($input[ResourceInterface::REMOTE_ID])? $input[ResourceInterface::REMOTE_ID] :null;
                $product['options'][] = $optionToBeInserted;
                DocumentHelper::update('product', 'travel', $input['product_id'], $product->toArray());
                break;
            case 'destroy':
                if($input->offsetExists(OptionsListener::OPTION_DELETED_DATA)){
                    $product = $this->getTravelDocumentById($input[OptionsListener::OPTION_DELETED_DATA]['product_id'], 'product');
                    if(isset($product['options']) && !is_null($product['options'])){
                        $product['options'] = $this->deleteFromOptionsArray($product['options'], $input->offsetGet(OptionsListener::OPTION_DELETED_DATA));
                        DocumentHelper::update('product', 'travel', $input[OptionsListener::OPTION_DELETED_DATA]['product_id'], $product->toArray());
                    }
                }
                break;
            default:
                return;
        }
    }

    protected function updateOptionsArray($options, $updatedOptionData)
    {
        foreach ($options as $key => $option){
            if($option['name'] === $updatedOptionData['name']){
                $options[$key][ResourceInterface::COST] = $updatedOptionData[ResourceInterface::COST];
                return $options;
            }
        }
        return $options;
    }

    protected function deleteFromOptionsArray($options, $deletedOptionData)
    {
        foreach ($options as $key => $option){
            if($option['name'] === $deletedOptionData['name']){
                unset($options[$key]);
            }
        }
        return $options;
    }

    protected function getTravelDocumentById($productId, $documentType)
    {
        try {
            return DocumentHelper::show($documentType, 'travel', $productId);
        } catch (\Exception $e) {
            return null;
        }

    }
}