<?php
namespace App\Resources\Inshared\Methods;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\Inshared\InsharedAbstractRequest;

class CalculatePremium extends InsharedAbstractRequest
{
    protected $cacheDays = false;

    protected $inputToParamMapping = [
        ResourceInterface::BIRTHDATE => 'relatie.algemeen.geboortedatum',
        ResourceInterface::POSTAL_CODE => 'relatie.woonadres.postcode',

        ResourceInterface::YEARS_WITHOUT_DAMAGE => 'premiefactor.schadevrije_jaren_aantal',
        ResourceInterface::MILEAGE => 'premiefactor.kilometrage_auto',
        ResourceInterface::FAMILY_COMPOSITION => 'premiefactor.gezinssamenstelling_code',

        ResourceInterface::LICENSEPLATE => 'object.kenteken',
        ResourceInterface::TYPE_ID => 'object.uitvoering_id',
        ResourceInterface::CONSTRUCTION_DATE_MONTH => 'object.bouwmaand',
        ResourceInterface::CONSTRUCTION_DATE_YEAR => 'object.bouwjaar',
    ];

    protected $insharedNoPremiumMessageTarget = [
        'unmapped:acceptatie_product_melding'
    ];

    protected $noPremiumsAvailable = false;

    protected $coverageOwnRisk = [
        ResourceValue::CAR_COVERAGE_MINIMUM => 0,    // 19-09-2016: https://www.inshared.nl/autoverzekering/wa-autoverzekering
        ResourceValue::CAR_COVERAGE_LIMITED => 135,  // 19-09-2016: https://www.inshared.nl/autoverzekering/beperkt-casco-autoverzekering
        ResourceValue::CAR_COVERAGE_COMPLETE => 135, // 19-09-2016: https://www.inshared.nl/autoverzekering/allrisk-autoverzekering
    ];

    public function __construct(\SoapClient $soapClient = null)
    {
        parent::__construct('/verzekering-dekking/berekenen/module-premie-auto?wsdl', $soapClient);
    }

    public function getDefaultParams()
    {
        return [
            ResourceInterface::SESSION_ID => '',
            'relatie' => [
                'algemeen' => [
                    'geboortedatum' => null,
                ],
                'woonadres' => [
                    'postcode' => null,
                ],
            ],
            'afwijkende_bestuurder' => null,        // Optional
            /*
                if 'afwijkende_bestuurder' defined: [
                    'relatie_code' => null,
                    'geboortedatum' => null,
                    'postcode' => null,             // Optional
                ],
            */
            'premiefactor' => [
                'kilometrage_auto' => null,
                'schadevrije_jaren_aantal' => null,
                'ingangsdatum' => null,             // Optional
                'gezinssamenstelling_code' => self::FAMILY_COMPOSITION_TO_CODE[ResourceValue::SINGLE_NO_KIDS],
                'actiecode' => null,                // Optional
                'partner_id' => $this->partnerId,   // Optional
                'partner_onderdeel_code' => null,   // Optional
                'extern_referentie_id' => null,     // Optional
            ],
            'object' => [
                'kenteken' => null,                 // Optional
                'uitvoering_id' => self::CAR_TYPE_ID_UNKNOWN,
                'bouwjaar' => null,                 // Optional
                'bouwmaand' => null,                // Optional
            ],
            'modules' => null,                       // Optional
            /*
                If 'modules' has content:
                One 'item' => [], where every item entry: ['module_id' => null]
            */
        ];
    }

    public function setParams(Array $params)
    {
        $this->inputParams = $params;

        // Convert Coverage. This is only used in ->getResult()
        // (we want to return all modules)
        if (isset($this->inputParams[ResourceInterface::COVERAGE]))
            $this->inputParams[ResourceInterface::COVERAGE] = (array)$this->inputParams[ResourceInterface::COVERAGE];
        else
            $this->inputParams[ResourceInterface::COVERAGE] = [ResourceValue::CAR_COVERAGE_ALL];

        // if we only request one coverage, we don't want to throw an error but just return an empty array
        if (count($this->inputParams[ResourceInterface::COVERAGE]) == 1 && ($this->inputParams[ResourceInterface::COVERAGE][0] != 'all')) {
            $this->ignoreErrorMessages = true;
        }

        if (!empty($this->inputParams[ResourceInterface::FAMILY_COMPOSITION]))
            $this->inputParams[ResourceInterface::FAMILY_COMPOSITION] = self::FAMILY_COMPOSITION_TO_CODE[$this->inputParams[ResourceInterface::FAMILY_COMPOSITION]];

        // Do input value conversion
        $inputParams = $this->inputParams;

        if (isset($inputParams[ResourceInterface::CONSTRUCTION_DATE]))
        {
            $inputParams[ResourceInterface::CONSTRUCTION_DATE_YEAR] = date('Y', strtotime($inputParams[ResourceInterface::CONSTRUCTION_DATE]));
            $inputParams[ResourceInterface::CONSTRUCTION_DATE_MONTH] = date('m', strtotime($inputParams[ResourceInterface::CONSTRUCTION_DATE]));
        }

        if (isset($inputParams[ResourceInterface::BIRTHDATE]))
            $inputParams[ResourceInterface::BIRTHDATE] = $this->formatDate($inputParams[ResourceInterface::BIRTHDATE]);

        if (isset($inputParams[ResourceInterface::MILEAGE]))
            $inputParams[ResourceInterface::MILEAGE] = $this->mapMileageToGroup($inputParams[ResourceInterface::MILEAGE]);
        if (isset($inputParams[ResourceInterface::LICENSEPLATE]))
            $inputParams[ResourceInterface::LICENSEPLATE] = ResourceFilterHelper::filterAlfaNumber($inputParams[ResourceInterface::LICENSEPLATE]);

        // Map all 1-to-1 fields
        $params = $this->mapInputToParams($inputParams, $this->getDefaultParams());

        // Look up object.uitvoering_id if not present
        if (isset($inputParams[ResourceInterface::LICENSEPLATE]) && $params['object']['uitvoering_id'] == self::CAR_TYPE_ID_UNKNOWN)
        {
            // TODO: Insert 'uitvoering_id' lookup here (from providers A2Sp or VWE)
        }
        if (!isset($params['object']['kenteken']))
            unset($params['object']['kenteken']);

        return parent::setParams($params);
    }

