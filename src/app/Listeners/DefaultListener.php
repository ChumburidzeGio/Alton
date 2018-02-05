<?php
/**
 * User: Roeland Werring
 * Date: 22/08/16
 * Time: 13:18
 *
 */

namespace App\Listeners\Resources2;

use App\Helpers\WebsiteHelper;
use App\Helpers\ResourceHelper;
use App\Models\Resource;
use App\Models\Website;
use ArrayObject;

class DefaultListener
{
    public static function setAffiliateLinks(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(isset($input['website'])){
            $website       = Website::find($input['website']);
            $urlIdentifier = $website['url_identifier'];
        }else{
            $productType   = $resource->productType;
            $urlIdentifier = '__' . $productType->name . '.concept';
        }
        foreach($output as &$row){
            $row['url'] = sprintf('%s://code.komparu.%s/%s/c/%s', WebsiteHelper::protocol(), WebsiteHelper::tld(),$urlIdentifier, $row['__id']);
        }
    }
}