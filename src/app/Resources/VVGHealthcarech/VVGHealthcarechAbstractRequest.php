<?php
namespace App\Resources\VVGHealthcarech;

use App\Helpers\DocumentHelper;
use App\Resources\AbstractMethodRequest;

class VVGHealthcarechAbstractRequest extends AbstractMethodRequest
{
    protected $cacheDays = false;
    public $resource2Request = true;

    protected $params = [];
    protected $result;

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        return $this->result;
    }

    protected function getOrderById($orderId)
    {
        try {
            return DocumentHelper::show('order', 'vvghealthcarech', $orderId);
        } catch (\Exception $e) {
            $this->setErrorString('Cannot find Swiss VVG Healthcare order `'. $orderId .'`.');
        }
        return null;
    }
}