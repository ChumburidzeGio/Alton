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

class Hollandsnieuwe extends DefaultProvider
{
    // 3G, but speed is 25? (no 4G available) - source 19 Aug 2016: https://www.hollandsnieuwe.nl/sim-only
    protected $maximumNetworkSpeeds = [
        '3G' =>  ['down' => '25', 'up' => '3.6'],
    ];

    public function process_sms()
    {
        parent::process_sms();
        $abotype = $this->data->abo_type;
        if(stristr($abotype, 'SMS'))
        {
            //extra sms bundle
            $data = explode('+', $abotype);
            $smsindex = count($data) - 1;
            if(isset($data[$smsindex]))
            {
                $this->data->sms += ResourceFilterHelper::filterExtractFloat ($data[$smsindex]);
            }
        }
    }

//    public function process_data()
//    {
//        if($this->data->abo_sms > 275)
//        {
//            $this->data->data = ResourceFilterHelper::filterConvertTerm($this->data->abo_data) + ResourceFilterHelper::filterConvertTerm($this->data->abo_sms);
//        }
//        else
//        {
//            $this->data->data = 0;
//        }
//    }

    public function process_all_in_one()
    {
        $value = 0;
        if((int) $this->data->minutes > 200)
        {
            $value = 1;
        }
        $this->data->all_in_one = $value;
    }

//    public function process_action_price()
//    {
//        $price = (string) $this->data->abo_price_standard - (string) $this->data->abo_price;
//        if($price > 0)
//        {
//            $this->data->action_price = $price;
//        }
//        else
//        {
//            $this->data->action_price = 0;
//        }
//    }

    public function process_call_limit()
    {
        $this->data->call_limit = 1;
    }

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = $this->get_price_per();
    }

    public function process_price_per_sms()
    {
        $this->data->price_per_sms = $this->get_price_per();
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = $this->get_price_per();
    }

    private function get_price_per()
    {
        switch($this->data->minutes)
        {
            case '75' :
                return '0.06';
            case '175' :
                return '0.04';
            case '275' :
                return '0.038';
            case '550' :
                return '0.034';
            default:
                return '0.034';
        }
    }
}