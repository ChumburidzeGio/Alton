<?php

namespace App\Listeners\Resources2;

use App\Helpers\WebsiteHelper;
use App\Interfaces\ResourceInterface;
use Illuminate\Support\Arr;
use App\Models\Resource;
use App\Helpers\ResourceHelper;
use ArrayObject;
use Input;

/**
 * Class DefaultListener, to store generic functions
 * @package App\Listeners\Resources2
 */
class SimonlyListener extends DefaultListener
{

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.product.simonly7.process.after', [$this, 'setAffiliateLinks']);
    }

}