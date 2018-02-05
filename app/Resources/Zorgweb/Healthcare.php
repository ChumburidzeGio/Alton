<?php

namespace App\Resources\Zorgweb;

use App\Resources\AbstractServiceRequest;

class Healthcare extends AbstractServiceRequest
{

    public $skipDefaultFields = true;

    protected $methodMapping = [
        'generateform'     => [
            'class'       => \App\Resources\Zorgweb\Methods\GenerateForm::class,
            'description' => 'Get contract contract fields'
        ],
        'generate_form_2018'     => [
            'class'       => \App\Resources\Zorgweb\Methods\GenerateForm2018::class,
            'description' => 'Get contract fields (2018)'
        ],
        'contract'     => [
            'class'       => \App\Resources\Zorgweb\Methods\SubmitForm::class,
            'description' => 'Submit contract contract fields'
        ],
    ];
}