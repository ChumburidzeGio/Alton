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

class DocumentHelper
{
    /**
     * Map (patch) the document, needed for some document drivers.
     *
     * @param string $index
     * @param string $type
     *
     * @return Response
     */
    public static function map($index, $type)
    {
        return static::invoke($index, $type, 'map', []);
    }

    /**
     * @param string $index
     * @param string $type
     * @param Array|array $options
     *
     * @param bool $forceMap
     *
     * @return Response
     */
    public static function get($index, $type, Array $options = [], $forceMap = false)
    {
        //        /* @var \App\Controllers\DocumentController $docController */
        //        $docController = App::make('App\Controllers\DocumentController');
        //
        //        // TODO: find out why this shit makes caruinsurance work slow as hell
        //        if( ! ($index === 'product' and $type === 'settings')){
        //            $options = new \ArrayObject($options);
        //            event('document.' . $index . '.index', [$docController, $index, $type, $options]);
        //            event('document.' . $index . '.' . $type . '.index', [$docController, $index, $type, $options]);
        //            $options = $options->getArrayCopy();
        //        }

        $collection = static::invoke($index, $type, 'get', [$options], $forceMap);

        //        if( ! ($index === 'product' and $type === 'settings')){
        //            event('document.' . $index . '.collection', ['response' => $collection, 'client' => $docController->getClient()]);
        //            event('document.' . $index . '.' . $type . '.collection', ['response' => $collection, 'client' => $docController->getClient()]);
        //        }
        return $collection;
    }

    /**
     * @param string $index
     * @param string $type
     * @param string|int $id
     * @param Array|array $options
     *
     * @param bool $forceMap if you sant to force schema mapping
     *
     * @return Document
     */
    public static function show($index, $type, $id, Array $options = [], $forceMap = false)
    {
        $doc = static::invoke($index, $type, 'show', [$id, $options], $forceMap);
        event('document.' . $index . '.view', ['document' => $doc, 'options' => $options, 'type' => $type]);
        event('document.' . $index . '.' . $type . '.view', ['document' => $doc, 'options' => $options]);
        return $doc;
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
        return static::invoke($index, $type, 'delete', [$id, $options], $forceMap);
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
        return static::invoke($index, $type, 'truncate', [$options], $forceMap);
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
        $dataObj = new ArrayObject($values);
        event('document.' . $index . '.insert', [$dataObj]);
        return static::invoke($index, $type, 'insert', [$dataObj->getArrayCopy(), $options], $forceMap);
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
        $docController = App::make('App\Controllers\DocumentController');
        $docs          = static::get($index, $type, ['filters' => $keys], $forceMap)->documents();

        try{
            return $docs->count() ? static::update($index, $type, $docs[0]->__id, $values, $options, $forceMap) : static::insert($index, $type, $values, $options, $forceMap);
        }catch(\Exception $ex){
            echo $ex->getMessage() . PHP_EOL;
            //WHO FUCKING CARES
        }
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
        return static::invoke($index, $type, 'update', [$id, $values, $options], $forceMap);
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
        return static::invoke($index, $type, 'bulk', [$values, $options]);
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
        $response = static::get($index, $type, $options);

        foreach($response->documents() as $document){
            static::delete($index, $type, $document['__id']);
        }
    }

    /**
     * @param string $index
     * @param string $type
     *
     * @return Document
     */
    public static function info($index, $type)
    {
        return static::invoke($index, $type, 'info', []);
    }

    protected static function methodToAction($type)
    {
        switch($type){
            case 'get':
                return 'index';
            case 'insert':
                return 'store';
            case 'delete':
                return 'destroy';
            default:
                return $type;
        }
    }


    public static function getMysqlDriver($emitter,$valueFactory,$arrayHelper)
    {


        // Get a working mysql connection
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8;', getenv('MYSQL_PRODUCT_HOST'), getenv('MYSQL_PRODUCT_NAME'));
        $pdo = new PDO($dsn, getenv('MYSQL_PRODUCT_USER'), getenv('MYSQL_PRODUCT_PASS'), [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            PDO::ATTR_PERSISTENT         => true
        ]);


        // Setup the driver with its dependencies
        $builder = new FluentPDO($pdo, new FluentStructure('__id'));


        $events       = new \Komparu\Document\EventHandler($emitter);
        $helper       = new \Komparu\Document\Driver\Mysql\Helper($valueFactory, $arrayHelper);

        $events->addListener(new \Komparu\Document\Driver\Mysql\Events\ConditionsListener);
        $events->addListener(new \Komparu\Document\Driver\Mysql\Events\FiltersListener);
        $events->addListener(new \Komparu\Document\Driver\Mysql\Events\OptionsListener($valueFactory));
        $events->addListener(new \Komparu\Document\Driver\Mysql\Events\AggregatesListener);

        return new MysqlDriver($events, $builder, $helper);
    }


