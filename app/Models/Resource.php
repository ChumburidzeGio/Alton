<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    const ACT_AS_REST = 'rest';
    const ACT_AS_SINGLE = 'single';
    const ACT_AS_COLLECTION = 'collection';
    const ACT_AS_ELOQUENT_REST = 'wrapped_rest';
    const ACT_AS_SERVICE_REST = 'service_rest';

    /*
     * This will result in always resulting the complete data set. This is needed when we do big merges between to documents
     */

    const BEHAVIOUR_UNLIMITABLE = 'unlimitable';
    const BEHAVIOUR_PARALLEL = 'parallel';
    const BEHAVIOUR_PARALLEL_CHILD = 'parallel_child';
    const BEHAVIOUR_SPLITABLE = 'splitable';
    const BEHAVIOUR_CACHABLE = 'cachable';
    const BEHAVIOUR_LIMITABLE = 'limitable';
    const BEHAVIOUR_PLAINARRAY = 'plainarray';
    const BEHAVIOUR_SORTABLE = 'sortable';
    const BEHAVIOUR_VIEWABLE = 'viewable';
    const BEHAVIOUR_SPLITABLE_BY_FIELD = 'splitable_by_field';
    const BEHAVIOUR_AUTOAPPLY_ENABLED = 'behaviour_autoapply_enabled';

    /**
     * @deprecated
     */
    const BEHAVIOUR_PROTECTABLE = 'protectable';

    const BEHAVIOUR_USE_PLAN = 'behaviour_use_plan';
    const BEHAVIOUR_EXTERNAL_DATA_PROVIDER = 'external_data_provider';

    // Prefer POST instead of GET as default method
    const BEHAVIOUR_PREFER_POST = '_prefer_post';

    //Dummy interface: do nothing
    const BEHAVIOUR_DUMMY = 'dummy';

    //no propagation, but still call the service
    const BEHAVIOUR_SERVICE_NO_PROPAGATION = 'service_no_propagation';

    protected $fillable = ['index', 'name', 'label', 'tags', 'description', 'user_id', 'act_as', 'eloquent', 'document_query', 'behaviours', 'driver', 'permissions'];

    protected $hidden = [
    ];

    protected $appends = [
        'primaryFields',
        'keyFields',
        'defaults',
    ];

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
    }

