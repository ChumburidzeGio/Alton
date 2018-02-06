<?php

namespace App\Listeners\Resources2;

use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Application;
use App\Models\ProductType;
use App\Models\Resource;
use App\Models\Role;
use App\Models\User;
use App\Models\Website;
use ArrayObject;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\App;

class TravelUsersListener
{
    protected $_fixedRole = null;

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.users.travel.process.input', [$this, 'addTravelFilter']);
        $events->listen('resource.users.travel.process.input', [$this, 'processPassword']);
        $events->listen('resource.users.travel.process.after', [$this, 'setTravelRelatedData']);
        $events->listen('resource.users.travel.process.after', [$this, 'addUserTokenToResult']);
        $events->listen('resource.users.travel.process.after', [$this, 'fixZeroManagingUser']);
    }

    public function addTravelFilter(Resource $resource, ArrayObject $input, $action)
    {
        if ($this->_fixedRole) {
            $input[OptionsListener::OPTION_PERMISSIONS_FILTER][OptionsListener::OPTION_SUB_FILTER]['roles'] = [
                'whereHas' => [
                    'where'  => ['name', $this->_fixedRole],
                ],
            ];
        }

        $input[OptionsListener::OPTION_PERMISSIONS_FILTER][OptionsListener::OPTION_SUB_FILTER]['id'] = [
            'whereIn' => [
                'select' => 'user_id',
                'from'   => 'product_type_user',
                'where'  => ['product_type_id', '=', 47],
            ],
        ];
    }

    public function processPassword(Resource $resource, ArrayObject $input, $action)
    {
        //If it is not an update and not a store do not do anything
        if (!in_array($action, ['update', 'store'])) {
            return;
        }
        //Check if you have password_input in the input
        if($input->offsetExists('password_input')){
            //If there is a password_input field add it to the input
            $input->offsetSet('password', $input->offsetGet('password_input'));
        }
    }

    public function fixZeroManagingUser(Resource $resource, ArrayObject $input, ArrayObject $output, $action, $id)
    {
        if ($action != 'index' && isset($output[ResourceInterface::MANAGING_USER]) && $output[ResourceInterface::MANAGING_USER] == 0)
            $output[ResourceInterface::MANAGING_USER] = null;

        if ($action == 'index') {
            foreach ($output as $k => $v)
                if (isset($v[ResourceInterface::MANAGING_USER]) && $v[ResourceInterface::MANAGING_USER] == 0)
                    $output[$k][ResourceInterface::MANAGING_USER] = null;
        }
    }

    public function setTravelRelatedData(Resource $resource, ArrayObject $input, ArrayObject $output, $action, $id)
    {
        //If it is not an update and not a store do not do anything
        if( ! ($action === 'store')){
            return;
        }
        /** @var User $user */
        $user = User::findOrFail(array_get($output->getArrayCopy(), 'id'));

        //Get the travel product type
        $productType = ProductType::where('name', 'travel')->firstOrFail();
        //save relation to user
        $user->productTypes()->save($productType);

        //give this new user a publisher role
        $role = Role::where('name', isset($this->_fixedRole) ? $this->_fixedRole : array_get($input->getArrayCopy(), ResourceInterface::ROLE))->firstOrFail();
        $user->roles()->save($role);

        // Create website for usage in CRM
        /** @var Website $crmWebsite */
        $crmWebsite = Website::create([
            ResourceInterface::NAME => 'Mobian Dashboard comparison',
            ResourceInterface::PRODUCT_TYPE_ID => 47,
            ResourceInterface::USER_ID => $user->id,
            ResourceInterface::TEMPLATE_ID => 25,
            ResourceInterface::LANGUAGE => 'nl',
        ]);
        switch (App::environment())
        {
            case 'prod':
            case 'production' :
                $url = 'https://travel-crm.komparu.com';
                break;
            case 'local':
            case 'development' :
                $url = 'http://localhost:3000';
                break;
            case 'test':
            case 'testing':
                $url = 'http://travel-crm.komparu.test';
                break;
            case 'acc':
            case 'acceptation':
                $url = 'https://travel-crm-acc.komparu.com';
                break;
            default:
                throw new \Exception('Unknown environment: `'. App::environment() .'`');
        }
        $crmWebsite->update([ResourceInterface::URL => $url .'/#/websites/'. $crmWebsite->id .'/compare']);
        ResourceHelper::callResource2('website_rights.travel', [
            ResourceInterface::__ID => $crmWebsite->id,
            ResourceInterface::USER_ID => $user->id,
            ResourceInterface::IS_CRM_TOOL => true,
            ResourceInterface::SKIP_PAYMENT => false,
        ], RestListener::ACTION_UPDATE);
    }

    public function addUserTokenToResult(Resource $resource, ArrayObject $input, ArrayObject $output, $action, $id)
    {
        //If it is not a show do nothing
        if( ! ($action === 'show')){
            return;
        }
        $data = $output->getArrayCopy();
        if(isset($data['id'])){
            //Get the application of the user!
            $app = Application::where('user_id', $data['id'])->first();
            if($app && $app->token != null){
                $output->offsetSet('token', $app->token);
            }
        }
    }
}