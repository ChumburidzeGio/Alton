<?php

namespace App\Listeners\Resources2;

use Agent;
use Event;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Komparu\Document\Contract\Options;
use Komparu\Value\ValueInterface;

class TravelOrderAggregateListener
{
    public function subscribe(Dispatcher $events)
    {
        //TODO: Expand the listen to resource.aggregate.process.after
        $events->listen('resource.aggregate.order.travel.process.after', [$this, 'processData']);

        $events->listen('resource.aggregate.order.travel.process.input', [$this, 'processInput']);
    }

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public function processInput(Resource $resource, ArrayObject $input)
    {
        $moment  = $input->offsetGet('moment');
        $from_to = $input->offsetExists($moment) ? $input->offsetGet($moment) : false;
        if ($from_to) {
            list($from, $to) = explode(Options::SORT_DELIMITER, $from_to);

            $input->offsetSet('from', $from);
            $input->offsetSet('to', $to);

            $input->offsetUnset($moment);
        }
        else if (!isset($input['from']) || !isset($input['to'])) {
            $input->offsetSet('from', date('Y-m-d H:i:s', strtotime('-1 year')));
            $input->offsetSet('to', date('Y-m-d H:i:s', strtotime('now')));
        }

        Event::fire('resource.product.travel.process.input', [$resource, $input, 'aggregate']);
    }


    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     */
    public function processData(Resource $resource, ArrayObject $input, ArrayObject $data)
    {
        $interval = $input['interval'];
        $from = strtotime($input['from']);
        $to = min(time(), strtotime($input['to']));
        //Add ids to the data
        $data->exchangeArray($this->addIds($data));

        //Get the empty date data
        $empty_data = $this->getEmptyDateData($data, $interval, $from, $to);

        $merged_data = $this->mergeData($data, $empty_data);

        //Limit the data
        $offset     = isset($input[OptionsListener::OPTION_OFFSET]) ? $input[OptionsListener::OPTION_OFFSET] : 0;
        $limit      = isset($input[OptionsListener::OPTION_LIMIT]) ? $input[OptionsListener::OPTION_LIMIT] : ValueInterface::INFINITE;
        $order      = isset($input[OptionsListener::OPTION_ORDER]) ? $input[OptionsListener::OPTION_ORDER] : '__id';
        $direction = (isset($input[OptionsListener::OPTION_DIRECTION]) ? $input[OptionsListener::OPTION_DIRECTION] : OptionsListener::OPTION_DIRECTION_ASC);
        $descending = $direction === OptionsListener::OPTION_DIRECTION_DESC ? true : false;
        if (!(defined('TOTAL_HEADER_SENT') and TOTAL_HEADER_SENT)) {
            if (!(strpos(php_sapi_name(), 'cli') !== false)) {
                header("X-Total-Count: " . count($merged_data['processed']));

                $range = sprintf('Content-Range: %s %d-%d/%d', $resource->name, $offset, $limit, count($merged_data['processed']));
                header($range);
            }
            define('TOTAL_HEADER_SENT', true);
        }
        $limited = Collection::make($merged_data['processed'])->sortBy($order, null, $descending)->slice($offset, $limit)->toArray();

        // we need to do it here because otherwise it wouldn't get sorted
        if (!empty($limited) and preg_match('/[\d]{4}-[\d]{2}-[\d]{2}/', $limited[0]['__id'])) {
            array_walk($limited, function (&$period) {
                $period = array_merge([
                    '__id'   => date('d-m-Y', strtotime($period['__id'])),
                    'period' => date('d-m-Y', strtotime($period['period'])),
                ], $period);
            });
        }

        $data->exchangeArray($limited);
    }

    private function mergeData(ArrayObject $data, array $empty_data)
    {
        foreach ($data as $aggregate_item){
            $array_key = $empty_data['index'][$aggregate_item['__id']];
            $merged_item = array_merge($empty_data['processed'][$array_key], $aggregate_item);
            $empty_data['processed'][$array_key] = $merged_item;
        }
        return $empty_data;
    }

    /**
     * This function creates the empty date data array from set beginning of time.
     * It returns array with:
     * index for easy lookup of array keys to speed up merging with the actual data
     * processed contains the actual "empty" date array
     *
     * @param ArrayObject $data
     * @param $interval
     * @param $from
     * @param $to
     *
     * @return mixed
     */
    private function getEmptyDateData(ArrayObject $data, $interval, $from, $to)
    {
        $return['index'] = [];
        $sub_array = array_fill_keys(['period', 'count', 'min', 'max', 'avg', 'sum'], 0);
        $empty_array = [];
        $array_index = 0;

        foreach(self::nextInterval($interval, $from, $to) as $interval) {
            $sub_array['period'] = $interval;
            $empty_array[] = $sub_array;
            $return['index'][$interval] = $array_index++;
        }

        $return['processed'] =  $this->addIds($empty_array);

        return $return;
    }

    private static function nextInterval($interval, $from, $to)
    {
        $yield     = '';
        while ($from <= $to) {
            $new_yield = self::formatInterval($interval, $from);

            $from += 86400;

            if ($yield !== $new_yield) {
                $yield = $new_yield;
                yield $yield;
            }
        }
    }

    private function addIds($data)
    {
        $process_data = $data;
        if($data instanceof ArrayObject){
            $process_data = $data->getArrayCopy();
        }
        array_walk($process_data, function (&$item, $key){
            if(is_array($item) && (!isset($item['__id']) || $item['__id'] == null || $item['__id'] === 0)){
                //Get the first element for now to use as an "id"
                $item = ['__id' => array_values($item)[0]] + $item;
            }
        });
        return $process_data;
    }



    /**
     * @param $interval
     * @param $from
     *
     * @return false|string
     */
    private static function formatInterval($interval, $from)
    {
        switch ($interval) {
            case 'month':
                $new_yield = date('Y-m', $from);
                break;
            case 'year':
                $new_yield = date('Y', $from);
                break;
            case 'week':
                $w = (int) date('W', $from);
                $m = (int) date('n', $from);
                $w = $w == 1 ? ($m == 12 ? 53 : 1) : ($w >= 51 ? ($m == 1 ? 0 : $w) : $w);
                $new_yield = date('Y', $from) . ' #' . sprintf('%02d', $w);
                break;
            default:
                $new_yield = date('Y-m-d', $from);
                break;

        }

        return $new_yield;
    }
}