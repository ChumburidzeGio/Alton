<?php

namespace App\Resources\Healthcare\Methods;


use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use DB;

class Cart extends HealthcareAbstractRequest
{
    private $requesters = [
        'applicant' => 'Uzelf',
        'applicant_partner' => 'Uw partner',
        'child0' => 'Kind 1',
        'child1' => 'Kind 2',
        'child2' => 'Kind 3',
        'child3' => 'Kind 4',
        'child4' => 'Kind 5',
        'child5' => 'Kind 6',
        'child6' => 'Kind 7',
        'child7' => 'Kind 8',
        'child8' => 'Kind 9',
        'child9' => 'Kind 10',
    ];

    public function executeFunction()
    {
        $this->result = ['products' => [], 'price_total' => 0];

        $conditions = [
            'user' => $this->params[ResourceInterface::USER],
            'website' => $this->params[ResourceInterface::WEBSITE],
            'collectivity_id' => array_get($this->params, ResourceInterface::COLLECTIVITY_ID),
        ];

        foreach ($this->requesters as $requester => $requesterDescription) {
            if (!isset($this->params[$requester])) {
                continue;
            }
            $results[$requester] = ResourceHelper::callResource2('product.healthcare2018', $this->params[$requester] + $conditions + ['_limit' => 1]);
            if (count($results[$requester]) > 0){
                $this->result['products'][] = [
                    'current' => $requester,
                    'requester_description' => $requesterDescription,
                ] + head($results[$requester]) + $this->params[$requester];
                $this->result['price_total'] += head($results[$requester])[ResourceInterface::PRICE_ACTUAL];
            }
        }
    }
}