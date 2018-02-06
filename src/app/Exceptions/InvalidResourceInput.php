<?php

namespace App\Exception;

use App\Models\Resource;

/**
 * Custom exception to handle validation.
 * Can be move to separate package later.
 *
 * Class InvalidResourceInput
 */
class InvalidResourceInput extends \Exception
{
    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $input = [];

    /**
     * InvalidResourceInput constructor.
     * @param Resource $resource
     * @param array $messages
     * @param array $input
     * @param string $message
     */
    public function __construct(Resource $resource, Array $messages, Array $input, $message = 'Invalid resource input')
    {
        parent::__construct($message);

        $this->resource = $resource;
        $this->messages = $messages;
        $this->input = $input;
    }

    /**
     * @return Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getInput()
    {
        return $this->input;
    }
}