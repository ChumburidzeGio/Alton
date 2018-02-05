<?php
/**
 * Created by PhpStorm.
 * User: giorgi
 * Date: 11/30/17
 * Time: 4:52 PM
 */

namespace App\Listeners\Resources2;

use App\Interfaces\ResourceInterface;
use Illuminate\Events\Dispatcher;
use App\Models\Resource;
use ArrayObject;

class ContentsInsuranceMoneyview2Listener
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.product.contentsinsurance2.limit.before', [$this, 'process']);
    }

    public function process(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $output->exchangeArray(array_values(array_filter($output->getArrayCopy(), function ($item) {
            return $item[ResourceInterface::PRICE_ACTUAL];
        })));
    }
}