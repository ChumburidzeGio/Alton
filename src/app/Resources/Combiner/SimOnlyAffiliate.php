<?php
/**
 * User: Roeland Werring
 * Date: 19/05/15
 * Time: 13:40
 * 
 */

namespace App\Resources\Combiner;
class SimOnlyAffiliate extends FeedCombinerService {

    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Combiner\Methods\SimOnlyAffiliateProducts::class,
            'description' => 'Request list merged list of products'
        ]
    ];
}