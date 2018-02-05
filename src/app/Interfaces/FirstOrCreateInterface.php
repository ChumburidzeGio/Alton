<?php

namespace App\Interfaces;

interface FirstOrCreateInterface
{
    /**
     * Get the fields that are unique and should be
     * checked it they already exists with the provided
     * values.
     *
     * @return Array
     */
    public function getFirstOrCreateKeys();
}