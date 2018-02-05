<?php

namespace App\Helpers;

use App\Models\Website;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Input;

class WebsiteHelper
{
    /**
     * Get the totals from the ChartData table.
     *
     * @param Website $website
     * @return Collection
     */
    public static function totals(Website $website)
    {
        $old = Input::all();

        // First replace the original input
        Input::merge(['website' => $website->id]);

        // Get the totals based on the new input
        $totals = App::make('App\Controllers\ChartDataController')->totals()->getData(true);

        // Set the original input back
        Input::replace($old);

        return new Collection($totals);
    }

    /**
     * Get current TLD
     * 
     * @return string
     */
    public static function tld($local = false) {
        switch(App::environment()){
            case 'test':
                $tld = 'test';
                break;
            case 'acc':
                $tld = 'acc';
                break;
            case 'prod':
            case 'production':
                $tld = $local?'nl':'com';
                break;
            default:
                $tld = 'dev';
        }
        return $tld;
    }

    /**
     * Get current TLD
     *
     * @return string
     */
    public static function protocol() {
        switch(App::environment()){
            case 'prod':
            case 'production':
                return 'https';
            default:
                return 'http';
        }
    }

    /**
     * Get code project url
     * @return string
     * @throws \Exception
     */
    public static function getCodeUrl()
    {
        $appUrl = \Config::get('app.url');
        switch ($appUrl)
        {
            case 'http://api.komparu.dev': return 'http://code.komparu.dev/';
            case 'http://api.komparu.test': return 'http://code.komparu.test/';
            case 'http://api.komparu.acc': return 'https://code-acc.komparu.com/';
            case 'http://api.komparu.com': return 'https://code.komparu.com/';
            default:
                throw new \Exception('Unknown app url:'. $appUrl);
        }
    }
}