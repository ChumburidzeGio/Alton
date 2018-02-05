<?php
namespace App\Resources\Telecombinatie\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\BasicAuthRequest;
use Config;


/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */
class TelecombinatieAbstractRequest extends BasicAuthRequest
{
    protected $propertyMap = [
        'ProviderName'                              => ResourceInterface::NAME,
        'ProviderDescriptionLong'                   => ResourceInterface::DESCRIPTION,
        'ProviderActive'                            => ResourceInterface::ACTIVE,
        'ProviderCode'                              => ResourceInterface::CODE,
        //'Propositions
        'PropositionOneTimeConnectionCost'          => ResourceInterface::PRICE_INITIAL,
        'PropositionMonthlyStandardCost'            => ResourceInterface::PRICE_DEFAULT,
        'PropositionMinuteAmount'                   => ResourceInterface::MINUTES,
        'PropositionSMSAmount'                      => ResourceInterface::SMS,
        'PropositionDataAmount'                     => ResourceInterface::DATA,
        'PropositionDataDownloadSpeed'              => ResourceInterface::SPEED_DOWNLOAD,
        'PropositionDataUploadSpeed'                => ResourceInterface::SPEED_UPLOAD,
        'PropositionCostDataOutsideBundle'          => ResourceInterface::PRICE_PER_DATA,
        'PropositionCostMinuteOutsideBundle'        => ResourceInterface::PRICE_PER_MINUTE,
        'PropositionCostSMSOutsideBundle'           => ResourceInterface::PRICE_PER_SMS,
        'PropositionActionPeriod'                   => ResourceInterface::ACTION_DURATION,
        'PropositionMonthlyActionCost'              => ResourceInterface::PRICE_ACTUAL,
        'PropositionNetworkCode'                    => ResourceInterface::NETWORK_CODE,
        'PropositionProviderName'                   => ResourceInterface::PROVIDER_NAME,
        'PropositionProviderCode'                   => ResourceInterface::PROVIDER_ID,
        'PropositionFamilyName'                     => ResourceInterface::TITLE,
        'PropositionIsSimOnly'                      => ResourceInterface::SIM_ONLY,
        'PropositionIsRenewal'                      => ResourceInterface::RENEWAL,
        'PropositionPeriod'                         => ResourceInterface::TIME,
        'PropositionTransferUnusedUnitsToNextMonth' => ResourceInterface::TRANSFER_UNUSED_UNITS,
        'PropositionBillingMethode'                 => ResourceInterface::BILLING_METHOD,
        'PropositionInternetType'                   => ResourceInterface::INTERNET_TYPE,
        'PropositionHandsetBrand'                   => ResourceInterface::MOBILE_BRAND,
        'PropositionHandsetModel'                   => ResourceInterface::MOBILE_MODEL,
    ];


    //Let op: indien de method POST wordt gebruikt richting onze API, moet de content-Type op
    //‘multipart/form-data’ staan.

    public function __construct($methodUrl, $typeRequest = 'get', $resource = 'content')
    {
        $mode         = ((app()->configure('app')) ? '' : config('app.debug')) ? 'test' : 'live';
        $configprefix = 'resource_telecombinatie.' . $mode . '_' . $resource . '_settings';
        $url          = Config::get($configprefix . '.url');
        $url .= $methodUrl;

        $this->basicAuthService = [
            'type_request' => $typeRequest,
            'method_url'   => $url,
            'username'     => Config::get($configprefix . '.username'),
            'password'     => Config::get($configprefix . '.password')
        ];
    }


    public function getResult()
    {
        return $this->result;
    }

    /**
     * Convert fields based on the propertyMap telecomb style
     *
     * @param $result
     *
     * @return array
     */
    protected function convertFields($result)
    {
        $returnArr = [];
        foreach($result as $val){
            if(isset($this->propertyMap[$val['propertyName']])){
                $returnArr[$this->propertyMap[$val['propertyName']]] = $val['value'];
            }
        }
        return $returnArr;
    }

    public function parseResponseError($error, \Exception $exception = null) {
        $errorArr = json_decode($error, true);
        return trim($errorArr['message']);
    }

}