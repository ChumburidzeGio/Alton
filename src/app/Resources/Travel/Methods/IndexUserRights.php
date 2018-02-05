<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Models\Right;
use App\Resources\Travel\TravelRightsAbstractRequest;
use Illuminate\Support\Collection;
use Komparu\Value\ValueInterface;

class IndexUserRights extends TravelRightsAbstractRequest
{
    public function setParams(Array $params)
    {
        $this->params = $params;
        $this->params[OptionsListener::OPTION_OFFSET] = isset($params[OptionsListener::OPTION_OFFSET]) ? $params[OptionsListener::OPTION_OFFSET] : 0;
        $this->params[OptionsListener::OPTION_LIMIT] = isset($params[OptionsListener::OPTION_LIMIT]) ? $params[OptionsListener::OPTION_LIMIT] : ValueInterface::INFINITE;
        $this->params[OptionsListener::OPTION_ORDER] = isset($params[OptionsListener::OPTION_ORDER]) ? $params[OptionsListener::OPTION_ORDER] : 'id';
        $this->params[OptionsListener::OPTION_DIRECTION]= (isset($params[OptionsListener::OPTION_DIRECTION]) ? $params[OptionsListener::OPTION_DIRECTION] : OptionsListener::OPTION_DIRECTION_ASC);
        $this->params[OptionsListener::OPTION_VISIBLE] = isset($params[OptionsListener::OPTION_VISIBLE]) ? $params[OptionsListener::OPTION_VISIBLE] : null;
    }

    public function executeFunction()
    {
        $query = Right::query()->take(ValueInterface::INFINITE)
            ->where('product_type_id',47)
            ->where('website_id', 0)
            ->where('active', 1)
            ->whereIn('key', $this->travelRightKeys);
        $query = $this->applyRightPermissionFilters($query);

        //Bundle rights per website before limiting
        $bundled = $this->bundleRightsPerUser($query->get(['user_id', 'website_id', 'key', 'value']));
        $limited = Collection::make($bundled)->sortBy(
            $this->params[OptionsListener::OPTION_ORDER],
            null,
            $this->params[OptionsListener::OPTION_DIRECTION] === OptionsListener::OPTION_DIRECTION_DESC
        )->slice($this->params[OptionsListener::OPTION_OFFSET], $this->params[OptionsListener::OPTION_LIMIT])->toArray();

        if (!(defined('TOTAL_HEADER_SENT') and TOTAL_HEADER_SENT)) { // Add the total count header for pagination purposes
            $total = count($bundled);
            header("X-Total-Count: " . $total);

            $range = sprintf('Content-Range: %s %d-%d/%d', 'user_rights.travel', $this->params[OptionsListener::OPTION_OFFSET], $this->params[OptionsListener::OPTION_LIMIT], $total);
            header($range);
            define('TOTAL_HEADER_SENT', true);
        }

        $this->result = array_values($limited);
    }

    private function bundleRightsPerUser($rights)
    {
        $results = [];
        foreach ($rights as $right){
            if(!isset($results[$right->user_id])){
                $results[$right->user_id] = [ResourceInterface::__ID => $right->user_id] + array_fill_keys($this->travelRightKeys, null);
            }
            $results[$right->user_id][$right->key] = $right->value;
        }

        return $results;
    }
}