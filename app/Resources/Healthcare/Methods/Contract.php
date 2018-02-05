<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Healthcare\Methods;

use App\Exception\ServiceError;
use App\Helpers\Healthcare2018Helper;
use App\Helpers\IAKHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Models\Sale;
use App\Resources\Healthcare\BasicAuthRequest;
use Config, Cache, Log, Event, DB;
use GuzzleHttp\Exception\RequestException;


class Contract extends BasicAuthRequest
{
    const MENZIS_COMPANY_ID = '202736';


    protected $cacheDays = false;

    const VALID_ZORGWEB_INPUT = ['aanvrager', 'partner', 'kinderen', 'verzekeringsgegevens', 'hoofdadres', 'verzekerden', 'zorgvragenMap', 'waardes', 'aanbieder'];

    const PERSONMAP = [
        'applicant'         => 'aanvrager',
        'applicant_partner' => 'partner',
        'child'             => 'kinderen',
        'aanvrager'         => 'applicant',
        'partner'           => 'applicant_partner',
        'kinderen'          => 'child'

    ];

    private $initParams;
    private $pushIt = false;
    private $skipZorgwebOrder = false;
    protected $jsonCall;

    private $clickId = null;


    public function __construct()
    {
        // The actual method we are calling is determined in setParams()
        $this->basicAuthService = [
            'type_request' => 'post_json_no_auth',
            'method_url'   => ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.url')) . '/aanvraag/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/',
            'username'     => ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.username')),
            'password'     => ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.password'))
        ];

        $this->funnelRequest        = true;
        $this->resource2Request     = true;
        $this->strictStandardFields = false;
        $this->defaultParamsFilter  = ['kinderen'];
    }

    public function setParams(Array $params)
    {
        if(isset($params[ResourceInterface::CLICK_ID])){
            $this->clickId = $params[ResourceInterface::CLICK_ID];
        }
        $this->initParams       = $params; //$this->setCurrentlyInsuredChildren($params);
        $this->skipZorgwebOrder = (array_get($params, 'product_data.company.id') == self::MENZIS_COMPANY_ID);
        $zorgwebParams          = $this->createZorgwebParams($params);

        parent::setParams($zorgwebParams);
    }


