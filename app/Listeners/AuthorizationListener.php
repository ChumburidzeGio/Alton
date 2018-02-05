<?php

namespace App\Listeners\Resources2;

use App;
use App\Models\Field;
use App\Models\Resource;
use ArrayObject;
use BeatSwitch\Lock\Callers\Caller;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class AuthorizationListener
 * @package App\Listeners\Resources2
 */
class AuthorizationListener
{
    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
       // $events->listen('resource.process.input', [$this, 'checkUserPrivilege']);
    }


    /**
     * Check if the user has the privilege to perform an action on a resource
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param $input
     */
    public static function checkUserPrivilege(Resource $resource, ArrayObject $input, $action)
    {
        //If the resource is not protectable allow access
        if(!$resource->hasBehaviour(Resource::BEHAVIOUR_PROTECTABLE)){
            return;
        }
        //Check if the user has access to the resource itself first
        if(!self::canUseResource($resource,$action)){
            throw new AuthenticationException('Not allowed');
        }
        $user_visible = [];
        if($input->offsetExists('_visible')){
            $user_visible = $input->offsetGet('_visible');
        }
        $visible = [];
        $model = null;
        //The resource is an eloquent model
        if($resource->eloquent){
            $model = \Illuminate\Support\Facades\app($resource->eloquent);
            $visible = $model->getFillable();
            $visible[] = 'id';
        }else{
            //Classic resource 2
            $fields = $resource->fields;
            $visible = collect($fields)->keyBy('name')->keys();
        }

        //Check which fields the user has access to and set the visible option
        foreach($visible as $fieldName){
            if(!self::canUseField($resource, $fieldName, $action)){
                unset($visible[$fieldName]);
            }
        }

        //Check the user input removing any appended attributes of the Eloquent model
        $user_input = $input->getArrayCopy();
        if($model){
            $user_input = array_filter($user_input, function($key) use ($model){
                return !$model->hasGetMutator($key);
            },ARRAY_FILTER_USE_KEY);
        }
        $extra_fields = array_diff( array_keys($user_input), array_values($visible));
        if($action === 'update' && !empty($extra_fields) && $resource->act_as === Resource::ACT_AS_ELOQUENT_REST){

            //User trying to change a field he does not have access to
            //If you only have password input allow it!
            if(!(count($extra_fields) === 1 && array_values($extra_fields)[0] == 'password_input')){
                throw new AuthenticationException('Not allowed');
            }
        }


        if(!empty($user_visible)){
            //Get what the user wants to hide by diffing what he wants to see from visible
            //This ensures the user cannot use the visible input to see fields he is not supposed to see.
            $user_hidden = array_diff($visible, $user_visible);
            $visible = array_diff($visible, $user_hidden);
        }
        $input->offsetSet('_visible', $visible);

        //self::applyAuthFilter($resource, $input, $action);

        //Throw exception if user has no privilege or fields
        if(empty($visible)){
            throw new AuthenticationException('Not allowed');
        }
    }

    private static function applyAuthFilter($resource, $input, $action)
    {
        if((!$resource->hasBehaviour(Resource::BEHAVIOUR_PROTECTABLE)) || (!empty($resource->eloquent))){
            return;
        }
        $user = app('application')->user;
        //Return if the user is an admin or it is one of the "special" komparu apps with no user :-|
        //TODO: The $user->id is only TEMPORARY until parcompare goes live and the orders will get created with their own account
        if(!$user || $user->is_admin || $user->id === 121){
            return;
        }

        //Find the auth filter field
        $fields = $resource->fields;
        $filterField = self::getAuthFilterField($fields);
        if(!$filterField){
            return;
        }

        $input->offsetSet($filterField->name, $user->id);

    }

    protected static function getAuthFilterField($fields)
    {
        foreach ($fields as $field){
            if($field->strategy == Field::STRATEGY_AUTH_FILTER){
                return $field;
            }
        }
        return null;
    }

    protected static function getCaller()
    {
        // Get the current application that is viewing the api.
        $caller = app('application')->user;

        return $caller instanceof Caller ? $caller : null;
    }

    /**
     * @param Caller $caller
     * @return Lock
     */
    protected static function getLock(Caller $caller)
    {
        /** @var Manager $lock */
        $manager = app('lock.manager');

        return $manager->caller($caller);
    }

    protected static function canUseResource(Resource $resource, $action)
    {
        // Get the current application user that is using the api.
        $caller = self::getCaller();

        if(!$caller){
            return true;
        }
        $lock = self::getLock($caller);

        // Build the right resource type to search the lock_permissions table
        $resourceType = sprintf('api:resource2.%s', $resource->name);

        return $lock->can($action, $resourceType);
    }

    protected static function canUseField(Resource $resource, $field, $action)
    {
//         Always allow users to view ID. This prevent future problems
//         with authorizing in partner, that requires a user id...
        if($field == 'id') return true;

        // Get the current application that is viewing the api.
        if(!$caller = self::getCaller()){
            return true;
        }

        $lock = self::getLock($caller);

        // Build the right resource type to search the lock_permissions table
        $resourceType = sprintf('api:resource2.%s.%s', $resource->name, $field);

        return $lock->can($action, $resourceType);
    }
}