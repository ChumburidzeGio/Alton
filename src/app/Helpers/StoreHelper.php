<?php namespace App\Helpers;

use App\Models\Field;
use App\Models\Resource;
use App\Interfaces\ResourceInterface;
use ArrayObject;
use FluentPDO;
use FluentStructure;
use Illuminate\Support\Facades\App;
use Komparu\Document\Aggregate\AggregateFactory;
use Komparu\Document\ArrayHelper;

use Komparu\Document\Contract\Document;
use Komparu\Document\Contract\Response;
use Komparu\Document\Contract\Result;
use Komparu\Document\Driver\DriverCollection;
use Komparu\Document\Driver\MongoDriver;
use Komparu\Document\Driver\MysqlDriver;
use Komparu\Document\EventHandler;
use Komparu\Document\FieldFactory;
use Komparu\Document\Filter\FilterFactory;
use Komparu\Document\MappingCollection;
use Komparu\Document\MappingFactory;
use Komparu\Document\OptionsFactory;
use Komparu\Document\Writer;
use Komparu\Input\Filtration\SiriusSanitizer;
use Komparu\Input\Rule\RuleFactory;
use Komparu\Input\Validation\SiriusValidator;
use Komparu\Resolver\Resolver;
use Komparu\Value\Type;
use Komparu\Value\ValueFactory;
use Komparu\Value\ValueInterface;
use League\Event\Emitter;
use MongoClient;
use MongoCode;
use PDO;
use Sirius\Filtration\Filtrator;
use Sirius\Validation\Validator;

class StoreHelper
{
    /**
     * Refresh the document. This means create it if it is not there
     * or update the fields if necessary if it is there.
     *
     * @param string $index
     * @param string $type
     *
     * @return Response
     */
    public static function refresh($index, $type)
    {

    }

    /**
     * @param string $index
     * @param string $type
     * @param Array|array $options
     *
     * @param bool $forceMap
     *
     * @return array
     */
    public static function get($index, $type, Array $options = [], $forceMap = false)
    {

        return [];
    }

    /**
     * @param string $index
     * @param string $type
     * @param string|int $id
     * @param Array|array $options
     *
     * @param bool $forceMap if you sant to force schema mapping
     *
     * @return array
     */
    public static function show($index, $type, $id, Array $options = [], $forceMap = false)
    {

    }


    /**
     * @param string $index
     * @param string $type
     * @param string|int $id
     * @param Array $options
     * @param bool $forceMap
     *
     * @return Document
     */
    public static function delete($index, $type, $id, Array $options = [], $forceMap = false)
    {

    }

    /**
     * @param string $index
     * @param string $type
     * @param Array|array $options
     *
     * @param bool $forceMap
     *
     * @return Document
     */
    public static function truncate($index, $type, Array $options = [], $forceMap = false)
    {

    }


    /**
     * @param string $index
     * @param string $type
     * @param $values
     * @param Array|array $options
     *
     * @param bool $forceMap
     *
     * @return Result
     * @internal param int|string $id
     */
    public static function insert($index, $type, $values, Array $options = [], $forceMap = false)
    {

    }


    /**
     * Upsert this values. Check on keys if similar.
     * Note that performance is shit so don't use for front end.
     *
     * @param $index
     * @param $type
     * @param $keys
     * @param $values
     * @param array $options
     *
     * @param bool $forceMap
     *
     * @return Result|mixed
     */
    public static function upsert($index, $type, $keys, $values, Array $options = [], $forceMap = false)
    {

    }


    /**
     * @param string $index
     * @param string $type
     * @param string|int $id
     * @param $values
     * @param Array|array $options
     *
     * @param bool $forceMap
     *
     * @return Result
     */
    public static function update($index, $type, $id, $values, Array $options = [], $forceMap = false)
    {

    }

    /**
     * @param string $index
     * @param string $type
     * @param array $values
     * @param Array $options
     *
     * @return Document
     */
    public static function bulk($index, $type, Array $values, Array $options = [])
    {

    }

    /**
     * Delete many documents with the conditions in the options.
     *
     * @param $index
     * @param $type
     * @param array $options
     */
    public static function deleteMany($index, $type, Array $options = [])
    {

    }

    /**
     * @param string $index
     * @param string $type
     *
     * @return Document
     */
    public static function info($index, $type)
    {

    }

    protected static function methodToAction($type)
    {

    }


    /**
     * !!!Only use this for development environments!!!
     *
     * It will patch, delete existings records and insert new ones.
     *
     * @param $index
     * @param $type
     * @param array $records
     * @param array $options
     * @param bool $truncate
     * @param bool $bulk
     */
    public static function seed($index, $type, Array $records, Array $options = [], $truncate = true, $bulk = false)
    {
        StoreHelper::map($index, $type);
        if( ! isset($options['conditions']) && $truncate){
            StoreHelper::truncate($index, $type);

            if($bulk){
                StoreHelper::bulk($index, $type, $records, $options);
                return;
            }
        }

        foreach($records as $record){
            if( ! $truncate){
                StoreHelper::upsert($index, $type, array_only($record, ['__id']), $record, $options);
            }else{
                StoreHelper::insert($index, $type, $record, $options);
            }
        }
    }
}