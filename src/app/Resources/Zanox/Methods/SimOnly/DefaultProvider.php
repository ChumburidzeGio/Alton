<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 23:36
 *
 */

namespace App\Resources\Zanox\Methods\SimOnly;


use App\Helpers\ResourceFilterHelper;
use Komparu\Value\ValueInterface;

class DefaultProvider
{
    protected $colls = array(
        'provider_name',
        'network',
        'url',
        'title',
        'minutes',
        'data',
        'sms',
        'time',
        'price_actual',
        'price_initial',
        'price_default',
        'price_per_minute',
        'price_per_sms',
        'price_per_data',
        'speed_download',
        'speed_upload',
        'action_duration',
        'call_limit',
        'all_in_one',
        'bundle_strategy',
        'internet_type',
        'additional'
    );

    // Theoretical maximum speeds, with device capable of network technology
    protected $maximumNetworkSpeeds = [
        '3G' =>  ['down' => '14.4', 'up' => '2'],
        '4G' =>  ['down' => '150',  'up' => '30'],
        '4G+' => ['down' => '225',  'up' => '50'],
    ];
    protected $has4Gplus = false;


    protected $data;

    public function __construct()
    {
    }


    public function set_data($data)
    {
        $this->data = $data;
    }

    public function get_data()
    {
        $retdata = array();
        foreach($this->colls as $col){
            $retdata[$col] = $this->data->{$col} . "";
        }
        return $retdata;
    }

    public function process_defaults()
    {
    }

    // geen blackberry abbos
    public function is_excluded()
    {
        if($this->data->abo_time == '' || $this->data->abo_time == '0' || $this->data->abo_price == '0.00' || stripos($this->data->title, 'Blackberry') !== false){
            return true;
        }
        return false;
    }

    public function is_excluded_after_process()
    {
        return false;
    }


    protected function set_sms_onbeperkt()
    {
        $this->data->sms = ValueInterface::INFINITE;
    }

    protected function set_minutes_onbeperkt()
    {
        $this->data->minutes = ValueInterface::INFINITE;
    }

    public function process_title()
    {
        $this->data->title = str_ireplace($this->data->provider . ' ', '', $this->data->title);

        //        if ($this->data->title == '') {
        //            $this->data->title = $this->data->abo_type;
        //        }
        //        $title = str_replace("'", '-', $this->data->title);
        //        $title = str_replace(",", '.', $title);

        //        $title = str_ireplace('acquisitie ', '', $title);
        //        $title = preg_replace('/[^\s\-\/\.a-zA-Z0-9]/', ' ', $title);
        //        $title = preg_replace('/[\s]{2,}/', ' ', $title);
        //        $this->data->title = trim($title, " /-\t\n\r\0\x0B");
    }

    public function process_price_actual()
    {
        if(!empty($this->data->abo_price)){
            $this->data->price_actual = ResourceFilterHelper::filterExtractFloat($this->data->abo_price);
        }
        else{
            $this->data->price_actual = ResourceFilterHelper::filterExtractFloat($this->data->abo_price_standard);
        }
    }

    public function process_price_initial()
    {
        $this->data->price_initial = ResourceFilterHelper::filterExtractFloat($this->data->price_initial);
    }

    public function process_time()
    {
        $this->data->time = ResourceFilterHelper::filterConvertTerm($this->data->abo_time);
    }

    public function process_sms()
    {
        $this->data->sms = ResourceFilterHelper::filterConvertTerm($this->data->abo_sms);
        if($this->data->sms >= 9999){
            $this->data->sms = ValueInterface::INFINITE;
        }
    }

    public function process_minutes()
    {
        $this->data->minutes = ResourceFilterHelper::filterConvertTerm($this->data->abo_minutes);
        if($this->data->minutes >= 9999){
            $this->data->minutes = ValueInterface::INFINITE;
        }

    }

    public function process_data()
    {
        $data = ResourceFilterHelper::filterConvertTerm($this->data->abo_data);

        // Make MB's from GB's
        if($data > 0 && $data < 100)
        {
            $data = $data * 1000;
        }
        $this->data->data = $data;
    }

    public function process_network()
    {
    }

    public function process_all_in_one()
    {
        $this->data->all_in_one = 0;
    }

    public function process_bundle_strategy()
    {
        $this->data->bundle_strategy = 0;
        if($this->data->minutes > 9999 || $this->data->sms > 9999){
            $this->data->bundle_strategy = - 1;
        }
    }

    public function process_additional()
    {
        $this->data->additional = 'buitenland, servicenummers';
    }

    public function process_price_default()
    {
        if(empty($this->data->abo_price_standard) || (string) $this->data->abo_price_standard < 1){
            $this->data->price_default = $this->data->price;
        }else{
            $this->data->price_default = $this->data->abo_price_standard;
        }
    }

    public function process_action_duration()
    {
        $action_duration = ResourceFilterHelper::filterConvertTerm($this->data->action_duration);
        if(empty($action_duration) || $action_duration < 1){
            $this->data->action_duration = 0;
        }else{
            $this->data->action_duration = $action_duration;
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

    public function process_price_per_minute()
    {
        $this->data->price_per_minute = 0;
    }

    public function process_price_per_sms()
    {
        $this->data->price_per_sms = 0;
    }

    public function process_price_per_data()
    {
        $this->data->price_per_data = 0;
    }

    public function process_speed()
    {
        $internetType = (string)$this->data->internet_type;
        if ($internetType == '4G' && $this->has4Gplus)
            $internetType = '4G+';

        if (isset($this->maximumNetworkSpeeds[$internetType]))
        {
            $this->data->speed_download = $this->maximumNetworkSpeeds[$internetType]['down'];
            $this->data->speed_upload   = $this->maximumNetworkSpeeds[$internetType]['up'];
        }
        else
        {
            // Unknown speeds
            $this->data->speed_download = 0;
            $this->data->speed_upload = 0;
        }
    }

    public function process_call_limit()
    {
        $this->data->call_limit = 0;
    }

    /**
     * Wanneer onbeperkt, is de prijs buiten de bundle altijd nul
     */
    public function process_fix_price_outside_bundle()
    {
        if($this->data->minutes == ValueInterface::INFINITE){
            $this->data->price_per_minute = 0.0;
        }
        if($this->data->sms == ValueInterface::INFINITE){
            $this->data->price_per_sms = 0.0;
        }
    }

    /**
     * Wanneer of minutes of sms = 0, bundle_strategy = 2 (Tweakers 28-3-2014)
     */
    public function process_fix_bundle_strategy()
    {
        if((($this->data->minutes == 0) && ($this->data->sms != 0)) || (($this->data->minutes != 0) && ($this->data->sms == 0))){
            $this->data->bundle_strategy = 2;
        }
    }

    public function process_provider_name()
    {
        $this->data->provider_name = $this->data->provider;
    }

    //
}
