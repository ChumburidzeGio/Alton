<?php

namespace App\Exception;
use App\Models\Resource;

/**
 * Custom exception to handle validation.
 * Can be move to separate package later.
 *
 * Class ServiceError
 */
class PrettyServiceError extends \Exception
{
    protected $resource;
    protected $customMessage;
    protected $input = [];

    /**
     * ServiceError constructor.
     * @param Resource $resource
     * @param array $input
     * @param string $message
     */
    public function __construct(Resource $resource, Array $input, $message)
    {
        parent::__construct($message);

        $this->resource = $resource;
        $this->customMessage = $message;
        $this->input = $input;
    }

    /**
     * @return Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return array
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return string
     */
    public function getCustomMessage()
    {
        return $this->customMessage;
    }
}