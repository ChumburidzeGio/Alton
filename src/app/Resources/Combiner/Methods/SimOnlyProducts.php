<?php
/**
 * User: Roeland Werring
 * Date: 19/05/15
 * Time: 13:46
 * 
 */

namespace App\Resources\Combiner\Methods;

class SimOnlyProducts extends AbstractProductsRequest {
    protected $sources = ['simonly3','simonly1', 'simonly5'];
    protected $ignoreFields = ['speed_download'];
}