<?php

namespace App\Resources\Travel\Methods;


use App\Helpers\ResourceHelper;
use App\Listeners\Resources2\OptionsListener;
use App\Listeners\Resources2\RestListener;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class IndexProductListing extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $this->params[OptionsListener::OPTION_NO_PROPAGATION] = 1;
        $this->params[OptionsListener::OPTION_FORCE_LIMIT] = 1;
        unset($this->params[OptionsListener::OPTION_PERMISSIONS_APPLIED]);
        $products = ResourceHelper::callResource2('product.travel', $this->params, RestListener::ACTION_INDEX);

        $this->result = $products;
    }
}