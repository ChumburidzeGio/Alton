<?php
namespace App\Resources\Healthcare;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;

class HealthcareAbstractRequest extends AbstractMethodRequest
{
    const KOMPARU_CONTRACT_NUMBER = '70121';

    public $skipDefaultFields = true;

    const PERSONS = ['applicant', 'applicant_partner', 'child0', 'child1', 'child2', 'child3', 'child4', 'child5', 'child6', 'child7', 'child8', 'child9'];

    protected $member_mapping = [
        'applicant' => 'aanvrager',
        'applicant_partner' => 'partner',
        'child0' => 'kinderen.0',
        'child1' => 'kinderen.1',
        'child2' => 'kinderen.2',
        'child3' => 'kinderen.3',
        'child4' => 'kinderen.4',
        'child5' => 'kinderen.5',
        'child6' => 'kinderen.6',
        'child7' => 'kinderen.7',
        'child8' => 'kinderen.8',
        'child9' => 'kinderen.9',
    ];

    private $property_mapping = [
        'birthdate' => 'geboortedatum',
        'own_risk' => 'overige.gewenstEigenRisico',
        'product_id' => 'aanTeVragenPakketId',
    ];

    private $formitemToVerzekerden = [
        'aanvrager.geslacht' => 'aanvrager.geslacht',
        'partner.geslacht' => 'partner.geslacht',
    ];


    /**
     * Mapping of ID to Tussen persoon. Quick fix for passing on right tussen persoon for IAK
     * @var array
     */
    private $tussenPersoonMap = [
        4261 => ['id' => '003018849', 'name' => 'IAK'],
        79   => ['id' => '4323947', 'name' => 'Overstappen'],
        66   => ['id' => '4323947', 'name' => 'Easyswitch'],
    ];

    protected $cacheDays = false;
    public $resource2Request = true;

    protected $params = [];
    protected $result;

    public function __construct()
    {
        //   cw('/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/');
        $this->resource2Request     = true;
        $this->strictStandardFields = false;
    }

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getMappedParams($params)
    {
        foreach($params as $familyMember => $memberInfo) {
            if (isset($this->member_mapping[$familyMember])) {
                $mappedMemberName = $this->member_mapping[$familyMember];

                foreach($memberInfo as $property => $value ) {
                    if (isset($this->property_mapping[$property])) {
                        $mappedPropertyName = $this->property_mapping[$property];
                        array_set($params, $mappedMemberName.'.'.$mappedPropertyName, $value);
                    }
                }
            }
        }
        return $params;
    }

    public function getContractParams(&$params)
    {
        $newMapping = [];
        foreach($params as $familyMember => $memberInfo) {
            if (isset($this->member_mapping[$familyMember])) {

                $mappedMemberName = $this->member_mapping[$familyMember];
                foreach($memberInfo as $property => $value ) {
                    if (isset($this->property_mapping[$property])) {
                        $mappedPropertyName = $this->property_mapping[$property];
                        array_set($newMapping, $mappedMemberName.'.'.$mappedPropertyName, $value);
                        array_forget($params, "$familyMember.$property");
                    }
                }
            }
        }
        // Some form questions need to be set into the 'verzekerden' array as well
        foreach ($this->formitemToVerzekerden as $from => $to) {
            $value = array_get($params, $from);
            if ($value !== null) {
                array_set($newMapping, $to, $value);
            }
        }

        return $newMapping;
    }

    protected function createZorgwebFormParams($params)
    {
        $zorgwebParams = [];
        $zorgwebParams['verzekerden'] = $this->getContractParams($params);

        if(!empty($params[ResourceInterface::COLLECTIVITY_ID])){
            //collectivity and contract stuff
            $contractNummer    = self::KOMPARU_CONTRACT_NUMBER;
            $tussenpersoonNaam = 'Komparu';
            if(isset($params[ResourceInterface::USER])){
                if(isset($this->tussenPersoonMap[$params[ResourceInterface::USER]])){
                    $contractNummer    = $this->tussenPersoonMap[$params[ResourceInterface::USER]]['id'];
                    $tussenpersoonNaam = $this->tussenPersoonMap[$params[ResourceInterface::USER]]['name'];
                }
            }

            $zorgwebParams['verzekerden']['collectiviteitId'] = array_get($params,'collectivity_for_zorgweb');
            $zorgwebParams['aanbieder'] = [
                'collectiviteit' => [
                    'naam' => $tussenpersoonNaam,
                    'code' => '',
                    'contractNummer' => array_get($params,'collectivity_for_zorgweb')
                ],
                'tussenpersoon' => $contractNummer
            ];
        }

        return $zorgwebParams;
    }

    protected function processZorgwebFormInputs($params)
    {
        /**
         * prefix all burger service nrs with 0 till 8 characters
         */
        foreach($params as $groupNr => $group) {
            if (!is_array($group))
                continue;
            foreach ($group as $key => $value) {
                if (str_contains($key, 'burgerservicenummer')) {
                    $params[$groupNr][$key] = str_pad($value, 8, "0", STR_PAD_LEFT);;
                }
            }
        }

        /**
         * limit tussenvoegse
         */
        foreach($params as $groupNr => $group) {
            if (!is_array($group))
                continue;
            foreach ($group as $key => $value) {
                if(str_contains($key, 'tussenvoegsel')) {
                    if (!$value || trim($value) == '' || trim($value) == 'false') {
                        unset($params[$groupNr][$key]);
                    } else {
                        $params[$groupNr][$key] = substr($value, 0, 10);
                    }
                }
            }
        }

        foreach($params as $groupNr => $group) {
            if (!is_array($group))
                continue;
            foreach ($group as $key => $value) {
                if(str_contains($key, 'achternaam')) {
                    if( ! preg_match("/^[a-zA-Z\s.-]+$/", $value)){
                        $this->addErrorMessage($groupNr .'.'. $key, 'invalid-input', 'Ongeldige invoer');
                        return $params;
                    }
                }
            }
        }

        /**
         * fix phone numbers
         */
        if(array_has($params, 'hoofdadres.telefoonnummer')){
            $phoneNumber = array_get($params, 'hoofdadres.telefoonnummer');
            $phoneNumber = preg_replace('/\s+/', '', $phoneNumber);
            $phoneNumber = str_replace('+31', '0', $phoneNumber);
            if( ! is_numeric($phoneNumber) || (strlen($phoneNumber) != 10) || substr($phoneNumber, 0, 1) != '0'){
                $this->addErrorMessage('hoofdadres.telefoonnummer', 'invalid-input', 'Ongeldige invoer');
                return $params;
            }
            array_set($params, 'hoofdadres.telefoonnummer', $phoneNumber);
        }

        return $params;
    }
}