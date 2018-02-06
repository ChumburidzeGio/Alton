<?php

namespace App\Helpers;

use App\Exception\NoMatchingChildResourceData;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\ResourceRecursionListener;
use App\Models\Field;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Komparu\Document\ArrayHelper;
use Komparu\Schema\Contract\Registry;
use Komparu\Value\Type;
use Komparu\Value\ValueInterface;

class FieldHelper
{
    const AUTO_INCREMENTED = true;

    const CONTRACT_WITH_STATUS = true;
    const CONTRACT_WITHOUT_STATUS = false;

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
                if ($field && $field->name == ResourceInterface::CREATED_AT)
                    return ValueInterface::TYPE_CREATED_AT;
                if ($field && $field->name == ResourceInterface::UPDATED_AT)
                    return ValueInterface::TYPE_UPDATED_AT;

                return ValueInterface::TYPE_DATETIME;

            case Type::DATESTRING:
                return ValueInterface::TYPE_DATETIME;

            case Type::BLOB:
                return ValueInterface::TYPE_BLOB;

            case Type::CHOICE:
                // this could be enum, but have no enum type
                return ValueInterface::TYPE_STRING;
        }

        if ($registry == null) {
            return null;
        }

        /**
         * From here, we need to look up the schema and its parent types
         */
        $schema  = $registry->get($type);
        $parents = $schema->types();

        foreach($parents as $type){
            if($found = static::mapDocumentType($type, $field, $registry)){
                return $found;
            }
        }

        // Probably don't return anything here, because of the recursive nature of this method.
    }

    /**
     * @param $baseId
     * @param $resourceId
     * @param int $order
     * @param array $filters
     * @param string $id
     *
     * @return array
     */
    public static function getIdField($baseId, $resourceId, $order = 0, $filters = ['patch'], $id = ResourceInterface::__ID)
    {
        return [
            'id'          => $baseId,
            'resource_id' => $resourceId,
            'strategy'    => Field::STRATEGY_PRIMARY_KEY,
            'name'        => $id,
            'label'       => 'ID',
            'type'        => Type::STRING,
            'filters'     => $filters,
            'order'       => $order,
            'overview'    => false
        ];
    }

    public static function getIntegerIdField($baseId, $resourceId, $order = 0, $filters = ['patch'])
    {
        $field = self::getIdField($baseId, $resourceId, $order, $filters);
        $field['type'] = Type::INTEGER;
        return $field;
    }

    public static function getAutoIncrementIdField($baseId, $resourceId, $order = 0, $filters = ['patch'])
    {
        return [
            'id'          => $baseId,
            'resource_id' => $resourceId,
            'strategy'    => Field::STRATEGY_AUTO_INCREMENTED_PRIMARY_KEY,
            'name'        => ResourceInterface::__ID,
            'label'       => 'ID',
            'type'        => Type::INTEGER,
            'filters'     => $filters,
            'order'       => $order,
            'overview'    => false
        ];
    }

    public static function getProductFields($baseId, $resourceId, $order = 0, $autoincremented = false)
    {
        return [
            $autoincremented
                ? self::getAutoIncrementIdField($baseId ++, $resourceId, $order ++)
                : self::getIdField($baseId ++, $resourceId, $order ++),
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::ENABLED,
                'label'       => 'enabled',
                'type'        => Type::BOOLEAN,
                'filters'     => [FIELD::FILTER_PATCH],
                'value'       => true,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::TITLE,
                'label'       => 'Title',
                'type'        => Type::HEADING,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
                'overwrite'   => [
                    'store'     => ['rules' => 'required'],
                    'update'    => ['rules' => 'required'],
                ]
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::COMPANY,
                'label'       => 'Company',
                'type'        => Type::OBJECT,
                'order'       => $order ++,
                'input'       => false,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::COMP_NAME,
                'label'       => 'Company name',
                'type'        => Type::STRING,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::COMP_ID,
                'label'       => 'Company ID',
                'type'        => Type::INTEGER,
                'filters'     => [FIELD::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::COMP_IMAGE,
                'label'       => 'Company Image',
                'type'        => Type::IMAGE,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::COMP_TITLE,
                'label'       => 'Company Title',
                'type'        => Type::STRING,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::COMP_DESCRIPTION,
                'label'       => 'Company description',
                'type'        => Type::TEXT,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::URL,
                'label'       => 'URL',
                'type'        => Type::URL,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::RATING,
                'label'       => 'Rating',
                'type'        => Type::RATING,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'clickable',
                'label'       => 'Clickable',
                'type'        => Type::BOOLEAN,
                'filters'     => [FIELD::FILTER_PATCH],
                'value'       => true,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::ACTIVE,
                'label'       => 'Active',
                'type'        => Type::BOOLEAN,
                'filters'     => [FIELD::FILTER_PATCH],
                'value'       => true,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'deal',
                'label'       => 'Deal',
                'type'        => Type::BOOLEAN,
                'filters'     => [FIELD::FILTER_PATCH],
                'value'       => true,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'points',
                'label'       => 'Points',
                'type'        => Type::POINTS,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'shopuid',
                'label'       => 'Shop UID',
                'type'        => Type::INTEGER,
                'filters'     => [FIELD::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'action',
                'label'       => 'Action',
                'type'        => Type::STRING,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::RESOURCE,
                'label'       => 'Resource',
                'description' => 'External resource',
                'type'        => Type::OBJECT,
                'order'       => $order ++,
                'overview'    => false,
                'input'       => false,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::RESOURCE_ID,
                'label'       => 'Resource ID',
                'description' => 'External resource ID from anywhere',
                'type'        => Type::STRING,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
                'overview'    => false,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::RESOURCE_NAME,
                'label'       => 'Resource Name',
                'description' => 'External resource name',
                'type'        => Type::STRING,
                'filters'     => [FIELD::FILTER_PATCH],
                'order'       => $order ++,
                'overview'    => false,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::WEBSITE,
                'label'       => 'Website Condition',
                'type'        => Type::STRING,
                'filters'     => [Field::FILTER_CONDITION],
                'output'      => false,
                'order'       => $order ++,
                'overview'    => false,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::USER,
                'label'       => 'User Condition',
                'type'        => Type::STRING,
                'filters'     => [Field::FILTER_CONDITION],
                'output'      => false,
                'order'       => $order ++,
                'overview'    => false,
            ],
        ];
    }

    public static function getTravelUserFields($resourceId)
    {
        $fieldId = $resourceId * 1000;

        return [
            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::ID,
                'label'       => 'Logo',
                'type'        => Type::INTEGER,
            ],
            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::USERNAME,
                'label'       => 'Username',
                'type'        => Type::STRING,
            ],
            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::PASSWORD_INPUT,
                'label'       => 'Password for user account (input only)',
                'type'        => Type::STRING,
                'output'      => false,
            ],
            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => 'firstname',
                'label'       => 'First name',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => 'lastname',
                'label'       => 'Last name',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::LANGUAGE,
                'label'       => 'Language',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::COMPANY_NAME,
                'label'       => 'Company name',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::COMPANY_VAT,
                'label'       => 'Company VAT',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => 'company_registration',
                'label'       => 'Company registration number',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => 'bic',
                'label'       => 'BIC bank account',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => 'iban',
                'label'       => 'IBAN number',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::ADDRESS,
                'label'       => 'Address',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => 'zipcode',
                'label'       => 'Zipcode',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::CITY,
                'label'       => 'City',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => 'country',
                'label'       => 'Country name',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => 'phonenumber',
                'label'       => 'Phonenumber',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::BUSINESS,
                'label'       => 'Is a company?',
                'type'        => Type::BOOLEAN,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::MANAGING_USER,
                'label'       => 'Managing user',
                'type'        => Type::INTEGER,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::EMAIL,
                'label'       => 'Email address',
                'type'        => Type::EMAIL,
            ],
            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::LOGO,
                'label'       => 'Logo',
                'type'        => Type::IMAGE,
                'input'       => false,
            ],
            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::TOKEN,
                'label'       => 'The API Key/Token',
                'type'        => Type::STRING,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::ACCOUNT_ID,
                'label'       => 'account manager id',
                'type'        => Type::INTEGER,
            ],

            [
                'id'          => $fieldId++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::CONTRACT,
                'label'       => 'Logo',
                'type'        => Type::FILE,
                'input'       => false,
                'overwrite'   => [
                    'store' => [
                        'ignore'       => true,
                        'travel-admin' => ['ignore' => false],
                    ],
                    'update' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ],
                ]
            ],

            [
                'id'        => $fieldId++,
                'resource_id' => $resourceId,
                'name' => ResourceInterface::VAT,
                'label' => 'VAT',
                'type' => Type::DECIMAL,
                'input' => false,
                'overwrite' => [
                    'store' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ],
                    'update' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ]
                ]
            ],
            [
                'id'        => $fieldId++,
                'resource_id' => $resourceId,
                'name' => 'external_yield',
                'label' => 'External yield',
                'type' => Type::DECIMAL,
                'input' => false,
                'overwrite' => [
                    'store' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ],
                    'update' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ]
                ]
            ],
            [
                'id'        => $fieldId++,
                'resource_id' => $resourceId,
                'name' => 'yield_commission',
                'label' => 'Yield commission',
                'type' => Type::DECIMAL,
                'input' => false,
                'overwrite' => [
                    'store' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ],
                    'update' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ]
                ]
            ],
            [
                'id'        => $fieldId++,
                'resource_id' => $resourceId,
                'name' => 'product_commission_reseller',
                'label' => 'Reseller product commission',
                'type' => Type::DECIMAL,
                'input' => false,
                'overwrite' => [
                    'store' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ],
                    'update' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ]
                ]
            ],
            [
                'id'        => $fieldId++,
                'resource_id' => $resourceId,
                'name' => 'provider_commission',
                'label' => 'Provider product commission',
                'type' => Type::INTEGER,
                'input' => false,
                'overwrite' => [
                    'store' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ],
                    'update' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ]
                ]
            ],
            [
                'id'        => $fieldId++,
                'resource_id' => $resourceId,
                'name' => 'provider_commission_percentage',
                'label' => 'Provider product commission percentage',
                'type' => Type::DECIMAL,
                'input' => false,
                'overwrite' => [
                    'store' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ],
                    'update' => [
                        'ignore' => true,
                        'travel-admin' => ['ignore' => false],
                    ]
                ]
            ]
        ];
    }


    /**
     * Build seed blocks to insert in the DB.
     *
     * @param $baseId
     * @param $resourceId
     * @param int $order
     *
     * @return array
     */
    public static function getInsuranceProductFields($baseId, $resourceId, $order = 0)
    {
        return [
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::ENABLED,
                'label'       => 'enabled',
                'type'        => Type::BOOLEAN,
                'filters'     => [Field::FILTER_PATCH],
                'value'       => true,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => ResourceInterface::TITLE,
                'label'       => 'Title',
                'type'        => Type::HEADING,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
                'overwrite'   => [
                    'store' => ['rules' => 'required'],
                    'update' => ['rules' => 'required']
                ]
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'company',
                'label'       => 'Company',
                'type'        => Type::OBJECT,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'company.name',
                'label'       => 'Company name',
                'type'        => Type::STRING,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'company.id',
                'label'       => 'Company ID',
                'type'        => Type::INTEGER,
                'filters'     => [Field::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'company.image',
                'label'       => 'Company Image',
                'type'        => Type::IMAGE,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'company.title',
                'label'       => 'Company Title',
                'type'        => Type::STRING,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'company.description',
                'label'       => 'Company description',
                'type'        => Type::TEXT,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'url',
                'label'       => 'URL',
                'type'        => Type::URL,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'rating',
                'label'       => 'Rating',
                'type'        => Type::RATING,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'price_quality',
                'label'       => 'Price Quality rating',//("{coverage}"=="wa")?0:{accessoires_coverage_value}
                'script'      => '(({price_default} == 0)?0:(({average_rating} + {rating} + {deal} * 1.5 - ({own_risk}/100))/(({price_default}*(12/{payment_period})+{fee}))))',
                'input'       => false,
                'order'       => 2500,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'clickable',
                'label'       => 'Clickable',
                'type'        => Type::BOOLEAN,
                'filters'     => [Field::FILTER_PATCH],
                'value'       => true,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'active',
                'label'       => 'Active',
                'type'        => Type::BOOLEAN,
                'filters'     => [Field::FILTER_PATCH],
                'value'       => true,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'deal',
                'label'       => 'Deal',
                'type'        => Type::BOOLEAN,
                'filters'     => [Field::FILTER_PATCH],
                'value'       => true,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'points',
                'label'       => 'Points',
                'type'        => Type::POINTS,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'shopuid',
                'label'       => 'Shop UID',
                'type'        => Type::INTEGER,
                'filters'     => [Field::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'action',
                'label'       => 'Action',
                'type'        => Type::STRING,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'conditions',
                'label'       => 'Policy conditions',
                'type'        => Type::TEXT,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'rating_count',
                'label'       => 'Rating count',
                'type'        => Type::INTEGER,
                'filters'     => [Field::FILTER_PATCH],
                'order'       => $order ++,
            ],

            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'commission',
                'label'       => 'Commission',
                'type'        => Type::OBJECT,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'commission.total',
                'label'       => 'Commission total',
                'type'        => Type::DECIMAL,
                'filters'     => [Field::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'commission.partner',
                'label'       => 'Commission partner',
                'type'        => Type::DECIMAL,
                'filters'     => [Field::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'polis',
                'label'       => 'Polis',
                'type'        => Type::OBJECT,
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'polis.email',
                'label'       => 'Polis email',
                'type'        => Type::STRING,
                'filters'     => [Field::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'polis.own_funnel',
                'label'       => 'Defines if this product should be closed in own funnel',
                'type'        => Type::BOOLEAN,
                'filters'     => [Field::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],
            [
                'id'          => $baseId ++,
                'resource_id' => $resourceId,
                'name'        => 'polis.website',
                'label'       => 'Defines which website id to use for this funnel',
                'type'        => Type::STRING,
                'filters'     => [Field::FILTER_PATCH],
                'overview'    => false,
                'order'       => $order ++,
            ],

        ];
    }

    /**
     * Build seed blocks to insert in the DB.
     *
     * @param $baseId
     * @param $resourceId
     * @param int $order
     * @param bool $withStatus
     *
     * @return array
     */
    public static function getContractFields($baseId, $resourceId, $order = 0, $withStatus = FieldHelper::CONTRACT_WITHOUT_STATUS)
    {
        return array_merge(
            [
                [
                    'id'          => $baseId++,
                    'resource_id' => $resourceId,
                    'name'        => ResourceInterface::PRODUCT_ID,
                    'label'       => 'Product ID',
                    'type'        => Type::STRING, // use string to make it also possible to store mongo IDs
                    'output'      => false,
                    'overview'    => false,
                    'order'       => $order++,
                    'overwrite'   => [
                        'store' => ['rules' => 'required'],
                    ]
                ],
                [
                    'id'          => $baseId++,
                    'resource_id' => $resourceId,
                    'name'        => ResourceInterface::WEBSITE,
                    'label'       => 'website ID',
                    'type'        => Type::INTEGER,
                    'output'      => false,
                    'overview'    => false,
                    'order'       => $order++,
                    'overwrite'   => [
                        'store' => ['rules' => 'required'],
                    ]
                ],
                [
                    'id'          => $baseId++,
                    'resource_id' => $resourceId,
                    'name'        => ResourceInterface::USER,
                    'label'       => 'user ID',
                    'type'        => Type::INTEGER,
                    'output'      => false,
                    'overview'    => false,
                    'order'       => $order++,
                    'overwrite'   => [
                        'store' => ['rules' => 'required'],
                    ]
                ],
                [
                    'id'          => $baseId++,
                    'resource_id' => $resourceId,
                    'name'        => ResourceInterface::IP,
                    'label'       => 'IP adress',
                    'type'        => Type::STRING,
                    'output'      => false,
                    'overview'    => false,
                    'order'       => $order++,
                    'overwrite'   => [
                        'store' => ['rules' => 'required'],
                    ]
                ],
                [
                    'id'          => $baseId++,
                    'resource_id' => $resourceId,
                    'name'        => ResourceInterface::SESSION_ID,
                    'label'       => 'session ID',
                    'type'        => Type::STRING,
                    'output'      => false,
                    'overview'    => false,
                    'order'       => $order++,
                    'overwrite'   => [
                        'store' => ['rules' => 'required'],
                    ]
                ],
                [
                    'id'          => $baseId++,
                    'resource_id' => $resourceId,
                    'name'        => ResourceInterface::SESSION,
                    'label'       => 'session',
                    'type'        => Type::TEXT,
                    'output'      => false,
                    'overview'    => false,
                    'order'       => $order++,
                    'overwrite'   => [
                        'store' => ['rules' => 'required'],
                    ]
                ],
            ],
            $withStatus ? [
                [
                    'id'          => $baseId++,
                    'resource_id' => $resourceId,
                    'name'        => ResourceInterface::STATUS,
                    'label'       => 'status',
                    'type'        => Type::STRING,
                    'output'      => false,
                    'overview'    => false,
                    'order'       => $order++,
                ]
            ] : []
        );
    }

    /**
     * Combined Field processing function to reduce amount of function calls in recursion.
     *
     * @param Field $field
     * @param ArrayObject $data
     * @param ArrayObject $resolved
     * @param ArrayObject $output
     * @param ArrayObject $input
     * @param ArrayObject $matches
     */
    public static function process(Field $field, ArrayObject $data, ArrayObject $resolved, ArrayObject $output, ArrayObject $input, ArrayObject $matches)
    {
        self::offsetSet($output, $field->name, $field->value);

        $value = Arr::get($data->getArrayCopy(), $field->name);

        // If there is no value from the data, get one from the input as a fallback
        if(is_null($value)){
            $value = Arr::get($input->getArrayCopy(), $field->name, $field->value);
            if(is_array($value)){
                $value = $value[0];
            }

        }

        // First fill it with the last data
        self::offsetSet($output, $field->name, $value);

        //Merge own resource data with the collected data from other resources.
        foreach($field->fromFields as $fromField){

            // Get the resource ID as alias to check
            // the resolved data.
            $alias = $fromField->resource->id;

            // Nothing to do if there is no resolved data
            if( ! isset($resolved[$alias])){
                continue;
            }

            $childData = $resolved->offsetGet($alias);


            // TODO: fromFields check this
            // If resolved data is not a collection, just merge the resolved data (for now)
            if(ArrayHelper::isAssoc($childData)){
                cw($field->name . ' = assoc');
                self::offsetSet($output, $field->name, $childData);
                return;
            }

            // Provide data for matching including the field defaults as a fallback
            $resource   = $field->resource;
            $parentData = $data->getArrayCopy() + $output->getArrayCopy() + $input->getArrayCopy() + $resource->defaults;

            //TODO do we still need this?
            // When this field had a filter on overwrite, and it's overwritten (i.e. not null), keep that value
            if($field->hasFilter(Field::FILTER_OVERWRITE) && $parentData[$field->name] !== null){
                return;
            }

            // Merge the child output with the parent output
            $matched = ResourceRecursionListener::matchChildOutput($resource, $fromField->resource, $parentData, $childData, $field, $matches);

            if($matched != null){
                //set the output the the array
                self::offsetSet($output, $field->name, $matched);

                // Nothing to do for mappings to primary keys.
                // We decided to output all the child resource data as is.
                // We only need to map data to single values if the field
                // is not a primary key, like 'price' or 'date'.

                if($fromField->isPrimary){
                    return;
                }

                // No data? Then we have nothing to merge
                if( ! isset($output[$field->name][$fromField->name])){
                    continue;
                }

                // little trick to work with 'false', 0 and 0.0
                if($output[$field->name][$fromField->name] != null || $output[$field->name][$fromField->name] === false || $output[$field->name][$fromField->name] === 0 || $output[$field->name][$fromField->name] === 0.0){
                    // Pluck one field from the array
                    self::offsetSet($output, $field->name, $output[$field->name][$fromField->name]);
                    return;
                }
            }
        }

        // No script? Nothing to do...
        if($field->script && ( ! isset($input['skipTransform']) || ! $input['skipTransform'])){
            // Add the resource defaults with the output. There can be values
            // that are needed in the scripts, but are stripped of in previous events
            $inputWithDefaults = $input->getArrayCopy() + $field->resource->defaults;


            // Merge the scripted output back to the output
            self::offsetSet($output, $field->name, ResourceRecursionListener::script($field->script, $output->getArrayCopy(), $inputWithDefaults, $field->hasFilter(Field::FILTER_ALLOW_NULL)));
        }

        $value = $output->offsetExists($field->name) ? $output->offsetGet($field->name) : null;

        // Don't typecast if the value is an array
        if($value != null && ! is_array($value)){
            // Just get an array for objects, to avoid errors
            if($value instanceof ArrayObject){
                $value = $value->getArrayCopy();
            }

            switch($field->type){

                case Type::INTEGER:
                case 'int':
                    $value = (int) $value;
                    break;

                case Type::PRICE:
                case 'float':
                case 'decimal':

                    $value = (float) $value;
                    break;

                case 'string':
                case 'text':
                    $value = (string) $value;
                    break;
                case Type::BOOLEAN:
                    //                if ($field->id == 404 && !$value) {
                    //                    die($value);
                    //                }
                    $value = (bool) $value;
                    break;
            }
            self::offsetSet($output, $field->name, $value);
        }
    }

    /**
     * Process one field
     */
    public static function defaults(Field $field, ArrayObject $data, ArrayObject $resolved, ArrayObject $output)
    {
        // The defaults are now in data, because reasons ...
    }

    /**
     * Process one field
     */
    public static function data(Field $field, ArrayObject $data, ArrayObject $resolved, ArrayObject $output, ArrayObject $input,Collection $fields)
    {
        // Get the value from the data
        $hasValue = Arr::has($data->getArrayCopy(), $field->name);
        if($hasValue){
            $value = Arr::get($data->getArrayCopy(), $field->name);
            self::setValue($field, $output, $fields, $value);
        }else{
            if(!in_array($field->type, [Type::ARR, Type::OBJECT])){
                // If there is no value from the data, get one from the input as a fallback
                $hasValue = Arr::has($input->getArrayCopy(), $field->name);
                if($hasValue){
                    $value = Arr::get($input->getArrayCopy(), $field->name);
                    if(is_array($value)){
                        $value = ArrayHelper::isAssoc($value) ? $value : $value[0];
                    }
                    self::setValue($field, $output, $fields, $value);
                }else{
                    self::setValue($field, $output,$fields, $field->value);
                }
            }
        }
    }

    /**
     * Merge own resource data with the collected data from other resources.
     */
    public static function merge(Field $field, ArrayObject $data, ArrayObject $resolved, ArrayObject $output, ArrayObject $input, $index = null)
    {
        if( ! count($field->fromFields)){
            return;
        }

        foreach($field->fromFields as $fromField){

            // Get the resource ID as alias to check
            // the resolved data.
            $alias    = $fromField->resource->id;
            $resource = $field->resource;
            // Provide data for matching including the field defaults as a fallback
            $parentData = $data->getArrayCopy() + $output->getArrayCopy() + $input->getArrayCopy() + $resource->defaults;

            $parallelChildData = static::getParallelChildData($resource, $field, $fromField, $resolved, $parentData);

            if(isset($parallelChildData)){
                $childData = $parallelChildData;
            }elseif( ! isset($resolved[$alias]) || empty($resolved[$alias])){
                continue;
            }else{
                $childData = $resolved->offsetGet($alias);
            }

            // TODO: fromFields check this
            // If resolved data is not a collection, just merge the resolved data (for now)
            if(ArrayHelper::isAssoc($childData)){
                self::offsetSet($output, $field->name, $childData);
                return;
            }

            //TODO do we still need this?
            // When this field had a filter on overwrite, and it's overwritten (i.e. not null), keep that value
            if($field->hasFilter(Field::FILTER_OVERWRITE) && $parentData[$field->name] !== null){
                return;
            }

            // Merge the child output with the parent output
            $matched = ResourceRecursionListener::matchChildOutput($resource, $fromField->resource, $parentData, $childData, $field);


            if($matched != null){
                //set the output the the array

                self::offsetSet($output, $field->name, $matched);

                // Nothing to do for mappings to primary keys.
                // We decided to output all the child resource data as is.
                // We only need to map data to single values if the field
                // is not a primary key, like 'price' or 'date'.

                if($fromField->isPrimary){
                    return;
                }

                // No data? Then we have nothing to merge
                if( ! array_has($output->getArrayCopy(),$field->name.'.'.$fromField->name)){
                    continue;
                }

                $value = array_get($output->getArrayCopy(),$field->name.'.'.$fromField->name);

                // little trick to work with 'false', 0 and 0.0
                if($value != null || $value === false || $value === 0 || $value === 0.0){
                    // Pluck one field from the array
                    self::offsetSet($output, $field->name, $value);
                    return;
                }
            }
        }
        // self::offsetSet($output, $field->name, array_get($output->getArrayCopy(), $field->name, null));
        if (!$field->hasFilter(Field::FILTER_IFNULL_KEEP_ORIGINAL)) {
            self::offsetSet($output, $field->name, null);
        }
    }


    /**
     * Transform the data using scripts
     */
    public static function transform(Field $field, ArrayObject $data, ArrayObject $resolved, ArrayObject $output, ArrayObject $input, $index = null)
    {

        // No script? Nothing to do...
        if( ! $field->script){
            return;
        }

        if(isset($input['skipTransform']) && $input['skipTransform']){
            return;
        }

        // Add the resource defaults with the output. There can be values
        // that are needed in the scripts, but are stripped of in previous events
        $inputWithDefaults = $input->getArrayCopy() + $field->resource->defaults;


        // Merge the scripted output back to the output
        self::offsetSet($output, $field->name, ResourceRecursionListener::script($field->script, $output->getArrayCopy(), $inputWithDefaults, $field->hasFilter(Field::FILTER_ALLOW_NULL)));
    }

    /**
     * Typecast to the right format
     */
    public static function typecast(Field $field, ArrayObject $data, ArrayObject $resolved, ArrayObject $output)
    {

        $name  = $field->name;
        $value = $output->offsetExists($name) ? $output->offsetGet($name) : null;

        // Don't typecast if the value is an array
        if(is_array($value)){
            return;
        }

        if($value === null){
            return;
        }

        // Just get an array for objects, to avoid errors
        if($value instanceof ArrayObject){
            $value = $value->getArrayCopy();
        }

        switch($field->type){

            case Type::INTEGER:
            case 'int':
                $value = (int) $value;
                break;

            case Type::PRICE:
            case 'float':
            case 'decimal':

                $value = (float) $value;
                break;

            case 'string':
            case 'text':
                $value = (string) $value;
                break;
            case Type::BOOLEAN:
                //                if ($field->id == 404 && !$value) {
                //                    die($value);
                //                }
                $value = (bool) $value;
                break;
        }

        self::offsetSet($output, $name, $value);
    }

    /** Get the parallel child data for a resource field if there are any
     *
     * @param Resource $resource
     * @param Field $field
     * @param Field $fromField
     * @param ArrayObject $resolved
     * @param array $parentData
     *
     * @return array|null
     * @throws NoMatchingChildResourceData
     */
    private static function getParallelChildData(Resource $resource, Field $field, Field $fromField, ArrayObject $resolved, array $parentData)
    {
        $parallelChildData = null;
        if($resource->hasBehaviour(Resource::BEHAVIOUR_PARALLEL) && $resolved->offsetExists('parallel')){
            $parallelData = $resolved->offsetGet('parallel');
            //Check to see if you have only one fromField that is not mapped to parallel child data
            $shouldDoNothing = count($field->fromFields) == 1 && $fromField->resource->name != $parentData['resource']['name'];
            if( ! $shouldDoNothing && isset($parallelData[$parentData['resource']['name']])){
                //We only need to merge with parallelChildData when we have the child's resource in the parent data
                //When the parent has the parallel behavior the resource_name will be the specific child
                if($fromField->resource->name == $parentData['resource']['name']){
                    if(empty($parallelData[$parentData['resource']['name']])){
                        //Throw to skip rows for which the parallel service could not fetch due to an error
                        throw new NoMatchingChildResourceData($field, 'Cannot map `'. $field->resource->name .'.'. $field->name .'` from `'. $fromField->resource->name .'.'. $fromField->name .'`: Parallel request `'. $parentData['resource']['name'] .'` empty.');
                    }
                    $parallelChildData = $parallelData[$parentData['resource']['name']];
                }
            }
        }
        return $parallelChildData;
    }

    /**
     * Check if the parent is an array or object and set the value at the correct level
     *
     * @param Field $field
     * @param ArrayObject $output
     * @param $value
     *
     * @throws \Exception
     */
    private static function setValue(Field $field, ArrayObject $output, Collection $fields, $value = null)
    {
        $nameParts = explode(ResourceInterface::SEPARATOR, $field->name);
        if(count($nameParts) > 1){
            $searchFields = $fields->keyBy('name');
            $fieldName = array_pop($nameParts);
            $parentFieldName = implode(ResourceInterface::SEPARATOR, $nameParts);
            $parentField = $searchFields->get($parentFieldName);
            if(is_null($parentField)){
                throw new \Exception("Field ".$parentFieldName." not defined.");
            };

            switch($parentField->type){
                case Type::ARR:
                    $parentValue = self::offsetGet($output, $parentFieldName);
                    foreach($parentValue as &$item){
                        $item = Arr::set($item, $fieldName, $value);
                    }
                    break;
                case Type::OBJECT:
                    self::offsetSet($output, $field->name, $value);
                    break;
                default:
                    throw new \Exception("Field ".$parentFieldName." not of type array or object.");
            }
        }else{

            if($field->type)

            // First fill it with the last data
            self::offsetSet($output, $field->name, $value);
        }
    }


    private static function offsetSet(ArrayObject $object, $key, $value)
    {
        $arr = $object->getArrayCopy();
        Arr::set($arr, $key, $value);
        $object->exchangeArray($arr);
    }

    private static function offsetGet(ArrayObject $object, $key, $default = null)
    {
        $arr = $object->getArrayCopy();
        return Arr::get($arr, $key, $default);
    }

}