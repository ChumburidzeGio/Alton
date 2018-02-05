<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 9/11/15
 * Time: 11:35 AM
 */
namespace App\Resources\Zanox\Methods\Shoes\Providers;

use App\Helpers\ResourceFilterHelper;
use App\Resources\Zanox\Methods\Shoes\DefaultProvider;


class Omoda extends DefaultProvider
{


    public function process_size()
    {

        $pregRewriteRules = ['[,]'               => '.',
                             '[\|]'              => ',',
                             '[(\d{2})/(\d{2})]' => '$1,$2',
                             '[.(1/3)]'          => '.3',
                             '[.(2/3)]'          => '.6',
                             '[-]'               => ',',
                             '[\+]'              => '.5'];

        $sizeString = $this->data["size"];

        if (is_array($sizeString || empty($sizeString))) {
            $this->data["size"] = [];
            $this->data['error'] = 'this dataset have a incorrect size';
            return;
        }

        $sizeString = ResourceFilterHelper::multiPregReplace($sizeString, $pregRewriteRules);
        $this->data["size"] = array_values(array_filter(explode(",", $sizeString)));
    }

    public function process_price_shipping()
    {
        $minOrderSize = 50.00;
        $shippingPrice = 3.95;

        if ($this->data['price_shipping'] == NULL || is_array($this->data['price_shipping'])) {

            if ($this->data['price'] < $minOrderSize) {
                $this->data['price_shipping'] = $shippingPrice;
                return;
            }

            $this->data['price_shipping'] = 0.00;
        }
    }
}