    public function executeFunction()
    {
        $products               = [];
        $productTitleArray      = [];
        $productIdArray         = [];
        $zorgwebRequesterParams = array_only($this->initParams, self::PERSONS);
        $otherParams            = array_except($this->params, self::PERSONS);
        $redirect               = false;
        $redirectProductId      = null;
        $zorgwebNecessary       = true;
        $alreadyInsuredPersons  = [];

        $usedBsnNummers = [];

        foreach(self::PERSONS as $personName){
            cw($personName . ' -> START');
            if( ! array_get($this->initParams, $personName . '.' . ResourceInterface::PRODUCT_ID)){
                cw($personName . ' - No product id');
                unset($zorgwebRequesterParams[$personName]);
                continue;
            }
            if(Healthcare2018Helper::isCurrentlyInsured($this->initParams, $personName)){
                //Save here all the parameters necessary for the email to iak
                cw($personName . ' - Already Insured');
                $alreadyInsuredPersons[] = $personName; //array_merge(array_get($this->initParams,$personName), array_get($this->initParams,Healthcare2018Helper::MEMBER_MAPPING[$personName]));
            }


            if( ! Healthcare2018Helper::shouldGoToZorgweb($this->initParams, $personName)){
                cw($personName . ' - should not go to zorgweb');
                if( ! Healthcare2018Helper::isCurrentlyInsured($this->initParams, $personName)){
                    //Only redirect to IAK if the person is not currently insured
                    cw($personName . ' - will be redirected to IAK');
                    $redirect = true;
                }
                if($personName === 'applicant'){
                    //The applicant is IAK so we need to remove him from there and move up the partner to be applicant
                    if(Healthcare2018Helper::shouldGoToZorgweb($this->initParams, 'applicant_partner')){
                        cw('applicant is not going to zorgweb, but partner is, need to swap');
                        $zorgwebRequesterParams['applicant'] = $zorgwebRequesterParams['applicant_partner'];
                        unset($zorgwebRequesterParams['applicant_partner']);

                        //HOTFIX
                        //if children are insured with the applicant, remove them too, otherwise Zorgweb brutally fucked

                        $removedChildren = [];
                        for($childcount = 0; $childcount < 9; $childcount ++){
                            $key = 'child' . $childcount;
                            if( ! isset($zorgwebRequesterParams[$key])){
                                continue;
                            }
                            if( ! isset($zorgwebRequesterParams[$key]['insure_with']) || $zorgwebRequesterParams[$key]['insure_with'] != 'applicant'){
                                continue;
                            }
                            unset($zorgwebRequesterParams[$key]);
                            $removedChildren[] = $childcount;
                        }


                        //remove also from other params
                        $otherParams['verzekerden']['aanvrager'] = $otherParams['verzekerden']['partner'];
                        unset($otherParams['verzekerden']['partner']);

                        //remove the children too
                        foreach($removedChildren as $removedChild){
                            if(isset($otherParams['verzekerden'], $otherParams['verzekerden']['kinderen'], $otherParams['verzekerden']['kinderen'][$removedChild])){
                                unset($otherParams['verzekerden']['kinderen'][$removedChild]);
                            }
                        }
                        //cleanup the holder if empty
                        if(isset($otherParams['verzekerden']['kinderen']) && empty($otherParams['verzekerden']['kinderen'])){
                            unset($otherParams['verzekerden']['kinderen']);
                        }


                        $waardes = [];

                        $partnerExists = false;
                        foreach($otherParams['waardes'] as $waarde){
                            if(str_contains($waarde['property'], 'aanvrager.')){
                                continue;
                            }
                            if(str_contains($waarde['property'], 'partner.')){
                                $waarde['property'] = str_replace('partner.', 'aanvrager.', $waarde['property']);
                                $waardes[]          = $waarde;
                                $partnerExists      = true;
                                continue;
                            }
                            $waardes[] = $waarde;
                        }
                        if($partnerExists){
                            $otherParams['waardes'] = $waardes;
                        }

                    }else{
                        //Just unset the applicant
                        unset($zorgwebRequesterParams[$personName]);
                    }
                }else{
                    //This is the normal case where somebody other than an applicant has IAK.
                    //Just unset the corresponding $zorgwebParams entry
                    cw('unsetting ' . $personName);
                    unset($zorgwebRequesterParams[$personName]);
                    $personToBeRemoved = array_get(Healthcare2018Helper::MEMBER_MAPPING, $personName);
                    if($personToBeRemoved){
                        cw('unsetting verzekerden ' . $personToBeRemoved);
                        unset($otherParams['verzekerden'][$personToBeRemoved]);
                        foreach($otherParams['waardes'] as $waarde){
                            if(str_contains($waarde['property'], $personToBeRemoved . '.')){
                                continue;
                            }
                            $waardes[] = $waarde;
                        }
                        $otherParams['waardes'] = $waardes;
                    }

                }
                $redirectProductId = array_get($this->initParams, $personName . '.' . ResourceInterface::PRODUCT_ID);
            }else{
                //check on bsn
                $zorgwebKey = Healthcare2018Helper::MEMBER_MAPPING[$personName];
                if($zorgwebKey){
                    $burgerServiceNr  = array_get($this->initParams, $zorgwebKey . '.burgerservicenummerString');
                    if(in_array($burgerServiceNr, $usedBsnNummers)) {
                        $this->addErrorMessage($zorgwebKey . '.burgerservicenummerString', 'zorgweb.' . $zorgwebKey . '.burgerservicenummerString', 'U kunt niet 2 keer het zelfde BSN nummer gebruiken', 'input');
                        return;
                    }
                    $usedBsnNummers[] = $burgerServiceNr;
                }

                cw($personName . ' - should go to zorgweb');
            }

            $prodStruct = Healthcare2018Helper::getProductStructure(array_get($this->initParams, $personName . '.' . ResourceInterface::PRODUCT_ID), array_get($this->initParams, $personName . '.' . ResourceInterface::OWN_RISK),
                array_get($this->initParams, $personName . '.' . ResourceInterface::BIRTHDATE), array_get($this->initParams, $personName . '.' . ResourceInterface::USER), array_get($this->initParams, $personName . '.' . ResourceInterface::WEBSITE),
                array_get($this->initParams, ResourceInterface::COLLECTIVITY_ID), ['hide_free_toppings' => true]);
            if( ! ($prodStruct)){
                $this->setErrorString('Product could not be found for ' . $personName . ':' . array_get($this->initParams, $personName . '.' . ResourceInterface::PRODUCT_ID));
                return;
            }

            $collectivityProduct = head(DB::connection('mysql_product')->table('collectivity_products_healthcare2018')->where('product_id', $prodStruct['total_product']['base_id'])->where(ResourceInterface::COLLECTIVITY_ID,
                array_get($this->initParams, ResourceInterface::COLLECTIVITY_ID))->get());
            if($collectivityProduct){
                $prodStruct['collectivity_for_zorgweb'] = $collectivityProduct->provider_collectivity_id;
            }
            $products['products'][$personName] = $prodStruct;

            $productTitleArray[] = $prodStruct['total_product']['title'];
            $productIdArray[]    = $prodStruct['total_product']['__id'];
        }

        cw('all params');
        cw($zorgwebRequesterParams);
        cw($otherParams);


        //Create the pending order
        $orderData = [
            ResourceInterface::USER       => array_get($this->initParams, ResourceInterface::USER),
            ResourceInterface::WEBSITE    => array_get($this->initParams, ResourceInterface::WEBSITE),
            ResourceInterface::IP         => array_get($this->initParams, ResourceInterface::IP),
            ResourceInterface::SESSION_ID => array_get($this->initParams, ResourceInterface::SESSION_ID),
            ResourceInterface::PRODUCT_ID => array_get($this->initParams, ResourceInterface::PRODUCT_ID),
            ResourceInterface::PRODUCT    => json_encode($products),
            ResourceInterface::SESSION    => array_get($this->initParams, ResourceInterface::SESSION),
            ResourceInterface::STATUS     => ['PENDING'],
            ResourceInterface::REQUEST    => json_encode($this->params),
        ];
        $order     = ResourceHelper::callResource2('order.healthcare2018', $orderData, RestListener::ACTION_STORE);


        // Validate if our form answers are actually valid
        try{

            if( ! empty($zorgwebRequesterParams)){
                $array_merge = array_merge($zorgwebRequesterParams, $otherParams);
                cw('merge it');
                cw($array_merge);
                $formResult = ResourceHelper::callResource2('submit_form.healthcare2018', $array_merge);
            }else{
                //You do not have any persons that need to go to Zorgweb
                $zorgwebNecessary = false;
            }


            if( ! empty($alreadyInsuredPersons)){
                //You have currently insured persons that you need to mail to iak
                //TODO: UPDATE THE ORDER STATUS TO REFLECT THE CURRENTLY INSURED
                cw('Zorgwebmails');
                cw($alreadyInsuredPersons);

                $orderUpdateData = [
                    'zorgweb_order_id'        => $this->result['aanvraagNummer'],
                    'xml'                     => 'n/a: currentlyinsured',
                    ResourceInterface::STATUS => ['SUCCESS'],
                ];
                $order           = ResourceHelper::callResource2('order.healthcare2018', $orderUpdateData, RestListener::ACTION_UPDATE, $order[ResourceInterface::__ID]);

                if($this->clickId){
                    $this->createSale($order, $productIdArray, $productTitleArray);
                }

                Event::fire('email.notify', ['healthcare2018', 'order.status.currently_insured_success', $order['__id'], $orderData[ResourceInterface::WEBSITE]]);
            }

        }catch(\Exception $e){
            $orderUpdateData = [
                'error'                   => $e->getMessage(),
                ResourceInterface::STATUS => ['ERROR'],
            ];
            ResourceHelper::callResource2('order.healthcare2018', $orderUpdateData, RestListener::ACTION_UPDATE, $order[ResourceInterface::__ID]);
            $this->setErrorString('Unknown error calling `submit_form`: ' . $e->getMessage());
            return;
        }

        if($zorgwebNecessary){
            // We got an error in the form questions?
            if( ! isset($formResult['offerteAanvraagXml'])){
                // If there are any invalid questions, mark the order as an 'error', and add errors to order
                $errors = [];
                if(isset($formResult['ongeldigeVragen'])){
                    foreach($formResult['ongeldigeVragen'] as $key){
                        $inValid  = $this->getVraag($formResult['vragen'], $key);
                        $errors[] = ['name' => $key, 'msgkey' => $inValid['validatieMessageKey'], 'msg' => $inValid['validatieMessage'], 'input' => array_get($this->initParams, $key, 'no input')];
                    }
                }
                $orderUpdateData = [
                    'error'                   => json_encode($errors),
                    ResourceInterface::STATUS => ['ERROR'],
                ];

                ResourceHelper::callResource2('order.healthcare2018', $orderUpdateData, RestListener::ACTION_UPDATE, $order[ResourceInterface::__ID]);

                // Set resource errors
                if(isset($formResult['ongeldigeVragen'])){
                    foreach($formResult['ongeldigeVragen'] as $key){
                        //hack for invalid postcode/huisner
                        if($key == 'hoofdadres'){
                            $this->addErrorMessage('hoofdadres.postcode', 'invalid', 'Combinatie postcode/huisnummer bestaat niet');
                            $this->addErrorMessage('hoofdadres.huisnummer', 'invalid', '');
                            continue;
                        }

                        $inValid = $this->getVraag($formResult['vragen'], $key);
                        $this->addErrorMessage($key, 'zorgweb.' . $inValid['validatieMessageKey'], $inValid['validatieMessage'], 'input');
                    }
                    $this->result = ['error' => $this->getErrorString()];

                }


                return;
            }
            // Zorgweb warnings
            // TODO: activate this
            if(isset($formResult['warnings'])){
                $this->setErrorString('Zorgweb warnings: ' . json_encode($formResult['warnings']));
                $orderUpdateData = [
                    'error'                   => json_encode(['result' => $this->result, 'error_string' => $this->getErrorString()]),
                    ResourceInterface::STATUS => ['ERROR'],
                ];
                ResourceHelper::callResource2('order.healthcare2018', $orderUpdateData, RestListener::ACTION_UPDATE, $order[ResourceInterface::__ID]);

                $this->result = ['error' => $this->getErrorString()];
                return;
            }
            // Fire the zorgweb request to 'aanvraag'
            if($this->shouldFireZorgwebAanvrag($zorgwebRequesterParams)){
                $toSendParams = (array_only(array_merge($zorgwebRequesterParams, $otherParams), self::VALID_ZORGWEB_INPUT));
                $this->postToZorgweb($toSendParams);
            }


            if(isset($this->result['aanvraagNummer'], $this->result['verzondenXml'])){
                // We have a successful 'aanvraag, update the order to reflect successful call
                $orderUpdateData = [
                    'zorgweb_order_id'        => $this->result['aanvraagNummer'],
                    'xml'                     => $this->result['verzondenXml'],
                    ResourceInterface::STATUS => ['SUCCESS'],
                ];
                $order           = ResourceHelper::callResource2('order.healthcare2018', $orderUpdateData, RestListener::ACTION_UPDATE, $order[ResourceInterface::__ID]);

                $this->result = [
                    'status'           => 'success',
                    'redirect'         => $redirect,
                    'product_id'       => $redirectProductId,
                    'xml'              => $this->result['verzondenXml'],
                    'order_id'         => $order[ResourceInterface::__ID],
                    'zorgweb_order_id' => $this->result['aanvraagNummer'],
                ];
                if($this->clickId){
                    $this->createSale($order, $productIdArray, $productTitleArray);
                }

            }else{
                $orderUpdateData = [
                    'error'                   => json_encode(['result' => $this->result, 'error_string' => $this->getErrorString()]),
                    ResourceInterface::STATUS => ['ERROR'],
                ];
                ResourceHelper::callResource2('order.healthcare2018', $orderUpdateData, RestListener::ACTION_UPDATE, $order[ResourceInterface::__ID]);

                $this->result = ['error' => $this->getErrorString()];
            }
        }else{
            //No zorgweb calls were made
            //This means either all the persons are iak so they should redirect
            //or that all persons are currently insured so iak has been notified
            $orderUpdateData = [
                'redirect'                => $redirect,
                ResourceInterface::STATUS => ['SUCCESS'],
            ];
            if(isset($formResult['offerteAanvraagXml'])){
                $orderUpdateData['xml'] = $formResult['offerteAanvraagXml'];
            }
            $order        = ResourceHelper::callResource2('order.healthcare2018', $orderUpdateData, RestListener::ACTION_UPDATE, $order[ResourceInterface::__ID]);
            $this->result = $orderUpdateData;
        }


        //Send the email
        if(array_get($this->result, 'status') === 'success'){
            Event::fire('email.notify', ['healthcare2018', 'order.status.success', $order['__id'], $orderData[ResourceInterface::WEBSITE]]);
            Event::fire('email.notify', ['healthcare2018', 'order.status.success2', $order['__id'], $orderData[ResourceInterface::WEBSITE]]);
        }
    }

