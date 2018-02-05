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


class Marksspencer extends DefaultProvider
{


    public function process_size()
    {
        $pregRewriteRules = [
        '[\|]'                    => ',',
        '[ (1/2)]'                => '.5',
        '[ (1/3)]'                => '.3',
        '[ (2/3)]'                => '.6',
        '[(\d{2})\s*/\s*(\d{2})]' => '$1,$2',
        '[\+]'                    => '.5'];

        $sizeString = $this->data["size"];

        if (is_array($sizeString) || empty($sizeString)) {
            $this->data["size"] = [];
            $this->data['error'] = 'this dataset have a incorrect size';
            return;
        }

        if ( preg_match('/[A-Za-z]/', $sizeString)) {
            $this->data['error'] = 'this dataset have a incorrect size';
        }

        $string = ResourceFilterHelper::multiPregReplace($sizeString, $pregRewriteRules);
        $this->data["size"] = array_values(array_filter(explode(",", $string)));

    }

    public function process_justfilterout(){
        $arrayFilterTitle = ['/sokken/'];
        ResourceFilterHelper::pregfilterOutDataSet($this->data, 'title', $arrayFilterTitle ,'this articel is not a shoe');
    }


//    public function process_gender()
//    {
//        $this->data['gender'] = ResourceFilterHelper::getGenderDefinition($this->data['gender']);
//    }


}