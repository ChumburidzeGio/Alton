<?php
namespace App\Resources\Inshared\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Inshared\InsharedAbstractRequest;

class SessionLogin extends InsharedAbstractRequest
{
    const USERNAME_NAME = 'gebruikersnaam';
    const PASSWORD_NAME = 'wachtwoord';

    protected $cacheDays = false;

    protected $mapErrorSourceToInputField = true;

    public function __construct(\SoapClient $soapClient = null)
    {
        parent::__construct('partner-autorisatie/valideren/account?wsdl', $soapClient);
    }

    public function getDefaultParams()
    {
        return [
            self::USERNAME_NAME => '',
            self::PASSWORD_NAME => '',
        ];
    }

    public function setParams(Array $params)
    {
        $params = array_merge([
            self::USERNAME_NAME => $this->username,
            self::PASSWORD_NAME => $this->password,
        ], $params);

        parent::setParams($params);
    }

    public function getResult()
    {
        $result = parent::getResult();

        $result[ResourceInterface::SESSION_ID] = $result[self::SESSION_ID_NAME];

        return $result;
    }
}