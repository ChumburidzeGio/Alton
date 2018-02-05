<?php

namespace App\Resources\Adapters;

interface AdapterInterface
{
    /**
     * @return FieldInterface[]
     */
    public function inputs();

    /**
     * @return FieldInterface[]
     */
    public function outputs();

    /**
     * @return bool
     */
    public function collection();

    /**
     * @param array $input
     * @return array
     */
    public function process(Array $input);
}