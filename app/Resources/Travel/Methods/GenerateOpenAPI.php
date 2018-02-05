<?php

namespace App\Resources\Travel\Methods;


use App\Helpers\FactoryHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Models\Resource;
use App\Resources\Travel\TravelWrapperAbstractRequest;
use Illuminate\Support\Facades\App;
use Komparu\Value\Type;
use Symfony\Component\Yaml\Yaml;

class GenerateOpenAPI extends TravelWrapperAbstractRequest
{
    protected $resourceNames = [
        'product.travel',
        'product_listing.travel',
        'order.travel',
        'options.travel',
        'cancel_order.travel',
        'services.travel',
        'rebook_order.travel',
        'resend_email.travel',
        'websites.travel',
// Restricted for now
/*
        'resellers.travel',
        'providers.travel',
        'admins.travel',
        'website_rights.travel',
        'user_rights.travel',
        'product_settings.travel',
*/
    ];

    protected $resourceTags = [
        'product.travel' => ['products'],
        'product_options.travel' => ['products'],
        'product_listing.travel' => ['products'],
        'contract.travel' => ['orders'],
        'order.travel' => ['orders'],
        'order_options.travel' => ['orders'],
        'options.travel' => ['products'],
        'cancel_order.travel' => ['orders'],
        'services.travel' => ['products'],
        'rebook_order.travel' => ['orders'],
        'resend_email.travel' => ['orders'],
        'websites.travel' => ['tools'],
// Restricted for now
/*
        'resellers.travel' => ['users'],
        'providers.travel' => ['users'],
        'admins.travel' => ['users'],
        'website_rights.travel' => ['tools'],
        'user_rights.travel' => ['tools'],
        'product_settings.travel' => ['tools'],
*/
    ];

    protected $tags = [
        'products' => [
            'description' => 'Everything about travel products and their prices.'
        ],
        'orders' => [
            'description' => 'Everything about travel orders.'
        ],
        'tools' => [
            'description' => 'Everything about websites/tools.'
        ],
// Restricted for now
/*
        'users' => [
            'description' => 'Everything about users.'
        ],
*/
    ];

    // Fallback: string
    protected $resource2TypeToOpenAPIType = [
        Type::INTEGER => 'integer',
        Type::STRING => 'string',
        Type::FLOAT => 'number',
        Type::PRICE => 'number',
        Type::NUMBER => 'number',
        Type::DECIMAL => 'number',
        Type::EMAIL => 'string',
        Type::URL => 'string',
        Type::TEXT => 'string',
        Type::DATE => 'string',
        Type::DATETIME => 'string',
        Type::BOOLEAN => 'boolean',
        Type::OBJECT => 'object',
        Type::ARR => 'array',
    ];

    protected $resource2TypeToOpenAPIFormat = [
        Type::INTEGER => 'int32',
        Type::FLOAT => 'float',
        Type::BLOB => 'byte',
        Type::DATE => 'date',
        Type::DATETIME => 'date-time',
        // Unsupported
        Type::EMAIL => 'email',
    ];

