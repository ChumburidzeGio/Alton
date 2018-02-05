<?php

namespace App\Listeners\Resources2;

use Illuminate\Events\Dispatcher;
use App\Models\Resource;
use ArrayObject, DB;

class PrivateHealthcareDeListener
{

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.product_privateliabilityde.blaudirekt.limit.before', [$this, 'process']);
    }

    public function process(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $output->exchangeArray(array_values(array_filter($output->getArrayCopy(), function ($item) {
            return floatval($item['price']) > 0;
        })));
    }
}