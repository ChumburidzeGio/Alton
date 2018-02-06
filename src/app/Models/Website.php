<?php

namespace App\Models;

use App;
use App\Behaviours\Ownable;
use App\Behaviours\ProductTypable;
use App\Behaviours\Relatable;
use App\Helpers\WebsiteHelper;
use Config;
use Illuminate\Auth\UserInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;

/**
 * Class Website
 * @package v1
 *
 * @property ProductType $productType
 * @property User $user
 * @property Click[] $clicks
 * @property TestSuite[] $suites
 * @property TestSuite[] $activeSuite
 * @property Test[] $tests
 * @property Template $template
// * @property Collection $stats
 * @property Revision[] $revisions
 * @property boolean $widget_page_selected
 * @property string $language
 */
class Website extends BaseModel implements Relatable, Ownable, ProductTypable
{
    protected $visible = [
        'id',
        'product_type_id',
        'title',
        'name',
        'url',
        'css',
        'live',
        'user',
        'user_id',
        'rights',
        'productType',
        'suites',
        'tests',
//        'revisions',
        'use_custom_urls',
//        'stats',
        'template',
        'template_id',
        'url_identifier',
        'version',
        'logo',
        'type',
        'language',
        'analytics',
        'autofocus',
        'is_locked',
        'urlDemo',
        'url_demo',
        'code',
    ];

    protected $appends = [
        'title',
        'domain',
        'urlDemo',
        'url_demo',
        'urlIframe',
        'code',
        'config',
//        'stats',
        'version',
        'scss',
        'page_templates',
        'activeSuite',
    ];

    protected $fillable = [
        'product_type_id',
        'user_id',
        'name',
        'url',
        'logo',
        'language',
        'css',
        'live',
        'use_custom_urls',
        'template_id',
        'widget_page_selected',
        'selected_preset',
        'analytics',
        'autofocus',
        'type'
    ];

    protected $with = ['user', 'productType.settings.options.setting'];

