<?php

namespace App\Resources\Adapters\LegalExpensesInsurance\Moneyview;

use App\Resources\Adapters\AdapterInterface;
use App\Resources\Adapters\Field;

class Products implements AdapterInterface
{
    /**
     * @return bool
     */
    public function collection()
    {
        return true;
    }

    /**
     * @return FieldInterface[]
     */
    public function inputs()
    {
        return [
            new Field('first_name_moneyview', 'string', 'First name', 'required', null, 'Give us your first name now!'),
            new Field('last_name_moneyview', 'string', 'Last name', 'required', null, 'Give us your last name now!'),
        ];
    }

    /**
     * @return FieldInterface[]
     */
    public function outputs()
    {
        return [
            new Field('first_name_moneyview', 'First name', 'string', true, 'Give us your first name now!'),
            new Field('last_name_moneyview', 'Last name', 'string', true, 'Give us your last name now!'),
        ];
    }

    /**
     * @param array $input
     * @return array
     */
    public function process(Array $input)
    {
        return [
            [
                'first_name_moneyview' => 'Foo',
                'last_name_moneyview' => 'Bar',
            ],
            [
                'first_name_moneyview' => 'Foo',
                'last_name_moneyview' => 'Bar',
            ],
        ];
    }
}