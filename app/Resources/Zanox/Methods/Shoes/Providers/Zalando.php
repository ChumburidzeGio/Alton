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


class Zalando extends DefaultProvider
{
    public function process_size()
    {
        $pregRewriteRules = ['[One|Size]'        => 'OneSize',
                             '[,]'               => '.',
                             '[\|]'              => ',',
                             '[(\d{2})/(\d{2})]' => '$1,$2',
                             '[.(1/2)]'          => '.5',
                             '[.(1/3)]'          => '.3',
                             '[.(2/3)]'          => '.6',
                             '[-]'               => ',',
                             '[/]'               => ',',
                             '[(\d)m]'           => '$1',
                             '[\+]'              => '.5'];

        $this->data["size_origin"] = $this->data["size"];
        $sizeString = $this->data["size"];

        if (is_array($sizeString)) {
            $this->data["size"] = [];
            return;
        }

        $sizeString = ResourceFilterHelper::replaceCharacter($sizeString, $pregRewriteRules);
        $this->data["size"] = array_values(array_filter(explode(",", $sizeString)));
    }

    public function process_tags()
    {
        $arrCategoryFilter = ['accessories',
            'jewellery',
            'bags & purses',
            'knee socks',
            'socks',
            'hats',
            'not applicable',
            'sunglasses'];
        $this->data = ResourceFilterHelper::filterOutDataSet($this->data, 'tags', $arrCategoryFilter, 'This category does not belong to the attributes');
        parent::process_tags();
    }

//    public function process_title()
//    {
//
//        $pregReplaceArray = ['[/]'    => '[/]*',];
//        $this->data['color_origin'] = ResourceFilterHelper::multiPregReplace($this->data['color_origin'],$pregReplaceArray);
//
//        $pregReplaceArray = ['([^\s]*[/]*'.$this->data['color_origin'].'[/]*[^\s]*)' => ''];
//        foreach(self::$basicColorDefine['en'] as $key => $value)
//        {
//            $pregReplaceArray['([^\s]*[/]*'.$value.'[/]*[^\s]*)'] = '';
//            $pregReplaceArray['([^\s]*[/]*'.strtolower($value).'[/]*[^\s]*)'] = '';
//        }
//
//        $this->data['title'] = ResourceFilterHelper::multiPregReplace($this->data['title'],$pregReplaceArray);
//    }

}