    /**
     * @return MongoDriver
     */
    public static function getMongoDriver($emitter)
    {

        // check for the environmental variable to skip mongo test

        // Creating a MongoDB connection
        // Data Source Name from environment params
        $dsn    = sprintf('mongodb://%s:%s;', getenv('MONGO_HOST'), getenv('MONGO_PORT'));
        $client = new MongoClient($dsn);
        $db     = $client->selectDB(getenv('MONGO_NAME'));
        $db->execute(new MongoCode(file_get_contents('tests/data/mongo.js')));

        // Setup the events
        $events  = new \Komparu\Document\EventHandler($emitter);
        $helper  = new \Komparu\Document\Driver\Mongo\Helper($db);

        $events->addListener(new \Komparu\Document\Driver\Mongo\Events\AggregatesListener());
        $events->addListener(new \Komparu\Document\Driver\Mongo\Events\AutoCalculatedFieldsListener());
        $events->addListener(new \Komparu\Document\Driver\Mongo\Events\FieldsListener());
        $events->addListener(new \Komparu\Document\Driver\Mongo\Events\FiltersListener());
        $events->addListener(new \Komparu\Document\Driver\Mongo\Events\OptionsListener());
        $events->addListener(new \Komparu\Document\Driver\Mongo\Events\ConditionsListener());

        return new MongoDriver($events, $helper);
    }


    /**
     * Only works on Schemas!!!
     *
     * @param string $index
     * @param string $type
     * @param string $method
     * @param array $arguments
     *
     * @param bool $forceMap
     *
     * @return mixed
     * @throws \Exception
     */
    public static function invoke($index, $type, $method, Array $arguments, $forceMap = false)
    {


        $name = sprintf('%s.%s', $index, $type);

        /** @var \App\Resource $resource */
        $resource = FactoryHelper::retrieveModel(Resource::class, 'name', $name);


        $resource->populateFields(self::methodToAction($method));
        $resourceFields = $resource->fields;

        if( ! strstr($resource->name, '.')){
            $message = 'A REST resource must implement a dot in the name, like "product.simonly".
                The resource "%s" is therefor not valid.';
            throw new \Exception(sprintf($message, $resource->name));
        }


        // Get the index and type from the resource name
        list($index, $type) = explode('.', $resource->name);

        $fields  = [];
        $rules   = [];
        $options = [];
        $unique  = [];

        $options['auto_generated_id'] = false;

        // We only need outputs to be mapped to the document package
        foreach($resourceFields as $field){
            if($field->strategy == Field::STRATEGY_AUTO_INCREMENTED_PRIMARY_KEY){
                $options['auto_generated_id'] = true;
            }

            if($field->hasFilter(Field::FILTER_CONDITION2)){
                $options['conditions2'] = true;
            }
            // We are only interested in fields that need to be patched to
            // the Document package. All other fields have nothing to do
            // with the Document package.
            if( ! $field->hasFilter(Field::FILTER_PATCH)){
                continue;
            }

            if($field->hasFilter(Field::FILTER_UNIQUE)){
                $unique[] = $field->name;
            }

            // Add the basic resource field properties to the list of Document fields
            $fields[$field->name] = $field->toArray();

            // Map the right type to use in the Document package
            $fields[$field->name]['type'] = self::getDocumentType($field);

            // No rules? Nothing to add then.
            if( ! $field->rules){
                continue;
            }
            // Add the rules to the list of rules
            $rules[$field->name] = $field->rules;
        }




        $driver     = $resource->driver;
        $plainarray = $resource->hasBehaviour(Resource::BEHAVIOUR_PLAINARRAY);

        $resolver         = new Resolver();
        $fieldFactory     = new FieldFactory($resolver);
        $ruleFactory      = new RuleFactory($resolver);
        $valueFactory     = new ValueFactory();
        $aggregateFactory = new AggregateFactory($valueFactory, $resolver);
        $filterFactory    = new FilterFactory($valueFactory, $resolver);
        $emitter          = new Emitter();
        $eventHandler     = new EventHandler($emitter);

        $optionsFactory = new OptionsFactory($aggregateFactory, $filterFactory, $resolver);

        /** @var MappingFactory $mappingFactory */
        $mappingFactory    = new MappingFactory($fieldFactory, $ruleFactory, $aggregateFactory, $eventHandler, $resolver);

        $mapping           = $mappingFactory->fromArray(array_merge($options, compact('index', 'type', 'fields', 'rules', 'driver', 'unique', 'plainarray')));

        $arrayHelper       = new ArrayHelper();

        $mysqlDriver = self::getMysqlDriver($emitter,$valueFactory,$arrayHelper);
//        $mongoDriver = self::getMongoDriver($emitter);

        $driverCollection  = new DriverCollection([$mysqlDriver]);
        $mappingCollection = new MappingCollection([$mapping]);
        $validator         = new Validator();
        $validator         = new SiriusValidator($validator, $ruleFactory);
        $filtrator         = new Filtrator();
        $sanitizer         = new SiriusSanitizer($filtrator);

        $writer = new Writer(
            $driverCollection,
            $mappingCollection,
            $mappingFactory,
            $optionsFactory,
            $eventHandler,
            $validator,
            $sanitizer,
            $arrayHelper);

        return call_user_func_array([$writer, $method], $arguments);
    }


