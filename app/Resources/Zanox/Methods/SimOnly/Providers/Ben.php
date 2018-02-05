<?php
/**
 * User: Roeland Werring
 * Date: 23/01/14
 * Time: 11:52
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Helpers\ResourceFilterHelper;
use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;


class Ben extends DefaultProvider
{
    //  3G default, 4G (75 Mbit) - source: 22 Aug 2016 https://www.ben.nl/sim-only
    protected $maximumNetworkSpeeds = [
        '3G' =>  ['down' => '14.4',  'up' => '2'],
        '4G' =>  ['down' => '75',  'up' => '15'],
    ];


    public function process_sms()
    {
        $this->data->sms = $this->data->minutes;
    }

    public function process_bundle_strategy()
    {
        $this->data->bundle_strategy = 1;
    }


    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.31;
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = 0.1;
    }

    public function process_data()
    {
        if(stripos($this->data->abo_internet, 'sms') !== false){
            $this->data->data = 0;
        }else{
            $this->data->data = ResourceFilterHelper::filterConvertTerm($this->data->abo_data);
        }
    }

    public function process_internet_type()
    {
        $this->data->internet_type = '3G';
        if(stripos($this->data->abo_internet, '4g') !== FALSE)
        {
            $this->data->internet_type = '4G';
        }
    }
}
