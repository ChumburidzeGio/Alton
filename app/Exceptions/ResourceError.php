<?php

namespace App\Exception;

//use App\Helpers\LangHelper;
use App\Helpers\LangHelper;


class ResourceError extends \Exception
{

    private $messageArr = [];

    /**
     * ServiceError constructor.
     *
     * @param Resource $resource
     * @param array $input
     * @param string $message
     */
    public function __construct($resourceOrString, Array $input, Array $messages)
    {
        $this->messageArr['code']        = 400;
        $this->messageArr['message']     = 'Bad request';
        $this->messageArr['resource']    = is_a($resourceOrString, 'App\Models\Resource') ? $resourceOrString->name : $resourceOrString;
        $this->messageArr['description'] = 'Something went wrong in this resource request';
        $this->messageArr['errors']      = $this->translateMessages($messages);

        parent::__construct($this->messageArr['description']);
    }

    public function getMessages()
    {
        return $this->messageArr;
    }

    private function translateMessages($messages)
    {
        foreach($messages as &$message){
            $transKey = 'errors.' . $message['code'];
            $trans    = LangHelper::getByLangHeader($transKey);
            if($trans){
                $message['message'] = $trans;
            }
        }
        return $messages;
    }
}