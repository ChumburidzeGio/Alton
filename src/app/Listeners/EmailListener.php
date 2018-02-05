<?php

namespace App\Listeners\Resources2;

use App;
use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Models\Website;
use ArrayObject;
use Carbon\Carbon;
use Config;
use Event;
use Illuminate\Mail\Message;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Mail;
use View;

/**
 * Class EmailListener
 * @package App\Listeners\Resources2
 */
class EmailListener
{
    protected $action = 'mail';

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.global.email.process.input', [$this, 'createHash']);
        $events->listen('resource.global.email.process.after', [$this, 'handleEmail']);
        $events->listen('resource.global.handle_email.process.after', [$this, 'createAndEmailTemplate']);
        $events->listen('email.notify', [$this, 'emailNotify']);
    }


    public function createHash(Resource $resource, ArrayObject $input)
    {
        if($input->offsetExists(ResourceInterface::HASH) && (strlen($input->offsetGet(ResourceInterface::HASH)) > 10)){
            return;
        }

        if( ! $input->offsetExists(ResourceInterface::SESSION) || ! $input->offsetExists(ResourceInterface::WEBSITE)){
            return;
        }

        /**
         * Create hash based on website ID and session
         */
        $input->offsetSet(ResourceInterface::HASH, md5(json_encode($input->offsetGet(ResourceInterface::SESSION)) . '.' . $input->offsetGet(ResourceInterface::WEBSITE) . microtime()));
    }


    //md5(json_encode($session) . '.' . $config['website_id'] . microtime())

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     * @param $action
     * @param $id
     */

    public function handleEmail(Resource $resource, ArrayObject $input, ArrayObject $output, $action, $id)
    {
        $resendEmail = ($action == 'show' && ! empty($input['_resend']));

        if($action != 'store' && ! $resendEmail){
            return;
        }

        if($resendEmail){
            $input = $output->getArrayCopy();
        }else{
            $input = $input->getArrayCopy();
        }

        //handle block list
        $blocklist = ((app()->configure('mail')) ? '' : config('mail.blocklist'));
        $fallBack  = ((app()->configure('mail')) ? '' : config('mail.block_fallback'));
        foreach(['to_email', 'bcc_email', 'cc_email'] as $checkMailField){
            if(isset($input[$checkMailField]) && str_contains(strtolower($input[$checkMailField]), $blocklist)){
                cw('Blocked ' . $checkMailField . ': ' . $input[$checkMailField]);
                $input[$checkMailField] = isset($_ENV['DEV_EMAIL']) ? $_ENV['DEV_EMAIL'] : $fallBack;
            }
        }


        $fromEmail = array_get($input, 'from_email');
        $fromName  = array_get($input, 'from_name');
        $subject   = array_get($input, 'subject');
        $toName    = array_get($input, 'to_name');
        $content   = array_get($input, 'contents');
        $toEmail   = array_get($input, 'to_email');
        $bccEmail  = array_get($input, 'bcc_email');
        if(strpos($bccEmail, ',,') !== false){
            //The bcc email is borked set it to null
            Log::warning('EmailListener: Bcc email contains of only ,, : ' . json_encode($input));
            $bccEmail = null;
        }

        if(substr($bccEmail, - 1) == ','){
            Log::warning('EmailListener: Bcc email ends with , : ' . json_encode($input));
        }
        $bccEmail = trim($bccEmail, ',');


        $attachment = $this->handleAttachment($input);

        cw('Sending email from ' . $fromName . ' (' . $fromEmail . ') to ' . $toEmail . ' (' . $toEmail . ') bcc ' . $bccEmail);

        try{

            Mail::send('emails.email', compact('content'), function ($message) use ($input, $fromName, $fromEmail, $subject, $toEmail, $toName, $bccEmail, $attachment) {

                /** @var Message $message */
                $message->from($fromEmail, $fromName)->to($toEmail, $toName)->subject($subject);
                if($bccEmail){
                    $message->bcc(explode(',', $bccEmail));
                }
                if($attachment){
                    $message->attachData($attachment['data'], $attachment['name'], $attachment['options']);
                }
            });
        }catch(Exception $e){
            Log::error('Error in versturen e-mail: ' . $e->getMessage());
        }
    }

    /**
     * @param Array $input
     *
     */
    protected function handleAttachment(Array $input)
    {
        if( ! array_get($input, 'attachment')){
            return false;
        }

        if( ! array_get($input, 'data.data.csv')){
            return false;
        }

        $input       = array_get($input, 'data');
        $fields      = json_decode(array_get($input, 'data.csv'), true);
        $defaultName = sprintf('%s - %s', array_get($input, 'data.company.title', 'Export'), date('Y-m-d H:i:s'));
        $name        = array_get($input, 'csv_name', $defaultName) . '.csv';

        // The rows that need to be in the csv or excel file
        $rows[] = $this->getFieldData($fields, $input);

        $data    = $this->createFile($name, $rows, 'csv');
        $options = [];

        return compact('data', 'name', 'options');
    }

    /**
     * @param array $fields
     * @param array $input
     *
     * @return array
     */
    protected function getFieldData(Array $fields, Array $input)
    {
        return array_map(function ($key) use ($input) {

            // Filters are separated by a pipe
            $parts = explode('|', $key);

            // First get the value from the input.
            $value = array_get($input, $parts[0]);

            // We only need the filters, so skip index 0
            array_splice($parts, 0, 1);

            return $this->transform($value, $parts);

        }, $fields);
    }

    /**
     * @param $value
     * @param array $filters
     *
     * @return mixed
     */
    protected function transform($value, Array $filters = [])
    {
        // If there are no filters, then there is nothing to transform
        if( ! $filters){
            return $value;
        }

        foreach($filters as $part){

            // The filter name is always the first item in the comma separated list
            $params = explode(',', $part);
            $filter = $params[0];

            // Extract the params needed for the filter.
            // They are the remaining items in the comma separated list
            array_splice($params, 0, 1);

            // This is the transformed value
            $value = call_user_func_array([$this, 'filter'], [$filter, $value, $params]);
        }

        return $value;
    }

    /**
     * @param $type
     * @param $value
     * @param $params
     *
     * @return mixed
     */
    public function filter($type, $value, $params)
    {
        switch($type){

            case 'if':
                return $value == $params[0] ? $params[1] : $params[2];

            case 'day':
            case 'week':
            case 'month':
            case 'year':
                $format = isset($param[0]) ? $params[0] : 'Y-m-d';
                $date   = Carbon::createFromFormat($format, $value);
                return $date->{$type};

            default:
                return $value;
        }
    }

    /**
     * @param $name
     * @param array $fields
     * @param array $data
     * @param string $format
     *
     * @return mixed
     */
    protected function createFile($name, Array $data, $format = 'csv')
    {
        return Excel::create($name, function (LaravelExcelWriter $excel) use ($data) {
            $excel->sheet('Sheet1', function ($sheet) use ($data) {
                $sheet->fromArray($data);
            });
        })->string('csv');
    }

    public function emailNotify($productType, $event, $orderId = null, $websiteId = null, $userId = null, $mail = null)
    {
        cw('Notify email for product type:' . $productType . ' event: ' . $event);
        $resource = Resource::where('name', 'global.handle_email')->firstOrFail();
        $params   = [ResourceInterface::PRODUCT_TYPE => $productType, ResourceInterface::EVENT => $event, ResourceInterface::ORDER_ID => $orderId, ResourceInterface::SEND => true];
        //, ResourceInterface::WEBSITE => $websiteId];
        if($userId){
            $params[ResourceInterface::USER] = $userId;
        }
        if($websiteId){
            $params[ResourceInterface::WEBSITE] = $websiteId;
        }
        if($mail){
            $params[ResourceInterface::TO_EMAIL_OVERWRITE] = $mail;
        }
        cw('http://api.komparu.' . App\Helpers\WebsiteHelper::tld() . '/v1/resource2/global.handle_email/data?' . http_build_query($params));
        ResourceHelper::call($resource, 'index', $params);

    }

    public function createAndEmailTemplate(Resource $resource, ArrayObject $input, ArrayObject $output, $action, $id)
    {
        if( ! $output->count()){
            cw('Nothing found to notify');
            return;
        }
        /*
         * Check inputs
         */

        if( ! isset($input[ResourceInterface::PRODUCT_TYPE], $input[ResourceInterface::ORDER_ID])){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'global.handle_email',
                    "message" => 'Geen order id, website en product type meegegeven',
                    "type"    => 'input'
                ]
            ]);
        }

        $productType = $input[ResourceInterface::PRODUCT_TYPE];
        $params      = $output->getArrayCopy();
        if( ! isset($params[0])){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'global.handle_email',
                    "message" => 'Geen parameters gevonden voor ' . $productType,
                    "type"    => 'input'
                ]
            ]);
        }
        $params = $params[0];


        /**
         * Get order
         */
        try{
            $order = DocumentHelper::show('order', $productType, $input[ResourceInterface::ORDER_ID]);
        }catch(\Exception $e){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'global.handle_email',
                    "message" => 'Order ID niet gevonden: ' . $input[ResourceInterface::ORDER_ID] . '  voor type ' . $productType,
                    "type"    => 'input'
                ]
            ]);
        }

        if( ! isset($input[ResourceInterface::WEBSITE])){
            if(isset($order[ResourceInterface::WEBSITE]) && $order[ResourceInterface::WEBSITE]){
                $input[ResourceInterface::WEBSITE] = $order[ResourceInterface::WEBSITE];
            }else if(isset($order[ResourceInterface::WEBSITE_ID]) && $order[ResourceInterface::WEBSITE_ID]){
                $input[ResourceInterface::WEBSITE] = $order[ResourceInterface::WEBSITE_ID];
            }else{
                throw new ResourceError($resource, $input->getArrayCopy(), [
                    [
                        "code"    => 'global.handle_email',
                        "message" => 'No website or website id specified or available in the order',
                        "type"    => 'input'
                    ]
                ]);
            }
        }


        $config = Website::find($input[ResourceInterface::WEBSITE]);

        if( ! $config){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'global.handle_email',
                    "message" => 'Ongeldige website ID ' . $input[ResourceInterface::WEBSITE],
                    "type"    => 'input'
                ]
            ]);
        }



        if($input->offsetExists(ResourceInterface::TO_EMAIL_OVERWRITE)){
            cw('Overwrite to email! ' . ResourceInterface::TO_EMAIL_OVERWRITE);
            $params[ResourceInterface::TO_EMAIL] = $input->offsetGet(ResourceInterface::TO_EMAIL_OVERWRITE);
        }

        /**
         * Congif tst mode
         */
        if(Config::get('TEST_MODE')){
            $params[ResourceInterface::TO_EMAIL]  = $params[ResourceInterface::TEST_EMAIL];
            $params[ResourceInterface::BCC_EMAIL] = $params[ResourceInterface::TEST_EMAIL];
        }
        unset($params[ResourceInterface::TEST_EMAIL]);


        if( ! isset($order[ResourceInterface::PRODUCT])){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'global.handle_email',
                    "message" => 'No product data for order with ID ' . $input[ResourceInterface::ORDER_ID],
                    "type"    => 'input'
                ]
            ]);
        }

        if(gettype($order[ResourceInterface::PRODUCT]) === 'string'){
            $product = json_decode($order[ResourceInterface::PRODUCT], true);
        }else{
            $product = $order[ResourceInterface::PRODUCT];
        }

        //copy rest to product as well.
        foreach($order->data() as $orderField => $orderValue){
            if(isset($product[$orderField])){
                continue;
            }
            $product[$orderField] = $orderValue;
        }


        if(gettype($order[ResourceInterface::SESSION]) === 'string'){
            $session = json_decode($order[ResourceInterface::SESSION], true);
        }else{
            $session = $order[ResourceInterface::SESSION];
        }

        //new structure: session devided by pages
        if(isset($session['thankyou']) && is_array($session['thankyou'])){
            $session = $session['thankyou'];
        }

        if( ! $session){
            $session = [];
        }

        if( ! $product){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'global.handle_email',
                    "message" => 'Invalid product for order with ID ' . $input[ResourceInterface::ORDER_ID],
                    "type"    => 'input'
                ]
            ]);
        }


        /**
         * Prak session together
         */
        $sessionMerge = $this->sessionPrakker($session, $productType);


        //copy reference if present
        if(isset($session['__reference'])){
            $sessionMerge['__reference'] = $session['__reference'];
        }

        $style = DocumentHelper::show('style', $config['template']['name'], 1,  ['conditions' => [ResourceInterface::WEBSITE => $input[ResourceInterface::WEBSITE], ResourceInterface::USER => $config['user']['id']]], true);

        if ($style && array_has($style->toArray(),'primary.color')) {
            $sessionMerge['primary_color'] = array_get($style->toArray(),'primary.color');
        }




        $replaceArrayObj = new ArrayObject($sessionMerge);
        Event::fire('email.' . $config['product_type']['name'] . '.process.session', [$replaceArrayObj]);
        $sessionMerge = $replaceArrayObj->getArrayCopy();


        /**
         * Create website data as dot array. Also contains website user and website product type.
         * (ex 'website.name', 'website.user.email', 'website.product_type.title')
         */
        $websiteData = array_dot(['website' => $config->toArray()]);

        /**
         * fill the params
         */
        $params = $this->fillParamsFromSessionAndProduct($params, $sessionMerge, $product, $websiteData);


        /**
         * fill the params second time, to fill placeholders in placeholders...
         */
        $params = $this->fillParamsFromSessionAndProduct($params, $sessionMerge, $product, $websiteData);


        $output->exchangeArray($params);

        /**
         * Send actual email
         */
        $params['contents']                 = $this->makeHtml($sessionMerge, $config, $params, $product, $order->toArray());
        $params['view']                     = 'email';
        $params['session']                  = $session;
        $params[ResourceInterface::WEBSITE] = $input[ResourceInterface::WEBSITE];
        $params[ResourceInterface::USER]    = $config['user']['id'];


        //$params['website'] = $
        //Send email
        $emailResource = Resource::where('name', 'global.email')->firstOrFail();

        // show result in output anyway
        $output->offsetSet('text', $params['contents']);
        if(isset($input[ResourceInterface::SEND]) && $input[ResourceInterface::SEND]){
            ResourceHelper::call($emailResource, 'store', array_except($params, ['__index', '__id', '__type']));
        }
    }


    /**
     * @param $session
     * @param $productType
     *
     * @return array|mixed
     */
    private function sessionPrakker($session, $productType)
    {
        //legacy: no sub pages
        if(array_has($session, 'package.' . $productType) || array_has($session, 'product.' . $productType) || array_has($session, 'contract.' . $productType)){
            return call_user_func_array('array_merge', array_map(function ($prefix) use ($session, $productType) {
                return array_get($session, $prefix . '.' . $productType, []);
            }, ['package', 'product', 'contract']));
        }
        $return = [];
        foreach($session as $page => $sessionArray){
            if( ! is_array($sessionArray)){
                continue;
            }

            foreach($sessionArray as $resourceName => $resourceMap){
                if( ! is_array($resourceMap)){
                    continue;
                }
                $resourceMap = array_dot($resourceMap);
                foreach($resourceMap as $key => $val){
                    if( ! isset($return[$key])){
                        $return[$key] = $val;
                    }
                }
            }
        }
        return $return;
    }

    protected function getDataArray($session, $config, $params, $data, $array = [])
    {
        return array_merge([
            'params'  => $params,
            'session' => $session,
            'config'  => $config,
            'data'    => $data,
        ], $array);
    }


    private function makeHtml($session, $config, $params, $data, $order)
    {
        $defaultLanguage = App::getLocale();
        App::setLocale(array_get($config, 'language', 'nl'));

        // hack for verzekering.nl
        if(isset($session['years_without_damage']) && $session['years_without_damage'] < 0 && $config['user_id'] == 2125){
            $view = View::make($config['template']['name'] . '.' . $this->action . '.' . 'template_verzekeringnl', $this->getDataArray($session, $config, $params, $data, [
                'logo' => $this->getLogo($config, $data),
                'text' => $this->processText($session, $config, $params, $data, $order)
            ]))->render();
        }else{

            $view = View::make($config['template']['name'] . '.' . $this->action . '.' . $params['template'], $this->getDataArray($session, $config, $params, $data, [
                'view' => $this->makeView($session, $config, $params, $data, $order),
                'logo' => $this->getLogo($config, $data),
                'text' => $this->processText($session, $config, $params, $data, $order)
            ]))->render();
        }
        App::setLocale($defaultLanguage);

        return $view;
    }

    private function makeView($session, $config, $params, $data, $order)
    {
//        cw('show product');
//        cw($this->getDataArray($session, $config->toArray(), $params, $data, $order));
        return View::make($config['template']['name'] . '.' . $this->action . '.' . $params['view'], $this->getDataArray($session, $config->toArray(), $params, $data))->render();
    }

    /**
     * Get logo
     *
     */
    private function getLogo($config, $data)
    {
        if(array_get($data, 'polis.own_funnel') !== null && array_get($config, 'logo') !== null && array_get($config, 'logo') != ''){
            return $config['logo'];
        }
        return 'https://www.komparu.com/images/komparu-verzekeringen.png';
    }

    /**
     * Process the text
     */
    private function processText($session, $config, $params, $data, $order)
    {
        if($params['view'] === 'remember'){
            return "";
        }

        $replaceArrayObj = new ArrayObject();
        Event::fire('email.' . $config['product_type']['name'] . '.process.text', [$replaceArrayObj, $config, $session, $data, $order]);
        $replaceArray = $replaceArrayObj->getArrayCopy();

        // check params for conditions if so add to the replaceArray
        foreach($params as $k => $v){
            if(ends_with($k, '.condition')){
                $name       = str_replace('.condition', '', $k);
                $conditions = explode(',', $v);
                $value      = true;
                foreach($conditions as $c){
                    if(isset($session[$c]) && $session[$c] === "1"){
                        $value = false;
                    }
                }


                // set a default value (empty) just to be sure, set it as first so the other placeholders work
                $replaceArray = array_merge(['{' . $name . '}' => ''], $replaceArray);

                if($value){
                    if(isset($params[$k . '.true'])){
                        $replaceArray['{' . $name . '}'] = $params[$k . '.true'];
                    }
                }else{
                    if(isset($params[$k . '.false'])){
                        $replaceArray['{' . $name . '}'] = $params[$k . '.false'];
                    }
                }
            }
        }

        foreach($replaceArray as $k => $v){
            if(is_array($v)){
                $replaceArray[$k] = implode(',', $v);
            }
        }

        return $this->replaceArray($replaceArray, array_get($params, 'text', ''));

    }

    /**
     * Helper to replace whole array
     */
    protected function replaceArray($array = [], $text = '')
    {
        foreach($array as $k => $v){
            $text = str_replace($k, $v, $text);
        }
        return $text;
    }

    /**
     * @param $params
     * @param $sessionMerge
     * @param $product
     *
     * @return mixed
     */
    private function fillParamsFromSessionAndProduct($params, $sessionMerge, $product, $websiteData)
    {
//        cw('show session');
//        cw($sessionMerge);
        foreach($params as $key => $value){
            if(strpos($value, '{{') !== false){
                $params[$key] = preg_replace_callback("/{{([a-zA-Z0-9-_.]*)(?:\\?(.*))?}}/mi", function ($matches) use ($sessionMerge, $product, $websiteData) {
                    if(array_has($sessionMerge, $matches[1])){
                        return array_get($sessionMerge, $matches[1]);
                    }elseif(array_has($product, $matches[1])){
                        return array_get($product, $matches[1]);
                    }elseif(array_has($websiteData, $matches[1])){
                        return array_get($websiteData, $matches[1]);
                    }elseif(isset($matches[2])){
                        return $matches[2];
                    }
                    return '';
                }, $value);
            }
        }
        return $params;
    }


}