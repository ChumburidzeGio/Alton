<?php
namespace App\Resources\Parkandfly\Methods;

use App\Resources\Parkandfly\ParkandflyAbstractRequest;

class CancelReservation extends ParkandflyAbstractRequest
{
    protected $cacheDays = false;

    public function __construct()
    {
        parent::__construct('users/{user_id}/{user_hash}/reservations/{order_id}', self::METHOD_DELETE);
    }
}