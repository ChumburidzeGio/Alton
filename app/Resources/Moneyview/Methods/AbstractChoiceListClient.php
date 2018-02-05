<?php
/**
 * User: Roeland Werring
 * Date: 13/04/15
 * Time: 15:29
 *
 */

namespace App\Resources\Moneyview\Methods;

use Config;


class AbstractChoiceListClient extends MoneyviewAbstractSoapRequest
{


    protected $arguments = [
        'list' => [
            'rules'     => 'required',
            'example'  => '',
        ]
    ];

    protected $moneyviewModuleName = '';

    public $skipDefaultFields = true;


    public function __construct($process = self::TASK_PROCESS_TWO)
    {
        parent::__construct( '', self::TASK_LOOKUP );
        $this->choiceLists = Config::get( 'resource_moneyview.choicelist' );
        $this->arguments['list']['example'] = array_keys( $this->choiceLists );
        $this->defaultParams = [
            self::TASK_KEY          => $process,
            self::GLOBAL_KEY        => $this->moneyviewModuleName,
            self::BEREKENING_MY_KEY => Config::get( 'resource_moneyview.settings.code' ),
        ];
    }

    public function setParams( Array $params )
    {
        $method = $this->choiceLists[$params['list']];
        parent::setParams([self::FIELD_KEY => $method]);
    }

}
