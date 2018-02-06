<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 21:42
 * 
 */

namespace App\Resources\Zanox;

use App\Resources\AbstractServiceRequest;

class AbstractZanoxFeedRequest extends AbstractServiceRequest
{
    protected $serviceProvider = 'zanox';



    //    protected $filterMapping = [
    //        self::SMS     => 'convertMinusOneToInfinite',
    //        self::DATA    => 'convertMinusOneToInfinite',
    //        self::MINUTES => 'convertMinusOneToInfinite',
    //    ];
    //    protected $filterKeyMapping = [
    //    ];
    //
    //    protected $fieldMapping = [
    //    ];


}
