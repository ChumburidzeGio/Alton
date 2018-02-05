<?php
/**
 * User: Roeland Werring
 * Date: 27/01/14
 * Time: 22:35
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;

// Verfied 22 Aug 2016: 3G for all sim-only - source: https://www.simpel.nl/alles-over-mobiel-internet
class Simpel extends DefaultProvider
{
    public function process_bundle_strategy()
    {
        $this->data->bundle_strategy = 1;
        if($this->data->minutes > 9999 || $this->data->sms > 9999)
        {
            $this->data->bundle_strategy = -1;
        }
    }

    public function process_minutes()
    {
        if(stripos($this->data->abo_minutes, 'Gratis bellen') !== FALSE)
        {
            $this->set_minutes_onbeperkt();
        }
        else
        {
            parent::process_minutes();
        }
    }

    public function process_action_duration()
    {
        $this->data->action_duration = 0;
    }

    public function process_call_limit()
    {
        $this->data->call_limit = 1;
    }

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.31;
    }

    public function process_price_per_sms()
    {
        $this->data->price_per_sms = 0.31;
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = 0.15;
    }
}