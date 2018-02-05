<?php

namespace App\Listeners\Resources2;

use App;
use App\Exception\PrettyServiceError;
use App\Models\Resource;
use ArrayObject;
use Log;
use Request;
use Restable;

class PermissionListener
{
    public static $permissionsApplied = false;
    const PARTNER_WHITELIST = [
        '127.0.0.1',
        '104.155.18.20',
        '172.16.0.1',
    ];

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.process.input', [$this, 'autoapplyEnabled']);
        $events->listen('resource.process.permissions', [$this, 'handlePermissions']);
    }


    // apply enabled by default if behaviour
    public function autoapplyEnabled(Resource $resource, ArrayObject $input)
    {
        if((strpos(php_sapi_name(), 'cli') !== false)){
            return;
        }

        if(empty($input[OptionsListener::OPTION_PERMISSIONS_APPLIED])){
            return;
        }

        if( ! $resource->hasBehaviour(Resource::BEHAVIOUR_AUTOAPPLY_ENABLED)){
            return;
        }

        $user = app('application') ? app('application')->user : null;
        if( ! $user){
            //TODO:this is dangerous as fuck, don't want to blow things up now
            return;
        }

        $roles = $user->roles->lists('name');

        if($this->hasFullAccess($resource, $roles)){
            return;
        }

        $input->offsetSet('enabled', true);
    }

    /*
     * Handle permissions
     */
    public function handlePermissions(Resource $resource, ArrayObject $input, $action)
    {
        if((strpos(php_sapi_name(), 'cli') !== false)){
            return;
        }

        // We do not allow any _permissions_filter from user-input space, because: security.
        if(isset($input[OptionsListener::OPTION_PERMISSIONS_FILTER])){
            unset($input[OptionsListener::OPTION_PERMISSIONS_FILTER]);
        }

        // Only this class can mark as applied
        if(isset($input[OptionsListener::OPTION_PERMISSIONS_APPLIED])){
            unset($input[OptionsListener::OPTION_PERMISSIONS_APPLIED]);
        }
        if(self::$permissionsApplied){
            return;
        }
        self::$permissionsApplied                           = true;
        $input[OptionsListener::OPTION_PERMISSIONS_APPLIED] = true;

        $application = app('application');

        //allow partner calls
        if(in_array(Request::getClientIp(), self::PARTNER_WHITELIST)){
            return;
        }

        $user = $application ? $application->user : null;
        if ($application && !$user) {
            // No user == system internal call
            return;
        }

        if(empty($resource->permissions)){
            // No permissions set? Do not allow anything.
            throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Resource2 Permission: Access not allowed ' . $resource->name . ' IP = ' . Request::getClientIp());
        }

        $roles = $user ? $user->roles->lists('name') : ['anonymous'];
        cw('user roles:' . implode($roles));
        foreach($resource->permissions as $role => $permissionScheme){
            if( ! (in_array($role, $roles))){
                continue;
            }
            if(array_get($permissionScheme, 'full_access', false) == false && ! isset($permissionScheme['mapping']) && ! isset($permissionScheme['actions'])){
                throw new PrettyServiceError($resource, $input->getArrayCopy(), 'No mapping for this role');
            }
            cw('Role found: ' . $role);

            if(isset($permissionScheme['actions'])){
                cw('actions!');
                cw($permissionScheme['actions']);
                if( ! in_array($action, $permissionScheme['actions'])){
                    throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Action is not allowed: ' . $action);
                }
            }

            foreach(array_get($permissionScheme, 'mapping', []) as $source => $target){
                if(is_array($target)){
                    if($resource->act_as != Resource::ACT_AS_ELOQUENT_REST){
                        throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Unsupported rights mapping used.');
                    }
                    $input[OptionsListener::OPTION_PERMISSIONS_FILTER][OptionsListener::OPTION_SUB_FILTER] = $target;
                    continue;
                }
                $value = $user ? $user->{$source} : null;
                if($value === null){
                    Log::warning('Resource2 Permission issue: trying to filter on ' . $source . ' but user->' . $source . ' is null');
                    throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Resource2 Permission issue: trying to filter on ' . $source . ' but user->' . $source . ' is null');
                }
                $input[OptionsListener::OPTION_PERMISSIONS_FILTER][$target] = $value;
            }

            //set visible if put in permissions
            if(isset($permissionScheme['visible'])){
                if( ! empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
                    $input[OptionsListener::OPTION_VISIBLE] = $permissionScheme['visible'];
                }else{
                    // 'visible' may contain fields which are resource2 mapped. so fetch all, and apply _visible in ResourcePlanListener
                    $input[OptionsListener::OPTION_PERMISSIONS_FILTER][OptionsListener::OPTION_VISIBLE] = $permissionScheme['visible'];
                }

                // Remove any inputs for non-visible, non-underscore fields
                foreach(array_keys($input->getArrayCopy()) as $key){
                    if( ! in_array($key, $permissionScheme['visible']) && ! starts_with($key, '_') && $key != 'debug'){
                        unset($input[$key]);
                    }
                }
            }
            return;
        }

        throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Resource2 Permission: Access not allowed ' . $resource->name . ' IP = ' . Request::getClientIp());
    }

    private function hasFullAccess($resource, $roles)
    {
        if(in_array('admin', $roles)){
            //return true;
        }
        foreach($resource->permissions as $role => $permissionScheme){
            if( ! (in_array($role, $roles))){
                continue;
            }
            if(array_get($permissionScheme, 'full_access', false) === true){
                return true;
            }
        }

        return false;
    }
}