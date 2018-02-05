<?php

namespace App\Exception;

use App\Models\Field;

/**
 * Custom exception to handle validation.
 * Can be move to separate package later.
 *
 * Class NoMatchingChildResourceData
 */
class NoMatchingChildResourceData extends \Exception
{
    /**
     * @var Field
     */
    private $field;

    /**
     * NoMatchingChildResourceData constructor.
     *
     * @param Field $field
     */
    public function __construct(Field $field, $message = null)
    {
        $this->field = $field;

        if ($message === null)
            $message = 'Cannot map `'. $field->name .'` in `'. $field->resource->name.'`';

        parent::__construct($message);
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
    }

}