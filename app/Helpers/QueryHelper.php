<?php namespace App\Helpers;

use App\Constants\FieldTypes;
use App\Models\Field;
use App\Interfaces\ResourceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Komparu\Value\ValueInterface;

class QueryHelper
{
    /**
     * Create or update underlying resource table
     *
     * @param string $index
     * @param string $type
     *
     * @param $resource
     * @return array
     * @throws \Exception
     */
    public static function createOrUpdateTable($index, $type, $resource)
    {
        //Check if the table exists and if not create it
        if(!$resource){
            throw new \Exception('Resource not found. Did you forget to seed?');
        }


        $schemaBuilder = DB::connection('mysql_product')->getSchemaBuilder();
        $pdo = DB::connection('mysql_product')->getPdo();
        $tableName = $index . '_' . $type;
        if(!$schemaBuilder->hasTable($tableName)){
            //Need to create the table here
            self::createTable($tableName, $pdo, true);
        }
        $results = self::processFields($resource, $tableName, $pdo);

        //If we have conditions we need to create the extended table and fields
        $conditions = self::getFieldsByFilter($resource, Field::FILTER_CONDITION);
        if(!empty($conditions)){
            if(!$schemaBuilder->hasTable($tableName . '_extended')){
                self::createExtendedTable($tableName . '_extended' , $pdo);
            }
            $results[] = self::processFields($resource, $tableName . '_extended', $pdo, true);
        }

        return $results;
    }

    /**
     * @param string $index
     * @param string $type
     * @param $options
     * @param $resource
     * @return array
     * @internal param bool $forceMap
     *
     */
    public static function index($index, $type, $resource, $options = [])
    {
        //Get table name
        $tableName = self::getTableName($index, $type);
        //Shorthand for more readable select
        $tableShortHand = substr($tableName, 0, 3);

        //Get variables for sorting
        $sortVariables = self::getSortData($options);
        //Get the base query for the table
        $query = self::getBaseQuery($resource, $tableName, $tableShortHand, $sortVariables['offset'], $sortVariables['limit']);

        $query = self::applyWheres($query, $tableShortHand, $options['filters'], $resource);

        //Get the initial selects for the base table
        $selects = self::getSelects($resource, $tableShortHand);
        if($selects){
            $query->select($selects);
        }

        if(isset($options['conditions']) && !empty($options['conditions'])){
            $query = self::applyConditions($query, $tableName, $tableShortHand, $options['conditions'], $resource);
        }

        //fetch and ovewrite if necessary
        return self::fetchAndOverride($query, $resource, $options['conditions']['conditions']);
    }

    /**
     * @param string $index
     * @param string $type
     * @param string|int $id
     * @param $options
     * @param $resource
     * @return mixed
     */
    public static function show($index, $type, $id, $resource, $options = [])
    {
        //Call index with filter __id = $id
        $options['filters'][ResourceInterface::__ID] = $id;
        return head(self::index($index,$type, $resource,$options));
    }


    /**
     * @param string $index
     * @param string $type
     * @param string|int $id
     * @param $options
     * @param $resource
     *
     */
    public static function destroy($index, $type, $id, $resource, $options = [])
    {

    }

    /**
     * @param string $index
     * @param string $type
     * @param array $options
     *
     * @param $resource
     */
    public static function truncate($index, $type, $resource, $options = [])
    {

    }


    /**
     * @param string $index
     * @param string $type
     * @param $values
     * @param $resource
     * @param array $options
     *
     */
    public static function store($index, $type, $values, $resource, $options = [])
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
     * @param $resource
     * @param array $options
     */
    public static function storeOrUpdate($index, $type, $keys, $values, $resource, $options = [])
    {

    }


    /**
     * @param string $index
     * @param string $type
     * @param string|int $id
     * @param $values
     * @param $resource
     * @param array $options
     *
     */
    public static function update($index, $type, $id, $values, $resource, $options = [])
    {

    }

    /**
     * @param string $index
     * @param string $type
     * @param array $values
     * @param $resource
     * @param array $options
     */
    public static function bulk($index, $type, $values, $resource, $options = [])
    {

    }

    /**
     * Delete many documents with the conditions in the options.
     *
     * @param $index
     * @param $type
     * @param $resource
     * @param array $options
     */
    public static function destroyMany($index, $type, $resource, $options = [])
    {

    }


    /**
     * !!!Only use this for development environments!!!
     *
     * It will createOrUpdate, delete existing records and insert new ones.
     *
     * @param $index
     * @param $type
     * @param array $records
     * @param $resource
     * @param array $options
     * @param bool $truncate
     */
    public static function seed($index, $type, $records, $resource, $options = [], $truncate = true)
    {

    }


