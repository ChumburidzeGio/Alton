<?php
/**
 * User: Roeland Werring
 * Date: 13/03/15
 * Time: 13:01
 *
 */

namespace App\Resources\Telecombinatie;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractServiceRequest;

class TelecombinatieServiceRequest extends AbstractServiceRequest
{


    protected $filterMapping = [
        self::SMS     => 'convertMinusOneToInfinite',
        self::DATA    => 'convertMinusOneToInfinite',
        self::MINUTES => 'convertMinusOneToInfinite',
    ];
//    protected $filterKeyMapping = [
//        ResourceInterface::PHONE => 'uniqueCustomerId',
//    ];

    protected $fieldMapping = [
        'tokenId' => ResourceInterface::TOKEN,
    ];


}
