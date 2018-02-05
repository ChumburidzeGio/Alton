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


class Mango extends DefaultProvider
{

    public function process_gender()
    {
        $addGenderArray = ['d' => 'Baby',
                           'e' => 'Baby',
                           'f' => 'Baby',
                           's' => 'Dames'];

        $this->data['gender'] = ResourceFilterHelper::getGenderDefinition($this->data['gender'],$addGenderArray);
    }

}