    /**
     * Takes a query and conditions and loops applying left joins to handle extended tables.
     * For every condition you will essentially have a left join
     *
     * @param $query
     * @param $tableName
     * @param $tableShortHand
     * @param $conditions
     * @param $resource
     * @return mixed
     */
    private static function applyConditions($query, $tableName, $tableShortHand, $conditions, $resource)
    {

        if(!empty($conditions['conditions'])){
            //Find out which is the condition based on the orders
            foreach ($conditions['conditions'] as $condition => $value){
                $query->leftJoin($tableName . '_extended AS '.$condition, function ($join) use ($resource, $condition, $tableName, $value, $tableShortHand){
                    $leftPredicate = $tableShortHand .'.__id';
                    $rightPredicate = $condition .'.__id';

                    $join->on($leftPredicate, '=', $rightPredicate)
                        ->where($condition .'.__condition', '=', $condition)
                        ->where($condition . '.__value', '=', $value);
                });
                foreach (self::getExtendableFieldNames($resource) as $field){
                    $query->addSelect($condition . '.' . $field. ' as __' . $condition . '_' . $field );
                }
            }
        }
        return $query;
    }

    /**
     * Apply supplied where filters to a query. You must alias the table and provide the alias
     * in the tableShortHand parameter.
     *
     * @param $query
     * @param $tableShortHand
     * @param $wheres
     * @return mixed
     */
    private static function applyWheres($query, $tableShortHand, $wheres)
    {
        foreach ($wheres as $whereColumn => $whereValue){
            if(is_array($whereValue)){
                $query = $query->whereIn($tableShortHand . '.' .$whereColumn, $whereValue);
            }else{
                $query = $query->where($tableShortHand . '.' .$whereColumn, $whereValue);
            }
        }
        return $query;
    }

    /**
     * Get the base query for a given resource and table name. You must provide a shorthand name for the table
     * so it can be aliased.
     *
     * @param $resource
     * @param $tableName
     * @param $tableShortHand
     * @param $offset
     * @param $limit
     * @return mixed
     */
    private static function getBaseQuery($resource, $tableName, $tableShortHand, $offset, $limit)
    {
        $database = $resource->database ?? 'mysql_product';
        $query =  DB::connection($database)->table($tableName . ' AS '. $tableShortHand)->skip($offset)->take($limit);
        return $query;
    }

    /** Get the selects for a table
     * @param $resource
     * @param $tableShortHand
     * @param bool $forExtended
     * @return array
     */
    private static function getSelects($resource, $tableShortHand, $forExtended = false)
    {
        $selects = [];
        foreach ($resource->fields as $field){
            if($field->hasFilter(Field::FILTER_PATCH)){
                $fieldName = str_replace('.', '_', $field->name);
                $selects[] = $tableShortHand . '.' . $fieldName . ' AS ' . $fieldName;
            }
        }
        return $selects;
    }


    /**
     * Get the table name based on a resource index and type
     *
     * @param $index
     * @param $type
     * @return string
     */
    private static function getTableName($index, $type )
    {
        return $index . '_' . $type;

    }

    /** Get the sorting params from the input
     *
     * @param $input
     * @return array
     */
    private static function getSortData($input)
    {

        $offset     = $input['offset'] ?? 0;
        $limit      = $input['limit'] ?? ValueInterface::INFINITE;

        return compact('offset', 'limit');
    }

    /**Get the extendable fields of a resource
     *
     * @param $resource
     * @return array
     */
    private static function getExtendableFieldNames($resource)
    {
        $fields = [];
        foreach ($resource->fields as $field){
            if($field->hasFilter(Field::FILTER_EXTENDABLE)){
                $fieldName = str_replace('.', '_', $field->name);
                $fields[] = $fieldName;
            }
        }
        return $fields;
    }

    private static function getFieldsByFilter($resource, $filter)
    {
        $fields = [];
        foreach ($resource->fields as $field){
            if($field->hasFilter($filter)){
                $fields[] = $field;
            }
        }
        return $fields;
    }

    /**
     * Calls get on a query , overwrites any extended fields according to conditions and
     * returns the result
     *
     * @param $query
     * @param $resource
     * @param $conditions
     * @return mixed
     */
    private static function fetchAndOverride($query, $resource, $conditions)
    {
        $data = $query->get();
        $data = self::simpleToArray($data);
        foreach ($data as $row => $datum){
            foreach (self::getExtendableFieldNames($resource) as $field){
                foreach($conditions as $condition => $value){
                    $conditionalName = '__' . $condition .'_' . $field;
                    if($datum[$conditionalName]){
                        $data[$row][$field] = $datum[$conditionalName];
                    }
                    unset($data[$row][$conditionalName]);
                }
            }
        }
        return $data;
    }

