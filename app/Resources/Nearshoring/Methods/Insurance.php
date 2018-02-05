<?php
namespace App\Resources\Nearshoring\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Nearshoring\AbstractNearshoringRequest;
use Config;


/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */
class Insurance extends AbstractNearshoringRequest
{
    protected $arguments = [

        ResourceInterface::COLLECTIVITY_ID   => [
            'rules' => 'number | required',
        ],
        ResourceInterface::BIRTHDATE         => [
            'rules' => self::VALIDATION_REQUIRED_DATE,
        ],
        ResourceInterface::BIRTHDATE_PARTNER => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
        ],
        ResourceInterface::BIRTHDATE_CHILD_1 => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
        ],
        ResourceInterface::BIRTHDATE_CHILD_2 => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
        ],
        ResourceInterface::BIRTHDATE_CHILD_3 => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
        ],
        ResourceInterface::BIRTHDATE_CHILD_4 => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
        ],
        ResourceInterface::BIRTHDATE_CHILD_5 => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
        ],
        ResourceInterface::PACKAGES          => [
            'rules'   => 'array | required',
            'example' => '[213,345345,2342,12341234,1234]',
        ],
        ResourceInterface::PACKAGES_PARTNER  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
        ],
        ResourceInterface::PACKAGES_CHILD_1  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
        ],
        ResourceInterface::PACKAGES_CHILD_2  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
        ],
        ResourceInterface::PACKAGES_CHILD_3  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
        ],
        ResourceInterface::PACKAGES_CHILD_4  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
        ],
        ResourceInterface::PACKAGES_CHILD_5  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
        ],
    ];


    public function setParams(Array $params)
    {

        $persons['Person'][] = $this->createPerson($this->createPackageArray($params[ResourceInterface::PACKAGES]), $params[ResourceInterface::BIRTHDATE], 'PolicyOwner');

        if(isset($params[ResourceInterface::BIRTHDATE_PARTNER])){
            if (!isset($params[ResourceInterface::PACKAGES_PARTNER])) {
                $this->setErrorString('Geen packages voor partner ingevoerd');
            }
            $persons['Person'][] = $this->createPerson($this->createPackageArray($params[ResourceInterface::PACKAGES_PARTNER]), $params[ResourceInterface::BIRTHDATE_PARTNER], 'Partner');
        }

        for ($child = 1; $child < 6; $child ++){
            if(isset($params['birthdate_child_'.$child])){
                if (!isset($params['packages_child_'.$child])) {
                    $this->setErrorString('Geen packages voor child ingevoerd '.$child);
                }

                $persons['Person'][] = $this->createPerson($this->createPackageArray($params['packages_child_'.$child]), $params['birthdate_child_'.$child], 'Child');
            }
        }


        $paramArray   = [
            'AddSelectedInsurance' => [
                'request' => [
                    'Collective' => $params[ResourceInterface::COLLECTIVITY_ID],
                    'Persons'    => $persons
                ]
            ]
        ];
        $this->params = $paramArray;
    }


    public function __construct()
    {
        $this->method = 'AddSelectedInsurance';
        parent::__construct();
    }

    /**
     * @param $packageIds
     *
     * @return array
     */
    private function createPackageArray($packageIds)
    {
        $packageArr = [];
        foreach($packageIds as $packageId){
            $packageArr[] = [
                'PackageId' => $packageId

            ];
        }
        return $packageArr;
    }


    /**
     * @param $packageIds
     *
     * @return array
     */
    private function createPerson($packageArr, $birthdate, $personType)
    {
        return [
            'Birthdate' => $birthdate,
            'Packages'  => [
                'Package' => $packageArr
            ],
            'PersonType' => $personType
        ];
    }

}