<?php

namespace App\Interfaces;

interface ValidatorInterface
{
    /**
     * @param array $input
     * @param string $ruleset = null
     * @return bool
     */
    public function validate(Array $input, $ruleset = null);

    /**
     * @return array
     */
    public function messages();
}