<?php

namespace App\Listeners\Resources2;


use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Helpers\ResourceHelper;
use App\Models\Website;
use ArrayObject;
use DateTime;
use Input;

/**
 * Class LegalexpensesinsuranceListener
 * @package App\Listeners\Resources2
 */
class ProductListener
{
    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.collection.after', [$this, 'filterConditions']);

        $events->listen('email.carinsurance.process.text', [$this, 'processEmailText']);
        $events->listen('email.vaninsurance.process.text', [$this, 'processEmailText']);
        $events->listen('email.legalexpensesinsurance.process.text', [$this, 'processEmailText']);
        $events->listen('email.insurancepackage.process.text', [$this, 'processEmailText']);

        //email extra licensplate
        $events->listen('email.carinsurance.process.session', [$this, 'addLicenseplateToSession']);
        $events->listen('email.vaninsurance.process.session', [$this, 'addLicenseplateToSession']);

    }


    /**
     * Only show the conditions for the products wich are selected by the client..!
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public function filterConditions(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if( ! starts_with($resource->name, "product.")){
            return;
        }

        if( ! ResourceHelper::checkColsVisible($input, ['conditions'])){
            return;
        }
        foreach($output as &$row){
            $return = [];
            if(isset($row['conditions']) && ! is_null($row['conditions']) && is_string($row['conditions'])){
                $conditions = json_decode($row['conditions'], true);
                if(json_last_error() == JSON_ERROR_NONE && is_array($conditions)){
                    foreach($conditions as $condition){
                        /**
                         * Select right policy based on coverage, i.e. 'bc'.
                         */
                        switch($condition['type']){
                            case 'Array':
                                if(isset($row[$condition['name']]) && isset($condition['value'][$row[$condition['name']]])){
                                    if(is_array($condition['value'][$row[$condition['name']]])){
                                        $return = array_merge($return, $condition['value'][$row[$condition['name']]]);
                                    }else{
                                        $return[$condition['name']] = $condition['value'][$row[$condition['name']]];
                                    }
                                }
                                break;
                            case 'String':
                                if($condition['value'] != ''){
                                    if(str_contains($resource->name, 'carinsurance') === false || (isset($input[$condition['name']]) && $input[$condition['name']]))
                                    {
                                        foreach($resource->outputs as $field){
                                            /**
                                             * If possible, retrieve the right label from the Field output label. Also check _value
                                             */
                                            if($field->name == $condition['name'] || $field->name == $condition['name'] . '_value'){
                                                $return[$field->label] = $condition['value'];
                                                break(2);
                                            }
                                        }
                                        $return[$condition['name']] = $condition['value'];
                                    }
                                }
                                break;
                        }
                    }
                }
            }
            $row['conditions'] = $return;
        }
    }



    /**
     * Process the email text, standard for van, car and other insurances
     *
     * @param ArrayObject $replaceArray
     * @param $config Website Website
     * @param $session array merged Session
     * @param $data array merge product data + order
     *
     */
    public function processEmailText(ArrayObject $replaceArray, $config, $session, $data)
    {
        $replaceArray->exchangeArray([
            '{acceptation}' => isset($data['acceptation']) && $data['acceptation'] != '' ? $data['acceptation'] : 'U bent met ingang van {start_date} maximaal 5 werkdagen verzekerd op basis van een voorlopige dekking.* Voor het einde van de voorlopige dekking ontvangt u van ons bericht of uw aanvraag definitief is geaccepteerd of niet.',
            '{start_date}'  => (isset($session['start_date']) && DateTime::createFromFormat('Y-m-d', $session['start_date']) !== false) ? DateTime::createFromFormat('Y-m-d', $session['start_date'])->format('d-m-Y') : date('d-m-Y'),
            '{gender}'      => (isset($session['gender']) && ! empty($session['gender'])) ? ($session['gender'] == 'male' ? 'heer' : 'mevrouw') : (isset($session['car_owner_gender']) ? ($session['car_owner_gender'] == 'male' ? 'heer' : 'mevrouw') : ''),
            '{lastname}'    => array_get($session, 'lastname', array_get($session, 'last_name', array_get($session, 'car_owner_last_name'))),
            '{last_name}'   => array_get($session, 'lastname', array_get($session, 'last_name', array_get($session, 'car_owner_last_name')))
        ]);
    }


    public static function addLicenseplateToSession(ArrayObject $session){
        if (!isset($session[ResourceInterface::LICENSEPLATE])) {
            return;
        }
        try{
            $data = ResourceHelper::callResource2('licenseplate_rollscache.carinsurance', array_only($session->getArrayCopy(), ResourceInterface::LICENSEPLATE));
            $session->exchangeArray(array_merge($session->getArrayCopy(),$data));
            return;
        } catch (\Exception $e) {
            cw($e->getMessage());
            return;
        }
    }

}