    private function setCurrentlyInsuredChildren(Array $params)
    {
        if(isset($params['kinderen'])){
            //Move the children out of zorgweb because iak will deal with it
            foreach($params['kinderen'] as $index => $child){
                $params['child' . $index][ResourceInterface::CURRENTLY_INSURED] = true;
            }
        }

        return $params;
    }

    private function getVraag($vragen, $key)
    {
        foreach($vragen as $vraag){
            if(array_get($vraag, 'property') == $key){
                return $vraag;
            }
        }
        return null;
    }

    private function postToZorgweb($params)
    {
        $client = $this->getHttpClient();
        try{
            $options = [
                'headers' => ['Accept' => 'application/json'],
                'json'    => $params
            ];

            if( ! empty($this->basicAuthService['headers'])){
                $options['headers'] = array_merge($options['headers'], $this->basicAuthService['headers']);
            }

            $this->response = $client->post($this->basicAuthService['method_url'], $options);
            $this->result   = json_decode($this->response->getBody()->getContents(), true);
        }catch(RequestException $e){
            $this->handleError($this->parseResponseError($e->getResponse()->getBody()->getContents(), $e));
        }
    }

    /**
     * Checks if there is a person in the parameters that has a Zorgweb product
     * and thus a zorgweb aanvrag must be fired
     *
     * @param $params
     *
     * @return bool
     */
    private function shouldFireZorgwebAanvrag($params)
    {
        foreach(self::PERSONS as $personName){
            if($this->shouldGoToZorgweb($params, $personName)){
                return true;
            }
        }
        return false;
    }