    private static function simpleToArray(Collection $data)
    {
        $data->transform(function ($item, $key){
            $item = (array) $item;
            if(isset($item['resource_name'], $item['resource_id'])){
                $item['resource'] = [];
                $item['resource']['name'] = $item['resource_name'];
                $item['resource']['id'] = $item['resource_id'];
            }
            return $item;
        });
        return $data->all();
    }

    /**
     * WARNING: This is a raw unsafe function. You must make sure table name is safe before passing it here.
     *
     * Takes a tableName and a PDO object and creates the basic table in the database. By default it will use an
     * autoincremented identifier field unless you provide false for the third parameter. Created and Updated at fields
     * are also created by default for you.
     * @param $tableName
     * @param $pdo
     * @param bool $autoIncrementId
     * @throws \Exception
     */
    private static function createTable($tableName, $pdo, $autoIncrementId = true)
    {

        // Setup the primary key
        $identifier =  '__id INT(11) UNSIGNED'. ($autoIncrementId ? ' AUTO_INCREMENT' : '') .' PRIMARY KEY';

        $sql = sprintf('CREATE TABLE %s (%s) DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci', $tableName, $identifier);

        if ($pdo->exec($sql) !== 0) {
            throw new \Exception('Table could not be created: `' . json_encode($pdo->errorInfo()) . '`');
        }

        // Always add the timestamps
        self::createColumn(FieldTypes::CREATED_AT, 'created_at', $tableName, $pdo);
        self::createColumn(FieldTypes::UPDATED_AT, 'updated_at', $tableName, $pdo);
    }

    /**
     * WARNING: This is a raw unsafe function. You must make sure table name is safe before passing it here.
     *
     * Takes a tableName and a PDO object and creates an extended table (__id,__condition, __value)
     *
     * @param $tableName
     * @param $pdo
     * @throws \Exception
     */
    private static function createExtendedTable($tableName, $pdo)
    {

        $sql = sprintf('CREATE TABLE `%s` (`__id` varchar(255) NOT NULL, `__condition` char(255) NOT NULL, `__value` varchar(255) NOT NULL, PRIMARY KEY (`__id`,`__condition`,`__value`)) DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci', $tableName);

        if ($pdo->exec($sql) !== 0) {
            throw new \Exception('Table could not be created: `' . json_encode($pdo->errorInfo()) . '`');
        }
    }

    /**WARNING: This is a raw unsafe function. You must make sure table, name  and after are safe before passing it here.
     *
     * Will create or try to alter a field in the specified table in the database. If the function is unable to perform the
     * alteration due to an incompatible type, it will throw an exception with the required ALTER TABLE statement that
     * will have to be performed.
     * @param $type
     * @param $name
     * @param $table
     * @param $pdo
     * @param bool $autoIncrement
     * @param null $after
     * @return bool
     * @throws \Exception
     */
    private static function createColumn($type, $name, $table, $pdo,$isKey = false, $autoIncrement = false, $after = null)
    {
        try{
            $type = self::getDatabaseFieldType($type);
        }catch (\Exception $ex){
            $message = 'Creating column failed because: ' . $ex->getMessage();
            throw new \Exception($message);
        }

        if ($autoIncrement) {
            $type .= ' auto_increment';
        }

        $currentType          = null;
        $currentDefinitionSql = sprintf('SHOW COLUMNS FROM %s WHERE Field = ?', $table);
        $stat                 = $pdo->prepare($currentDefinitionSql);

        if ($stat->execute([$name])) {
            foreach ($stat as $columnInfo) {
                $currentType = $columnInfo['Type'];
                if ($columnInfo['Default']) {
                    $currentType .= ' DEFAULT ' . $columnInfo['Default'];
                }
                if ($columnInfo['Extra']) {
                    $currentType .= ' ' . $columnInfo['Extra'];
                }
            }
        }

        $alterSql = sprintf('ALTER TABLE %s ADD %s %s', $table, $name, $type);

        if ($after) {
            $alterSql .= ' AFTER ' . $after;
        }

        if ($currentType) {
            $possibleConverts = [
                'varchar(10)'   => ['varchar(255)', 'text', 'longtext'],
                'varchar(255)'  => ['text', 'longtext'],
                'text'          => ['longtext'],
                'date'          => ['datetime'],
                'decimal(10,4)' => ['decimal(14,4)'],
            ];

            $changeSql = sprintf('ALTER TABLE %s CHANGE %s %s %s', $table, $name, $name, $type);

            if (isset($possibleConverts[$currentType]) && in_array($type, $possibleConverts[$currentType])) {
                $alterSql = $changeSql;
            } else if ($currentType != $type) {
                throw new \Exception('Column `' . $name . '` already exists but has other type. Current: `' . $currentType . '`, wanted: `' . $type . '` - SQL to change: `' . $changeSql . '`');
            } else {
                return false;
            }
        }
        if($isKey){
            $alterSql .= sprintf(', ADD INDEX `%s` (`%s`)', $name, $name);
        }

        $result = $pdo->exec($alterSql);

        if ($result === false) {
            throw new \Exception('Column `' . $name . '` could not be created: ' . $pdo->errorInfo()[2]);
        }

        return true;
    }

