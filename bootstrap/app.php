<?php

require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

 $app->withFacades();

 $app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//    App\Http\Middleware\ExampleMiddleware::class
// ]);

// $app->routeMiddleware([
//     'auth' => App\Http\Middleware\Authenticate::class,
// ]);

$app->middleware([

	Clockwork\Support\Lumen\ClockworkMiddleware::class
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/


$app->register(Clockwork\Support\Lumen\ClockworkServiceProvider::class);

/**
 * Resource2 listeners
 */

//DO NOT REMOVE. For automatic import
//START RESOURCE2
Event::subscribe(\App\Listeners\Resources2\AuthorizationListener::class);
Event::subscribe(\App\Listeners\Resources2\CarinsuranceListener::class);
Event::subscribe(\App\Listeners\Resources2\ContentsInsuranceMoneyview2Listener::class);
Event::subscribe(\App\Listeners\Resources2\ElipslifeListener::class);
Event::subscribe(\App\Listeners\Resources2\EloquentRestListener::class);
Event::subscribe(\App\Listeners\Resources2\EmailListener::class);
Event::subscribe(\App\Listeners\Resources2\GeneralListener::class);
Event::subscribe(\App\Listeners\Resources2\GlobalGeoListener::class);
Event::subscribe(\App\Listeners\Resources2\HealthcareListener::class);
Event::subscribe(\App\Listeners\Resources2\HealthcarechListener::class);
Event::subscribe(\App\Listeners\Resources2\InsurancepackageListener::class);
Event::subscribe(\App\Listeners\Resources2\JobsListener::class);
Event::subscribe(\App\Listeners\Resources2\LegalexpensesinsuranceListener::class);
Event::subscribe(\App\Listeners\Resources2\ModelListener::class);
Event::subscribe(\App\Listeners\Resources2\OptionsListener::class);
Event::subscribe(\App\Listeners\Resources2\OutputListener::class);
Event::subscribe(\App\Listeners\Resources2\ParallelServiceListener::class);
Event::subscribe(\App\Listeners\Resources2\Parking2Listener::class);
Event::subscribe(\App\Listeners\Resources2\PastonListener::class);
Event::subscribe(\App\Listeners\Resources2\PrivateHealthcareDeListener::class);
Event::subscribe(\App\Listeners\Resources2\ProductListener::class);
Event::subscribe(\App\Listeners\Resources2\ResourcePlanListener::class);
Event::subscribe(\App\Listeners\Resources2\ResourceRecursionListener::class);
Event::subscribe(\App\Listeners\Resources2\RestListener::class);
Event::subscribe(\App\Listeners\Resources2\Rome2RioListener::class);
Event::subscribe(\App\Listeners\Resources2\ServiceListener::class);
Event::subscribe(\App\Listeners\Resources2\ServiceRestListener::class);
Event::subscribe(\App\Listeners\Resources2\SimonlyListener::class);
Event::subscribe(\App\Listeners\Resources2\TaxitenderListener::class);
Event::subscribe(\App\Listeners\Resources2\TravelAdminsListener::class);
Event::subscribe(\App\Listeners\Resources2\TravelListener::class);
Event::subscribe(\App\Listeners\Resources2\TravelOrderAggregateListener::class);
Event::subscribe(\App\Listeners\Resources2\TravelOrderListener::class);
Event::subscribe(\App\Listeners\Resources2\TravelProductOptionsListener::class);
Event::subscribe(\App\Listeners\Resources2\TravelProvidersListener::class);
Event::subscribe(\App\Listeners\Resources2\TravelResellersListener::class);
Event::subscribe(\App\Listeners\Resources2\TravelUsersListener::class);
Event::subscribe(\App\Listeners\Resources2\VaninsuranceListener::class);
Event::subscribe(\App\Listeners\Resources2\CompaniesListener::class);
//STOP RESOURCE2

//include the resources binding
include_once (base_path('app/Resources/resources.php'));




/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

$app->configure('affiliatenetworks');
$app->configure('database');
$app->configure('iak');
$app->configure('mail');
$app->configure('resource_rolls');

return $app;
