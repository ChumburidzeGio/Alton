<?php
/**
 * User: Roeland Werring
 * Date: 23/01/14
 * Time: 11:53
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Helpers\ResourceFilterHelper;
use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;

class Upc extends DefaultProvider
{
    public function process_sms()
    {
        $this->set_sms_onbeperkt();
    }

    public function process_data() {
        $this->data->data = ResourceFilterHelper::filterParseGB($this->data->abo_data);
    }
    
    public function process_bundle_strategy()
    {
        $this->data->bundle_strategy = -1;
    }
    
    public function process_internet_type()
    {
        $this->data->internet_type = '3G';
    }
    
    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.2;
    }
    
    public function is_excluded()
    {
        return FALSE;
    }
    
    public function process_price_actual()
    {
        $this->data->price_actual = ResourceFilterHelper::filterExtractFloat($this->data->abo_price_standard);
    }
}