    /**
     * Check if there is a person in the parameters that has a Zorgweb product
     *
     * @param $params
     * @param $personName
     *
     * @return bool
     */
    private function shouldGoToZorgweb($params, $personName)
    {
        if(substr(array_get($params, $personName . '.' . ResourceInterface::PRODUCT_ID, ''), 0, 1) === 'H'){
            return true;
        }
        return false;
    }

    /**
     * Map our internal parameters to the ones that zorgweb expects in the call
     *
     * @param $params
     *
     * @return array
     */
    private function createZorgwebParams($params)
    {
        $zorgwebParams = $this->createZorgwebFormParams($params);

        // Filter only allowed
        $params = array_only($params, self::VALID_ZORGWEB_INPUT);

        // Apply some generic input processing
        $params = $this->processZorgwebFormInputs($params);
        if($this->hasErrors()){
            return [];
        }

        // Set the dynamic input 'waardes'
        $params                   = array_dot($params);
        $zorgwebParams['waardes'] = [];
        foreach($params as $key => $val){
            $zorgwebParams['waardes'][] = ['property' => $key, 'waarde' => $val];
        }
        return $zorgwebParams;
    }

    /**
     * @param $order
     * @param $productIdArray
     * @param $productTitleArray
     */
    private function createSale($order, $productIdArray, $productTitleArray)
    {
        if( ! Config::get('IAK2018_SALE_CREATED', true)){
            return;
        }
        $sale = [
            'click_id'       => $this->clickId,
            'url_identifier' => IAKHelper::IAK_URL_IDENTIFIER,
            'network'        => 'healthcare2018-funnel',
            'order_uid'      => uniqid($order[ResourceInterface::__ID] . '-'),
            'total'          => 0,
            'commission'     => 0,
            'status'         => 'open',
            'sku'            => implode(",", $productIdArray),           // product name
            'store_name'     => 'IAK Funnel',
            'product_name'   => implode(",", $productTitleArray),           // product name
            'lead_time'      => date('Y-m-d H:i:s'),
        ];
        $sale = Sale::create($sale);
        Config::set('IAK2018_SALE_CREATED', true);
    }

}