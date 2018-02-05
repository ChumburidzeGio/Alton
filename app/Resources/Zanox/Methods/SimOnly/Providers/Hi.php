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


class Hi extends DefaultProvider
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
        if(stripos($this->data->additional, '4g') !== FALSE)
        {
            $this->data->internet_type = '4G';
        }
    }
    
    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.3;
    }
}