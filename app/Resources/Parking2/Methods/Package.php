<?php

namespace App\Resources\Parking2\Methods;


use App\Helpers\DocumentHelper;
use App\Interfaces\ResourceInterface;
use Request;
use App\Models\Resource;
use App\Listeners\Resources2\ParallelServiceListener;
use App\Resources\Parkandfly\Parking2WrapperAbstractRequest;

class Package extends Parking2WrapperAbstractRequest
{
    const resources = [
        'product.parking2only' => 250,
        //'rides.taxitender'     => 260
    ];

    public function executeFunction()
    {
        $data = (array_get($this->params, 'getproduct') and array_has($this->params, '__id'))
            ? [$this->getProducts($this->params)]
            : $this->getAll();

        $this->result = $data;

    }


    private function getProducts($params)
    {

        if (\MongoId::isValid(array_get($params, '__id'))) {
            $data = DocumentHelper::show(
                'product', 'parking2only',
                array_get($params, '__id'),
                ['conditions' => ['user' => array_get($params, 'user'), 'website' => array_get($params, 'website')]], true)->toArray();
        } else {
            $data = array_map(
                'self::transformTaxitender',
                $this->internalRequest('taxitender', 'findBookableRidesRetour', ['__id' => array_get($params, '__id')])
            )[0];
        }

        return $data;
    }

    private function getAll()
    {
        $resources                = self::getResources();
        $motherResource           = Resource::find(2500);
        $data                     = new \ArrayObject();
        $inputs                   = self::getInputs($this->params);
        $headers['X-Auth-Token']  = Request::header('X-Auth-Token');
        $headers['X-Auth-Domain'] = Request::header('X-Auth-Domain');

        ParallelServiceListener::process(new \ArrayObject($resources), $inputs, $data, $headers, 'index', $motherResource);

       // $data['rides.taxitender'] = array_map('self::transformTaxitender', array_get((array)$data, 'rides.taxitender', []));

        return self::mergeResults($data, array_get($this->params, '__order'), array_get($this->params, '_direction', 'asc'));
    }

    private static function getResources()
    {
        $resources = iterator_to_array(Resource::whereIn('id', self::resources)->get());

        return array_combine(array_map(function ($resource) {
            return $resource->name;
        }, $resources), $resources);
    }

    private static function getInputs($params)
    {
        return new \ArrayObject([
            'product.parking2only' => $params,
          //  'rides.taxitender'     => self::getTaxitenderInputs($params)
        ]);
    }

    private static function getTaxitenderInputs($params)
    {
        return array_filter(array_merge([
            '__id'               => array_get($params, '__id'),
            'area_id'            => array_get($params, 'area_id'),
            'postal_code'        => array_get($params, 'postal_code'),
            'house_number'       => array_get($params, 'house_number'),
            'fromLatitude'       => array_get($params, 'fromLatitude'),
            'fromLongitude'      => array_get($params, 'fromLongitude'),
            'pickupDatetime'     => isset($params['departure_date']) ? date('Y-m-d\TH:i:s\Z', strtotime($params['departure_date'])) : null,
            'pickupDatetimeFrom' => isset($params['arrival_date']) ? date('Y-m-d\TH:i:s\Z', strtotime($params['arrival_date'])) : null,
            '__timeout'          => 5,
        ]));
    }

    private static function transformTaxitender($ride)
    {
        return $ride;
        return array_merge($ride, [
            ResourceInterface::NAME                   => 'TaxiTender',
            ResourceInterface::RESOURCE               => [
                ResourceInterface::ID   => $ride['searchQueryID'] . '_' . $ride['searchQueryResultID'],
                ResourceInterface::NAME => 'rides.taxitender',
            ],
            ResourceInterface::SERVICE                => 'Taxi',
            ResourceInterface::TIME                   => round($ride['time'] / 60) . ' min',
            ResourceInterface::IMAGES                 => [$ride['image']],
            ResourceInterface::OPTIONS                => [],
            ResourceInterface::AVAILABILITY_COUNT     => 0,
            ResourceInterface::CONTRACT_RESOURCE_NAME => 'booking.taxitender'
        ]);
    }

    private static function mergeResults($data, $order, $direction)
    {
        $merged = call_user_func_array('array_merge', (array) $data);
        if ($order) {
            usort(
                $merged,
                $direction === 'asc'
                    ? function ($a, $b) use ($order) {
                    return array_get($a, $order) > array_get($b, $order);
                }
                    : function ($a, $b) use ($order) {
                    return array_get($a, $order) > array_get($b, $order);
                }
            );
        }

        return $merged;
    }


}