<?php
/**
 * User: Roeland Werring
 * Date: 23/01/14
 * Time: 11:49
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;

class Mtv extends DefaultProvider
{
    public function process_sms()
    {
        $this->set_sms_onbeperkt();
    }
    
    public function process_bundle_strategy()
    {
        $this->data->bundle_strategy = -1;
    }
    
    public function process_action_duration()
    {
        $this->data->action_duration = $this->data->time;
    }

    public function process_internet_type()
    {
        $this->data->internet_type = '4G';
    }
    
    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.3;
    }
}