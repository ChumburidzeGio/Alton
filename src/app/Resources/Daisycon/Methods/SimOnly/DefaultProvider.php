<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 23:36
 *
 */

namespace App\Resources\Daisycon\Methods\SimOnly;


use App\Interfaces\ResourceInterface;
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


    protected $data;
    protected $inputData;

    public function __construct()
    {
    }


    public function set_data($data, $uid)
    {
        $this->inputData                            = $data['product_info'];
        $this->data[ResourceInterface::PROVIDER_ID] = $uid;
        $this->data[ResourceInterface::URL] = $this->inputData['link'];
        //recordHash
        $this->data[ResourceInterface::RESOURCE_ID]   = 'daisycon_' . $uid . '_' . ($data['update_info']['daisycon_unique_id']);
        $this->data[ResourceInterface::RESOURCE_NAME] = 'daisycon';

    }

    public function get_data()
    {
        return $this->data;
    }

    public function process_defaults()
    {
    }

    public function is_excluded()
    {
        if($this->inputData['price'] == '' || $this->inputData['price'] == '0' || $this->inputData['price'] == '0.00'){
            return true;
        }
        return false;
    }

    public function is_excluded_after_process()
    {
        return false;
    }


    public function process_title()
    {
        $this->data[ResourceInterface::TITLE] = $this->inputData['title'];
    }

    public function process_price_actual()
    {
        $this->data[ResourceInterface::PRICE_ACTUAL] = $this->inputData['price'];
    }

    public function process_price_initial()
    {
        $this->data[ResourceInterface::PRICE_INITIAL] = $this->inputData['subscription_installation_costs'];
    }

    public function process_time()
    {
        $this->data[ResourceInterface::TIME] = $this->inputData['subscription_duration'];
    }

    public function process_sms()
    {
        $this->data[ResourceInterface::SMS] = $this->inputData['subscription_allowance_sms'];
        if($this->data[ResourceInterface::SMS] >= 9999){
            $this->data[ResourceInterface::SMS] = ValueInterface::INFINITE;
        }
    }

    public function process_minutes()
    {
        $this->data[ResourceInterface::MINUTES] = $this->inputData['subscription_allowance_minutes'];
        if($this->data[ResourceInterface::MINUTES] >= 9999){
            $this->data[ResourceInterface::MINUTES] = ValueInterface::INFINITE;
        }

    }

    public function process_data()
    {
        $this->data[ResourceInterface::DATA] = $this->inputData['subscription_allowance_data'];
    }

    public function process_network()
    {
        $this->data[ResourceInterface::NETWORK] = $this->inputData['network'];
    }

    public function process_all_in_one()
    {
        $this->data[ResourceInterface::ALL_IN_ONE] = 0;
    }

    public function process_bundle_strategy()
    {
        $this->data[ResourceInterface::BUNDLE_STRATEGY] = 0;
        if($this->data[ResourceInterface::MINUTES] > 9999 || $this->data[ResourceInterface::SMS] > 9999){
            $this->data[ResourceInterface::BUNDLE_STRATEGY] = - 1;
        }
    }

    public function process_additional()
    {
        $this->data[ResourceInterface::ADDITIONAL] = 'buitenland, servicenummers';
    }

    public function process_price_default()
    {
        $this->data[ResourceInterface::PRICE_DEFAULT] = $this->inputData['price'];
    }

    public function process_action_duration()
    {
        $this->data[ResourceInterface::ACTION_DURATION] = 0;
    }

    public function process_internet_type()
    {
        $this->data[ResourceInterface::INTERNET_TYPE] = ($this->inputData['has_subscription_4g'] == "true") ? "4G" : "3G";
    }

    public function process_price_per_minute()
    {
        $this->data[ResourceInterface::PRICE_PER_MINUTE] = 0;
    }

    public function process_price_per_sms()
    {
        $this->data[ResourceInterface::PRICE_PER_SMS] = 0;
    }

    public function process_price_per_data()
    {
        $this->data[ResourceInterface::PRICE_PER_DATA] = 0;
    }

    public function process_speed()
    {
        $this->data[ResourceInterface::SPEED_DOWNLOAD] = $this->inputData['bandwidth_download'];
        $this->data[ResourceInterface::SPEED_UPLOAD]   = 0; // Overload per provider
    }

    public function process_call_limit()
    {
        $this->data[ResourceInterface::CALL_LIMIT] = 0;
    }

    /**
     * Wanneer onbeperkt, is de prijs buiten de bundle altijd nul
     */
    public function process_fix_price_outside_bundle()
    {
        if($this->data[ResourceInterface::MINUTES] == ValueInterface::INFINITE){
            $this->data[ResourceInterface::PRICE_PER_MINUTE] = 0.0;
        }
        if($this->data[ResourceInterface::SMS] == ValueInterface::INFINITE){
            $this->data[ResourceInterface::PRICE_PER_SMS] = 0.0;
        }
    }

    /**
     * Wanneer of minutes of sms = 0, bundle_strategy = 2 (Tweakers 28-3-2014)
     */
    public function process_fix_bundle_strategy()
    {
        if((($this->data[ResourceInterface::MINUTES] == 0) && ($this->data[ResourceInterface::SMS] != 0)) || (($this->data[ResourceInterface::MINUTES] != 0) && ($this->data[ResourceInterface::SMS] == 0))){
            $this->data[ResourceInterface::BUNDLE_STRATEGY] = 2;
        }
    }

    public function process_provider_name()
    {
        $this->data[ResourceInterface::PROVIDER_NAME] = $this->inputData['provider'];
    }


}
