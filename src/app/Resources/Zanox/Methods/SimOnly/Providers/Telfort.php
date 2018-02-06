<?php

/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 23:34
 *
 */
namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;

class Telfort extends DefaultProvider
{
    public function process_price_default()
    {
        parent::process_price_default();
        if($this->data->price_actual == 0.00)
        {
            $this->data->price_actual = $this->data->price_default;
        }
    }

    public function process_bundle_strategy()
    {
        $this->data->bundle_strategy = 0;
        if($this->data->minutes > 9999 || $this->data->sms > 9999)
        {
            $this->data->bundle_strategy = -1;
        }
    }

    public function process_action_duration()
    {
        $this->data->action_duration = $this->data->time;
    }

    public function process_internet_type()
    {
        // 4G for every internet bundle - source: 22 Aug 2016 https://www.telfort.nl/sim-only/
        $this->data->internet_type = '4G';
    }

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.24;
    }

    public function process_price_per_sms()
    {
        $this->data->price_per_sms = 0.24;
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = 0.10;
    }

    public function process_data()
    {
        //        preg_match("/(\d+) MB/", $this->data->title, $matches);
        //        $this->data->data = isset($matches[1]) ? $matches[1] : 0;
        $this->data->data = $this->data->abo_data;
    }
}