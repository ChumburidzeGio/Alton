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

class Vodafone extends DefaultProvider{

    // 4G+ for all - source: 22 Aug 2016 https://www.vodafone.nl/4g/
    protected $has4Gplus = true;

    public function process_price_actual()
    {
        if(stripos($this->data->title, 'Smart 500') !== FALSE)
        {
            $this->data->price_actual = $this->data->abo_price - 1;
        }
    }

    public function process_data() {
        $this->data->data = ResourceFilterHelper::filterParseGB($this->data->abo_data);
        if ($this->data->abo_data > 0 && $this->data->abo_data < 10) {
            $this->data->abo_data = $this->data->abo_data * 1000;
        }
    }

    public function process_speed()
    {
        parent::process_speed();

        if(stripos($this->data->title, 'scherp') !== FALSE)
        {
            // 22 Aug 2016:
            // Source: https://www.vodafone.nl/support/abonnementen/alle-tarieven-en-voorwaarden.shtml
            // Source: https://www.vodafone.nl/_assets/downloads/tarieven/tarievenoverzicht_vodafone_scherp_4_augustus_2014.pdf
            $this->data->speed_download = '43.2';
            $this->data->speed_upload = '5.2';
        }
    }

    public function process_bundle_strategy()
    {
        $value = 1;
        if(stripos($this->data->title, 'scherp') === FALSE)
        {
            $value = 0;
        }
        $this->data->bundle_strategy = $value;
        if($this->data->minutes > 9999)
        {
            $this->data->minutes = 100000;
            $this->data->bundle_strategy = -1;
        }
        if ( $this->data->sms > 9999) {
            $this->data->sms = 100000;
            $this->data->bundle_strategy = -1;
        }

    }

    public function process_internet_type()
    {
        $this->data->internet_type = '4G';
        if(stripos($this->data->title, 'scherp') !== FALSE)
        {
            $this->data->internet_type = '3G';
        }
    }

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.25;
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = 0.02;
    }

    public function process_minutes()
    {
        $this->data->minutes = ResourceFilterHelper::filterConvertTerm($this->data->abo_minutes);
    }
}