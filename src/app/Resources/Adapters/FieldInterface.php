<?php

namespace App\Resources\Adapters;

interface FieldInterface
{
    /**
     * @return string
     */
    public function rules();
    /**
     * @return string
     */
    public function filters();

    /**
     * @return string
     */
    public function name();

    /**
     * @return string
     */
    public function label();

    /**
     * @return string
     */
    public function type();

    /**
     * @return string
     */
    public function description();
}