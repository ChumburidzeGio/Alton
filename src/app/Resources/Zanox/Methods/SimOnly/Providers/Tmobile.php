<?php
/**
 * User: Roeland Werring
 * Date: 27/01/14
 * Time: 21:57
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;
use Komparu\Value\ValueInterface;


class Tmobile extends DefaultProvider
{
    // 4G for all 'custom' abbos - source: 19 Aug 2016 https://www.t-mobile.nl/mobiel-abonnement/sim-only
    protected $defaultMaximumNetworkSpeeds = [
        '4G' =>  ['down' => '120',  'up' => '25'],
    ];


    public function is_excluded()
    {
        if(str_contains($this->data->description2,'Sim Only 3-in-1 simkaart icm Stel Samen')){
            return true;
        }
        return false;
    }

    public function process_action_duration()
    {
        if((string) $this->data->abo_price < (string) $this->data->abo_price_standard)
        {
            $this->data->action_duration = $this->data->time;
        }
        else
        {
            $this->data->action_duration = 0;
        }
    }

    public function process_internet_type()
    {
        $this->data->internet_type = '4G';
    }

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.35;
    }

    public function process_price_per_sms()
    {
        $this->data->price_per_sms = 0.35;
    }


    public function process_speed()
    {
        if (str_contains('Basis Sim', (string)$this->data->title))
        {
            // Is 4G, but limited speed
            // Source: https://www.t-mobile.nl/mobiel-abonnement/sim-only/basis-sim

            $this->data->speed_download = '15';
            $this->data->speed_upload = '2';
        }
        else
        {
            parent::process_speed();
        }
    }

    public function process_title()
    {
        $this->data->title = $this->data->description2;
    }
}