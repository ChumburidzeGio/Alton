<?php
namespace App\Resources\General\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;


class LimitedAccessToken extends AbstractMethodRequest
{
    protected $result = [];
    public $resource2Request = true;
    protected $cacheDays = false;

    public function executeFunction()
    {
        $application = App::make('application');

        $expirationInMinutes = 5;

        $expirationTimestamp = strtotime('+'. $expirationInMinutes .' minutes');

        do {
            $hash = substr(md5(rand() . 'salty-mc-salt-salt'), 0, 12);
        }
        while (Cache::get('limited-access-token-'. $hash));
        Cache::put('limited-access-token-'. $hash, [
            'token' => $application->token,
            'domain' => $application->domain()->first()->name,
            'expiration_timestamp' => $expirationTimestamp,
        ], $expirationInMinutes);

        $this->result = [
            ResourceInterface::HASH_TOKEN => $hash,
            ResourceInterface::CALL_LIMIT => 1,
            ResourceInterface::EXPIRATION_DATE => date('c', $expirationTimestamp),
        ];
    }

    public function getResult()
    {
        return $this->result;
    }

    public static function getTokenData($hash)
    {
        $tokenData = Cache::get('limited-access-token-'. $hash);
        if ($tokenData) {
            Cache::forget('limited-access-token-'. $hash);
        }

        if ($tokenData && array_get($tokenData, 'expiration_timestamp') < time()) {
            $tokenData = null;
        }

        return $tokenData;
    }
}