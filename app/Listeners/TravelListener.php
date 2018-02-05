<?php

namespace App\Listeners\Resources2;

use Agent;
use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Models\Right;
use App\Models\Website;
use App\Resources\Travel\TravelWrapperAbstractRequest;
use ArrayObject;
use Carbon\Carbon;
use DateTime;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Request;
use Komparu\Document\ArrayHelper;
use Komparu\Document\Contract\Response;
use Komparu\Value\ValueInterface;

class TravelListener
{
    static protected $optionsOrder = [
        6, // Sleutel behouden
        4, // Overdekt
        1, // Wassen
        12, // Electrisch laden
    ];

    protected $processedServices = [];
    protected $serviceIds = [];
    protected $services = null;

    const MEAN_RADIUS_EARTH_KM = 6731;

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.providers_settings.travel.process.input', [$this, 'setEnabledWhenActive']);
        $events->listen('resource.product.travel.process.input', [$this, 'filterServiceInput']);
        $events->listen('resource.product.travel.process.input', [$this, 'filterDestinationRadius']);
        $events->listen('resource.product.travel.process.input', [$this, 'mapAvailableOptionsToOptionIds']);
        $events->listen('resource.product.travel.process.input', [$this, 'addInrixTimeout']);
        $events->listen('resource.websites.travel.process.input', [$this, 'addWebsitePropertyTypeFilter']);

        $events->listen('resource.product.travel.process.input', [$this, 'addProviderCondition']);
        $events->listen('resource.product.travel.process.input', [$this, 'addUserAndEnabledCondition']);
        $events->listen('resource.product_settings.travel.process.input', [$this, 'addUserAndEnabledCondition']);
        $events->listen('resource.providers.travel.process.input', [$this, 'addUserAndEnabledCondition']);
        $events->listen('resource.providers_settings.travel.process.input', [$this, 'addUserAndEnabledCondition']);
        $events->listen('resource.order.travel.process.input', [$this, 'addUserCondition']);
        $events->listen('resource.cancel_order.travel.process.input', [$this, 'addUserCondition']);
        $events->listen('resource.order.travel.process.input', [$this, 'licenseplateArray']);

        $events->listen('resource.product.travel.process.input', [$this, 'filterProductBySettings']);
        $events->listen('resource.providers.travel.process.input', [$this, 'filterProviderBySettings']);

        $events->listen('resource.product.travel.limit.before', [$this, 'filterOutZeroPriceProducts']);
        $events->listen('resource.product.travel.limit.before', [$this, 'filterUnavailableProducts']);
        $events->listen('resource.collection.product.travel.before', [$this, 'removeTaxiWhenNoOrigin']);

        $events->listen('resource.product.travel.process.after', [$this, 'orderByType'], - 1);

        $events->listen('resource.product.travel.limit.before', [$this, 'addAdministrationFees']);

        $events->listen('resource.product.travel.limit.before', [$this, 'enrichParkingsWithRealDistances']);
        $events->listen('resource.product.travel.limit.before', [$this, 'updateResellerActive']);
        $events->listen('resource.product.travel.limit.before', [$this, 'translatableOptions']);
        $events->listen('resource.product.travel.limit.before', [$this, 'multipleCars']);
        $events->listen('resource.product.travel.limit.before', [$this, 'filterProviderId']);
        $events->listen('resource.product.travel.limit.before', [$this, 'addRemoteOptionPrices']);

        $events->listen('resource.collection.product.travel.before', [$this, 'getProductOptions']);
        $events->listen('resource.collection.product.travel.before', [$this, 'mapRemoteOptionIds']);

        $events->listen('resource.product.travel.row.after', [$this, 'populateSettings']);

        $events->listen('resource.product.travel.collection.after', [$this, 'addSettings']);
        $events->listen('resource.providers.travel.collection.after', [$this, 'addSettings']);

        $events->listen('resource.product.travel.row.after', [$this, 'addSettings']);
        $events->listen('resource.providers.travel.row.after', [$this, 'addSettings']);

        $events->listen('resource.product.travel.collection.after', [$this, 'enrichProductOptions']);
        $events->listen('resource.product.travel.collection.after', [$this, 'calculatePriceInitialAndPriceOptions']);
        $events->listen('resource.contract.travel.process.input', [$this, 'validateLicenseplateUnknown']);
        $events->listen('resource.contract.travel.process.input', [$this, 'licenseplateArray']);
        $events->listen('resource.contract.travel.process.input', [$this, 'addContractDirectFields']);

        $events->listen('email.travel.process.text', [$this, 'processEmailText']);

        $events->listen('resource.product.travel.process.input', [$this, 'mergeDateTimeInputs']);
        $events->listen('resource.contract.travel.process.input', [$this, 'mergeDateTimeInputs']);
        $events->listen('resource.order.travel.process.input', [$this, 'mergeDateTimeInputs']);
        $events->listen('resource.services.travel.process.input', [$this, 'orderServicesInput']);

        $events->listen('resource.order.travel.process.before', [$this, 'mergeDateTimeOutput']);
        $events->listen('resource.order.travel.process.after', [$this, 'splitDateTimeOutput']);
        $events->listen('resource.order.travel.process.after', [$this, 'splitLicenseplate']);
        $events->listen('resource.order.travel.process.after', [$this, 'correctTimeFormat']);
        $events->listen('resource.order.travel.process.after', [$this, 'addPdfLink']);


        $events->listen('resource.global.handle_email.process.after', [$this, 'translateEmailFields'], 10);


        $events->listen('resource.resend_email.travel.process.after', [$this, 'resendEmail']);
        $events->listen('resource.product.travel.process.after.store', [$this, 'createProductSettingsTravel']);
        $events->listen('resource.product.travel.process.after.update', [$this, 'updateProductSettingsTravel']);
        $events->listen('resource.providers.travel.process.after.store', [$this, 'createProviderSettingsTravel']);

        $events->listen('resource.product.travel.process.input', [$this, 'autoLatLong']);


        $events->listen('resource.contract.travel.process.input', [$this, 'convertContractDatesToUTC']);
        $events->listen('resource.product.travel.process.input', [$this, 'autoGenerateId']);

        $events->listen('resource.websites.travel.process.input', [$this, 'addConstantCreateInput']);
        $events->listen('resource.websites.travel.process.input', [$this, 'filterCrmOnlyWebsites']);
        $events->listen('resource.websites.travel.process.after', [$this, 'addCrmOnlyHash']);


        $events->listen('resource.product.travel.process.input', [$this, 'validateArrivalDepartureDates']);
        $events->listen('resource.order.travel.process.input', [$this, 'validateArrivalDepartureDates']);
        $events->listen('resource.product.travel.process.input', [$this, 'validateWebsiteInput']);
        $events->listen('resource.order.travel.process.input', [$this, 'validateWebsiteInput']);

        $events->listen('resource.paymentmethods.payment.multisafepay.process.input', [$this, 'addWebsiteCountryCode']);

