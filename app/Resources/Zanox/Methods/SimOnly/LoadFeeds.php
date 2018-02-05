<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 21:39
 * 
 */

namespace App\Resources\Zanox\Methods\SimOnly;


use App\Resources\Zanox\Methods\AbstractLoadFeeds;

class LoadFeeds extends AbstractLoadFeeds
{
    protected $productType = 'simonly1';
    protected $processFields = array(
        'defaults',
        'provider_name',
        'price_actual',
        'price_initial',
        'time',
        'minutes',
        'sms',
        'data',
        'network',
        'all_in_one',
        'bundle_strategy',
        'additional',
        'price_default',
        'action_duration',
        'internet_type',
        'price_per_minute',
        'price_per_sms',
        'price_per_data',
        'speed',
        'call_limit',
        'title',
        'fix_price_outside_bundle',
        'fix_bundle_strategy'
    );

    protected $classUrl = '';

    public function __construct() {
        parent::__construct();
        $this->classUrl = __NAMESPACE__;

    }

}
