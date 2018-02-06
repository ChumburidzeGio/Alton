<?php

namespace App\Exception;

/**
 * Custom exception service requests that do not exist
 *
 * Class ServiceError
 */
class NotExistError extends \Exception
{

    /**
     *
     */
    public function __construct($method)
    {
        $message = sprintf('Service method \'%s\' does not exist', $method);
        parent::__construct($message);
    }

}