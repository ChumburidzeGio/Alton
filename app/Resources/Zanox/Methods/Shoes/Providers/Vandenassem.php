<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 9/10/15
 * Time: 4:08 PM
 */

namespace App\Resources\Zanox\Methods\Shoes\Providers;

use App\Resources\Zanox\Methods\Shoes\DefaultProvider;
use App\Helpers\ResourceFilterHelper;


class Vandenassem extends DefaultProvider
{
    public function process_size()
    {
        $pregRewriteRules = ['[\|]'                    => ',',
                           '[ (1/2)]'                => '.5',
                           '[ (1/3)]'                => '.3',
                           '[ (2/3)]'                => '.6',
                           '[(\d{2})\s*/\s*(\d{2})]' => '$1,$2',
                           '[\+]'                    => '.5'];

        $stingSize = $this->data["size"];

        if (is_array($stingSize)) {
            $this->data["size"] = [];
            $this->data['error'] = 'this dataset have a incorrect size';
            return;
        }

        $stingSize = ResourceFilterHelper::replaceCharacter($stingSize, $pregRewriteRules);


        if (empty($stingSize) or preg_match('/[A-Za-z]/', $stingSize)) {
            $this->data['error'] = 'this dataset have a incorrect size';
        }
        $this->data["size"] = array_values(array_filter(explode(",", $stingSize)));
    }
}