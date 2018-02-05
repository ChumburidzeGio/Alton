<?php

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;

class Ziggo extends DefaultProvider
{
    // 4G standard - source: 22 Aug 2016 https://www.ziggo.nl/klantenservice/mobiel/netwerk/
    protected $has4Gplus = true;

    public function process_provider()
    {
        $this->data->provider = 'Ziggo';
    }

    public function process_internet_type()
    {
        $this->data->internet_type = '4G';
    }

    public function process_price_per_sms()
    {
        $this->data->price_per_sms = 0.2;
    }

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.2;
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = 0.25;
    }

}