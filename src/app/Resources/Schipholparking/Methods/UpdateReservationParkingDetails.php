<?php
namespace App\Resources\Schipholparking\Methods;

class UpdateReservationParkingDetails extends CreateReservation
{
    protected $defaultMethodName = 'ManageBookingChangeParkingDetails';

    protected $cacheDays = false;
    public $resource2Request = true;

    protected function getParamDefaults()
    {
        // This method can change:
        // StartDate
        // EndDate
        // ArrivalTimeHHMM
        // DepartureTimeHHMM
        // CarParkCode
        // ProductCode
        // VehicleRegistration
        // VehicleMake
        // VehicleModel
        // VehicleColor
        // CarParkAccessNumber
        // CarParkAccessCardType

        $params = parent::getParamDefaults();
        $params['BookingNumber'] = '';
        unset($params['DestinationAirportCode']);

        return $params;
    }

    public function executeFunction()
    {
        parent::executeFunction();
        if ($this->hasErrors() && $this->getErrorString() == 'Errorcode 0: Ammend successfull')
        {
            $this->clearErrors();
            $this->result = ['success' => true];
        }
    }
}