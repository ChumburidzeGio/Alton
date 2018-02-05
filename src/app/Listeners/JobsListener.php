<?php

namespace App\Listeners\Resources2;

use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Helpers\WebsiteHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Support\Arr;
use Komparu\Value\ValueInterface;

/**
 * Class CarinsuranceListener
 * @package App\Listeners\Resources2
 */
class JobsListener
{
    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        //$events->listen('resource.jobs.general.process.input', [$this, 'setDefaults']);
    }

    public static function setDefaults(Resource $resource, ArrayObject $input, $action)
    {
        if($action === RestListener::ACTION_STORE){
            if(!isset($input['retries'])){ $input['retries'] = 0; }
            if(!isset($input['status'])){ $input['status'] = 0; }
            if(!isset($input['timestamp'])){ $input['timestamp'] = (new \DateTime('now', New \DateTimeZone('UTC')))->format('Y-m-d H:i:s'); }
        }
    }
}