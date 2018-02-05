<?php
/**
 * User: Roeland Werring
 * Date: 23/01/14
 * Time: 11:53
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;

class Kpn extends DefaultProvider
{
    protected $has4Gplus = true;

    public function process_all_in_one()
    {
        $value = 0;
        if(stripos($this->data->abo_type, 'budget') !== false){
            $value = 1;
        }
        $this->data->all_in_one = $value;
    }

    public function process_internet_type()
    {
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

    public function process_price_initial()
    {
        parent::process_price_initial();
        if($this->data->price_initial == 0.00){
            $this->data->price_initial = 19.95;
        }
    }
}