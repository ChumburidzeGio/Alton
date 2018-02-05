<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;

class Field extends Model
{
    const STRATEGY_AUTO_INCREMENTED_PRIMARY_KEY = 'auto_incremented_primary_key';
    const STRATEGY_PRIMARY_KEY = 'primary_key';
    const STRATEGY_KEY = 'key';
    const STRATEGY_TIMESTAMP = 'timestamp';
    const STRATEGY_TIMESTAMP_ON_UPDATE = 'timestamp_on_update';
    const STRATEGY_AUTH_FILTER = 'auth_filter_field';

    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_TEXT = 'text';
    const TYPE_BOOLEAN = 'boolean';

    const FILTER_OVERWRITE = 'overwrite';
    const FILTER_PATCH = 'patch';
    const FILTER_EXPLODE = 'explode';
    const FILTER_UNIQUE = 'unique';
    const FILTER_FILTER = 'filter';
    const FILTER_CONDITION = 'condition';
    const FILTER_CONDITION2 = 'condition2';
    const FILTER_ALLOW_NULL = 'allowNull';
    const FILTER_USE_AS_KEY = 'useAsKey';
    const FILTER_USE_AS_LABEL = 'useAsLabel';
    const FILTER_USE_FOR_CHUNKING = 'chunkSplit';
    const FILTER_OPTION_KEY = 'option_key';
    const FILTER_OPTION_LABEL = 'option_label';
    const FILTER_IFNULL_KEEP_ORIGINAL = 'ifnull_keep_original';
    const FILTER_DEFAULT_ORDER = 'default_order';
    const FILTER_TRANSLATABLE = 'translatable';
    const FILTER_UNEDITABLE = 'uneditable';
    const FILTER_EXTENDABLE = 'extendable';
    const FILTER_MERGE_FULL_RESOURCE = 'merge_full_resource';

    const FILTER_SPLIT = 'split';
    const FILTER_ADD_OPTION = 'addOption';
    const FILTER_COMMA_SEPARATED_INPUT_ALLOWED = 'comma_separated_input_allowed';

    //don't show this field in data editor, so not overwriteable.
    const FILTER_HIDDEN = 'hidden';

    protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = [
        'resource_id',
        'input',
        'strategy',
        'output',
        'overview',
        'type',
        'name',
        'label',
        'description',
        'format',
        'tags',
        'rules',
        'filters',
        'value',
        'from',
        'script',
        'order',
        'input_default',
        'overwrite'
    ];

    protected $appends = [
        //        'extended_label',
        'form_name',
        'isRequired',
        'isPrimary',
        'isKey',
    ];

    public static function createSync(Array $fields)
    {
        $from = null;
        $to   = null;
        if(isset($fields['from'])){
            $from = is_array($fields['from']) ? $fields['from'] : [$fields['from']];
            //            unset($fields['from']);

            if(is_array($fields['from'])){
                $fields['from'] = current($fields['from']);
            }
        }
        if(isset($fields['to'])){
            $to = is_array($fields['to']) ? $fields['to'] : [$fields['to']];
            unset($fields['to']);
        }
        if( ! isset($fields['overview'])){
            $fields['overview'] = 1;
        }

        if( ! isset($fields['label'])){
            $label           = $fields['name'];
            $label           = str_replace('_', ' ', $label);
            $fields['label'] = ucfirst($label);
        }

        if(isset($fields['overwrite'])){
            $fields['overwrite'] = json_encode($fields['overwrite']);
        }

        $field = Field::create($fields);
        if($from){
            $field->fromFields()->sync($from);
        }
        if($to){
            $field->toFields()->sync($to);
        }

        return isset($fields['id']) ? $fields['id'] : 0;

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function toFields()
    {
        return $this->belongsToMany(Field::class, 'mappings', 'from', 'to');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function fromFields()
    {
        return $this->belongsToMany(Field::class, 'mappings_from', 'to', 'from');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mappedBy()
    {
        return $this->belongsToMany(Field::class, 'mappings', 'to', 'from');
    }

    /**
     * Get a translated label field only if it is translated.
     *
     * @return string
     */
    public function getLabelAttribute()
    {
        //TODO FIX THIS
        ////$lang = substr(App::getLocale(), 0, 2);

        $lang = Lang::getLocale();

        return isset($this->attributes['label_' . $lang]) ? $this->attributes['label_' . $lang] : $this->attributes['label'];
    }

    /**
     * @return string
     */
    public function getExtendedLabelAttribute()
    {
        if( ! $this->resource){
            return $this->label;
        }

        return $this->resource->label . ' - ' . $this->label;
    }

    /**
     * @param string|array $tags
     */
    public function setTagsAttribute($tags = [])
    {
        $this->attributes['tags'] = json_encode((array) $tags);
    }

    /**
     * @return array
     */
    public function getTagsAttribute()
    {
        return $this->attributes['tags'] ? json_decode($this->attributes['tags'], true) : [];
    }

    /**
     * Check if this field has a validation rule 'required'.
     *
     * @return bool
     */
    public function getIsRequiredAttribute()
    {
        return false !== strpos($this->rules, 'required');
    }

    /**
     * Check if this field is (part of) a primary key
     *
     * @return bool
     */
    public function getIsPrimaryAttribute()
    {
        return in_array($this->strategy, [
            static::STRATEGY_PRIMARY_KEY,
            static::STRATEGY_AUTO_INCREMENTED_PRIMARY_KEY
        ]);
    }

    /**
     * Check if this field is (part of) a primary key
     *
     * @return bool
     */
    public function getIsKeyAttribute()
    {
        return in_array($this->strategy, [
            static::STRATEGY_PRIMARY_KEY,
            static::STRATEGY_AUTO_INCREMENTED_PRIMARY_KEY,
            static::STRATEGY_KEY,
        ]);
    }

    /**
     * @return string
     */
    public function getFormNameAttribute()
    {
        $name = $this->name;

        // No dots in the name means no nesting, just return the name itself.
        if( ! strstr($name, '.')){
            return $name;
        }

        // Create a nested html notation for the dotted field name.
        $name = str_replace('.', '][', $name);
        $name = preg_replace('/\]\[/', '[', $name, 1) . ']';

        return $name;
    }


    /**
     * @return array
     */
    public function setFiltersAttribute(Array $filters)
    {
        $this->attributes['filters'] = implode('|', array_map('trim', $filters));
    }

    /**
     * @return array
     */
    public function getFiltersAttribute()
    {
        if( ! $this->attributes['filters']){
            return [];
        }

        return $this->attributes['filters'] ? explode('|', $this->attributes['filters']) : [];
    }

    /**
     * @param string $filter
     *
     * @return bool
     */
    public function hasFilter($filter)
    {
        return in_array($filter, $this->filters);
    }

    /**
     * Get a list of fields that can be used for filtering queries.
     *
     * @return array
     */
    public function getFilterFields()
    {
        return ['resource_id', 'script', 'name', 'input', 'output', 'filters', 'input_default'];
    }

    /**
     * All permission actions are build up like '{namespace}.{field}'.
     * We need to provide a {namespace} for this model here. This can
     * be the database table name, but it does not have to.
     *
     * @return string
     */
    public function getPermissionPrefix()
    {
        return 'field';
    }

    /**
     * Get a list of all possible relations to load.
     * This includes nested relations, separated with
     * a dot character, e.g. 'click.product'.
     *
     * @return array
     */
    public function getRelationFields()
    {
        return ['toFields', 'fromFields', 'resource', 'mappedBy'];
    }
}
