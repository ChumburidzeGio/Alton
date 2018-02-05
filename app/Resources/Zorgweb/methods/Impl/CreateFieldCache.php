<?php
/**
 * This function basically updates the argument list
 * push me
 *
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Zorgweb\Methods;
use App\Interfaces\ResourceInterface;
use Cache, Config;

class CreateFieldCache extends ZorgwebAbstractRequest
{

    /**
     * Makes if way faster
     */
    protected $cacheDays = false;


    protected $arguments = [
        ResourceInterface::IDS              => [
            'rules'   => 'array',
            'example' => '[H60011291,H96118965,H59978902,H96962691,H98046606,H71379742,H101425833,H67576438,H67887068,H69793347,H99632655]',
            'default' => '[H60011291,H96118965,H59978902,H96962691,H98046606,H71379742,H101425833,H67576438,H67887068,H69793347,H99632655]'
        ],
    ];



    public function __construct()
    {
        $this->strictStandardFields = false;
        $this->arguments[ResourceInterface::IDS]['default'] = ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.contractfields'));
    }

    /**
     * Get results of request
     * @return mixed
     */
    public function getResult()
    {
        if (!is_array($this->params['ids'])) {
            $this->params['ids'] = explode(',',trim($this->params['ids'],'[]'));
        }

        $arguments = [];
        foreach ($this->params['ids'] as $id) {
            $contractId = $this->getContractId($id);
            $arguments = array_merge($arguments, $this->internalRequest('healthcare2','contractfields',['contract_id' => $contractId, 'create_arguments' => 1]));
        }
        return $arguments;
    }

    private function getContractId($id)
    {
        return $id;
    }

}