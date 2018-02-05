<?php

namespace App\Interfaces;

interface ModelInterface
{
    /**
     * @return string
     */
    public function getKeyName();

    public static function create(array $attributes);

    public function update(array $attributes = array());

    public function delete();

    public function load($relations);

    public static function firstOrNew(array $attributes);

    public function fill(array $attributes);
}