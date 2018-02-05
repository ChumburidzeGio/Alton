<?php
namespace App\Resources\General\Methods;

use App\Helpers\ResourceHelper;
use App\Listeners\Resources2\RestListener;
use App\Resources\AbstractMethodRequest;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Config, Response, File, URL;

class Storage extends AbstractMethodRequest
{
    protected $cacheDays = false;
    protected $result = [];
    public $resource2Request = true;

    protected $resource;
    protected $filename;

    public function setParams(array $params)
    {
        $this->resource = $params['resource'];
        if(isset($params['filename'])){
            $this->filename = $params['filename'];
        }
    }

    /**
     * we get an file and add it to the queue, return it's future location
     */
    public function executeFunction()
    {
        if(Request::method() === 'POST'){

            /** @var UploadedFile $file */
            $files = Input::file('file');

            $this->result = ['file_location' => []];

            foreach($files as $file){
                $name      = $file->getClientOriginalName();
                $filename  = pathinfo($name, PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();

                $uniqueFile = sprintf("%s-%s.%s", $filename, str_random(5), $extension);

                $fullStoredPath = null;

                switch(Input::get('visibility', 'public')){
                    case 'public':
                        $cdn         = ((app()->configure('cdn')) ? '' : config('cdn.domain'));
                        $uriLocation = "{$cdn}/uploads/{$this->resource}/{$uniqueFile}";

                        // Base64 encode because the document always json_encodes everything
                        ResourceHelper::callResource2('jobs.general', [
                            'queue'   => 'static',
                            'payload' => [
                                'job'  => 'StorageJob',
                                'data' => [
                                    'uri'  => $uriLocation,
                                    'path' => "{$this->resource}/{$uniqueFile}",
                                    'file' => base64_encode(File::get($file->getRealPath())),
                                ]
                            ]
                        ], RestListener::ACTION_STORE);

                        break;
                    case 'private':
                        $path = storage_path("uploads/{$this->resource}");
                        File::makeDirectory($path, 493, true, true);
                        $file->move($path, $uniqueFile);
                        //move_uploaded_file($file->getFilename(), $path);

                        // TODO Add a Job somewhere to distribute this file

                        // TODO store this file and it's access rights somewhere

                        $uriLocation = URL::route('files', [ 'resource' => $this->resource, 'filename' => $uniqueFile]);
                        break;
                }

                array_push($this->result['file_location'], $uriLocation);
            }
            return;
        }else{
            $this->result = ['file_location' => storage_path("uploads/{$this->resource}/{$this->filename}")];
        }
    }

    public function getResult()
    {
        return $this->result;
    }
}