    protected $definitions = [
        'id'          => 'integer',
        'live'        => 'bool',
        'version'     => 'integer',
        'template_id' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function productType()
    {
        return $this->belongsTo('App\Models\ProductType');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clicks()
    {
        return $this->hasMany('App\Models\Click');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function configurations()
    {
        return $this->hasMany('App\Models\Configuration');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function suites()
    {
        return $this->hasMany('App\Models\TestSuite');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tests()
    {
        return $this->hasMany('App\Models\Test');
    }

//    /**
//     * @return \Illuminate\Database\Eloquent\Relations\HasMany
//     */
//    public function revisions()
//    {
//        return $this->morphMany('App\Models\Revision', 'revisionable');
//    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function template()
    {
        return $this->belongsTo('App\Models\Template');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userConfigurations()
    {
        return $this->hasMany('App\Models\Configuration', 'user_id', 'user_id');
    }



    /**
     * Returns rights which either have same product type, or where product_type_id =0
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rights()
    {
        // Get all rights belonging to a user, and potentially specifically set by `product_type_id` or `website_id`
        $relation = $this->hasMany('App\Models\Right', 'user_id', 'user_id')->where(function ($query) {
            $query->where('product_type_id',$this->getAttributes()['product_type_id'])
                  ->orWhere('product_type_id', 0);
        })->where(function ($query) {
            $query->where('website_id',$this->getAttributes()['id'])
                ->orWhere('website_id', 0);
        })->where('active','1');

        // Sort by specific IDs so that the most relevant are returned last, and rights are always returned in a specific order.
        // (a right with website_id `0` will be before a specific website ID)
        $relation = $relation->orderBy('website_id')->orderBy('product_type_id')->orderBy('user_id');

        return $relation;
    }

    public function getRight($rightKey)
    {
        $value = null;

        foreach ($this->rights()->getQuery()->get() as $right)
        {
            if ($right->key === $rightKey)
                $value = $right->value; // Do not directly return here, we want the last right that matches
        }

        return $value;
    }

    /**
     * @return TestSuite|null
     */
    public function getActiveSuiteAttribute()
    {
        return $this->suites()->where('status', TestSuite::STATUS_ACTIVE)->first();
    }
    /**
     * @return int
     */
    public function getVersionAttribute()
    {
        return 10;
    }

    /**
     * @return Collection
     */
    public function getPageTemplatesAttribute()
    {
        return Config::get('widget.seed.' . $this->productType['name']);
    }

    /**
     * Narrow down the query results to only records that are
     * owned by this application user or company.
     *
     * @param UserInterface $user
     * @param EloquentBuilder $qb
     *
     * @return mixed
     */
    public function filterUser(UserInterface $user, EloquentBuilder $qb)
    {
        if($user->networks->count()) {
            return;
        }


        if(in_array('product-owner', $user->roles->lists('name')) || in_array('service-owner', $user->roles->lists('name'))) {
            $qb->whereIn('product_type_id', $user->product_types->lists('id'));
            return;
        }

        if($user->companies->count()) {
//            $qb->whereHas('user', function($q) use ($user) {
//                $q->whereIn('company_id', $user->companies->lists('id'));
//            });
            return;
        }


        // The user only is allowed to its own data or the permission based user ones.
        $users =  array_unique(array_merge($user->children->lists('id'), [$user->getAuthIdentifier()]));
        $qb->whereIn('user_id', $users);
    }

    /**
     * Check if the user or company is allowed to this model.
     *
     * @param UserInterface $user
     *
     * @return bool
     */
    public function isAllowed(UserInterface $user)
    {
        if($user->productTypes->count() && !in_array($this->product_type_id, $user->productTypes->lists('id'))) {
            return false;
        }

        if($user->companies->count() && !in_array($this->user->company_id, $user->companies->lists('id'))) {
            return false;
        }

        // The user is allowed for the children and itself
        $allowed = array_merge($user->children->lists('id'), [$user->getAuthIdentifier()]);
        if(!in_array($this->user_id, $allowed)) {
            return false;
        }

        return true;
    }

    /**
     * Set the owner of a model.
     *
     * @param UserInterface $user
     *
     * @return mixed
     */
    public function setOwner(UserInterface $user)
    {
        $this->user_id = $user->getAuthIdentifier();
    }

    /**
     * @return string
     */
    public function getTitleAttribute()
    {
        return $this->name ?: '... - ' . ($this->productType ? ' ' . $this->productType->name : '');
    }

    /**
     * @return string
     */
    public function getUrlDemoAttribute()
    {
        if( ! $this->user){
            return;
        }
        if($this->productType->iframe_js == 'iframe'){
            return sprintf('%s/demo/%s/%d/%d', $this->domain, $this->productType->name, $this->user->url_identifier, $this->id);
        }
        if($this->productType->iframe_js == 'js' || $this->productType->iframe_js == 'both'){
            return sprintf('%s/jsdemo/%s/%d/%d', $this->domain, $this->productType->name, $this->user->url_identifier, $this->id);
        }

        // New style...
        return sprintf('%s/demo/%s', $this->domain, $this->url_identifier);
    }

    /**
     * @return string
     */
    public function getUrlIframeAttribute()
    {
        if( ! $this->user){
            return;
        }
        return sprintf('%s/%s/%s/%s', $this->domain, $this->productType->name, $this->user->url_identifier, $this->id);
    }


    /**
     *
     * @return string
     */
    public function getCodeAttribute()
    {
        if( ! $this->user || !$this->productType){
            return;
        }
        if($this->productType->iframe_js == 'iframe'){
            return sprintf('<iframe width="980" height="1500" src="%s" id="iFrame" frameborder="0" scrolling="no" onload="scroll(0,0);"></iframe><script>function receiveSize(e){if(e.origin==="%s"){document.getElementById("iFrame").style.height=e.data+"px"}}window.addEventListener("message",receiveSize,false)</script>',
                $this->urlIframe, $this->domain);
        }
        if($this->productType->iframe_js == 'js' || $this->productType->iframe_js == 'both'){
            return sprintf('<div id="kz"><script src="%s/js/%s/%s/%s"></script></div>', $this->domain, $this->productType->name, $this->user->url_identifier, $this->id);
        }
        if($this->productType->iframe_js == 'laravel'){
            return sprintf('<div id="kz"><script src="' . $this->domain . '/' . $this->url_identifier . '.js"></script></div>', $this->domain, $this->url_identifier);
        }
        return sprintf('<div id="kz"><script src="' . $this->domain . '/' . $this->url_identifier . '"></script></div>', $this->domain, $this->url_identifier);
    }
    /**
     * Get website domain based on locale
     * @return string com/de/nl
     */
    public function getDomainAttribute()
    {
        if( ! $this->user){
            return;
        }
        $domain = 'komparu';

        //        switch($this->user->language) {
        //            case 'en':          $tld = 'com'; break;
        //            default:            $tld = $this->user->language;
        //        }

        switch(App::environment()){
            case 'test':
                $tld = 'test';
                break;
            case 'acc':
                $tld = 'acc';
                break;
            case 'prod':
            case 'production':
                $tld = 'com';
                break;
            default:
                $tld = 'dev';
        }

        if( ! $this->productType){
            return;
        }

        switch($this->productType->iframe_js){
            case 'laravel':
            case 'komparu':
                $sub = 'code';
                if (App::environment() == 'acc') {
                    $sub = 'code-acc';
                    $tld = 'com';
                }
                break;
            default:
                $sub = 'media';
        }

        return sprintf(WebsiteHelper::protocol().'://%s.%s.%s', $sub, $domain, $tld);
    }

    /**
     * @deprecated 2.0 (Only here for healthcare)
     * @return array
     */
    public function getConfigAttribute()
    {
        $webconfig  = $this->configurations->lists('value', 'key');
        $userconfig = $this->userConfigurations->lists('value', 'key');
        return array_merge($userconfig, $webconfig);
    }


//    /**
//     * @return \Illuminate\Support\Collection
//     */
//    public function getStatsAttribute()
//    {
//        return WebsiteHelper::totals($this);
//    }

    /**
     * Only fetch items related with these product type IDs
     *
     * @interface ProductTypable
     *
     * @param Array $types
     * @param EloquentBuilder $qb
     *
     * @return mixed
     */
    public function filterProductTypes(Array $types, EloquentBuilder $qb)
    {
        $qb->whereIn('product_type_id', $types);
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
        return [
            'user',
            'user.rights',
            'rights',
            'clicks',
//            'revisions',
            'configurations',
            'template',
            'productType',
            'productType.settings',
            'productType.settings.options',
            'productType.products',
            'productType.filters',
            'productType.filters.specifications',
            'suites',
            'suites.tests',
            'suites.tests.website',
        ];
    }
}