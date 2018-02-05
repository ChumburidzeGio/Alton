<?php
namespace App\Resources\Schipholparking\Methods;

use App\Resources\AbstractMethodRequest;

class UpdateReservation extends AbstractMethodRequest
{
    protected $params = [];

    protected $result = [];

    public $resource2Request = true;

    protected $updateParkingDetails = true;
    protected $updateIdentificationDetails = true;
    protected $updatePersonalDetails = true;

    protected $cacheDays = false;

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function executeFunction()
    {
        // Do Personal Details first, in case it changes the email address (which is authentication for the other two calls)
        if ($this->updatePersonalDetails)
        {
            $personalMethod = new UpdateReservationPersonalDetails();
            $personalMethod->call($this->params, $this->path, $this->validator, $this->fieldMapping, $this->filterKeyMapping, $this->filterMapping, $this->serviceproviderName);
            if ($personalMethod->hasErrors())
                $this->setErrorString('Personal Details: ' . $personalMethod->getErrorString());
            $this->result['personalDetails'] = $personalMethod->getResult();
        }

        if ($this->updateParkingDetails)
        {
            $parkingMethod = new UpdateReservationParkingDetails();
            $parkingMethod->call($this->params, $this->path, $this->validator, $this->fieldMapping, $this->filterKeyMapping, $this->filterMapping, $this->serviceproviderName);
            if ($parkingMethod->hasErrors()) {
                $this->setErrorString('Parking Details: ' . $parkingMethod->getErrorString());
                return;
            }
            $this->result['parkingDetails'] = $parkingMethod->getResult();
        }

        if ($this->updateIdentificationDetails)
        {
            $idMethod = new UpdateReservationIdentificationDetails();
            $idMethod->call($this->params, $this->path, $this->validator, $this->fieldMapping, $this->filterKeyMapping, $this->filterMapping, $this->serviceproviderName);
            if ($idMethod->hasErrors()) {
                $this->setErrorString('Identification Details: ' . $idMethod->getErrorString());
                return;
            }
            $this->result['identificationDetails'] = $idMethod->getResult();
        }
    }

    public function getResult()
    {
        return $this->result;
    }
}