    /**
     * @param Field $field
     * @param Registry $registry
     *
     * @return string
     * @throws \Exception
     */
    public static function getDocumentType(Field $field, Registry $registry = null)
    {
        $mapped = static::mapDocumentType($field->type, $field, $registry);

        // Just check if we have found a mapped type
        if( ! $mapped){

            throw new \Exception(sprintf('Cannot map %s to a document type', $field->id));
        }

        return $mapped;
    }

    /**
     * @param $type
     * @param Registry $registry
     *
     * @return string
     */
    public static function mapDocumentType($type, Field $field = null, Registry $registry = null)
    {

        //$type = ucfirst($type);
        /**
         * Convert based on the file: schema/events.php
         */
        switch($type){

            case Type::STRING:
            case Type::HEADING:
            case Type::IMAGE:
            case Type::EMAIL:
            case Type::LICENSEPLATE:
            case Type::PHONENUMBER:
            case Type::PHONENUMBERINT:
            case Type::POSTALCODE:
                return ValueInterface::TYPE_STRING;

            case Type::SHORTSTRING:
                return ValueInterface::TYPE_SHORTSTRING;

            case Type::TEXT:
            case Type::URL:
                return ValueInterface::TYPE_TEXT;

            case Type::BOOLEAN:
                return ValueInterface::TYPE_BOOLEAN;

            case Type::INTEGER:
            case Type::POINTS:
                return ValueInterface::TYPE_INTEGER;

            case Type::DECIMAL:
            case Type::PRICE:
            case Type::RATING:
                return ValueInterface::TYPE_DECIMAL;

            case Type::FLOAT:
                return ValueInterface::TYPE_FLOAT;

            case Type::ARR:
                return ValueInterface::TYPE_ARRAY;

            case Type::OBJECT:
                return ValueInterface::TYPE_OBJECT;

            case Type::DATE:
                return ValueInterface::TYPE_DATE;
            case Type::DATETIME:
                if($field && $field->name == ResourceInterface::CREATED_AT){
                    return ValueInterface::TYPE_CREATED_AT;
                }
                if($field && $field->name == ResourceInterface::UPDATED_AT){
                    return ValueInterface::TYPE_UPDATED_AT;
                }

                return ValueInterface::TYPE_DATETIME;

            case Type::DATESTRING:
                return ValueInterface::TYPE_DATETIME;

            case Type::BLOB:
                return ValueInterface::TYPE_BLOB;

            case Type::CHOICE:
                // this could be enum, but have no enum type
                return ValueInterface::TYPE_STRING;
        }

        dd('not found...');
        return null;
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
        DocumentHelper::map($index, $type);
        if( ! isset($options['conditions']) && $truncate){
            DocumentHelper::truncate($index, $type);

            if($bulk){
                DocumentHelper::bulk($index, $type, $records, $options);
                return;
            }
        }

        foreach($records as $record){
            if( ! $truncate){
                DocumentHelper::upsert($index, $type, array_only($record, ['__id']), $record, $options);
            }else{
                DocumentHelper::insert($index, $type, $record, $options);
            }
        }
    }
}