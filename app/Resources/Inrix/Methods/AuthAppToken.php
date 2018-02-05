<?php
namespace App\Resources\Inrix\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Paston\InrixAbstractRequest;

/**
 * Class AuthAppToken
 *
 * Get an access token, to be used in other Inrix requests.
 *
 * See:
 * <http://devzonedocs.inrix.com/v3/docs/index.php/cs/getappToken/>
 * <http://docs.parkme.com/authentication/getting_authorized/>
 *
 * @package App\Resources\Inrix\Methods
 */
class AuthAppToken extends InrixAbstractRequest
{
    protected $useAccessToken = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [
        ResourceInterface::APP_ID => 'appId',
        ResourceInterface::HASH_TOKEN => 'hashToken',
    ];
    protected $externalToResultMapping = [
        'token' => ResourceInterface::TOKEN,
        'expiry' => ResourceInterface::EXPIRATION_DATE,
    ];
    protected $resultTransformations = [
    ];

    public function __construct()
    {
        parent::__construct('auth', 'appToken');
    }
}