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

class Tele2 extends DefaultProvider
{
    protected $has4Gplus = true;

    public function process_sms()
    {
        if(stripos($this->data->abo_sms, 'Onbeperkt') !== FALSE)
        {
            $this->set_sms_onbeperkt();
        }
        else
        {
            parent::process_sms();
        }
    }

    public function process_price_actual()
    {
        $this->data->price_actual = ResourceFilterHelper::filterExtractFloat($this->data->abo_price_standard);
    }

    public function process_internet_type()
    {
        // 4G+ for all - source: 22 Aug 2016 https://www.tele2.nl/mobiel/sim-only/
        $this->data->internet_type = '4G';
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