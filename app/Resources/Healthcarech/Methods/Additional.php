<?php
namespace App\Resources\Healthcarech\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Healthcarech\AbstractKnipRequest;

class Additional extends AbstractKnipRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
        ResourceInterface::SELECTED_COVERAGES => 'formatSelectedCoverages',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::KEY                  => 'privateKey',
        ResourceInterface::SELECTED_COVERAGES   => 'selectedCoverages',
    ];
    protected $externalToResultMapping = [];
    protected $resultTransformations = [];

    protected $availableCoverages = [
        ResourceInterface::LEGAL_PROTECTION,
        ResourceInterface::FATALITY,
        ResourceInterface::LONG_TERM_CARE,
        ResourceInterface::HOSPITAL_TREATMENT,
        ResourceInterface::DENTAL_CARE,
        ResourceInterface::OUTPATIENT_TREATMENT,
    ];

    public function __construct()
    {
        parent::__construct('komparu/account/{account_id}/add/additionalHealth', self::METHOD_POST);
    }

    public function formatSelectedCoverages($value)
    {
        if (empty($value))
            return [];
        if (is_array($value))
            return $value;

        $coverages = explode(',', (string)$value);

        $returnValue = [];
        foreach ($coverages as $coverage)
        {
            if (!in_array($coverage, $this->availableCoverages))
            {
                $this->setErrorString('Unknown coverage `' . $coverage . '` specified.');
                return [];
            }

            $returnValue[] = $coverage;
        }

        return $returnValue;
    }
}