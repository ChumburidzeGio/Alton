<?php

namespace App\Resources\Healthcare\Methods;


use App\Models\Right;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use App\Models\User;
use App\Models\Role;
use App\Models\Website;
use DB, Artisan, Queue;
use PhpSpec\Exception\Exception;

class WebsitesDaisycon extends HealthcareAbstractRequest
{


    const HEALTHCARE2018_ID = 48;

    const TEMPLATE_AFF = 27;

    public function executeFunction()
    {
        $exists = true;
        $user   = User::where('daisycon_media_id', $this->params['daisycon_media_id'])->first();

        $chars    = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr(str_shuffle($chars), 0, 10);


        if( ! $user || is_null($user)){
            cw('Creating new user');
            $exists = false;
            //Create a user and save the daisycon media id
            $user = User::create([
                'username'          => 'DaisyconUser' . $this->params['daisycon_media_id'],
                'url_identifier'    => uniqid(),
                'password'          => $password,
                'daisycon_media_id' => $this->params['daisycon_media_id'],
                'email'             => $this->params['email'],
                'firstname'         => 'Daisycon',
                'lastname'          => 'User',
                'user_status_id'    => 9,
            ]);

            //Set the user to be a publisher
            cw('Adding role to user ' . $user->id);
            $role = Role::where('name', 'publisher')->firstOrFail();
            $user->roles()->save($role);
        }

        $website = Website::where('user_id', $user->id)->where('product_type_id', self::HEALTHCARE2018_ID)->first();

        if( ! $website){

            $website = Website::create([
                'user_id'         => $user->id,
                'product_type_id' => self::HEALTHCARE2018_ID,
                'template_id'     => self::TEMPLATE_AFF,
                'name'            => 'DaisyconUser' . $user->daisycon_media_id . 'Website',
                'url'             => isset($this->params['website_url']) ? $this->params['website_url'] : 'https://daisycon.nl',
                'autofocus'       => 1,
            ]);
            cw('Created new website ' . $website->id);
            Right::create([
                'user_id'         => $user->id,
                'product_type_id' => self::HEALTHCARE2018_ID,
                'website_id'      => $website->id,
                'key'             => 'daisycon_widget',
                'value'           => 1,
            ]);

            //add style
            try{
                $daisyconStyle = [
                    '__id'            => 1,
                    '__condition'     => 'website',
                    '__value'         => $website->id,
                    'primary_color'   => '#9CD138',
                    'secondary_color' => '#3498DB'
                ];
                DB::connection('mysql_product')->table('style_healthcare_extended')->insert($daisyconStyle);
            }catch(Exception $e){
                return ['error' => $e->getMessage()];
            }
        }

        $url     = 'https://code.komparu.com/demo/' . $website->url_identifier;
        $urlDemo = 'https://code.komparu.com/' . $website->url_identifier;
        Queue::push(\App\Jobs\NotifyApplicationsJob::class, ['resource' => 'website', 'action' => 'saved', 'id' => $website->id, 'multi' => true]);
        sleep(3);
        Queue::push(\App\Jobs\NotifyApplicationsJob::class, ['resource' => 'website', 'action' => 'saved', 'id' => $website->id, 'multi' => true]);
        sleep(3);

        $this->result = [
            'username' => $user->username,
            'password' => $exists ? null : $password,
            'url_demo' => $url,
            'code'     => '<div id="kz"> <script async="async" src="' . $urlDemo . '"></script></div>',
        ];

    }
}