    public function executeFunction()
    {
        $generateForRole = App::environment() === 'local' ? App::getarray_get($this->params, ResourceInterface::ROLE, 'publisher') : 'publisher';
        $resourceNames = $this->resourceNames;
        $environment = array_get($this->params, 'environment', App::environment());

        switch ($environment)
        {
            case 'prod':
            case 'production' :
                $env['host'] = 'api.komparu.com';
                $env['schemes'] = ['https'];
                $env['name'] = 'production';
                break;
            case 'local':
            case 'development' :
                $env['host'] = 'api.komparu.dev';
                $env['schemes'] = ['http'];
                $env['name'] = 'development';
                break;
            case 'test':
            case 'testing':
                $env['host'] = 'api.komparu.test';
                $env['schemes'] = ['http'];
                $env['name'] = 'test';
                break;
            case 'acc':
            case 'acceptation':
                $env['host'] = 'api-acc.komparu.com';
                $env['schemes'] = ['https'];
                $env['name'] = 'acceptation';
                break;
            default:
                throw new \Exception('Unknown environment: `'. $environment .'`');
        }

        $root = [
            'swagger' => '2.0',
            'info' => [
                'title' => 'Mobian Travel API'. ($env['name'] != 'production' ? ': '. ucfirst($env['name']) : ''),
                'description' => <<<DOC
## Description
This is the documentation for using the Mobian Travel API. With this API you can request travel [products and prices](#/products) for going a location, and then returning from that location on another time.
The types of products might be offered are, for example: Long stay parking, Valet parking, Taxi services, Public transport or Off-street parking lots.

Some of these products may be directly booked, by using the [order](#/orders) operations.

Products and orders are always requested/created for a specific [website or tool](#/tools), which may have additional configuration such as language, available payment methods, or available products. 

## Authentication

You must authenticate your API requests by passing your API token via the `X-Auth-Token` HTTP header. Your API Token can be found on your profile page of the Mobian CRM.

## API methodology

This API uses a mixture of [**REST** endpoints](https://en.wikipedia.org/wiki/Representational_state_transfer) for created objects, and [**RPC** methods](https://en.wikipedia.org/wiki/Remote_procedure_call) for specific operations. Both of these use `HTTP GET` parameters, or `HTTP POST/PUT` body objects formatted in [**JSON** format](https://en.wikipedia.org/wiki/JSON). All results are returned in the [**JSON** format](https://en.wikipedia.org/wiki/JSON).

DOC
                ,
                'version' => '1.0.0',
            ],
            'host' => $env['host'],
            'basePath' => '/v1/resource2',
            'schemes' => $env['schemes'],
            'consumes' => ['application/json'],
            'produces' => ['application/json'],
            'securityDefinitions' => [
                'X-Auth-Token' => [
                    'name' => 'X-Auth-Token',
                    'type' => 'apiKey',
                    'in' => 'header',
                    'description' => 'Your API authentication token.',
                ],
            ],
            'security' => [
                ['X-Auth-Token' => []],
            ],
            'parameters' => $this->createListOptionsParameters() + $this->createLanguageParameters(),
            'tags' => [],
            'paths' => [],
            'responses' => $this->createCommonErrorResponses(),
        ];


        foreach ($this->tags as $name => $tag) {
            $root['tags'][] = [
                'name' => $name
            ] + $tag;
        }

        // Handle all resources
        foreach ($resourceNames as $resourceName) {
            $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', $resourceName, false, true);

            $permissions = array_get($resource->permissions, $generateForRole, []);

            if ($generateForRole && !$permissions)
                continue;

            $fields = $this->sortResourceFields($resource);

            if (in_array($resource->act_as, [Resource::ACT_AS_REST, Resource::ACT_AS_ELOQUENT_REST, Resource::ACT_AS_SERVICE_REST])) {

                $idName = isset($fields['id']) ? 'id' : '__id';

                // Index
                if (!isset($permissions['actions']) || in_array('index', $permissions['actions'])) {
                    $root['definitions'][$resource->name] = $this->createSchema($fields, $permissions);
                    $root['paths']['/'. $resource->name .'/data']['get'] = [
                        'summary' => 'List of '. $resource->label,
                        'tags' => array_get($this->resourceTags, $resourceName, []),
                        'description' => $resource->description,
                        'parameters' => array_merge($this->getLanguageParametersRefs(), $this->getListOptionsRefs(), $this->createParameters($fields, ['*' => 'query'], 'index', $permissions)),
                        'responses' => $this->getListResponses($resource),
                    ];
                }

                // Show
                if (!isset($permissions['actions']) || in_array('show', $permissions['actions'])) {
                    $root['definitions'][$resource->name] = $this->createSchema($fields, $permissions);
                    $root['paths']['/'. $resource->name .'/data/{'. $idName .'}']['get'] = [
                        'summary' => 'Get single by ID: '. $resource->label,
                        'tags' => array_get($this->resourceTags, $resourceName, []),
                        'description' => $resource->description,
                        'parameters' => array_merge($this->getLanguageParametersRefs(), $this->createParameters(array_only($fields, [$idName]), [$idName => 'path', '*' => 'query'], 'show', $permissions)),
                        'responses' => $this->getSingleResourceResponses($resource),
                    ];
                }

                // Store
                if (!isset($permissions['actions']) || in_array('store', $permissions['actions'])) {
                    $root['definitions'][$resource->name] = $this->createSchema($fields, $permissions);
                    $root['paths']['/'. $resource->name .'/data']['post'] = [
                        'summary' => 'Create: '. $resource->label,
                        'tags' => array_get($this->resourceTags, $resourceName, []),
                        'description' => $resource->description,
                        'parameters' => $this->createParameters($fields, ['*' => 'body-property'], 'store', $permissions),
                        'responses' => $this->getSingleResourceResponses($resource),
                    ];
                }

                // Update
                if (!isset($permissions['actions']) || in_array('update', $permissions['actions'])) {
                    $root['definitions'][$resource->name] = $this->createSchema($fields, $permissions);
                    $root['paths']['/'. $resource->name .'/data/{'. $idName .'}']['put'] = [
                        'summary' => 'Update single: '. $resource->label,
                        'tags' => array_get($this->resourceTags, $resourceName, []),
                        'description' => $resource->description,
                        'parameters' => $this->createParameters($fields, [$idName => 'path', '*' => 'body-property'], 'update', $permissions),
                        'responses' => $this->getSingleResourceResponses($resource),
                    ];
                }

                // Destroy
                if (!isset($permissions['actions']) || in_array('destroy', $permissions['actions'])) {
                    $root['paths']['/'. $resource->name .'/data/{'. $idName .'}']['delete'] = [
                        'summary' => 'Delete one: '. $resource->label,
                        'tags' => array_get($this->resourceTags, $resourceName, []),
                        'description' => $resource->description,
                        'parameters' => $this->createParameters(array_only($fields, [$idName]), [$idName => 'path', '*' => 'query'], 'destroy', $permissions),
                        'responses' => $this->getDestroyResourceResponses($resource),
                    ];
                }
            }
            else if ($resource->act_as == Resource::ACT_AS_COLLECTION)
            {
                $method = in_array(Resource::BEHAVIOUR_PREFER_POST, $resource->behaviours) ? 'post' : 'get';

                $root['paths']['/'. $resource->name .'/data'][$method] = [
                    'summary' => $resource->label,
                    'tags' => array_get($this->resourceTags, $resourceName, []),
                    'description' => $resource->description,
                    'parameters' => $this->createParameters($fields, $method == 'post' ? ['*' => 'body-property'] : ['*' => 'query'], $method == 'post' ? 'store' : 'index', $permissions),
                    'responses' => self::mergeArrayStringKeys([
                        '200' => [
                            'description' => 'List response success',
                            'schema' => [
                                'type' => 'array',
                                'items' => $this->createSchema($fields, $permissions),
                            ],
                        ],
                    ], $this->getCommonErrorRefs()),

                ];
            }
            else if ($resource->act_as == Resource::ACT_AS_SINGLE)
            {
                $method = in_array(Resource::BEHAVIOUR_PREFER_POST, $resource->behaviours) ? 'post' : 'get';

                $root['paths']['/'. $resource->name .'/data'][$method] = [
                    'summary' => $resource->label,
                    'tags' => array_get($this->resourceTags, $resourceName, []),
                    'description' => $resource->description,
                    'parameters' => $this->createParameters($fields, $method == 'post' ? ['*' => 'body-property'] : ['*' => 'query'], $method == 'post' ? 'store' : 'show', $permissions),
                    'responses' => self::mergeArrayStringKeys([
                        '200' => [
                            'description' => 'Response success',
                            'schema' => $this->createSchema($fields, $permissions),
                        ],
                    ], $this->getCommonErrorRefs()),
                ];
            }
            else
            {
                throw new \Exception('Unsupported resource type `'. $resource->act_as .'` for OpenAPI generation.');
            }
        }

        $this->result = $root;

        // Test YAML output
        /*
        if (array_get($this->params, ResourceInterface::FORMAT) == 'yaml') {
            echo Yaml::dump($root, 16, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_OBJECT_AS_MAP);
            exit;
        }
        */
    }

    protected function createParameters($fields, $in = ['*' => 'query'], $action = null, $permissions)
    {
        // Filter/input fields
        $parameters = [];
        $bodyFields = [];
        foreach ($fields as $field) {
            if (!$field['input'])
                continue;
            if (isset($permissions['visible']) && !in_array($field['name'], $permissions['visible']))
                continue;

            $paramIn = array_get($in, $field['name'], $in['*']);

            if ($paramIn == 'body-property') {
                $bodyFields[] = $field;
                continue;
            }

            $parameter = $this->createParameter($field, $paramIn, $action);

            if ($parameter && $parameter['in'] === 'path')
                $parameter['required'] = true;

            if ($parameter && $field['output'] && empty($parameter['required']))
                $parameter['description'] = '**Filter on**: '. $parameter['description'];

            if ($parameter)
                $parameters[] = $parameter;
        }

        if ($bodyFields) {
            $parameters[] = [
                'in' => 'body',
                'name' => 'request-body',
                'description' => 'Object',
                'schema' => $this->createSchema($bodyFields, $permissions, $action, '', true),
            ];
        }

        // Sort, putting required fields on top, then input-only fields, then 'filter' fields.
        $parameters = array_values(array_sort($parameters, function ($a) {
           if (!empty($a['required']))
               return '0 - '. $a['name'];
           else if (starts_with($a['description'], '**Filter on**:'))
               return '2 - '. $a['name'];
           else
               return '1 - '. $a['name'];
        }));

        return $parameters;
    }

    protected function createParameter($field, $in = 'query', $action = null)
    {
        $fieldData = $this->getFullFieldData($field, $action);

        if (!empty($fieldData['ignore']))
            return false;

        $parameter = [
            'name' => $fieldData['name'],
            'in' => $in,
            'description' => !empty($fieldData['description']) ? $fieldData['description'] : $fieldData['label'],
            'required' => str_contains($fieldData['rules'], 'required'),
            'type' => array_get($this->resource2TypeToOpenAPIType, $fieldData['type'], 'string'),
        ];

        if (isset($this->resource2TypeToOpenAPIFormat[$fieldData['type']])) {
            $parameter['format'] = $this->resource2TypeToOpenAPIFormat[$fieldData['type']];
        }

        if (isset($fieldData['value'])) {
            $parameter['default'] = $fieldData['value'];
        }

        // We cannot support 'object' parameters
        if ($parameter['type'] === 'object')
            return false;

        // Array is comma separated strings by default (for parameter input)
        if ($parameter['type'] === 'array') {
            $parameter['items'] = [
                'type' => 'string',
            ];
        }

        // We do not send booleans as 'true' and 'false' via the query, but as 0 & 1
        if ($parameter['type'] == 'boolean' && $parameter['in'] == 'query') {
            $parameter['type'] = 'integer';
            $parameter['enum'] = [0, 1];
        }

        return $parameter;
    }

    protected function createSchema($fields, $permissions, $action = null, $prefix = '', $asInput = false)
    {
        $schema = [
            'type' => 'object',
            'properties' => new \ArrayObject(), // This is an ArrayObject, so it will be an Object when empty, in YAML
        ];

        foreach ($fields as $field) {
            $fieldData = $this->getFullFieldData($field, $action);

            if (!empty($fieldData['ignore']))
                continue;
            if (!$asInput && !$fieldData['output'])
                continue;
            if ($asInput && !$fieldData['input'])
                continue;
            if (isset($permissions['visible']) && !in_array($fieldData['name'], $permissions['visible']))
                continue;
            if ($prefix !== '' && !starts_with($fieldData['name'], $prefix))
                continue;
            if (str_contains(str_replace($prefix, '', $fieldData['name']), '.'))
                continue;

            $propertyName = str_replace($prefix, '', $fieldData['name']);

            $responseField = [
                'description' => !empty($fieldData['description']) ? $fieldData['description'] : $fieldData['label'],
                'type' => array_get($this->resource2TypeToOpenAPIType, $fieldData['type'], 'string'),
            ];
            if (isset($this->resource2TypeToOpenAPIFormat[$fieldData['type']])) {
                $parameter['format'] = $this->resource2TypeToOpenAPIFormat[$fieldData['type']];
            }
            if ($fieldData['value']) {
                $parameter['default'] = $fieldData['value'];
            }

            if ($asInput && str_contains($fieldData['rules'], 'required')) {
                $schema['required'][] = $propertyName;
            }

            if ($responseField['type'] === 'object') {
                $subFields = [];
                foreach ($fields as $fieldName => $subField) {
                    if (starts_with($fieldName, $fieldData['name'] .'.')) {
                        $subFields[$fieldName] = $subField;
                    }
                }
                $responseField = array_merge($responseField, $this->createSchema($subFields, $permissions, $action, $fieldData['name'] .'.'));
            }
            else if ($responseField['type'] === 'array') {
                $subFields = [];
                foreach ($fields as $fieldName => $subField) {
                    if (starts_with($fieldName, $fieldData['name'] .'.')) {
                        $subFields[$fieldName] = $subField;
                    }
                }

                if ($subFields)
                    $responseField['items'] = $this->createSchema($subFields, $permissions, $action, $fieldData['name'] .'.');
                else
                    $responseField['items'] = ['type' => 'string']; // Fallback, we should define this somehow somewhere in our Field definition?
            }

            $schema['properties'][$propertyName] = $responseField;
        }
        return $schema;
    }

    protected function getFullFieldData($field, $action)
    {
        $fieldData = is_object($field) ? $field->toArray() : $field;

        if ($fieldData['overwrite'] && is_string($fieldData['overwrite'])) {
            $fieldData['overwrite'] = json_decode($fieldData['overwrite'], JSON_OBJECT_AS_ARRAY);

            if (isset($fieldData['overwrite'][$action])) {
                $fieldData = array_merge($fieldData, $fieldData['overwrite'][$action]);
            }
        }

        return $fieldData;
    }

    protected function sortResourceFields(Resource $resource)
    {
        $fields = $resource->fields->toArray();

        $fields = array_combine(array_pluck($fields, 'name'), $fields);

        ksort($fields);

        return $fields;
    }

    protected function createListOptionsParameters()
    {
        return [
            'index.'. OptionsListener::OPTION_LIMIT => [
                'name' => OptionsListener::OPTION_LIMIT,
                'description' => 'Maximum number of result items to return.',
                'type' => 'number',
                'in' => 'query',
                'default' => 10,
            ],
            'index.'. OptionsListener::OPTION_OFFSET => [
                'name' => OptionsListener::OPTION_OFFSET,
                'description' => 'Offset of result items.',
                'type' => 'number',
                'in' => 'query',
                'default' => 0,
            ],
            'index.'. OptionsListener::OPTION_ORDER => [
                'name' => OptionsListener::OPTION_ORDER,
                'description' => 'Sort result items by field specified.',
                'type' => 'string',
                'in' => 'query',
            ],
            'index.'. OptionsListener::OPTION_DIRECTION => [
                'name' => OptionsListener::OPTION_DIRECTION,
                'description' => 'Sorting order direction for result items. (`asc` (default) or `desc`)',
                'type' => 'string',
                'in' => 'query',
                'enum' => [OptionsListener::OPTION_DIRECTION_ASC, OptionsListener::OPTION_DIRECTION_DESC],
            ],
        ];
    }

    protected function getListOptionsRefs()
    {
        $refs = [];
        foreach ($this->createListOptionsParameters() as $name => $parameter) {
            $refs[] = ['$ref' => '#/parameters/'. $name];
        }
        return $refs;
    }


    protected function createLanguageParameters()
    {
        return [
            'header.accept_language' => [
                'name' => 'Accept-Language',
                'description' => '
Language to return the results in, if available. Currently supports:
 - `nl`: Dutch (default)
 - `en`: English
 - `de`: German
 - `fr`: French
 ',
                'type' => 'string',
                'in' => 'header',
                'default' => 'nl',
                'enum' => ['nl', 'en', 'de', 'fr'],
            ],
        ];
    }

    protected function getLanguageParametersRefs()
    {
        $refs = [];
        foreach ($this->createLanguageParameters() as $name => $parameter) {
            $refs[] = ['$ref' => '#/parameters/'. $name];
        }
        return $refs;
    }

    protected function getListResponses($resource)
    {
        return self::mergeArrayStringKeys([
            '200' => [
                'description' => 'Response success',
                'schema' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/definitions/'. $resource->name],
                ],
                'headers' => [
                    'x-total-count' => [
                        'description' => 'Total item count for result set, when not taking into account limits.',
                        'type' => 'number',
                    ]
                ],
            ],
        ], $this->getCommonErrorRefs());
    }

    protected function getSingleResourceResponses($resource)
    {
        return self::mergeArrayStringKeys([
            '200' =>  [
                'description' => 'Response success',
                'schema' => ['$ref' => '#/definitions/'. $resource->name],
            ],
        ], $this->getCommonErrorRefs());
    }

    protected function getDestroyResourceResponses($resource)
    {
        return self::mergeArrayStringKeys([
            '200' =>  [
                'description' => 'Response success',
                'schema' => [],
            ],
        ], $this->getCommonErrorRefs());
    }


    protected function getCommonErrorRefs()
    {
        return [
            '422' => ['$ref' => '#/responses/UnprocessableEntityError'],
            'default' => ['$ref' => '#/responses/GeneralError'],
            'x-400-detailed' => ['$ref' => '#/responses/Detailed400Error'],
        ];
    }

    protected function createCommonErrorResponses()
    {
        return [
            'UnprocessableEntityError' =>  [
                'description' => 'Unprocessable entity, returned if one or more user input errors occurred.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => [
                            'type' => 'number',
                            'description' => 'HTTP error code',
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => 'Short error title',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Error description',
                        ],
                        'errors' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'field' => [
                                        'type' => 'string',
                                        'description' => 'Input field name that has an error.',
                                    ],
                                    'message' => [
                                        'type' => 'string',
                                        'description' => 'Error message for this field. (may be translated)',
                                    ],
                                    'type' => [
                                        'type' => 'string',
                                        'description' => 'Error type category.',
                                    ],
                                    'code' => [
                                        'type' => 'string',
                                        'description' => 'Error textual identifier.',
                                    ],
                                ]
                            ]
                        ]
                    ],
                ],
            ],
            'GeneralError' =>  [
                'description' => 'General input error.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => [
                            'type' => 'number',
                            'description' => 'HTTP error code',
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => 'Short error title',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Error description',
                        ],
                    ],
                ],
            ],
            'Detailed400Error' =>  [
                'description' => 'Bad request, returned if one or more input errors occurred.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => [
                            'type' => 'number',
                            'description' => 'HTTP error code',
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => 'Short error title',
                        ],
                        'resource' => [
                            'type' => 'string',
                            'description' => 'Name of resource triggering error.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Error description',
                        ],
                        'errors' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'field' => [
                                        'type' => 'string',
                                        'description' => 'Input field name that has an error.',
                                    ],
                                    'message' => [
                                        'type' => 'string',
                                        'description' => 'Error message for this field.',
                                    ],
                                    'type' => [
                                        'type' => 'string',
                                        'description' => 'Error type category.',
                                    ],
                                    'code' => [
                                        'type' => 'string',
                                        'description' => 'Error textual identifier.',
                                    ],
                                ]
                            ]
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * `array_merge` does not keep numeric keys like '200' strings, so this is a simple implementation
     * which does keep the keys as strings.
     */
    protected static function mergeArrayStringKeys($array1, $array2)
    {
        foreach ($array2 as $key => $value)
            $array1[(string)$key] = $value;

        return $array1;
    }
}