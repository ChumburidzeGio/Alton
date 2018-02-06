<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 9/11/15
 * Time: 11:35 AM
 */
namespace App\Resources\Zanox\Methods\Shoes\Providers;

use App\Resources\Zanox\Methods\Shoes\DefaultProvider;
use App\Helpers\ResourceFilterHelper;


class Asos extends DefaultProvider
{
    public function process_size()
    {
        $pregRewriteRules = ['[\+]' => '.5',
                             '[/]' => '|',
                             '[-]' => '|',];

        $string = ResourceFilterHelper::delWhitespace($this->data['size']);
        $string = ResourceFilterHelper::multiPregReplace($string, $pregRewriteRules);

        $array = array_values(explode("|", $string));


        $gender = ($this->data['gender'] == 'Heren') ? 'male' : 'female';
        foreach ($array as $key => $value) {
            $array[$key] = ResourceFilterHelper::convertUKtoEUSize($value, $gender);

        }

        $arraySearch = ['NoSize'];
        if (in_array($arraySearch, $array)) {
            $this->data['error'] = 'this dataset have a incorrect size';
        }

        $this->data['size'] = array_values($array);
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


        $searchPreg = ['/(men)(\s)*/'                    => 'schoenen',
                       '/(women)(\s)*/'                  => 'schoenen',
                       '/(shoes)(\s)*/'                  => 'schoenen',
                       '/(new in: shoes accs)(\s)*/'     => 'new in: shoes & accs',
                       '/(shoes boots & trainers)(\s)*/' => 'shoes, boots & trainers'];

        $this->data = ResourceFilterHelper::filterOutDataSet($this->data, 'tags', $arrCategoryFilter, 'This category does not belong to the attributes');
        $this->data = ResourceFilterHelper::renameCategory($this->data, $searchPreg);
        parent::process_tags();
    }


}
