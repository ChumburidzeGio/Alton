<?php

namespace App\Resources\Elipslife\Methods;


use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Elipslife\AbstractElipslifeRequest;

class BmiListing extends AbstractElipslifeRequest
{

    public $params     = [];

    public function executeFunction()
    {
        $cellData = [];
        //Loop through possible heights
        $fetchedRows = [];
        foreach(self::possibleHeightRanges as $key => $heightRange)
        {
            //Loop through possible weights
            foreach (self::possibleWeightRanges as $possibleWeightRange)
            {
                $params = [];
                $params[ResourceInterface::ID]    = '1r8LN4RGWF50bJer36nAFtY1n09kffhIWeAlLB6rdNpI';
                $params[ResourceInterface::RANGE] = 'c'.$possibleWeightRange['row'].':'.'s'.$possibleWeightRange['row'];
                $params[ResourceInterface::OPTIONS] = [];

                if(!isset($fetchedRows[$params[ResourceInterface::RANGE]])){
                    //Call google to get the corresponding row if it has not been fetched already
                    $fetchedRows[$params[ResourceInterface::RANGE]] = ResourceHelper::callResource1('api.google', 'sheetwithoptions', $params);
                }
                $data = $fetchedRows[$params[ResourceInterface::RANGE]];
                $cells = $data['result']['sheets'][0]['data'][0]['rowData'][0]['values'];

                foreach($cells as $cell) {
                    $cellValue = array_get($cell, 'formattedValue', null);

                    //No strings ?
                    if (is_string($cellValue))
                        continue;

                    $cellEffectiveFormat = array_get($cell, 'effectiveFormat', null);

                    //If this is not an array, the cell does not contain any background color
                    //Associated with it
                    if (!is_array($cellEffectiveFormat))
                        continue;

                    $colours = array_get($cellEffectiveFormat, 'backgroundColor');
                    $type = null;
                    foreach(self::allowedColorCombos as $combo){

                        if ($combo['red'] === $colours['red'] && $combo['blue'] === $colours['blue'] && $combo['green'] === $colours['green']) {
                            //Is it the good color?  pass:true
                            $pass = true;
                            $key = $heightRange['height_from'] . $heightRange['height_to'] . $possibleWeightRange['weight_from'] . $combo[ResourceInterface::TYPE];

                            //Assign data to cell
                            $cellData[$key] = [
                                'height_from' => $heightRange['height_from'],
                                'height_to' => $heightRange['height_to'],
                                'weight_from' => $possibleWeightRange['weight_from'],
                                'weight_to' => $possibleWeightRange['weight_to'],
                                'pass' => $pass,
                                'type' => $combo[ResourceInterface::TYPE]
                            ];
                        }
                    }
                }
            }
        }

        $this->result = array_values($cellData);


    }


    const allowedColorCombos = [
        [
            'type' => ResourceInterface::ELIPSLIFE_NORMAL_APPLY,
            'red' => null,
            'green' => 1,
            'blue' => null
        ],
        [
            'type' => ResourceInterface::ELIPSLIFE_NORMAL_NONSMOKER_APPLY,
            'red' => 1,
            'green' => 1,
            'blue' => null
        ],
        [
            'type' => ResourceInterface::ELIPSLIFE_NORMAL_FEMALE_APPLY,
            'red' => null,
            'green' => 1,
            'blue' => 1
        ],
    ];


