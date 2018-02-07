<?php
/**
 * User: Roeland Werring
 * Date: 23/01/18
 * Time: 22:19
 *
 */

namespace App\Resources\Blaudirekt\Methods\Privateliabilityde;


use App\Interfaces\ResourceInterface;
use App\Resources\Blaudirekt\BlaudirektAbstractRequest;

class GetVertraegePrivateliabilityde extends BlaudirektAbstractRequest
{

    protected $brokerReq = true;

    public function __construct($requestParams = [])
    {
        parent::__construct('kunden/');
    }

    public function setParams(array $params)
    {
        $this->setUrl($this->getUrl().$params[ResourceInterface::CUSTOMER_ID].'/vertraege');
        if (isset($params[ResourceInterface::ID])) {
            $this->setUrl($this->getUrl().'/'.$params[ResourceInterface::ID]);
        }
        parent::setParams([]);
    }
}


