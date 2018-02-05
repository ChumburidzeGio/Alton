<?php

/**
 * User: Roeland Werring
 * Date: 23/01/14
 * Time: 11:48
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Helpers\ResourceFilterHelper;
use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;

class Unitedconsumers extends DefaultProvider
{

    public function process_sms()
    {
        if(stripos($this->data->abo_minutes, 'onb.') !== FALSE)
        {
            $this->set_sms_onbeperkt();
        }
        else
        {
            parent::process_sms();
        }
    }

    public function process_internet_type()
    {
        $this->data->internet_type = '4G';
    }

    public function process_network()
    {
        $this->data->network = $this->data->provider;
    }

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.25;
    }

    public function process_price_per_sms()
    {
        $this->data->price_per_sms = 0.25;
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = 0;
    }

    public function process_bundle_strategy()
    {
        $this->data->bundle_strategy = 1;
    }

}