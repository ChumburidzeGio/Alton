<?php namespace Komparu\Translation;

use App\Helpers\ResourceHelper;
use App\Listeners\Resources2\RestListener;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;

class ApiLoader extends FileLoader implements LoaderInterface {

    /**
     * All of the namespace hints.
     *
     * @var array
     */
    protected $resource = null;
    protected $translations = null;
    protected $conditions = [];

    /**
     * Create a new file loader instance.
     *
     * @param  string $resource
     * @param  \Illuminate\Filesystem\Filesystem $files
     * @param $path
     */
    public function __construct($resource, Filesystem $files, $path)
    {
        $this->resource = $resource;
        parent::__construct($files, $path);
    }

    /**
     * @param array $conditions
     */
    public function setConditions($conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        if (is_null($namespace) || $namespace == '*')
        {
            $val = null;
            try{
                $translations = ResourceHelper::callResource2($this->resource, $this->conditions, RestListener::ACTION_SHOW, $locale);
                $val = array_get($translations, $group, null);
            }catch(\Exception $ex){
            }

            if(is_null($val)){
                return parent::load($locale, $group, $namespace);
            }else{
                return $val;
            }
        }

        return $this->loadNamespaced($locale, $group, $namespace);
    }

}
