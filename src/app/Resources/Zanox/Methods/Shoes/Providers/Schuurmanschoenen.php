<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 9/10/15
 * Time: 4:08 PM
 */

namespace App\Resources\Zanox\Methods\Shoes\Providers;

use App\Helpers\ResourceFilterHelper;
use App\Resources\Zanox\Methods\Shoes\DefaultProvider;


class Schuurmanschoenen extends DefaultProvider
{


    public function process_size()
    {
        $pregRewriteRules = ['[,]'               => '.',
                             '[\|]'              => ',',
                             '[(\d{2})/(\d{2})]' => '$1,$2',
                             '[.(1/3)]'          => '.3',
                             '[.(2/3)]'          => '.6',
                             '[ (1/2)]'          => '.5',
                             '[-]'               => ',',
                             '[\+]'              => '.5'];

        $sizeString = $this->data["size"];

        if (is_array($sizeString) || empty($sizeString)) {
            $this->data["size"] = [];
            $this->data['error'] = 'this dataset have a incorrect size';
            return;
        }

        $sizeString = ResourceFilterHelper::replaceCharacter($sizeString, $pregRewriteRules);
        $sizeString = ResourceFilterHelper::delWhitespace($sizeString);

        $this->data["size"] = array_values(array_filter(explode(",", $sizeString)));
    }
}