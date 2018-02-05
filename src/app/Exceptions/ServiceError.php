<?php

namespace App\Exception;
use App\Models\Resource;

/**
 * Custom exception to handle validation.
 * Can be move to separate package later.
 *
 * Class ServiceError
 */
class ServiceError extends \Exception
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
        if(is_array($message)) {
            $message = json_encode($message);
        }

        
        $message = sprintf('Error in calling service "%s" (%s) with input: "%s"', $resource->name, $message , json_encode($input));
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
     * @return array
     */
    public function getCustomMessage()
    {
        return $this->customMessage;
    }
}