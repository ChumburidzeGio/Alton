<?php
namespace App\Resources\General\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Illuminate\Support\Facades\Queue;


class NotifyApplications extends AbstractMethodRequest
{
    protected $result = [];
    protected $params = [];
    public $resource2Request = true;

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function executeFunction()
    {
        Queue::push(\App\Jobs\NotifyApplicationsJob::class, [
            'resource' => array_get($this->params, ResourceInterface::RESOURCE),
            'action' => array_get($this->params, ResourceInterface::ACTION),
            'id' => array_get($this->params, ResourceInterface::ID),
            'multi' => array_get($this->params, ResourceInterface::ALL_CALLBACKS, false),
        ]);

        $this->result = [
            ResourceInterface::SUCCESS => true,
        ];
    }

    public function getResult()
    {
        return $this->result;
    }
}