<?php

namespace App\Listeners\Resources2;

use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Support\Facades\Config;

/**
 * Class DefaultListener, to store generic functions
 * @package App\Listeners\Resources2
 */
class CompaniesListener
{

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.companies.general.process.after', [$this, 'setImageCdn']);
    }

    public function setImageCdn(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if($action != RestListener::ACTION_INDEX){
            return;
        }

        app()->configure('cdn');
        $domain = Config::get('cdn.domain');

        $output->exchangeArray(array_map(function($item) use ($domain) {
            if (empty($item[ResourceInterface::IMAGE])) { return $item; }
            $item[ResourceInterface::IMAGE] = $domain.$item[ResourceInterface::IMAGE];
            return $item;
        }, $output->getArrayCopy()));
    }

}