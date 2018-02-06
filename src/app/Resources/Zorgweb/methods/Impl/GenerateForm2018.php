<?php

namespace App\Resources\Zorgweb\Methods;

use App\Interfaces\ResourceInterface;
use Config, Log;

class GenerateForm2018 extends GenerateForm
{
    public $skipDefaultFields = true;

    public function __construct()
    {
        parent::__construct(2018);
    }
}