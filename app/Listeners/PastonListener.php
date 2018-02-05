<?php

namespace App\Listeners\Resources2;

use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Support\Facades\Config;

class PastonListener
{
    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.premium.carinsurance.paston.process.after', [$this, 'addRoadsideAssistance']);
    }

    /**
     * Add NL roadside assistance to default price.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function addRoadsideAssistance(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if (!((app()->configure('resource_paston')) ? '' : config('resource_paston.settings.add_roadside_assistance_nl')))
            return;

        $products = [];
        foreach($output->getArrayCopy() as $key => $product){

            $product[ResourceInterface::PRICE_DEFAULT] = $product[ResourceInterface::PRICE_DEFAULT] + $product[ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE];
            $product[ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE] = 0;

            $products[] = $product;
        }
        $output->exchangeArray($products);
    }
}