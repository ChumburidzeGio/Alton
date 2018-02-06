<?php
/**
 * User: Roeland Werring
 * Date: 18/05/15
 * Time: 16:49
 *
 */

namespace App\Resources\Ipparking;


use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Exception;
use SoapVar;

class AbstractParkingRequest extends AbstractMethodRequest
{

    protected $cacheDays = false;

    protected $method;
    protected $params;

    public function __construct()
    {
        $this->soapClient = new IpparkingSoapClient();
        $this->strictStandardFields = false;
    }


    public function executeFunction()
    {
    }

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        return json_decode(json_encode($this->soapClient->executeMethod($this->method, $this->params)), true);
    }

    //[0]=>
    //string(71) "GetConfigurationResponse GetConfiguration(GetConfiguration $parameters)"
    //[1]=>
    //string(74) "CheckAvailabilityResponse CheckAvailability(CheckAvailability $parameters)"
    //[2]=>
    //string(56) "ReserveSpotResponse ReserveSpot(ReserveSpot $parameters)"
    //[3]=>
    //string(77) "ProlongReservationResponse ProlongReservation(ProlongReservation $parameters)"
    //[4]=>
    //string(68) "BookReservationResponse BookReservation(BookReservation $parameters)"
    //[5]=>
    //string(74) "UpdateReservationResponse UpdateReservation(UpdateReservation $parameters)"
    //[6]=>
    //string(74) "CancelReservationResponse CancelReservation(CancelReservation $parameters)"
    //[7]=>
    //string(77) "CheckReductionCodeResponse CheckReductionCode(CheckReductionCode $parameters)"
    //[8]=>
    //string(71) "GetConfigurationResponse GetConfiguration(GetConfiguration $parameters)"
    //[9]=>
    //string(74) "CheckAvailabilityResponse CheckAvailability(CheckAvailability $parameters)"
    //[10]=>
    //string(56) "ReserveSpotResponse ReserveSpot(ReserveSpot $parameters)"
    //[11]=>
    //string(77) "ProlongReservationResponse ProlongReservation(ProlongReservation $parameters)"
    //[12]=>
    //string(68) "BookReservationResponse BookReservation(BookReservation $parameters)"
    //[13]=>
    //string(74) "UpdateReservationResponse UpdateReservation(UpdateReservation $parameters)"
    //[14]=>
    //string(74) "CancelReservationResponse CancelReservation(CancelReservation $parameters)"
    //[15]=>
    //string(77) "CheckReductionCodeResponse CheckReductionCode(CheckReductionCode $parameters)"
}