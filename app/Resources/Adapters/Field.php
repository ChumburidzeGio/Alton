<?php

namespace App\Resources\Adapters;

use ArrayObject;

class Field extends ArrayObject implements FieldInterface
{
    /**
     * Field constructor.
     * @param string $name
     * @param string $type
     * @param string $label
     * @param string $rules
     * @param string $filters
     * @param string $description
     */
    public function __construct($name, $type, $label, $rules = '', $filters = '', $description = null)
    {
        parent::__construct(compact('name', 'type', 'label', 'rules', 'filters', 'description'));
    }

    /**
     * @return string
     */
    public function rules()
    {
        return $this['rules'];
    }

    /**
     * @return string
     */
    public function filters()
    {
        return $this['filters'];
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this['name'];
    }

    /**
     * @return string
     */
    public function label()
    {
        return $this['label'];
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this['type'];
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this['description'];
    }
}