    public function executeFunction()
    {
        parent::executeFunction();

        // Some input errors actually mean: We can't give you a premium.
        if (isset($this->errorMessages[0]) && in_array($this->errorMessages[0]['field'], $this->insharedNoPremiumMessageTarget))
        {
            $this->noPremiumsAvailable = true;
            $this->clearErrors();
        }
    }

    public function processInsharedMessages($messages)
    {
        if ($this->ignoreErrorMessages) {
            $this->noPremiumsAvailable = true;
        }
        parent::processInsharedMessages($messages);
    }

    public function getResult()
    {
        if ($this->noPremiumsAvailable)
            return [];

        $result = parent::getResult();

        // Default extras to null
        $additionalInsurancePremiums = [];
        foreach ($this->moduleIdToResourceValue as $resourceKey)
            $additionalInsurancePremiums[$resourceKey] = null;

        $coveragePremiums = [
            ResourceValue::CAR_COVERAGE_MINIMUM => null,
            ResourceValue::CAR_COVERAGE_LIMITED => null,
            ResourceValue::CAR_COVERAGE_COMPLETE => null,
        ];

        cw($result);

        // Base modules (WA, Beperkt Casco, Allrisk)
        // Some modules (WA), are mandatory and will always be processed.
        $premieNode = 'premie';

        foreach ($this->getItemAsArray($result['product']['basis_modules']) as $module)
        {
            if ($module['acceptatie']['geaccepteerd_indicatie'] != self::TRUE_STRING)
                continue;

            foreach ($coveragePremiums as $coverage => $premium)
                if ($module['module_id'] == self::COVERAGE_TO_MODULE_ID[$coverage])
                    $coveragePremiums[$coverage] = $module[$premieNode]['premie_inclusief_assurantiebelasting'] / $this->inputParams[ResourceInterface::PAYMENT_PERIOD];
        }
        cw($coveragePremiums);

        // Additional modules (car related)
        foreach ($this->getItemAsArray($result['product']['aanvullende_modules']) as $module)
        {
            if ($module['acceptatie']['geaccepteerd_indicatie'] != self::TRUE_STRING)
                continue;

            if (isset($this->moduleIdToResourceValue[$module['module_id']]))
            {
                $additionalInsurancePremiums[$this->moduleIdToResourceValue[$module['module_id']]] = $module[$premieNode]['premie_inclusief_assurantiebelasting'];
            }
        }

        // Cross sell modules (not necessarily car related)
        foreach ($this->getItemAsArray($result['product']['xsell']) as $product)
        {
            foreach ($this->getItemAsArray($product['modules']) as $module)
            {
                if (isset($this->moduleIdToResourceValue[$module['module_id']]))
                {
                    $additionalInsurancePremiums[$this->moduleIdToResourceValue[$module['module_id']]] = $module[$premieNode]['premie_inclusief_assurantiebelasting'];
                }
            }
        }

        // Convert into 0-3 'products' for each coverage & apply input coverage filter
        $products = [];
        cw('coverage premims');
        cw($coveragePremiums);
        foreach ($coveragePremiums as $coverage => $premium)
        {
            if ($premium === null)
                continue;
            if (!in_array($coverage, $this->inputParams[ResourceInterface::COVERAGE])
                && !in_array(ResourceValue::CAR_COVERAGE_ALL, $this->inputParams[ResourceInterface::COVERAGE]))
                continue;

            if ($coverage != ResourceValue::CAR_COVERAGE_MINIMUM)
                $premium += $coveragePremiums[ResourceValue::CAR_COVERAGE_MINIMUM];

            $products[] = [
                ResourceInterface::COVERAGE => $coverage,
                ResourceInterface::PRICE_DEFAULT => $premium,
                ResourceInterface::PRICE_INITIAL => $result['poliskosten'],
                ResourceInterface::OWN_RISK => $this->coverageOwnRisk[$coverage],
            ] + $additionalInsurancePremiums;
        }

        return $products;
    }
}