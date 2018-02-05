<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 21:39
 *
 */

namespace App\Resources\Zanox\Methods\Shoes;


use App\Resources\Zanox\Methods\AbstractLoadFeeds;

class LoadFeeds extends AbstractLoadFeeds
{


    protected $productType = 'shoes1';
    protected $skipDowload = false;

    //protected $limit = 500;

    protected $processFields = [
        //   'url',
        'justfilterout',
        'size',
        'category_path',
        'size_stock',
        'color',
        'title_origin',
        'title',
        'price_shipping',
        'discount',
        'gender',
        'tags',
        'matrial',
        'main'

//                'description',
//                'offerid',
//                'image',
//                'price',
//                'category',
//                'subcategory',
//                'timetoship',
//                'ean',
//                'price_old',
//                'vendor',
//                'description2',
//                'largeimage',
//                'action',
//                'thirdcategory',
//                'color',
//                'material',
//                'sku',
//                'image2',
//                'image3',
//                'gender',
//                'fourth_category',
//                'discount',
//                'age',
//                'merchantID',
//                'size_stock',
    ];


    protected $classUrl = '';

//    protected $filterMap = [
//
//         'size' => 'split_to_array',
////        'color' => 'filterToUppercase',
////        'price_shipping' => 'comma_to_dot',
////        'price_old' =>  'comma_to_dot',
//    ];

    public function __construct()
    {
        parent::__construct();
        $this->classUrl = __NAMESPACE__;
        $this->strictStandardFields = false;

    }

}
