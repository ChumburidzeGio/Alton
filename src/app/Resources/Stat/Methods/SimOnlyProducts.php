<?php
/**
 * User: Roeland Werring
 * Date: 19/05/15
 * Time: 13:46
 *
 */

namespace App\Resources\Stat\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Komparu\Value\ValueInterface;

class SimOnlyProducts extends AbstractMethodRequest
{
    protected $cacheDays = false;

    const ROBIN_PROVIDER_NAME = 'Robin Mobile';

    const ROBIN_PROVIDER_ID = 'robin';

    const ROBIN_PROVIDER_DESCRIPTION = "Robin Mobile is de eerste aanbieder waarbij je echt onbeperkt kan bellen, sms'en en internetten. Je hoeft je dus nooit geen zorgen meer te maken over verassingen op je factuur, want onbeperkt betekent écht onbeperkt. ";

    public function __construct()
    {
    }

    public function executeFunction()
    {
        //sit tight and do nothing
    }

    /**
     * Get results of request
     * @return mixed
     */
    public function getResult()
    {

        return [
            [
                ResourceInterface::MINUTES               => ValueInterface::INFINITE,
                ResourceInterface::SMS                   => ValueInterface::INFINITE,
                ResourceInterface::DATA                  => ValueInterface::INFINITE,
                ResourceInterface::SPEED_DOWNLOAD        => 2.0000, // Verified: 22 Aug 2016
                ResourceInterface::SPEED_UPLOAD          => 0.400,
                ResourceInterface::PRICE_DEFAULT         => 29.95,
                ResourceInterface::PRICE_ACTUAL          => 29.95,
                ResourceInterface::PRICE_INITIAL          => 9.95,
                //ResourceInterface::PRICE_INITIAL          => 9.95,
                ResourceInterface::PRICE_PER_MINUTE      => 0,
                ResourceInterface::PRICE_PER_DATA        => 0,
                ResourceInterface::PRICE_PER_SMS         => 0,
                ResourceInterface::ACTION_DURATION       => 1,
                ResourceInterface::PROVIDER_NAME         => self::ROBIN_PROVIDER_NAME,
                ResourceInterface::PROVIDER_ID           => self::ROBIN_PROVIDER_ID,
                ResourceInterface::COMPANY_ID           => 112,
                ResourceInterface::TITLE                 => 'Robin Mobile Gewoon Snel internet',
                ResourceInterface::RENEWAL               => false,
                ResourceInterface::TIME                  => 1,
                ResourceInterface::TRANSFER_UNUSED_UNITS => null,
                ResourceInterface::BILLING_METHOD        => null,
                ResourceInterface::INTERNET_TYPE         => '3G',
                ResourceInterface::URL                   => 'http://ad.zanox.com/ppc/?29184235C844332&zpar6=DF_1&ULP=[[https%3A%2F%2Fwww.robinmobile.nl%2Fbestellen%3Fproduct%3D1%26netwerk%3Dza%26utm_source%3Dzanox%26utm_medium%3Daffiliate%26utm_campaign%3Daffiliate%26aff%3D%23%23UserID%23%23%26subid%3D%23%23WebsiteID%23%23]]',
                ResourceInterface::NETWORK               => 'KPN',
                ResourceInterface::PROVIDER_DESCRIPTION  => self::ROBIN_PROVIDER_DESCRIPTION,
                ResourceInterface::RESOURCE_ID           => "robin1",
                ResourceInterface::RESOURCE_NAME         => "simonly5",
            ],
            [
                ResourceInterface::MINUTES               => ValueInterface::INFINITE,
                ResourceInterface::SMS                   => ValueInterface::INFINITE,
                ResourceInterface::DATA                  => ValueInterface::INFINITE,
                ResourceInterface::SPEED_DOWNLOAD        => 15, // Verified: 22 Aug 2016
                ResourceInterface::SPEED_UPLOAD          => 2.0,
                ResourceInterface::PRICE_DEFAULT         => 39.95,
                ResourceInterface::PRICE_ACTUAL          => 39.95,
                ResourceInterface::PRICE_INITIAL          => 9.95,
                //ResourceInterface::PRICE_INITIAL          => 9.95,
                ResourceInterface::PRICE_PER_MINUTE      => 0,
                ResourceInterface::PRICE_PER_DATA        => 0,
                ResourceInterface::PRICE_PER_SMS         => 0,
                ResourceInterface::ACTION_DURATION       => 1,
                ResourceInterface::PROVIDER_NAME         => self::ROBIN_PROVIDER_NAME,
                ResourceInterface::PROVIDER_ID           => self::ROBIN_PROVIDER_ID,
                ResourceInterface::COMPANY_ID           => 112,
                ResourceInterface::TITLE                 => 'Robin Mobile Super Snel internet',
                ResourceInterface::RENEWAL               => false,
                ResourceInterface::TIME                  => 1,
                ResourceInterface::TRANSFER_UNUSED_UNITS => null,
                ResourceInterface::BILLING_METHOD        => null,
                ResourceInterface::INTERNET_TYPE         => '4G',
                ResourceInterface::URL                   => 'http://ad.zanox.com/ppc/?29184235C844332&zpar6=DF_1&ULP=[[https%3A%2F%2Fwww.robinmobile.nl%2Fbestellen%3Fproduct%3D3%26netwerk%3Dza%26utm_source%3Dzanox%26utm_medium%3Daffiliate%26utm_campaign%3Daffiliate%26aff%3D%23%23UserID%23%23%26subid%3D%23%23WebsiteID%23%23]]',
                ResourceInterface::NETWORK               => 'KPN',
                ResourceInterface::PROVIDER_DESCRIPTION  => self::ROBIN_PROVIDER_DESCRIPTION,
                ResourceInterface::RESOURCE_ID           => "robin3",
                ResourceInterface::RESOURCE_NAME         => "simonly5",

            ],
            [
                ResourceInterface::MINUTES               => ValueInterface::INFINITE,
                ResourceInterface::SMS                   => ValueInterface::INFINITE,
                ResourceInterface::DATA                  => ValueInterface::INFINITE,
                ResourceInterface::SPEED_DOWNLOAD        => 0.800, // Verified: 22 Aug 2016
                ResourceInterface::SPEED_UPLOAD          => 0.400,
                ResourceInterface::PRICE_DEFAULT         => 19.95,
                ResourceInterface::PRICE_ACTUAL          => 19.95,
                ResourceInterface::PRICE_INITIAL          => 9.95,
                //ResourceInterface::PRICE_INITIAL          => 9.95,

                ResourceInterface::PRICE_PER_MINUTE      => 0,
                ResourceInterface::PRICE_PER_DATA        => 0,
                ResourceInterface::PRICE_PER_SMS         => 0,
                ResourceInterface::ACTION_DURATION       => 1,
                ResourceInterface::PROVIDER_NAME         => self::ROBIN_PROVIDER_NAME,
                ResourceInterface::PROVIDER_ID           => self::ROBIN_PROVIDER_ID,
                ResourceInterface::COMPANY_ID           => 112,
                ResourceInterface::TITLE                 => 'Robin Mobile Junior',
                ResourceInterface::RENEWAL               => false,
                ResourceInterface::TIME                  => 1,
                ResourceInterface::TRANSFER_UNUSED_UNITS => null,
                ResourceInterface::BILLING_METHOD        => null,
                ResourceInterface::INTERNET_TYPE         => '3G',
                ResourceInterface::URL                   => 'http://ad.zanox.com/ppc/?29184156C54792860&zpar6=DF_1&ULP=[[https%3A%2F%2Fwww.robinmobile.nl%2Fbestellen%3Fproduct%3D4%26netwerk%3Dza%26utm_source%3Dzanox%26utm_medium%3Daffiliate%26utm_campaign%3Daffiliate%26aff%3D%23%23UserID%23%23%26subid%3D%23%23WebsiteID%23%23]]',
                ResourceInterface::NETWORK               => 'KPN',
                ResourceInterface::PROVIDER_DESCRIPTION  => self::ROBIN_PROVIDER_DESCRIPTION,
                ResourceInterface::RESOURCE_ID           => "robin4",
                ResourceInterface::RESOURCE_NAME         => "simonly5",

            ],
            [
                ResourceInterface::MINUTES               => ValueInterface::INFINITE,
                ResourceInterface::SMS                   => ValueInterface::INFINITE,
                ResourceInterface::DATA                  => ValueInterface::INFINITE,
                ResourceInterface::SPEED_DOWNLOAD        => 25.0, // Verified: 22 Aug 2016
                ResourceInterface::SPEED_UPLOAD          => 2.0,  // Verified: 22 Aug 2016
                ResourceInterface::PRICE_DEFAULT         => 49.95,
                ResourceInterface::PRICE_ACTUAL          => 49.95,
                ResourceInterface::PRICE_INITIAL          => 9.95,
                //ResourceInterface::PRICE_INITIAL          => 9.95,

                ResourceInterface::PRICE_PER_MINUTE      => 0,
                ResourceInterface::PRICE_PER_DATA        => 0,
                ResourceInterface::PRICE_PER_SMS         => 0,
                ResourceInterface::ACTION_DURATION       => 1,
                ResourceInterface::PROVIDER_NAME         => self::ROBIN_PROVIDER_NAME,
                ResourceInterface::PROVIDER_ID           => self::ROBIN_PROVIDER_ID,
                ResourceInterface::COMPANY_ID           => 112,
                ResourceInterface::TITLE                 => 'Robin Mobile Pijl Snel 4G',
                ResourceInterface::RENEWAL               => false,
                ResourceInterface::TIME                  => 1,
                ResourceInterface::TRANSFER_UNUSED_UNITS => null,
                ResourceInterface::BILLING_METHOD        => null,
                ResourceInterface::INTERNET_TYPE         => '4G',
                ResourceInterface::URL                   => 'http://ad.zanox.com/ppc/?29184156C54792860&zpar6=DF_1&ULP=[[https%3A%2F%2Fwww.robinmobile.nl%2Fbestellen%3Fproduct%3D5%26netwerk%3Dza%26utm_source%3Dzanox%26utm_medium%3Daffiliate%26utm_campaign%3Daffiliate%26aff%3D%23%23UserID%23%23%26subid%3D%23%23WebsiteID%23%23]]',
                ResourceInterface::NETWORK               => 'KPN',
                ResourceInterface::PROVIDER_DESCRIPTION  => self::ROBIN_PROVIDER_DESCRIPTION,
                ResourceInterface::RESOURCE_ID           => "robin5",
                ResourceInterface::RESOURCE_NAME         => "simonly5",

            ],

        ];
    }

}