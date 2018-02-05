<?php
namespace App\Resources\Travel;

use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Models\Right;

class TravelRightsAbstractRequest extends TravelWrapperAbstractRequest
{
    protected $travelRightKeys = [
        ResourceInterface::MULTISAFEPAY_API_KEY,
        ResourceInterface::MULTISAFEPAY_TEST_ENVIRONMENT,
        ResourceInterface::SKIP_PAYMENT,
        ResourceInterface::EMBED_ADMINISTRATION_COST,
        ResourceInterface::ADMINISTRATION_FEE,
        ResourceInterface::PAGESTART,
        ResourceInterface::COUNTRY_CODE,
        ResourceInterface::IS_CRM_TOOL,
        ResourceInterface::RESELLER_MAIL_NL,
        ResourceInterface::RESELLER_MAIL_DE,
        ResourceInterface::RESELLER_MAIL_FR,
        ResourceInterface::RESELLER_MAIL_EN,
    ];

    protected function updateRights($rights, $userId, $websiteId = 0)
    {
        $rights = array_only($rights, $this->travelRightKeys);

        $currentRights = Right::where('website_id', $websiteId)
            ->where('user_id', $userId)
            ->where('active', 1)
            ->where('product_type_id', 47)
            ->whereIn('key', $this->travelRightKeys)
            ->get()->keyBy('key');

        // Fetch user level rights, if needed
        $currentUserRights = $websiteId !== 0 ? $this->getRights($userId) : null;

        foreach ($rights as $rightKey => $rightValue){

            if (is_bool($rightValue)) {
                // We want to store 'false' as 0, not empty string
                $rightValue = $rightValue ? '1' : '0';
            }

            if ($rightValue === null){
                //Value is null
                //We have to delete the right if it exists
                if ($existingRight = $currentRights->get($rightKey)){
                    $existingRight->destroy($existingRight->id);
                }
            }else{
                //There is a value
                //Update or create an existing right
                $existingRight = $currentRights->get($rightKey);
                if($existingRight){
                    if ($currentUserRights && array_get($currentUserRights, $rightKey, null) == $rightValue) {
                        // Same value as user level rights: delete website level right
                        $existingRight->destroy($existingRight->id);
                    }
                    else if ($existingRight->value != $rightValue) {
                        // Update
                        $existingRight->update(['value' => $rightValue]);
                    }
                }else{
                    if ($currentUserRights && array_get($currentUserRights, $rightKey, null) == $rightValue) {
                        // Do not make a website level right, if the user level right already is this value
                        continue;
                    }

                    Right::create([
                        ResourceInterface::PRODUCT_TYPE_ID => 47,
                        ResourceInterface::ACTIVE => true,
                        ResourceInterface::USER_ID => $userId,
                        ResourceInterface::WEBSITE_ID => $websiteId,
                        ResourceInterface::KEY => $rightKey,
                        ResourceInterface::VALUE => $rightValue
                    ]);
                }
            }
        }
    }

    protected function getRights($userId, $websiteId = 0, $fallback = false)
    {
        $rights = Right::where('website_id', $websiteId)
            ->where('user_id', $userId)
            ->where('product_type_id', 47)
            ->where('active', 1)
            ->whereIn('key', $this->travelRightKeys)
            ->lists('value', 'key');

        if ($fallback && $websiteId != 0)
            return array_merge($this->getRights($userId), $rights);

        return array_merge(array_fill_keys($this->travelRightKeys, null), $rights);
    }

    public function getResult()
    {
        if (!isset($this->params[OptionsListener::OPTION_VISIBLE]))
            return parent::getResult();

        $visible = (array)$this->params[OptionsListener::OPTION_VISIBLE];

        if (isset($this->result[0])) {
            return array_map(function ($r) use ($visible) {
                return array_only($r, $visible);
            }, parent::getResult());
        }
        else {
            return array_only(parent::getResult(), $visible);
        }
    }

    protected function addFilters($query, $filters)
    {
        foreach($filters as $fieldName => $filter){
            if(is_string($filter) && strpos($filter, '%') !== false){
                $query  = $query->where($fieldName, 'like', $filter);
            }
            else if (is_scalar($filter)) {
                $query = $query->where($fieldName, '=', $filter);
            }
            else if (is_array($filter)) {
                $query = $query->whereIn($fieldName, $filter);
            }
        }
        return $query;
    }

    protected function applyWebsitePermissionFilters($query)
    {
        return $this->addFilters($query, array_get($this->params, OptionsListener::OPTION_PERMISSIONS_FILTER, []));
    }

    protected function applyUserPermissionFilters($query)
    {
        $filters = array_get($this->params, OptionsListener::OPTION_PERMISSIONS_FILTER, []);
        if (isset($filters[ResourceInterface::USER_ID])) {
            $filters[ResourceInterface::ID] = $filters[ResourceInterface::USER_ID];
            unset($filters[ResourceInterface::USER_ID]);
        }

        return $this->addFilters($query, $filters);
    }

    protected function applyRightPermissionFilters($query)
    {
        return $this->addFilters($query, array_get($this->params, OptionsListener::OPTION_PERMISSIONS_FILTER, []));
    }
}