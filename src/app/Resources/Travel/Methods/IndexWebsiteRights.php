<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Models\Right;
use App\Resources\Travel\TravelRightsAbstractRequest;
use Illuminate\Support\Collection;
use Komparu\Value\ValueInterface;

class IndexWebsiteRights extends TravelRightsAbstractRequest
{
    public function setParams(Array $params)
    {
        $this->params = $params;
        $this->params[ResourceInterface::USER_ID] = isset($params[ResourceInterface::USER_ID]) ? $params[ResourceInterface::USER_ID] : null;
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
            ->where('website_id', '!=', 0)
            ->where('active', 1)
            ->whereIn('key', $this->travelRightKeys);
        $query = $this->applyRightPermissionFilters($query);
        if (isset($this->params[ResourceInterface::__ID]) && is_scalar($this->params[ResourceInterface::__ID]))
            $query->where('website_id', '=', $this->params[ResourceInterface::__ID]);
        if (isset($this->params[ResourceInterface::__ID]) && is_array($this->params[ResourceInterface::__ID]))
            $query->whereIn('website_id', $this->params[ResourceInterface::__ID]);

        if (isset($this->params[ResourceInterface::USER_ID]))
            $query->where('user_id', $this->params[ResourceInterface::USER_ID]);

        $userQuery = Right::query()->take(ValueInterface::INFINITE)
            ->where('product_type_id',47)
            ->where('website_id', '=', 0)
            ->where('active', 1)
            ->whereIn('key', $this->travelRightKeys);
        if (isset($this->params[ResourceInterface::USER_ID]))
            $userQuery->where('user_id', $this->params[ResourceInterface::USER_ID]);

        $userRights = $this->bundleRightsPerUser($userQuery->get());

        //Bundle rights per website before limiting
        $bundled = $this->bundleRightsPerWebsite($query->get()->toArray(), $userRights);
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

    private function bundleRightsPerWebsite($rights, $userRights)
    {
        $results = [];
        foreach ($rights as $right){
            if(!isset($results[$right->website_id])){
                $results[$right->website_id] = [ResourceInterface::__ID => $right->website_id, ResourceInterface::USER_ID => $right->user_id]
                    + array_merge(array_fill_keys($this->travelRightKeys, null), array_get($userRights, $right->user_id, []));
            }
            $results[$right->website_id][$right->key] = $right->value;
        }

        return $results;
    }
}