<?php
/**
 * User: Roeland Werring
 * Date: 18/06/15
 * Time: 16:29
 * 
 */

namespace App\Resources\Stat;

use App\Resources\AbstractServiceRequest;

class SimOnly extends AbstractServiceRequest {

    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Stat\Methods\SimOnlyProducts::class,
            'description' => 'Request list merged list of products'
        ]
    ];
}