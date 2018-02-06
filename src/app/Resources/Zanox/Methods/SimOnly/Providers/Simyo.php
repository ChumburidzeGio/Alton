<?php

/**
 * User: Roeland Werring
 * Date: 23/01/14
 * Time: 11:51
 *
 */
namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;


class Simyo extends DefaultProvider
{
    // Slower 4G speeds with simyo - source 22 Aug 2016 https://www.simyo.nl/mobiele-data-snelheid
    protected $maximumNetworkSpeeds = [
        '3G' =>  ['down' => '14.4', 'up' => '2'],
        '4G' =>  ['down' => '25',  'up' => '10'],
    ];

    public function is_excluded()
    {
        if($this->data->abo_time == '' || $this->data->abo_time == '0' || $this->data->abo_price == '0.00' || stripos($this->data->title, 'Blackberry') !== FALSE)
        {
            return TRUE;
        }
        if($this->data->abo_minutes == '')
        {
            return TRUE;
        }
        return FALSE;
    }

    public function process_sms()
    {
        if(stripos($this->data->abo_type, 'SMS onbeperkt') !== FALSE)
        {
            $this->set_sms_onbeperkt();
        }
        else
        {
            parent::process_sms();
        }
    }


    public function process_action_duration()
    {
        $this->data->action_duration = 0;
    }

    // 4g kost bij simyo 5 euro !!
    public function process_internet_type()
    {
        $this->data->internet_type = '3G';
        if(stripos($this->data->abo_internet, '4g') !== FALSE)
        {
            $this->data->internet_type = '4G';
        }
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

    public function process_bundle_strategy()
    {
        parent::process_bundle_strategy();
        if ($this->data->bundle_strategy == 0) {
            $this->data->bundle_strategy = 1;
        }
    }

    public function process_title()
    {
        $this->data->title = str_ireplace($this->data->provider . ' ', '', $this->data->title);
        $this->data->title = str_ireplace('(', '', $this->data->title);
        $this->data->title = str_ireplace(')', '', $this->data->title);
        $this->data->title = str_ireplace('|', '-', $this->data->title);
        $this->data->title = trim(str_ireplace('- flexibel', '', $this->data->title));
    }

}