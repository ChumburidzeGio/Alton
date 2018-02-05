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


class Spartoo extends DefaultProvider
{
    public function process_size()
    {
        $pregRewriteRules = ['[\|]'                    => ',',
                             '[ (1/2)]'                => '.5',
                             '[ (1/3)]'                => '.3',
                             '[ (2/3)]'                => '.6',
                             '[(\d{2})\s*/\s*(\d{2})]' => '$1,$2',
                             '[\+]'                    => '.5'];

        $stringSize = $this->data["size"];

        if (is_array($stringSize)) {
            $this->data["size"] = [];
            $this->data['error'] = 'this dataset have a incorrect size';
            return;
        }

        $stringSize = ResourceFilterHelper::replaceCharacter($stringSize, $pregRewriteRules);

        if (empty($stringSize) or preg_match('/[A-Za-z]/', $stringSize)) {
            $this->data['error'] = 'this dataset have a incorrect size';
        }

        $this->data["size"] = array_values(array_filter(explode(",", $stringSize)));
    }

    public function process_color()
    {
        $this->data["color"] = str_replace(' ', '', $this->data["color"]);
        $this->data["color"] = preg_replace('[(^/*)|(/*?)]', '', $this->data["color"]);
        parent::process_color();
    }
}