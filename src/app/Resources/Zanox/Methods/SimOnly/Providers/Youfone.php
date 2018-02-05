<?php
/**
 * User: Roeland Werring
 * Date: 23/01/14
 * Time: 11:51
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly\Providers;

use App\Helpers\ResourceFilterHelper;
use App\Resources\Zanox\Methods\SimOnly\DefaultProvider;
use Komparu\Value\ValueInterface;

class Youfone extends DefaultProvider
{
    // All 4G is 4G+ - source: 22 Aug 2016 https://www.youfone.nl/info/sim-only-4g
    protected $has4Gplus = true;

    public function process_sms()
    {
        if (stripos($this->data->title, 'Onbeperkt Sms') !== FALSE) {
            $this->data->sms = ValueInterface::INFINITE;
            return;
        }
        $this->data->sms = ResourceFilterHelper::filterConvertTerm($this->data->abo_minutes);
        if ($this->data->sms >= 9999) {
            $this->data->sms = ValueInterface::INFINITE;
        }
    }

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0.3;
        if (stripos($this->data->title, 'eindeloos') !== FALSE) {
            $this->data->price_per_minute = 0.1;
        }
    }

    public function process_price_per_sms()
    {
        $this->data->price_per_sms = 0.3;
        if (stripos($this->data->title, 'eindeloos') !== FALSE) {
            $this->data->price_per_sms = 0.1;
        }
    }

    public function process_bundle_strategy()
    {
        parent::process_bundle_strategy();
        if ($this->data->bundle_strategy == 0 && $this->data->sms != 0) {
            $this->data->bundle_strategy = 1;
        }
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = 0.0;
    }

    public function process_internet_type()
    {
        if (stripos($this->data->title, '4G') !== FALSE) {
            $this->data->internet_type = '4G';
            return;
        }
        $this->data->internet_type = '3G';
    }

    public function process_title()
    {
        parent::process_title();
        $this->data->title = str_ireplace('+', '-', $this->data->title);
    }
}