    /**
     * Goes through the fields of a resource and creates or changes the database columns where necessary
     *
     * @param $resource
     * @param $tableName
     * @param $pdo
     * @param bool $forExtended
     * @return array
     */
    private static function processFields($resource, $tableName, $pdo, $forExtended = false)
    {
        $filter = $forExtended ? Field::FILTER_EXTENDABLE : Field::FILTER_PATCH ;
        $fields = self::getFieldsByFilter($resource, $filter);
        $processed = [];
        $processed['keys'] = [];
        $processed['normal'] = [];
        $return = [];
        foreach ($fields as $field){
            if($field->isKey && !$forExtended){
                $processed['keys'][$field->name] = $field;
            }else{
                $processed['normal'][$field->name] = $field;
            }
        }
        //First create the keys that may be present
        foreach ($processed['keys'] as $field){
            $return[] = $field->name . '(key) now present in the database.';
            self::createColumn($field->type, $field->name, $tableName, $pdo, true);
        }
        foreach ($processed['normal'] as $field){
            $return[] = $field->name . ' now present in the database.';
            self::createColumn($field->type, $field->name, $tableName, $pdo);
        }
        return $return;
    }

    private static function getDatabaseFieldType($type, $unsigned = false)
    {
        switch ($type) {
            case FieldTypes::SHORTSTRING:
            case FieldTypes::INITIALS:
                $type = 'varchar(10)';
                break;
            case FieldTypes::STRING:
            case FieldTypes::DATESTRING:
            case FieldTypes::POSTALCODE:
            case FieldTypes::POSTALCODECH:
            case FieldTypes::IBAN:
            case FieldTypes::PHONENUMBER:
            case FieldTypes::EMAIL:
            case FieldTypes::HEADING:
            case FieldTypes::SUBHEADING:
            case FieldTypes::URL:
            case FieldTypes::FILE:
            case FieldTypes::IMAGE:
            case FieldTypes::CHOICE:
                $type = 'varchar(255)';
                break;
            case FieldTypes::TEXT:
            case FieldTypes::DESCRIPTION:
                $type = 'text';
                break;

            case FieldTypes::INTEGER:
            case FieldTypes::PHONENUMBERINT:
            case FieldTypes::PRICECENT:
            case FieldTypes::POINTS:
                $type = 'int(11)';
                if ($unsigned) {
                    $type .= ' unsigned';
                }
                break;

            case FieldTypes::DECIMAL:
            case FieldTypes::PRICE:
            case FieldTypes::PRICELARGE:
            case FieldTypes::RATING:
                $type = 'decimal(14,4)';
                break;

            case FieldTypes::FLOAT:
                $type = 'float';
                break;

            case FieldTypes::BOOLEAN:
                $type = 'tinyint(4)';
                break;

            case FieldTypes::ARRAY:
            case FieldTypes::OBJECT:
                $type = 'json';
                break;

            case FieldTypes::DATETIME:
                $type = 'datetime';
                break;

            case FieldTypes::DATE:
                $type = 'date';
                break;

            // type 'datetime' requires MySql 5.6 or higher
            case FieldTypes::CREATED_AT:
                $type = 'datetime DEFAULT CURRENT_TIMESTAMP';
                break;

            // type 'datetime' requires MySql 5.6 or higher
            case FieldTypes::UPDATED_AT:
                $type = 'datetime DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP';
                break;

            case FieldTypes::BLOB:
                $type = 'longblob';
                break;

            default:
                throw new \Exception('Invalid field type:'. $type);
        }

        return $type;
    }
}