    const possibleHeightRanges = [

        [
            'height_from' => 139,
            'height_to' => 139,
            'position' => 'c',
        ],
        [
            'height_from' => 140,
            'height_to' => 144,
            'position' =>'d'
        ],
        [
            'height_from' => 145,
            'height_to' => 148,
            'position' =>'e'
        ],
        [
            'height_from' => 149,
            'height_to' => 154,
            'position' =>'f'
        ],
        [
            'height_from' => 155,
            'height_to' => 158,
            'position' =>'g'
        ],
        [
            'height_from' => 159,
            'height_to' => 162,
            'position' =>'h'
        ],
        [
            'height_from' => 163,
            'height_to' => 166,
            'position' =>'i'
        ],
        [
            'height_from' => 167,
            'height_to' => 170,
            'position' =>'j'
        ],
        [
            'height_from' => 171,
            'height_to' => 174,
            'position' =>'k'
        ],
        [
            'height_from' => 175,
            'height_to' => 178,
            'position' =>'l'
        ],
        [
            'height_from' => 179,
            'height_to' => 182,
            'position' => 'm'
        ],
        [
            'height_from' => 183,
            'height_to' => 186,
            'position' => 'n'
        ],
        [
            'height_from' => 187,
            'height_to' => 190,
            'position' => 'o'
        ],
        [
            'height_from' => 191,
            'height_to' => 194,
            'position' => 'p'
        ],
        [
            'height_from' => 195,
            'height_to' => 198,
            'position' => 'q'
        ],
        [
            'height_from' => 199,
            'height_to' => 202,
            'position' => 'r'
        ],
        [
            'height_from' => 203,
            'height_to' => 203,
            'position' => 's'
        ],
    ];


    const possibleWeightRanges = [
        [
            'weight_from' => 33,
            'weight_to' => 35,
            'row' => 6
        ],
        [
            'weight_from' => 36,
            'weight_to' => 38,
            'row' => 7
        ],
        [
            'weight_from' => 39,
            'weight_to' => 41,
            'row' => 8
        ],
        [
            'weight_from' => 42,
            'weight_to' => 44,
            'row' => 9
        ],
        [
            'weight_from' => 45,
            'weight_to' => 46,
            'row' => 10
        ],
        [
            'weight_from' => 47,
            'weight_to' => 49,
            'row' => 11
        ],
        [
            'weight_from' => 50,
            'weight_to' => 52,
            'row' => 12
        ],
        [
            'weight_from' => 52,
            'weight_to' => 55,
            'row' => 13
        ],
        [
            'weight_from' => 56,
            'weight_to' => 58,
            'row' => 14
        ],
        [
            'weight_from' => 59,
            'weight_to' => 61,
            'row' => 15
        ],
        [
            'weight_from' => 62,
            'weight_to' => 64,
            'row' => 16
        ],
        [
            'weight_from' => 65,
            'weight_to' => 67,
            'row' => 17
        ],
        [
            'weight_from' => 68,
            'weight_to' => 70,
            'row' => 18
        ],
        [
            'weight_from' => 71,
            'weight_to' => 73,
            'row' => 19
        ],
        [
            'weight_from' => 74,
            'weight_to' => 76,
            'row' => 20
        ],
        [
            'weight_from' => 77,
            'weight_to' => 79,
            'row' => 21
        ],
        [
            'weight_from' => 80,
            'weight_to' => 82,
            'row' => 22
        ],
        [
            'weight_from' => 83,
            'weight_to' => 85,
            'row' => 23
        ],
        [
            'weight_from' => 86,
            'weight_to' => 88,
            'row' => 24
        ],
        [
            'weight_from' => 89,
            'weight_to' => 91,
            'row' => 25
        ],
        [
            'weight_from' => 92,
            'weight_to' => 94,
            'row' => 26
        ],
        [
            'weight_from' => 95,
            'weight_to' => 97,
            'row' => 27
        ],
        [
            'weight_from' => 98,
            'weight_to' => 100,
            'row' => 28
        ],
        [
            'weight_from' => 101,
            'weight_to' => 104,
            'row' => 29
        ],
        [
            'weight_from' => 105,
            'weight_to' => 107,
            'row' => 30
        ],
        [
            'weight_from' => 108,
            'weight_to' => 110,
            'row' => 31
        ],
        [
            'weight_from' => 111,
            'weight_to' => 115,
            'row' => 32
        ],
        [
            'weight_from' => 116,
            'weight_to' => 120,
            'row' => 33
        ],
        [
            'weight_from' => 121,
            'weight_to' => 125,
            'row' => 34
        ],
    ];
}