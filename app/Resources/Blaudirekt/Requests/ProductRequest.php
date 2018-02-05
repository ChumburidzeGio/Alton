<?php
namespace App\Resources\Blaudirekt\Requests;

use App\Interfaces\ResourceInterface;
use App\Resources\Blaudirekt\BlaudirektAbstractRequest;

class ProductRequest extends BlaudirektAbstractRequest
{
    protected $insuranceName = null;

    protected $externalToResultMapping = [
        'id' => ResourceInterface::PRODUCT_ID,
        'options.company' => ResourceInterface::COMPANY__ID
    ];

    public function __construct()
    {
        parent::__construct("service/bd/{$this->insuranceName}/products");
    }

    public function executeFunction()
    {
        parent::executeFunction();

        $this->result = array_filter($this->result, function($item) {
            return !is_null($item['options']['company']);
        });
    }
}