        // Notification listeners
        $events->listen('resource.process.after.store', [$this, 'notifyTravelChange']);
        $events->listen('resource.process.after.update', [$this, 'notifyTravelChange']);
        $events->listen('resource.process.after.destroy', [$this, 'notifyTravelChange']);
    }

    public function correctTimeFormat(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        // For multiple row outputs
        foreach($output as $k => $out){
            if(isset($out[ResourceInterface::CREATED_AT])){
                $date = new DateTime($out[ResourceInterface::CREATED_AT]);
                if ($date)
                    $output[$k][ResourceInterface::CREATED_AT] = $date->format('c');
            }
            if(isset($out[ResourceInterface::UPDATED_AT])){
                $date = new DateTime($out[ResourceInterface::UPDATED_AT]);
                if ($date)
                    $output[$k][ResourceInterface::UPDATED_AT] = $date->format('c');
            }
        }

        // For single row output
        if(isset($output[ResourceInterface::CREATED_AT])){
            $date = new DateTime($output[ResourceInterface::CREATED_AT]);
            if ($date)
                $output[ResourceInterface::CREATED_AT] = $date->format('c');
        }
        if(isset($output[ResourceInterface::UPDATED_AT])){
            $date = new DateTime($output[ResourceInterface::UPDATED_AT]);
            if ($date)
                $output[ResourceInterface::UPDATED_AT] = $date->format('c');
        }
    }

    public function addPdfLink(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        switch(App::environment()){
            case 'local':
                $urlPrefix = 'http://code.komparu.dev/pdf/travel/';
                break;
            case 'test':
                $urlPrefix = 'http://code.komparu.test/pdf/travel/';
                break;
            case 'acc':
                $urlPrefix = 'https://code-acc.komparu.com/pdf/travel/';
                break;
            default:
                $urlPrefix = 'https://code.komparu.com/pdf/travel/';
                break;
        }

        // For multiple row outputs
        foreach($output as $k => $out){
            if(isset($out[ResourceInterface::HASH])) {
                $output[$k][ResourceInterface::PDF] = $urlPrefix . $out[ResourceInterface::HASH];
            }
        }
        // For single row output
        if(isset($output[ResourceInterface::HASH])) {
            $output[ResourceInterface::PDF] = $urlPrefix . $output[ResourceInterface::HASH];
        }
    }

    public function addConstantCreateInput(Resource $resource, ArrayObject $input, $action)
    {
        if($action !== RestListener::ACTION_STORE){
            return;
        }
        $input->offsetSet('template_id', 25);
        $input->offsetSet('product_type_id', 47);

        $user = $this->getApplicationUser('publisher');

        if ($user) {
            $input[ResourceInterface::USER_ID] = $user->id;
        }
    }

    public function filterCrmOnlyWebsites(Resource $resource, ArrayObject $input, $action)
    {
        if($action !== RestListener::ACTION_INDEX || empty($input[ResourceInterface::IS_CRM_TOOL])){
            return;
        }

        $user = $this->getApplicationUser();
        $right = Right::where('product_type_id', 47)
            ->where('user_id', $user->id)
            ->where('active', 1)
            ->where('key', ResourceInterface::IS_CRM_TOOL)
            ->where('value', 1)
            ->first();


        if ($right)
            $input[ResourceInterface::ID] = $right->website_id;
        else
            $input[ResourceInterface::ID] = 0;
    }

    public function addCrmOnlyHash(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if($action !== RestListener::ACTION_INDEX && $action !== RestListener::ACTION_SHOW){
            return;
        }

        if ($action == RestListener::ACTION_INDEX) {
            foreach ($output as $k => $out) {
                $output[$k]['crm_only_hash'] = md5($out['id'] . ':_crm_only_key_salt');
            }
        }
        else if ($action == RestListener::ACTION_SHOW) {
            $output['crm_only_hash'] = md5($output['id'] . ':_crm_only_key_salt');
        }
    }

    public function autoGenerateId(Resource $resource, ArrayObject $input, $action)
    {
        if($action !== RestListener::ACTION_STORE){ return; }

        // get the latest parking_id
        /** @var Response $response */
        $response = DocumentHelper::get('product', 'travel', ['limit' => 1, 'order' => 'parking_id', 'direction' => 'desc', 'visible' => 'parking_id']);
        if($response === false){
            // failed to fetch parking_id's
        }

        $documents = $response->documents();
        $item = isset($documents[0]) ? $documents[0] : -1;

        // add 1 to the response
        $parkingId = $item['parking_id']+1;
        $resourceName = explode('.', array_get($input->getArrayCopy(), 'resource.name'));

        $input['parking_id'] = $parkingId;
        $input['_id'] = substr(md5('travel.'.array_get($resourceName, 1, 'unknown_resource').'.location_id.'. $parkingId), 0, 24);
        $input['__id'] = $input['_id'];
    }

    public function mapAvailableOptionsToOptionIds(Resource $resource, ArrayObject $input, $action)
    {
        if($action !== RestListener::ACTION_INDEX && $action !== RestListener::ACTION_SHOW){ return; }

        if (isset($input[ResourceInterface::AVAILABLE_OPTIONS])) {
            $input[ResourceInterface::AVAILABLE_OPTIONS] = !is_array($input[ResourceInterface::AVAILABLE_OPTIONS]) ? explode(',', $input[ResourceInterface::AVAILABLE_OPTIONS]) : $input[ResourceInterface::AVAILABLE_OPTIONS];
            $input[ResourceInterface::PRODUCT_OPTIONS_IDS] = $input[ResourceInterface::AVAILABLE_OPTIONS];
        }
    }

    public function autoLatLong(Resource $resource, ArrayObject $input, $action)
    {
        if(!in_array($action, [RestListener::ACTION_STORE, RestListener::ACTION_UPDATE])){ return; }

        if(!isset($input[ResourceInterface::LOCATION_DESCRIPTION])){ return; }

        $geolocation = ResourceHelper::callResource1('geocoding.google', 'coordinates', [
            'skipcache' => true,
            'geo_mode' => 'freeform',
            ResourceInterface::FREEFORM_ADDRESS => $input[ResourceInterface::LOCATION_DESCRIPTION]
        ]);

        $geoLocation = ResourceHelper::callResource2('coordinates.geocoding.google', [ResourceInterface::GEO_MODE => 'freeform', ResourceInterface::FREEFORM_ADDRESS => $input[ResourceInterface::LOCATION_DESCRIPTION]]);

        if(isset($geoLocation[0], $geoLocation[0][ResourceInterface::LATITUDE], $geoLocation[0][ResourceInterface::LONGITUDE])){

            $geoJsonPoint['type']        = 'Point';
            $geoJsonPoint['coordinates'] = [$geoLocation[0][ResourceInterface::LATITUDE], $geoLocation[0][ResourceInterface::LONGITUDE]];

            $input[ResourceInterface::LOCATION_GEOJSON]   = $geoJsonPoint;
            $input[ResourceInterface::LOCATION_LATITUDE]  = $geoLocation[0][ResourceInterface::LATITUDE];
            $input[ResourceInterface::LOCATION_LONGITUDE] = $geoLocation[0][ResourceInterface::LONGITUDE];
        }
    }

    public function addWebsiteCountryCode(Resource $resource, ArrayObject $input, $action)
    {
        if(!in_array($action, [RestListener::ACTION_INDEX, RestListener::ACTION_SHOW])){
            return;
        }

        if (isset($input[ResourceInterface::WEBSITE]) && !isset($input[ResourceInterface::COUNTRY_CODE])) {
            $rights = ResourceHelper::callResource2('website_rights.travel', [ResourceInterface::__ID => $input[ResourceInterface::WEBSITE]]);
            if (!empty($rights[0][ResourceInterface::COUNTRY_CODE])) {
                $input[ResourceInterface::COUNTRY_CODE] = $rights[0][ResourceInterface::COUNTRY_CODE];
            }
        }
    }

    public function orderServicesInput(Resource $resource, ArrayObject $input)
    {
        $input->offsetSet('_' . ResourceInterface::ORDER, ResourceInterface::ORDER);
    }

    protected function getApplicationUser($requiredRole = null)
    {
        if ((strpos(php_sapi_name(), 'cli') !== false) || !\app('application') || !\app('application')->user) {
            return null;
        }

        $user = \app('application')->user;

        // Must be a reseller
        if ($requiredRole && !in_array($requiredRole, $user->getCallerRoles()))
            return null;

        return $user;
    }

    public function addUserAndEnabledCondition(Resource $resource, ArrayObject $input, $action)
    {
        $user = $this->getApplicationUser('publisher');

        if (!$user) {
            return;
        }

        $input->offsetSet(ResourceInterface::USER, $this->getUserId($input));

        // Temporary fix to be able to save settings (Todo: check if website belongs to user)
        if ($resource->name == 'product_settings.travel' && in_array($action, [RestListener::ACTION_UPDATE, RestListener::ACTION_STORE, RestListener::ACTION_DESTROY]) && isset($input[ResourceInterface::WEBSITE])) {
            unset($input[ResourceInterface::USER]);
        }

        if ($action == RestListener::ACTION_INDEX)
            $input->offsetSet(ResourceInterface::ENABLED, true);
        else if (isset($input[ResourceInterface::ENABLED]))
            unset($input[ResourceInterface::ENABLED]); // reseller cannot 'set' enabled
    }

    public function addProviderCondition(Resource $resource, ArrayObject $input, $action)
    {
        $user = $this->getApplicationUser('travel-provider');

        if (!$user) {
            return;
        }

        $input->offsetSet(ResourceInterface::PROVIDER_ID, $this->getUserId($input));
    }

    public function addUserCondition(Resource $resource, ArrayObject $input, $action)
    {
        $user = $this->getApplicationUser('publisher');

        if (!$user) {
            return;
        }

        $input->offsetSet(ResourceInterface::USER, $this->getUserId($input));
    }

    public function mergeDateTimeInputs(Resource $resource, ArrayObject $input, $action)
    {
        // Do not do this magic when filtering orders
        if ($action == RestListener::ACTION_INDEX && $resource->name == 'order.travel')
            return;

        //If you have the date AND there is NO time part to it!
        if(isset($input[ResourceInterface::DESTINATION_ARRIVAL_DATE]) && ! preg_match('~[0-9]{2}\:[0-9]{2}(\:[0-9]{2}(\.[0-9]{3}Z)?)?$~', $input[ResourceInterface::DESTINATION_ARRIVAL_DATE])){
            if(isset($input[ResourceInterface::DESTINATION_ARRIVAL_TIME])){
                $input[ResourceInterface::DESTINATION_ARRIVAL_DATE] = $input[ResourceInterface::DESTINATION_ARRIVAL_DATE] . ' ' . $input[ResourceInterface::DESTINATION_ARRIVAL_TIME];
            }else{
                $input[ResourceInterface::DESTINATION_ARRIVAL_DATE] = $input[ResourceInterface::DESTINATION_ARRIVAL_DATE] . ' 12:00:00';
                $input[ResourceInterface::DESTINATION_ARRIVAL_TIME] = '12:00:00';
            }
        }elseif(isset($input[ResourceInterface::DESTINATION_ARRIVAL_DATE], $input[ResourceInterface::DESTINATION_ARRIVAL_TIME])){
            //If you have both times merge anyway for safety
            $date                                               = Carbon::parse($input[ResourceInterface::DESTINATION_ARRIVAL_DATE])->toDateString();
            $time                                               = Carbon::parse($input[ResourceInterface::DESTINATION_ARRIVAL_TIME])->toTimeString();
            $input[ResourceInterface::DESTINATION_ARRIVAL_DATE] = $date . ' ' . $time;
        }
        elseif(isset($input[ResourceInterface::DESTINATION_ARRIVAL_DATE]) && !isset($input[ResourceInterface::DESTINATION_ARRIVAL_TIME])){
            $input[ResourceInterface::DESTINATION_ARRIVAL_TIME] = Carbon::parse($input[ResourceInterface::DESTINATION_ARRIVAL_DATE])->toTimeString();
        }

        //If you have the date AND there is NO time part to it!
        if(isset($input[ResourceInterface::DESTINATION_DEPARTURE_DATE]) && ! preg_match('~[0-9]{2}\:[0-9]{2}(\:[0-9]{2}(\.[0-9]{3}Z)?)?$~', $input[ResourceInterface::DESTINATION_DEPARTURE_DATE])){
            if(isset($input[ResourceInterface::DESTINATION_DEPARTURE_TIME])){
                $input[ResourceInterface::DESTINATION_DEPARTURE_DATE] = $input[ResourceInterface::DESTINATION_DEPARTURE_DATE] . ' ' . $input[ResourceInterface::DESTINATION_DEPARTURE_TIME];
            }else{
                $input[ResourceInterface::DESTINATION_DEPARTURE_DATE] = $input[ResourceInterface::DESTINATION_DEPARTURE_DATE] . ' 12:00:00';
                $input[ResourceInterface::DESTINATION_DEPARTURE_TIME] = '12:00:00';
            }
        }elseif(isset($input[ResourceInterface::DESTINATION_DEPARTURE_DATE], $input[ResourceInterface::DESTINATION_DEPARTURE_TIME])){
            //If you have both times merge anyway for safety
            $date                                                 = Carbon::parse($input[ResourceInterface::DESTINATION_DEPARTURE_DATE])->toDateString();
            $time                                                 = Carbon::parse($input[ResourceInterface::DESTINATION_DEPARTURE_TIME])->toTimeString();
            $input[ResourceInterface::DESTINATION_DEPARTURE_DATE] = $date . ' ' . $time;
        }
        elseif(isset($input[ResourceInterface::DESTINATION_DEPARTURE_DATE]) && !isset($input[ResourceInterface::DESTINATION_DEPARTURE_TIME])){
            $input[ResourceInterface::DESTINATION_DEPARTURE_TIME] = Carbon::parse($input[ResourceInterface::DESTINATION_DEPARTURE_DATE])->toTimeString();
        }
    }

    /** Split Date and Time to show in CRM **/
    public function splitDateTimeOutput(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $isCrm = !empty($input[OptionsListener::OPTION_NO_PROPAGATION]);

        $dateFormat = $isCrm ? 'Y-m-d\TH:i:s.uP' : 'Y-m-d';
        $timeFormat = $isCrm ? null : 'H:i:s';
        $timezone = $isCrm ? 'UTC' : 'Europe/Amsterdam';

        if( ! isset($output[0])){
            $output = $this->formatItemDateTime($output, ResourceInterface::DESTINATION_DEPARTURE_DATE, $dateFormat, $timeFormat, $timezone);
            $output = $this->formatItemDateTime($output, ResourceInterface::DESTINATION_ARRIVAL_DATE, $dateFormat, $timeFormat, $timezone);
        }else{
            foreach($output as $k => $out){
                $output[$k] = $this->formatItemDateTime($output[$k], ResourceInterface::DESTINATION_DEPARTURE_DATE, $dateFormat, $timeFormat, $timezone);
                $output[$k] = $this->formatItemDateTime($output[$k], ResourceInterface::DESTINATION_ARRIVAL_DATE, $dateFormat, $timeFormat, $timezone);
            }
        }
    }

    protected function formatItemDateTime($item, $key, $dateFormat, $timeFormat, $outputTimezone)
    {
        $dateTime = isset($item[$key]) ? DateTime::createFromFormat('Y-m-d H:i:s', $item[$key], new \DateTimeZone('UTC')) : null;
        if ($dateTime) {
            $item[$key] = $dateTime->setTimezone(new \DateTimeZone($outputTimezone))->format($dateFormat);
            if ($timeFormat && array_key_exists(str_replace('_date', '_time', $key), $item))
                $item[str_replace('_date', '_time', $key)] = $dateTime->setTimezone(new \DateTimeZone($outputTimezone))->format($timeFormat);
        }

        return $item;
    }

    public function validateArrivalDepartureDates(Resource $resource, ArrayObject $input, $action)
    {
        if (!empty($input[OptionsListener::OPTION_NO_PROPAGATION]))
            return;

        if (!isset($input[ResourceInterface::DESTINATION_ARRIVAL_DATE], $input[ResourceInterface::DESTINATION_DEPARTURE_DATE]))
            return;

        $arrival = strtotime(array_get($input->getArrayCopy(), ResourceInterface::DESTINATION_ARRIVAL_DATE));
        $departure = strtotime(array_get($input->getArrayCopy(), ResourceInterface::DESTINATION_DEPARTURE_DATE));

        if ($arrival === false) {
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'travel.error.destination_arrival_date_invalid',
                    "message" => 'Destination arrival date / time input invalid.',
                    "field"   => ResourceInterface::DESTINATION_ARRIVAL_DATE,
                    "type"    => 'input',
                ]
            ]);
        }
        if ($departure === false) {
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'travel.error.destination_departure_date_invalid',
                    "message" => 'Destination departure date / time input invalid.',
                    "field"   => ResourceInterface::DESTINATION_DEPARTURE_DATE,
                    "type"    => 'input',
                ]
            ]);
        }

        if ($arrival >= $departure) {
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'travel.error.destination_arrival_after_departure',
                    "message" => 'Destination arrival date and time must be before departure date and time.',
                    "field"   => ResourceInterface::DESTINATION_ARRIVAL_DATE,
                    "type"    => 'input',
                ]
            ]);
        }

        $input[ResourceInterface::DESTINATION_ARRIVAL_DATE] = date('Y-m-d H:i:s', $arrival);
        $input[ResourceInterface::DESTINATION_DEPARTURE_DATE] = date('Y-m-d H:i:s', $departure);
        $input[ResourceInterface::DESTINATION_ARRIVAL_TIME] = date('H:i:s', $arrival);
        $input[ResourceInterface::DESTINATION_DEPARTURE_TIME] = date('H:i:s', $departure);
    }

    public function validateWebsiteInput(Resource $resource, ArrayObject $input, $action)
    {
        if (!empty($input[OptionsListener::OPTION_NO_PROPAGATION]))
            return;

        if (isset($input['user'], $input['website'])) {
            $website = Website::where('id', $input['website'])->where('user_id', $input['user'])->first();

            if (!$website) {
                throw new ResourceError($resource, $input->getArrayCopy(), [
                    [
                        "code"    => 'travel.error.unknown_website',
                        "message" => 'Website specified does not exist, or does not belong to user.',
                        "field"   => ResourceInterface::WEBSITE,
                        "type"    => 'input',
                    ]
                ]);
            }

            if ($website->product_type_id != 47) {
                throw new ResourceError($resource, $input->getArrayCopy(), [
                    [
                        "code"    => 'travel.error.invalid_website_type',
                        "message" => 'Website specified is not of the `travel` product type.',
                        "field"   => ResourceInterface::WEBSITE,
                        "type"    => 'input',
                    ]
                ]);
            }

        }
    }

    public function splitLicenseplate(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!isset($output[0])){
            if(!empty($output[ResourceInterface::LICENSEPLATE]) && is_string($output[ResourceInterface::LICENSEPLATE])){
                $output[ResourceInterface::LICENSEPLATE] = explode(',', $output[ResourceInterface::LICENSEPLATE]);
            }
        }else{
            foreach($output as $k => $out){
                if(!empty($out[ResourceInterface::LICENSEPLATE]) && is_string($out[ResourceInterface::LICENSEPLATE])){
                    $output[$k][ResourceInterface::LICENSEPLATE] = explode(',', $out[ResourceInterface::LICENSEPLATE]);
                }
            }
        }
    }

    /** Merge Date and Time when received from CRM **/
    public function mergeDateTimeOutput(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(DateTime::createFromFormat('Y-m-d', $output[ResourceInterface::DESTINATION_DEPARTURE_DATE]) !== false){
            $date                                                 = Carbon::parse($input[ResourceInterface::DESTINATION_DEPARTURE_DATE])->toDateString();
            $time                                                 = Carbon::parse($input[ResourceInterface::DESTINATION_DEPARTURE_TIME])->toTimeString();
            $input[ResourceInterface::DESTINATION_DEPARTURE_DATE] = $date . ' ' . $time;
        }
        if(DateTime::createFromFormat('Y-m-d', $output[ResourceInterface::DESTINATION_ARRIVAL_DATE]) !== false){
            $date                                               = Carbon::parse($input[ResourceInterface::DESTINATION_ARRIVAL_DATE])->toDateString();
            $time                                               = Carbon::parse($input[ResourceInterface::DESTINATION_ARRIVAL_TIME])->toTimeString();
            $input[ResourceInterface::DESTINATION_ARRIVAL_DATE] = $date . ' ' . $time;
        }
    }

    public function setEnabledWhenActive(Resource $resource, ArrayObject $input, $action){
        if($action != RestListener::ACTION_STORE && $action != RestListener::ACTION_UPDATE){
            return;
        }
        if($input->offsetExists(ResourceInterface::ACTIVE) && $input[ResourceInterface::ACTIVE]){
            $input->offsetSet(ResourceInterface::ENABLED, true);
        }
    }


    public function filterServiceInput(Resource $resource, ArrayObject $input)
    {
        // 7 = ov   // 8 = Geen voorkeur
        if(isset($input['service']) && ($input['service'] == '8' || $input['service'] == '7')){
            unset($input['service']);
        }
    }

    public function convertContractDatesToUTC(Resource $resource, ArrayObject $input)
    {
        foreach([ResourceInterface::DESTINATION_ARRIVAL_DATE, ResourceInterface::DESTINATION_DEPARTURE_DATE] as $field) {
            $input[$field] =
                (new DateTime($input[$field]))
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format('Y-m-d H:i:s'); // DATE_ISO8601
        }

        $input[ResourceInterface::DESTINATION_ARRIVAL_DATE];
    }

    protected $_settings = [];

    public function filterProductBySettings(Resource $resource, ArrayObject $input, $action, $id = null)
    {
        $id = isset($id) ? $id : (isset($input[ResourceInterface::__ID]) ? $input[ResourceInterface::__ID] : null);
        switch($action) {
            case RestListener::ACTION_SHOW:
            case RestListener::ACTION_INDEX:

                $conditions = array_only($input->getArrayCopy(), [ResourceInterface::USER, ResourceInterface::WEBSITE]);
                $filter     = array_only($input->getArrayCopy(), [ResourceInterface::ENABLED, ResourceInterface::ACTIVE]);

                $providerSettings = $this->getResourceSettings('providers_settings', 'travel', $conditions, $filter+ [OptionsListener::OPTION_LIMIT => 99999999]);

                $productSettings  = $this->getResourceSettings('product_settings', 'travel', $conditions, $filter + [OptionsListener::OPTION_LIMIT => 99999999, ResourceInterface::__ID => $id]);

                if ($filter !== []) {
                    if (isset($id)) {
                        $input[ResourceInterface::__ID] = array_intersect((array) $id, array_keys($productSettings));
                    } else {
                        $input[ResourceInterface::__ID] = array_keys($productSettings);
                    }

                    if (isset($input[ResourceInterface::PROVIDER_ID])) {
                        $input[ResourceInterface::PROVIDER_ID] = array_intersect((array) $input[ResourceInterface::PROVIDER_ID], array_keys($providerSettings));
                    } else {
                        $input[ResourceInterface::PROVIDER_ID] = array_keys($providerSettings);
                    }
                    // Allow products with 'null' provider
                    $input[ResourceInterface::PROVIDER_ID][] = null;
                }

                if ($action == RestListener::ACTION_INDEX) {
                    // We ignore the 'active' and 'enabled' in the product itself
                    foreach ($filter as $key => $value) {
                        unset($input[$key]);
                    }
                }
                break;
            default:
                $productSettings = [];
        }

        $this->_settings = [ResourceInterface::__ID => $productSettings];
    }

    public function filterProviderBySettings(Resource $resource, ArrayObject $input)
    {
        $conditions = array_only($input->getArrayCopy(), [ResourceInterface::USER, ResourceInterface::WEBSITE]);
        $filter = array_only($input->getArrayCopy(), [ResourceInterface::ENABLED, ResourceInterface::ACTIVE]);

        $providerSettings = $this->getResourceSettings('providers_settings', 'travel', $conditions, $filter);
        if ($filter !== []) {
            if (isset($input[ResourceInterface::ID]))
                $input[ResourceInterface::ID] = array_intersect((array)$input[ResourceInterface::ID], array_keys($providerSettings));
            else
                $input[ResourceInterface::ID] = array_keys($providerSettings);
            // Allow products with 'null' provider
            $input[ResourceInterface::ID][] = null;
        }

        // We ignore the 'active' and 'enabled' in the provider itself
        foreach ($filter as $key => $value)
            unset($input[$key]);

        $this->_settings = [ResourceInterface::ID => $providerSettings];
    }

    public function populateSettings(Resource $resource, ArrayObject $input, ArrayObject $output, $action, $id)
    {
        if ($action !== RestListener::ACTION_UPDATE) {
            return;
        }

        $conditions = array_only($input->getArrayCopy(), [ResourceInterface::USER, ResourceInterface::WEBSITE]);
        $filter = [ResourceInterface::__ID => $id];

        $productSettings  = $this->getResourceSettings('product_settings', 'travel', $conditions, $filter);

        if (isset($productSettings[$id])) {
            $productSettings[$id] = array_merge(
                $productSettings[$id],
                array_only($input->getArrayCopy(), [ResourceInterface::ENABLED, ResourceInterface::ACTIVE])
            );
        }

        $this->_settings = [ResourceInterface::__ID => $productSettings];
    }

    public function addSettings(Resource $resource, ArrayObject $input, ArrayObject $output, $action, $id)
    {
        foreach ($this->_settings as $filterField => $settings) {
            $closure = $this->addSettingsClosure($filterField, $settings);
            if (ArrayHelper::isAssoc($output->getArrayCopy())) {
                $output->exchangeArray($closure($output->getArrayCopy()));
            } else {
                $output->exchangeArray(array_filter(array_map($closure, $output->getArrayCopy())));
            }
        }
        $this->_settings = [];
    }

    public function filterDestinationRadius(Resource $resource, ArrayObject $input)
    {
        if( ! isset($input[ResourceInterface::RADIUS])){
            return;
        }
        // This works only in MongoDB
        if(isset($input[ResourceInterface::DESTINATION_LATITUDE], $input[ResourceInterface::DESTINATION_LONGITUDE])){
            $input->offsetSet('$or', [
                [ResourceInterface::LOCATION_GEOJSON => null], // No known location (Taxi's etc)
                [
                    ResourceInterface::LOCATION_GEOJSON => [
                        '$geoWithin' => [
                            '$centerSphere' => [
                                [$input[ResourceInterface::DESTINATION_LATITUDE], $input[ResourceInterface::DESTINATION_LONGITUDE]],
                                $input[ResourceInterface::RADIUS] / self::MEAN_RADIUS_EARTH_KM, // Convert to radians
                            ]
                        ]
                    ]
                ],
            ]);
        }
    }

    public function addInrixTimeout(Resource $resource, ArrayObject $input)
    {
        // Inrix is not critical - we want to timeout in 6 seconds
        $input[OptionsListener::OPTION_RESOURCE_TIMEOUT]['parking_lots.inrix'] = 6;
    }

    public function addWebsitePropertyTypeFilter(Resource $resource, ArrayObject $input)
    {
        // websites.travel should only return websites of type 47
        $input[ResourceInterface::PRODUCT_TYPE_ID] = 47;
    }

    public static function mapRemoteOptionIds(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }
        $output->exchangeArray(array_map(function ($product) use ($input) {
            // Map requested available options to remote option ids
            $product[ResourceInterface::REMOTE_OPTION_IDS] = [];
            if (isset($product[ResourceInterface::OPTIONS]))
            {
                foreach($product[ResourceInterface::OPTIONS] as $option){
                    if($option['id'] && in_array($option['id'], array_get($input, ResourceInterface::AVAILABLE_OPTIONS, []))){
                        if (isset($option[ResourceInterface::REMOTE_ID])) {
                            if ($product[ResourceInterface::SOURCE] == 'parkingpro') {
                                // Parking pro needs to have location-specific options :(
                                $product[ResourceInterface::REMOTE_OPTION_IDS][] = $product[ResourceInterface::RESOURCE][ResourceInterface::ID] .'|'. $option[ResourceInterface::REMOTE_ID];
                            }
                            else {
                                $product[ResourceInterface::REMOTE_OPTION_IDS][] = $option[ResourceInterface::REMOTE_ID];
                            }
                        }
                    }
                }
            }

            return $product;
        }, $output->getArrayCopy()));
    }

    public static function getProductOptions(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }

        $ids = array_map(function ($p) {
            return $p[ResourceInterface::__ID];
        }, $output->getArrayCopy());

        if (empty($ids))
            return;

        $options = ResourceHelper::callResource2('product_options.travel', [
            ResourceInterface::PRODUCT_ID => $ids,
            '_limit'                      => ValueInterface::INFINITE,
        ]);

        $options = array_map(function ($option) {
            return [
                ResourceInterface::ID          => $option[ResourceInterface::OPTION_ID],
                ResourceInterface::COST        => $option[ResourceInterface::COST],
                ResourceInterface::NAME        => $option[ResourceInterface::NAME],
                ResourceInterface::DESCRIPTION => $option[ResourceInterface::DESCRIPTION],
                ResourceInterface::REMOTE_ID   => $option[ResourceInterface::REMOTE_ID],
                ResourceInterface::PRODUCT_ID  => $option[ResourceInterface::PRODUCT_ID],
            ];
        }, $options);

        $options_grouped = array_reduce($options, function ($acc, $option) {
            $acc[$option[ResourceInterface::PRODUCT_ID]][] = $option;

            return $acc;
        }, []);

        $output->exchangeArray(array_map(function ($product) use ($options_grouped) {
            $product[ResourceInterface::OPTIONS] = array_get($options_grouped, $product[ResourceInterface::__ID], []);

            return $product;
        }, $output->getArrayCopy()));

    }

    public static function enrichProductOptions(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }
        $output->exchangeArray(array_map(function ($product) use ($input) {

            if($product[ResourceInterface::AVAILABLE_OPTIONS] && !is_array($product[ResourceInterface::AVAILABLE_OPTIONS])){
                $product[ResourceInterface::AVAILABLE_OPTIONS] = array_map('intval', explode(',', $product[ResourceInterface::AVAILABLE_OPTIONS]));
            }
            else if (is_array($product[ResourceInterface::AVAILABLE_OPTIONS])) {
                $product[ResourceInterface::AVAILABLE_OPTIONS] = array_map('intval', $product[ResourceInterface::AVAILABLE_OPTIONS]);
            }
            else {
                $product[ResourceInterface::AVAILABLE_OPTIONS] = [];
            }

            // Retain original location options
            $product[ResourceInterface::PRODUCT_OPTIONS] = (array) array_get($product, ResourceInterface::OPTIONS);

            // By default, all available options are the options with a non-empty 'id'
            $product[ResourceInterface::AVAILABLE_OPTIONS] = array_values(array_filter(array_map(function ($option) {
                return intval($option['id']);
            }, (array)$product[ResourceInterface::PRODUCT_OPTIONS])));


            // Make options capitalized
            $product[ResourceInterface::PRODUCT_OPTIONS] = array_map(function ($option) {
                $option[ResourceInterface::NAME] = ucfirst($option[ResourceInterface::NAME]);

                return $option;
            }, $product[ResourceInterface::PRODUCT_OPTIONS]);

            // Sort options & filter options by availability
            $product[ResourceInterface::OPTIONS] = [];
            foreach(self::$optionsOrder as $optionsOrderId){
                if( ! in_array($optionsOrderId, $product[ResourceInterface::AVAILABLE_OPTIONS])){
                    continue;
                }

                // Get option by id
                $option = head(array_filter($product[ResourceInterface::PRODUCT_OPTIONS], function ($option) use ($optionsOrderId) {
                    return $option['id'] == $optionsOrderId;
                }));

                if( ! $option){
                    continue;
                }

                $product[ResourceInterface::OPTIONS][] = $option;
            }

            return $product;
        }, $output->getArrayCopy()));
    }


    /**
     * Multiply stuff for multiple cars
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function multipleCars(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if( ! isset($input[ResourceInterface::NUMBER_OF_CARS]) || $input[ResourceInterface::NUMBER_OF_CARS] < 2){
            return;
        }

        $factor = $input[ResourceInterface::NUMBER_OF_CARS];

        $output->exchangeArray(array_map(function ($product) use ($factor) {
            $product[ResourceInterface::PRICE_ACTUAL]                *= $factor;
            $product[ResourceInterface::PRICE_COSTFREE_CANCELLATION] *= $factor;
            $product[ResourceInterface::PRICE_ADMINISTRATION_FEE]    *= $factor;
            if( ! isset($product[ResourceInterface::OPTIONS])){
                return $product;
            }
            $product[ResourceInterface::OPTIONS] = array_map(function ($option) use ($factor) {
                $option['cost'] *= $factor;

                return $option;
            }, $product[ResourceInterface::OPTIONS]);

            return $product;
        }, $output->getArrayCopy()));
    }


    /**
     * Filter on provider ID if it exists
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function filterProviderId(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        return;
    }

    /**
     * Set remote option data into local options
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function addRemoteOptionPrices(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }
        $output->exchangeArray(array_map(function ($product) use ($input) {
            if (empty($product[ResourceInterface::REMOTE_OPTIONS]) || empty($product[ResourceInterface::OPTIONS]))
                return $product;

            foreach ($product[ResourceInterface::OPTIONS] as $key => $option) {
                if (isset($option[ResourceInterface::REMOTE_ID])) {
                    foreach ($product[ResourceInterface::REMOTE_OPTIONS] as $remoteOption) {
                        if ($remoteOption[ResourceInterface::OPTION_ID] == $option[ResourceInterface::REMOTE_ID]) {
                            $product[ResourceInterface::OPTIONS][$key][ResourceInterface::COST] = $remoteOption[ResourceInterface::PRICE_ACTUAL];
                        }
                    }
                }
            }
            return $product;
        }, $output->getArrayCopy()));
    }

    /**
     * If a price call fails, the price of the product will be 0. These products should not be shown.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function filterOutZeroPriceProducts(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION]) || ! empty($input['debug'])){
            return;
        }

        $products = [];
        foreach($output->getArrayCopy() as $key => $value){
            if( ! empty($value[ResourceInterface::PRICE_ACTUAL]) && $value[ResourceInterface::PRICE_ACTUAL] != 0){
                $products[] = $value;
            }
        }
        $output->exchangeArray($products);
    }

    /**
     * Remove any product that are not 'available'
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     *
     * @return void
     */
    public static function orderByType(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if( ! $output->offsetExists(0)){
            return;
        }

        $groups = array_reduce($output->getArrayCopy(), function ($acc, $p) {
            $key = array_get($p, ResourceInterface::TYPE);
            if( ! in_array($key, ['bookable', 'plannable', 'informative'])){
                $key = 'other'; // ?
            }
            $acc[$key][] = $p;

            return $acc;
        }, ['bookable' => [], 'plannable' => [], 'informative' => [], 'other' => []]);

        $output->exchangeArray(array_merge($groups['bookable'], $groups['plannable'], $groups['informative'], $groups['other']));
    }

    /**
     * Remove any product that are not 'available'
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function filterUnavailableProducts(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION]) || ! empty($input['debug'])){
            return;
        }

        $products = [];
        foreach($output->getArrayCopy() as $key => $value){
            if( ! isset($value[ResourceInterface::IS_UNAVAILABLE]) || ! $value[ResourceInterface::IS_UNAVAILABLE]){
                $products[] = $value;
            }
        }
        $output->exchangeArray($products);
    }

    public function addContractDirectFields(Resource $resource, ArrayObject $input)
    {
        $user = $this->getApplicationUser();
        if (!$user)
            return;

        $input[ResourceInterface::IP] = Request::getClientIp();
        $input[ResourceInterface::SESSION] = null;
        $input[ResourceInterface::SESSION_ID] = null;
        $input[ResourceInterface::USER] = $user->id;
    }

    /**
     * Covert licenseplate fields to array. Fuck me thats ugly
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function licenseplateArray(Resource $resource, ArrayObject $input, $action)
    {
        if ($action === RestListener::ACTION_INDEX) {
            // If we filter with a 'wildcard' (`*`), we want it to be a string, not an array, so the Document package does LIKE.
            if (isset($input[ResourceInterface::LICENSEPLATE][0]) && str_contains($input[ResourceInterface::LICENSEPLATE][0], '*')) {
                $input[ResourceInterface::LICENSEPLATE] = $input[ResourceInterface::LICENSEPLATE][0];
            }
        }

        if ($action !== RestListener::ACTION_STORE)
            return;

        $licenseplateArr = explode(',', implode(',', (array)array_get($input, ResourceInterface::LICENSEPLATE, [])));

        // Look for licenseplate2-6 inputs
        for ($id = 2; $id <= 6; $id ++){
            if(isset($input[ResourceInterface::LICENSEPLATE . $id])){
                $licenseplateArr[] = $input[ResourceInterface::LICENSEPLATE . $id];
                unset($input[ResourceInterface::LICENSEPLATE . $id]);
            }
        }
        $input[ResourceInterface::LICENSEPLATE] = $licenseplateArr;

        if (isset($input[ResourceInterface::NUMBER_OF_CARS]) && count($input[ResourceInterface::LICENSEPLATE]) !== (int)$input[ResourceInterface::NUMBER_OF_CARS]) {
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'travel.error.number_of_cars_licenseplate_incorrect',
                    "message" => 'The number of cars specified and the number of licenseplates provided does not match.',
                    "field"   => ResourceInterface::LICENSEPLATE,
                    "type"    => 'input',
                ]
            ]);
        }
        $input[ResourceInterface::NUMBER_OF_CARS] = count($input[ResourceInterface::LICENSEPLATE]);
    }

    public static function validateLicenseplateUnknown(Resource $resource, ArrayObject $input)
    {
        //TODO: Move this validation to Laravel input validation when we switch to Laravel 5.3

        $product = DocumentHelper::show('product', 'travel', array_get($input, 'product_id'));

        // 6 = taxi
        if($product && array_get($product, 'service') != '6' && empty($input[ResourceInterface::LICENSEPLATE]) && empty($input[ResourceInterface::LICENSEPLATE_UNKNOWN])){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'travel.error.licenseplate_required',
                    "message" => 'Het kenteken is vereist.',
                    "field"   => ResourceInterface::LICENSEPLATE,
                    "type"    => 'input',
                ]
            ]);
        }
    }

    /**
     * Process the email text
     *
     * @param $config Website Website
     * @param $session array merged Session
     * @param $data array merge product data + order
     * @param ArrayObject $replaceArray
     *
     */
    public function processEmailText(ArrayObject $replaceArray, $config, $session, $data, $order)
    {
        // If language of website is not nl, get mail from other language.
        $pf = $config['language'] == 'nl' ? '' : '_' . $config['language'];
        if( ! isset($data['mail' . $pf])){
            $pf = '';
        }
        $mail = array_get($data, 'mail' . $pf, '');

        $data = TravelWrapperAbstractRequest::convertOrderUTCtoCest($data);

        $replaceArray->exchangeArray(array_merge($replaceArray->getArrayCopy(), [
            '{mail}'                   => $mail,
            '{%PRICE%}'                => number_format($data[ResourceInterface::AMOUNT], 2),
            '{%AANBETAALD%}'           => number_format($data[ResourceInterface::AMOUNT], 2),
            '{%OPENSTAAND%}'           => 0.00,
            '{%ARRIVALDATE-PARKING%}'  => date('d-m-Y', strtotime($data[ResourceInterface::DESTINATION_ARRIVAL_DATE])),
            '{%ARRIVALTIME-PARKING%}'  => date('H:i:s', strtotime($data[ResourceInterface::DESTINATION_ARRIVAL_DATE])),
            '{%DAYS%}'                 => $this->getNumberOfDays($data[ResourceInterface::DESTINATION_ARRIVAL_DATE], $data[ResourceInterface::DESTINATION_DEPARTURE_DATE]),
            '{%NOTES%}'                => array_get($data, ResourceInterface::CUSTOMER_REMARKS),
            '{%RETURN-DATE%}'          => date('d-m-Y', strtotime($data[ResourceInterface::DESTINATION_DEPARTURE_DATE])),
            '{%RETURN-TIME%}'          => date('H:i:s', strtotime($data[ResourceInterface::DESTINATION_DEPARTURE_DATE])),
            '{%PERSONS%}'              => array_get($data, ResourceInterface::NUMBER_OF_PERSONS),
            '{%OPTIES%}'               => $this->getSelectedOptionNames($config, $session, $data, $order),
            '{%NAME%}'                 => array_get($data, ResourceInterface::FIRST_NAME) . " " . array_get($data, ResourceInterface::LAST_NAME),
            '{%CELLPHONE%}'            => array_get($data, ResourceInterface::PHONE),
            '{%EMAIL%}'                => array_get($data, ResourceInterface::EMAIL),
            '{%CAR-REGISTRATION%}'     => array_get($data, ResourceInterface::LICENSEPLATE),
            '{%FLIGHTNUMBER-ARRIVAL%}' => array_get($data, ResourceInterface::RETURN_FLIGHT_NUMBER),
            '{%FLIGHTNUMBER-RETOUR%}'  => array_get($data, ResourceInterface::RETURN_FLIGHT_NUMBER),
            '{%HOUSENUMBER%}'          => '',
            '{%ZIPCODE%}'              => '',
            '{%ADDRESS%}'              => array_get($data, ResourceInterface::ADDRESS), // Origin address
            '{%LUGGAGE%}'              => 1,
            '{%RESERVATION-CODE%}'     => array_get($data, ResourceInterface::RESERVATION_CODE),
            '{%inrijcode%}'            => array_get($data, ResourceInterface::RESERVATION_CODE),
            '{%INRIJCODE%}'            => array_get($data, ResourceInterface::RESERVATION_CODE),
            '{%DESTINATION-ADDRESS%}'  => array_get($data, ResourceInterface::DESTINATION_ADDRESS),
        ]));
    }

    public function updateResellerActive(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        return;
    }

    public function translatableOptions(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if( ! isset($input['website'])){
            return;
        }

        $website        = Website::find($input['website']);

        $definedOptions = ResourceHelper::callResource2('options.travel', [OptionsListener::OPTION_LANG => $website ? $website->language : 'nl', '_use_plan' => 1] + array_only($input->getArrayCopy(), ['website', 'user']));

        $output->exchangeArray(array_map(function ($product) use ($definedOptions) {

            if(isset($product['options']) && is_array($product['options']) && count($product['options']) > 0){
                $product['options'] = array_map(function ($option) use ($definedOptions) {

                    foreach($definedOptions as $definedOption){
                        if($option['id'] === $definedOption['__id']){
                            $option['name'] = $definedOption['label'];
                        }
                    }

                    return $option;

                }, $product['options']);
            }

            return $product;

        }, $output->getArrayCopy()));
    }

    public function calculatePriceInitialAndPriceOptions(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $available_options = array_get((array) $input, ResourceInterface::AVAILABLE_OPTIONS, []);
        if ($available_options === null)
            $available_options = [];

        $output->exchangeArray(array_map(function ($parking) use ($available_options, $input) {
            $nrOfCars = max(1, (int)array_get($input->getArrayCopy(), ResourceInterface::NUMBER_OF_CARS, 1));
            if(array_get($parking, 'source') === 'parcompare'){
                $options_total = 0;
                $options = array_get($parking, ResourceInterface::PRODUCT_OPTIONS, []);
                if (!$options)
                    $options = [];
                foreach($options as $option){
                    if(in_array(array_get($option, 'id'), $available_options)){
                        $options_total += array_get($option, 'cost', 0);
                    }
                }

                $parking[ResourceInterface::PRICE_OPTIONS] = $options_total * $nrOfCars;

                if(isset($parking[ResourceInterface::PRICE_INITIAL])){
                    $parking[ResourceInterface::PRICE_INITIAL] -= $options_total;
                }
            }

            if (isset($parking[ResourceInterface::PRICE_INITIAL])){
                $parking[ResourceInterface::PRICE_INITIAL] *= $nrOfCars;
            }

            return $parking;
        }, $output->getArrayCopy()));
    }


    public function removeTaxiWhenNoOrigin(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if (!empty($input[OptionsListener::OPTION_NO_PROPAGATION]))
            return;

        $output->exchangeArray(array_filter($output->getArrayCopy(), function ($product) use ($input) {
            if ($product['source'] == 'taxitender' && empty($input[ResourceInterface::ORIGIN_GOOGLE_PLACE_ID])) {
                return false;
            }

            return true;
        }));
    }

    public function enrichParkingsWithRealDistances(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(isset($input['destination_latitude'], $input['destination_longitude'])){
            // to put these values to production
            // change “calculated_” to
            // distance_to_destination
            // time_to_destination
            $output->exchangeArray(array_map(function ($parking) use ($input) {
                // 6 = taxi
                if($parking['service'] !== '6'){
                    $parking['calculated_distance'] = round(self::calculateDistance($parking['location_latitude'], $parking['location_longitude'], $input['destination_latitude'], $input['destination_longitude']) * 2.2528841653685, 1);

                    $parking['calculated_time'] = round($parking['calculated_distance'] / 0.71825174825175);
                }

                return $parking;
            }, $output->getArrayCopy()));
        }
    }

    private static function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = 'K')
    {
        $theta = $lon1 - $lon2;
        $dist  = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist  = acos($dist);
        $dist  = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit  = strtoupper($unit);

        switch($unit){
            case 'K':
                return ($miles * 1.609344);
            case 'N':
                return ($miles * 0.8684);
            default:
                return $miles;
        }
    }

    private function getSelectedOptionNames($website, $session, $data, $order)
    {
        $selectedOptionIds = [];
        if(isset($order[ResourceInterface::OPTIONS]) && $order[ResourceInterface::OPTIONS] !== ''){
            $selectedOptionIds = explode(',', $order[ResourceInterface::OPTIONS]);
        }
        else if(isset($session[ResourceInterface::AVAILABLE_OPTIONS]) && $session[ResourceInterface::AVAILABLE_OPTIONS] !== ''){
            $selectedOptionIds = explode(',', $session[ResourceInterface::AVAILABLE_OPTIONS]);
        }


        $options = ResourceHelper::callResource2('options.travel', [OptionsListener::OPTION_LANG => $website->language, 'website' => $website->id, 'user' => $website->user_id]);

        $names = [];
        foreach($options as $option){
            if(isset($option[ResourceInterface::__ID]) && in_array($option[ResourceInterface::__ID], $selectedOptionIds)){
                $names[] = $option[ResourceInterface::LABEL];
            }
        }

        if( ! empty($session[ResourceInterface::COSTFREE_CANCELLATION])){
            $names[] = $this->translateInLanguage('travel.mail.order_thankyou.costfree_cancellation', $website['language']);
        }

        return implode(', ', $names);
    }

    private function getNumberOfDays($start, $end)
    {
        $start_date = strtotime($start);
        $end_date   = strtotime($end);
        $datediff   = $end_date - $start_date;
        $days       = floor($datediff / (60 * 60 * 24)) + 1;
        return $days;
    }

    private function getOrderByOrderId($orderId)
    {
        try{
            $orders = DocumentHelper::get('order', 'travel', ['filters' => [ResourceInterface::ORDER_ID => $orderId]]);
            return head($orders['documents']);
        }catch(\Exception $e){
            return null;
        }
    }

    /**
     * @param ArrayObject $input
     *
     * @return mixed|null
     */
    private function getUserId(ArrayObject $input)
    {
        try{
            $application = \app('application');
        }catch(\Exception $ex){
            //There is no application because something is calling
            //from the cli
            return null;
        }

        $user_id = !is_null($application->user_id) ? $application->user_id : ($input->offsetExists(ResourceInterface::USER) ? $input->offsetGet(ResourceInterface::USER) : null);

        return $user_id;
    }

    public function addAdministrationFees(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        //Get all the service yields first
        $yields = self::assembleYields($input);
        $website = false;
        $embed = false;
        if (isset($input['website'])){
            $website = Website::find($input['website']);
            $rights = $website->rights->keyBy('key');
            if($rights->get(ResourceInterface::EMBED_ADMINISTRATION_COST)){
                $embed = true;
            }
        }

        $fee = $website ? (float) $website->getRight('administration_fee') : 0;

        $products = [];
        foreach ($output as $product){
            //First find the service
            $serviceId = $product['service'];

            if($embed ==true && isset($product[ResourceInterface::PRICE_ADMINISTRATION_FEE]) && $product[ResourceInterface::PRICE_ADMINISTRATION_FEE] == 0){
                //Embed the fee before any yields
                $product[ResourceInterface::PRICE_ADMINISTRATION_FEE] = $fee;
                if(isset($product[ResourceInterface::PRICE_ACTUAL])){
                    $product[ResourceInterface::PRICE_ACTUAL] += $fee;
                }
            }

            //Fee logic
            if($serviceId){
                if(isset($yields['service']['internal'][$serviceId]) && $yields['service']['internal'][$serviceId] > 0.0){
                    //Apply internal service yield
                    $product[ResourceInterface::PRICE_ACTUAL] = $product[ResourceInterface::PRICE_ACTUAL] * (1.0 + $yields['service']['internal'][$serviceId]);
                    $product[ResourceInterface::PRICE_YIELDED] = $product[ResourceInterface::PRICE_ACTUAL];
                }
            }

            if($yields['user'] > 0.0){
                //Apply product commission
                $product[ResourceInterface::PRICE_ACTUAL] = $product[ResourceInterface::PRICE_ACTUAL] * (1.0 + $yields['user']['commission']);
            }

            if($input->offsetExists(ResourceInterface::COSTFREE_CANCELLATION) && $input[ResourceInterface::COSTFREE_CANCELLATION] == true && $product[ResourceInterface::PRICE_COSTFREE_CANCELLATION] > 0){
                //Add the costfree cancellation
                $product[ResourceInterface::PRICE_ACTUAL] += $product[ResourceInterface::PRICE_COSTFREE_CANCELLATION];
            }

            if($embed ==false && isset($product[ResourceInterface::PRICE_ADMINISTRATION_FEE]) && $product[ResourceInterface::PRICE_ADMINISTRATION_FEE] == 0){
                $product[ResourceInterface::PRICE_ADMINISTRATION_FEE] = $fee;
                if(isset($product[ResourceInterface::PRICE_ACTUAL])){
                    $product[ResourceInterface::PRICE_ACTUAL] += $fee;
                }
            }

            if($input->offsetExists(ResourceInterface::AVAILABLE_OPTIONS)){
                $inputOptions = $input[ResourceInterface::AVAILABLE_OPTIONS];
                $optionsPriceTotal = 0;
                foreach ($product[ResourceInterface::OPTIONS] as $option) {
                    if (in_array($option[ResourceInterface::ID], $inputOptions)){
                        $optionsPriceTotal += $option[ResourceInterface::COST];
                    }
                }
                $product[ResourceInterface::PRICE_ACTUAL] += $optionsPriceTotal;
            }
            $products[] = $product;
        }

        $output->exchangeArray($products);
    }

    public function translateEmailFields(Resource $resource, ArrayObject $input, ArrayObject $output, $action, $id)
    {
        if( ! isset($input['website'])){
            return;
        }

        $website = Website::find($input['website']);

        // Only apply to know website, with Travel product type
        if( ! $website || $website->product_type_id != 47){
            return;
        }

        $language = array_get($website, 'language');

        $translatedOutput = [];
        foreach($output as $row){
            if($row[ResourceInterface::PRODUCT_TYPE] == 'travel' && $row[ResourceInterface::EVENT] == 'reservation.complete'){
                $row[ResourceInterface::SUBJECT]   = $this->translateInLanguage('travel.mail.order_thankyou.subject', $language);
                $row[ResourceInterface::FROM_NAME] = $this->translateInLanguage('travel.mail.order_thankyou.from_name', $language);
            }else if($row[ResourceInterface::PRODUCT_TYPE] == 'travel' && $row[ResourceInterface::EVENT] == 'order.cancel.success'){
                $row[ResourceInterface::SUBJECT]   = $this->translateInLanguage('travel.mail.order_canceled.subject', $language);
                $row[ResourceInterface::FROM_NAME] = $this->translateInLanguage('travel.mail.order_canceled.from_name', $language);
            }
            $translatedOutput[] = $row;
        }

        $output->exchangeArray($translatedOutput);
    }

    public function translateInLanguage($id, $language)
    {
        $defaultLanguage = App::getLocale();
        App::setLocale($language ? $language : 'nl');
        try{
            return Lang::get($id);
        }finally{
            App::setLocale($defaultLanguage);
        }
    }


    public function createProviderSettingsTravel(Resource $resource, ArrayObject $input, ArrayObject $data)
    {
        DocumentHelper::insert('providers_settings', 'travel',
            [
                ResourceInterface::__ID => $data['id'],
                ResourceInterface::ENABLED => array_get($input->getArrayCopy(), ResourceInterface::ENABLED),
                ResourceInterface::ACTIVE => array_get($input->getArrayCopy(), ResourceInterface::ACTIVE),
            ]
        );
    }

    public function createProductSettingsTravel(Resource $resource, ArrayObject $input, ArrayObject $data)
    {
        DocumentHelper::insert('product_settings', 'travel',
            [
                ResourceInterface::__ID => $data[ResourceInterface::__ID],
                ResourceInterface::TITLE => $data[ResourceInterface::TITLE],
                ResourceInterface::ENABLED => array_get($input->getArrayCopy(), ResourceInterface::ENABLED, true),
                ResourceInterface::ACTIVE => array_get($input->getArrayCopy(), ResourceInterface::ACTIVE, true),
            ]
        );
    }

    public function updateProductSettingsTravel(Resource $resource, ArrayObject $input, ArrayObject $data)
    {
        $newData = [
            ResourceInterface::TITLE => $data[ResourceInterface::TITLE],
        ];
        if (isset($input[ResourceInterface::ENABLED])) {
            $newData[ResourceInterface::ENABLED] = $input[ResourceInterface::ENABLED];
        }
        if (isset($input[ResourceInterface::ACTIVE])) {
            $newData[ResourceInterface::ACTIVE] = $input[ResourceInterface::ACTIVE];
        }

        $conditions = array_only($input->getArrayCopy(), [ResourceInterface::USER, ResourceInterface::WEBSITE]);

        DocumentHelper::update(
            'product_settings',
            'travel',
            $data[ResourceInterface::__ID],
            $newData,
            ['conditions' => $conditions]
        );
    }

    /**
     * form service is a service used to wrap the healthcare form
     */
    public function resendEmail(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if($action != 'index'){
            return;
        }
        $params = [
            ResourceInterface::PRODUCT_TYPE       => 'travel',
            ResourceInterface::EVENT              => 'reservation.complete',
            ResourceInterface::ORDER_ID           => $input->offsetGet(ResourceInterface::__ID),
            ResourceInterface::SEND               => true,
            ResourceInterface::TO_EMAIL_OVERWRITE => $input->offsetGet(ResourceInterface::EMAIL)
        ];
        $data   = ResourceHelper::callResource2('global.handle_email', $params);
        $output->exchangeArray($data);

    }

    private function getResourceSettings($index, $type, $conditions, $filter)
    {
        $settings = DocumentHelper::get($index, $type, [
            'filters'    => $filter,
            'conditions' => $conditions,
            'limit'      => 99999,
        ])->documents()->toArray();

        return array_combine(array_pluck($settings, ResourceInterface::__ID), $settings);
    }

    public function notifyTravelChange(Resource $resource, ArrayObject $input, ArrayObject $data)
    {
        // These resources influence `product.travel` results
        if (in_array($resource->name, ['options.travel', 'services.travel', 'company.travel'])) {
            ResourceHelper::callResource2('notify_applications.general', [
                ResourceInterface::RESOURCE => 'product.travel',
                ResourceInterface::ACTION => 'changed',
            ]);
        }
        // These resources are directly changed
        if (in_array($resource->name, ['product.travel', 'options.travel', 'services.travel', 'company.travel'])) {
            ResourceHelper::callResource2('notify_applications.general', [
                ResourceInterface::RESOURCE => $resource->name,
                ResourceInterface::ACTION => 'changed',
                ResourceInterface::ID => array_get($input->getArrayCopy(), ResourceInterface::__ID),
            ]);
        }


        // These resources may be overloaded per user/website
        if (in_array($resource->name, ['product_settings.travel', 'provider_settings.travel'])) {
            if (isset($input[ResourceInterface::USER])) {
                // Conditions (for enabled/active) per user
                ResourceHelper::callResource2('notify_applications.general', [
                    ResourceInterface::RESOURCE => 'user',
                    ResourceInterface::ACTION => 'changed',
                    ResourceInterface::ID => $input[ResourceInterface::USER],
                ]);
            }
            else if (isset($input[ResourceInterface::WEBSITE])) {
                // Conditions (for enabled/active) per website
                ResourceHelper::callResource2('notify_applications.general', [
                    ResourceInterface::RESOURCE => 'website',
                    ResourceInterface::ACTION => 'changed',
                    ResourceInterface::ID => $input[ResourceInterface::WEBSITE],
                ]);
            }
            else {
                // Has impact on all products
                ResourceHelper::callResource2('notify_applications.general', [
                    ResourceInterface::RESOURCE => 'product.travel',
                    ResourceInterface::ACTION => 'changed',
                ]);
            }
        }

        // Eloquent type changes
        if ($resource->name == 'website_rights.travel' && isset($input[ResourceInterface::WEBSITE_ID])) {
            ResourceHelper::callResource2('notify_applications.general', [
                ResourceInterface::RESOURCE => 'website',
                ResourceInterface::ACTION => 'changed',
                ResourceInterface::ID => $input[ResourceInterface::WEBSITE_ID],
            ]);
        }
        if ($resource->name == 'user_rights.travel' && isset($input[ResourceInterface::USER_ID])) {
            ResourceHelper::callResource2('notify_applications.general', [
                ResourceInterface::RESOURCE => 'user',
                ResourceInterface::ACTION => 'changed',
                ResourceInterface::ID => $input[ResourceInterface::USER_ID],
            ]);
        }
        if ($resource->name == 'websites.travel' && isset($input[ResourceInterface::WEBSITE_ID])) {
            ResourceHelper::callResource2('notify_applications.general', [
                ResourceInterface::RESOURCE => 'website',
                ResourceInterface::ACTION => 'changed',
                ResourceInterface::ID => $input[ResourceInterface::WEBSITE_ID],
            ]);
        }
    }

    /**
     * @param $filterField
     * @param $settings
     *
     * @return \Closure
     */
    private function addSettingsClosure($filterField, $settings)
    {
        return function ($product) use ($filterField, $settings) {

            if (!isset($product[$filterField]) || !isset($settings[$product[$filterField]])) {
                $product[ResourceInterface::ACTIVE]  = array_get($product, ResourceInterface::ACTIVE, true);
                $product[ResourceInterface::ENABLED] = array_get($product, ResourceInterface::ENABLED, true);

                return $product;
            }

            $product[ResourceInterface::ACTIVE]  = $settings[$product[$filterField]][ResourceInterface::ACTIVE];
            $product[ResourceInterface::ENABLED] = $settings[$product[$filterField]][ResourceInterface::ENABLED];

            return $product;
        };
    }

    /**
     * @param ArrayObject $input
     * @return array
     */
    public static function assembleYields(ArrayObject $input)
    {
        //Get all the service yields first
        $services = DB::connection('mysql_product')->table('services_travel')->get();
        $serviceYields = [];
        foreach ($services as $service){
            //Get the internal service yields for all the services
            if($service->internal_yield){
                $serviceYields['internal'][$service->__id] = floatval($service->internal_yield);
            }
            if($input->offsetExists(ResourceInterface::USER)){
                $userResult = DB::connection('mysql_product')->table('internal_yield_service_user_travel')
                    ->where('user_id', $input->offsetGet(ResourceInterface::USER))
                    ->where('service_id', $service->__id)
                    ->first();
                if($userResult){
                    $serviceYields['internal'][$service->__id] = floatval($userResult->percentage);
                }
            }
            $serviceIds[] = $service->__id;
        }

        //Get the user yield and commission
        $userCommission= 0.0;
        if($input->offsetExists(ResourceInterface::USER)){
            $userResult = DB::connection('mysql')->table('users')->where('id', $input->offsetGet(ResourceInterface::USER))->first();
            if($userResult->product_commission_reseller){
                $userCommission = floatval($userResult->product_commission_reseller);
            }
        }

        $result = [
            'service' => $serviceYields,
            'user' => $userCommission,
        ];
        return $result;
    }

    /**
     * @param $serviceName
     * @return int|null|string
     */
    private function findServiceByName($serviceName)
    {
        if(empty($this->processedServices)){
            //Process the service's translated labels to assist in searching
            if($this->services == null){
                $this->services = DB::connection('mysql_product')->table('services_travel')->get();
            }
            foreach ($this->services as $service){
                $labelTranslations = json_decode($service->label);
                foreach ($labelTranslations as $labelTranslation){
                    $this->processedServices[$service->__id][] = $labelTranslation->{'@value'};
                }
            }
        }

        foreach ($this->processedServices as $serviceId => $serviceLabels){
            if(in_array($serviceName, $serviceLabels)){
                //Found the service with the label
                return $serviceId;
            }
        }
        return null;
    }
}