//    /**
//     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
//     */
//    public function user()
//    {
//        return $this->belongsTo('App\Models\User');
//    }
//
//    /**
//     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
//     */
//    public function productType()
//    {
//        return $this->belongsTo(ProductType::class);
//    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inputs()
    {
        return $this->hasMany(Field::class)->where('input', true)->orderBy('order')->orderBy('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function outputs()
    {
        return $this->hasMany(Field::class)->where('output', true)->orderBy('order')->orderBy('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fields()
    {
        return $this->hasMany(Field::class)->orderBy('order', 'desc')->orderBy('id');
    }

    protected $currentAction;

    public function getCurrentAction()
    {
        return $this->currentAction;
    }

    public function populateFields($action = 'index')
    {
        if ($action == $this->currentAction)
            return;

        //TODO FIX
//        $roles = (!App::runningInConsole() && App::make('application') && $user = App::make('application')->user)
//            ? $user->roles->lists('name') : [];
        $roles = [];

        $this->currentAction = $action;
        $fields = $this->fields()->get()->all();

        // do overwrite shit
        $fields = array_filter($fields, function (Field $field) use ($action, $roles) {

            $overwrite = json_decode($field->overwrite, true);

            if (!isset($overwrite[$action])) {
                return true;
            }

            $rules = $overwrite[$action];

            $rules_left = array_only($rules, $roles);

            if (!empty($rules_left)) {
                $based_on_role = array_reduce($rules_left, function ($acc, $role) {
                    return $acc or !array_get($role, 'ignore', false);
                }, false);

                return $based_on_role;
            }

            if (isset($rules['ignore'])) {
                return !($rules['ignore'] === true);
            }

            return true;
        });

        $fields = array_map(function(Field $field) use ($action){

            $overwrite = json_decode($field->overwrite, true);

            if(isset($overwrite[$action])){
                foreach($overwrite[$action] as $key => $value){

                    if ($key == 'from' || $key == 'to') {
                        $field->{$key .'Fields'} = Field::whereIn($key, (array)$value)->get();
                    }
                    $field[$key] = $overwrite[$action][$key];
                }
            }

            return $field;
        }, $fields);

        $this->fields = array_values($fields);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function overview()
    {
        return $this->fields()->where('overview', true);
    }

    /**
     * @return Array
     */
    public function getDocumentsAttribute()
    {
        if (!$this->documentQuery) {
            return [];
        }

        $products = DocumentHelper::get(
            $this->documentQuery['index'],
            $this->documentQuery['type'],
            $this->documentQuery['options']);

        return $products->documents()->toArray();
    }

    /**
     * @param array $query
     */
    public function setDocumentQueryAttribute(Array $query)
    {
        $this->attributes['document_query'] = json_encode($query);
    }

    /**
     * @return Array
     */
    public function getDocumentQueryAttribute()
    {
        if (!$this->attributes['document_query']) {
            return [];
        }

        return json_decode($this->attributes['document_query'], true);
    }
//
    /**
     * Only get the fields that are a primary key
     * @return Field[]
     */
    public function getPrimaryFieldsAttribute()
    {
        return collect($this->fields)->filter(function (Field $field) {
            return $field->isPrimary;
        });
    }

    /**
     * Only get the fields that are keys
     * @return Field[]
     */
    public function getKeyFieldsAttribute()
    {
        return collect($this->fields)->filter(function (Field $field) {
            return $field->isKey;
        });
    }


    /**
     * Only get the fields that are keys
     * @return Field[]
     */
    public function getUniqueFields() {
        return collect($this->fields)->filter(function (Field $field) {
            return $field->hasFilter(Field::FILTER_UNIQUE);
        });
    }

    /**
     * Get simple key/value pairs for all field defaults
     * @return array
     */
    public function getDefaultsAttribute()
    {
        $return = [];
        foreach($this->fields as $field) {
            array_set($return,$field->name, $field->value);
        }
        return $return;
    }

    /**
     * @param string|array $tags
     */
    public function setTagsAttribute($tags = [])
    {
        $this->attributes['tags'] = json_encode( (array) $tags);
    }

    /**
     * @return array
     */
    public function getTagsAttribute()
    {
        return $this->attributes['tags'] ? json_decode($this->attributes['tags'], true) : [];
    }

    /**
     * @return array
     */
    public function setBehavioursAttribute(Array $behaviours)
    {
        $this->attributes['behaviours'] = implode('|', array_map('trim', $behaviours));
    }

    /**
     * @return array
     */
    public function getBehavioursAttribute()
    {
        if(!$this->attributes['behaviours']) return [];

        return $this->attributes['behaviours']
            ? explode('|', $this->attributes['behaviours'])
            : [];
    }

    /**
     * Check if a behaviour exists for this resource.
     *
     * @param $behaviour
     *
     * @return bool
     */
    public function hasBehaviour($behaviour)
    {
        return in_array($behaviour, $this->behaviours);
    }

    /**
     * @return array
     */
    public function setPermissionsAttribute(Array $permissions)
    {
        $this->attributes['permissions'] = json_encode( (array) $permissions);
    }

    /**
     * @return array
     */
    public function getPermissionsAttribute()
    {
        return $this->attributes['permissions'] ? json_decode($this->attributes['permissions'], true) : [];
    }



//    /**
//     * @return int
//     */
//    public function getPossibilitiesAttribute()
//    {
//        return ResourceHelper::possibilities($this);
//    }

    /**
     * Get a list of fields that can be used for filtering queries.
     * @return array
     */
    public function getFilterFields()
    {
        return ['id', 'tags', 'user_id', 'product_type_id', 'productType'];
    }

    /**
     * All permission actions are build up like '{namespace}.{field}'.
     * We need to provide a {namespace} for this model here. This can
     * be the database table name, but it does not have to.
     * @return string
     */
    public function getPermissionPrefix()
    {
        return 'resource';
    }

    /**
     * Get a list of all possible relations to load.
     * This includes nested relations, separated with
     * a dot character, e.g. 'click.product'.
     * @return array
     */
    public function getRelationFields()
    {
        return ['user', 'inputs', 'outputs', 'fields', 'overview', 'productType'];
    }

    public function getServiceMethodName()
    {
        return $this->service
            ? array_get(explode('/', $this->service, 2), 1)
            : explode('.', $this->name, 2)[0];
    }

    public function getServiceName()
    {
        return $this->service
            ? array_get(explode('/', $this->service), 0)
            : array_get(explode('.', $this->name, 2), 1);
    }
}
