<?php

namespace App\Resources\Google\Api;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Google_Client;
use Google_Service_Sheets;

class GoogleApiAbstractRequest extends AbstractMethodRequest
{

    const GOOGLE_API_CLIENT_JSON = 'config/auth/google_api_client.json';
    const GOOGLE_API_CREDENTIALS = 'config/auth/google_credentials.json';

    public $resource2Request = true;
    protected $cacheDays = false;

    private $secret = null;
    private $client = null;

    /** @var Google_Service_Sheets  */
    private $service = null;



    private $spreadsheetId = "";
    private $range = "";


    public function setParams(array $params)
    {
        if (!isset($params[ResourceInterface::ID]) || !isset($params[ResourceInterface::RANGE])) {
            $this->setErrorString("No range or id set");
            return;
        }
        $this->spreadsheetId = $params[ResourceInterface::ID];
        $this->range = $params[ResourceInterface::RANGE];
    }

    public function executeFunction(){
        $this->secret =  app_path(self::GOOGLE_API_CLIENT_JSON);
        $this->client = $this->getClient();
        $this->service = new Google_Service_Sheets($this->client);

    }


    public function getResult()
    {
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->range);
        return $response->getValues();
    }


    public function getResultWithOptions()
    {
        return $this->service->spreadsheets->get($this->spreadsheetId, [
            'ranges'          => $this->range,
            'includeGridData' => true, //For extra data and not only values,
        ]);
    }


    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    private function getClient()
    {
        $client = new Google_Client();
        $scopes = implode(' ', array(Google_Service_Sheets::SPREADSHEETS_READONLY));
        $client->setApplicationName("Raven API");
        $client->setScopes($scopes);
        $client->setAuthConfig($this->secret);
        $client->setAccessType('offline');


        // Load previously authorized credentials from a file.

        $credentialsPath = storage_path('google_credentials.json');


        //$credentialsPath =  app_path(self::GOOGLE_API_CREDENTIALS);
        if(file_exists($credentialsPath)){
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        }else{
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Store the credentials to disk.
            if( ! file_exists(dirname($credentialsPath))){
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
            exit;
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if($client->isAccessTokenExpired()){
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     *
     * @param string $path the path to expand.
     *
     * @return string the expanded path.
     */
    function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if(empty($homeDirectory)){
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

    // Get the API client and construct the service object.
//$client = getClient();
//$service = new Google_Service_Sheets($client);
//
//    // Prints the names and majors of students in a sample spreadsheet:
//    // https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
//$spreadsheetId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
//$range = 'Class Data!A2:E';
//$response = $service->spreadsheets_values->get($spreadsheetId, $range);
//$values = $response->getValues();
//
//if(count($values) == 0)
//{
//print "No data found.\n";
//}
//
//else{
//    print "Name, Major:\n";
//    foreach($values as $row){
//        // Print columns A and E, which correspond to indices 0 and 4.
//        printf("%s, %s\n", $row[0], $row[4]);
//    }
//}

}
