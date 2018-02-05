<?php
namespace App\Resources\Inshared\Methods;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\Inshared\InsharedAbstractRequest;

class CreateContract extends InsharedAbstractRequest
{
    protected $cacheDays = false;

    protected $inputToParamMapping = [
        ResourceInterface::GENDER => 'relatie.algemeen.geslacht_code',
        ResourceInterface::INITIALS => 'relatie.algemeen.voorletters',
        ResourceInterface::INSERTION => 'relatie.algemeen.tussenvoegsels',
        ResourceInterface::LAST_NAME => 'relatie.algemeen.achternaam',
        ResourceInterface::BIRTHDATE => 'relatie.algemeen.geboortedatum',
        ResourceInterface::FAMILY_COMPOSITION => 'premiefactor.gezinssamenstelling_code',
        ResourceInterface::POSTAL_CODE => 'relatie.woonadres.postcode',
        ResourceInterface::HOUSE_NUMBER => 'relatie.woonadres.huisnummer',
        ResourceInterface::HOUSE_NUMBER_SUFFIX => 'relatie.woonadres.huisnummer_toevoeging',
        ResourceInterface::PHONE => 'relatie.vast_of_mobiel_telefoonnummer.telefoonnummer',
        ResourceInterface::PHONE_MOBILE => 'relatie.mobiel_telefoonnummer.telefoonnummer',
        ResourceInterface::PHONE_LANDLINE => 'relatie.vast_telefoonnummer.telefoonnummer',
        ResourceInterface::EMAIL => 'relatie.primair_emailadres.emailadres',
        ResourceInterface::RECOVERY_EMAIL => 'relatie.herstel_emailadres.emailadres',

        ResourceInterface::BANK_ACCOUNT_NAME => 'relatie.bank_machtiging.tenaamstelling',
        ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT => 'relatie.bank_machtiging.automatische_incasso_akkoord_indicatie',

        ResourceInterface::CAR_OWNER_RELATION => 'afwijkende_bestuurder.relatie_code',
        ResourceInterface::CAR_OWNER_GENDER => 'afwijkende_bestuurder.geslacht_code',
        ResourceInterface::CAR_OWNER_INITIALS => 'afwijkende_bestuurder.voorletters',
        ResourceInterface::CAR_OWNER_INSERTION => 'afwijkende_bestuurder.tussenvoegsels',
        ResourceInterface::CAR_OWNER_LAST_NAME => 'afwijkende_bestuurder.achternaam',
        ResourceInterface::CAR_OWNER_EMAIL => 'afwijkende_bestuurder.emailadres',
        ResourceInterface::CAR_OWNER_BIRTHDATE => 'afwijkende_bestuurder.geboortedatum',
        ResourceInterface::CAR_OWNER_POSTAL_CODE => 'afwijkende_bestuurder.postcode',
        ResourceInterface::CAR_OWNER_HOUSE_NUMBER => 'afwijkende_bestuurder.huisnummer',
        ResourceInterface::CAR_OWNER_HOUSE_NUMBER_SUFFIX => 'afwijkende_bestuurder.huisnummer_toevoeging',

        ResourceInterface::YEARS_WITHOUT_DAMAGE => 'premiefactor.schadevrije_jaren_aantal',
        ResourceInterface::CAR_OWNER_YEARS_WITHOUT_DAMAGE => 'premiefactor.schadevrije_jaren_aantal',
        ResourceInterface::DRIVERS_LICENSE_AGE => 'premiefactor.jaren_rijbewijs_aantal',
        ResourceInterface::MILEAGE => 'premiefactor.kilometrage_auto',

        ResourceInterface::LICENSEPLATE => 'object.kenteken',
        ResourceInterface::TYPE_ID => 'object.uitvoering_id',
        ResourceInterface::CAR_REPORTING_CODE => 'object.meldcode',
        ResourceInterface::START_DATE => 'premiefactor.ingangsdatum',

        ResourceInterface::CAR_CRIMINAL_PAST => 'relatie.indicaties.strafrechtelijk_verleden_indicatie',
        ResourceInterface::CAR_INSURANCE_REFUSED => 'relatie.indicaties.verzekering_geweigerd_indicatie',
        ResourceInterface::AGREE_MARKETING_OPT_IN => 'relatie.indicaties.opt_out_indicatie',
        ResourceInterface::AGREE_POLICY_CONDITIONS => 'relatie.indicaties.voorwaarde_akkoord_indicatie',
        ResourceInterface::AGREE_DIGITAL_DISPATCH => 'relatie.indicaties.electronische_polis_akkoord_indicatie',

        ResourceInterface::USE_SWITCHING_SERVICE => 'indicaties.overstapservice_indicatie',
    ];

    protected $booleanInputFields = [
        ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT,
        ResourceInterface::CAR_CRIMINAL_PAST,
        ResourceInterface::CAR_INSURANCE_REFUSED,
        ResourceInterface::AGREE_MARKETING_OPT_IN,
        ResourceInterface::AGREE_POLICY_CONDITIONS,
        ResourceInterface::AGREE_DIGITAL_DISPATCH,
        ResourceInterface::USE_SWITCHING_SERVICE,
    ];

    // For mapping error messages information
    protected $mapErrorSourceToInputField = [
        'geboortedatum'             => ResourceInterface::BIRTHDATE,
        'postcode'                  => ResourceInterface::POSTAL_CODE,
        'kilometrage_auto'          => ResourceInterface::MILEAGE,
        'schadevrije_jaren_aantal'  => ResourceInterface::YEARS_WITHOUT_DAMAGE,
        'kenteken'                  => ResourceInterface::LICENSEPLATE,
        'opt_out_indicatie'         => ResourceInterface::AGREE_MARKETING_OPT_IN,
    ];
    protected $mapErrorCodeToInputField = [
        '14076-5' => ResourceInterface::CAR_CRIMINAL_PAST,
        '14076-6' => ResourceInterface::CAR_INSURANCE_REFUSED,
        '11303-1' => ResourceInterface::CAR_REPORTING_CODE,
        '11303-2' => ResourceInterface::CAR_REPORTING_CODE,
        '11303-3' => ResourceInterface::CAR_REPORTING_CODE,
        '11258-12' => ResourceInterface::START_DATE,
        '11258-13' => ResourceInterface::START_DATE,
        '11258-14' => ResourceInterface::START_DATE,
    ];

    public function __construct(\SoapClient $soapClient = null)
    {
        parent::__construct('/verzekering-product/vastleggen/genererenaccount-product-auto?wsdl', $soapClient);
    }

    public function getDefaultParams()
    {
        return [
            ResourceInterface::SESSION_ID => '',
            'relatie' => [
                'algemeen' => [
                    'geslacht_code' => null,
                    'voorletters' => null,
                    'tussenvoegsels' => null,           // Optional
                    'achternaam' => null,
                    'geboortedatum' => null,
                ],
                'mobiel_telefoonnummer' => null,            // Optional, contains ['telefoonnummer' => null]
                'vast_telefoonnummer' => null,              // Optional, contains ['telefoonnummer' => null]
                'vast_of_mobiel_telefoonnummer' => null,    // Optional, contains ['telefoonnummer' => null]
                'primair_emailadres' => [
                    'emailadres' => null,
                ],
                'herstel_emailadres' => null,               // Optional, contains ['email' => null]
                'bank_machtiging' => [
                    'tenaamstelling' => null,
                    'iban_land_code' => null,
                    'iban_controle_getal' => null,
                    'iban_bank_code' => null,
                    'iban_rekeningnummer' => null,
                    'automatische_incasso_akkoord_indicatie' => null,
                ],
                'woonadres' => [
                    'huisnummer_toevoeging' => null,    // Optional
                    'postcode' => null,
                    'huisnummer' => null,
                ],
                'indicaties' => [
                    'opt_out_indicatie' => self::TRUE_STRING,     // Should actually ask this
                    'strafrechtelijk_verleden_indicatie' => null,
                    'verzekering_geweigerd_indicatie' => null,
                    'voorwaarde_akkoord_indicatie' => null,
                    'electronische_polis_akkoord_indicatie' => null,
                ],
            ],
            'afwijkende_bestuurder' => [                // Optional
                'relatie_code' => null,
                'geslacht_code' => null,
                'voorletters' => null,
                'tussenvoegsels' => null,               // Optional
                'achternaam' => null,
                'postcode' => null,
                'huisnummer' => null,
                'huisnummer_toevoeging' => null,        // Optional
                'land_id' => null,                      // Optional
                'geboortedatum' => null,
                'emailadres' => null,                   // Optional
            ],
            'productwaardeonderdelen' => [              // Optional
                'item' => [], //Every item: ['product_waarde_onderdeel_id' => null]
            ],
            'premiefactor' => [
                'kilometrage_auto' => null,
                'jaren_rijbewijs_aantal' => null,       // Optional
                'schadevrije_jaren_aantal' => null,
                'gezinssamenstelling_code' => self::FAMILY_COMPOSITION_TO_CODE[ResourceValue::FAMILY_WITH_KIDS],
                'ingangsdatum' => null,                 // Optional
                'actiecode' => null,                    // Optional
                'partner_id' => $this->partnerId,       // Optional
                'partner_onderdeel_code' => null,       // Optional
                'extern_referentie_id' => null,         // Optional
            ],
            'modules' => [
                'item' => [], //Every item: ['module_id' => null]
            ],
            'object' => [
                'kenteken' => null,
                'uitvoering_id' => self::CAR_TYPE_ID_UNKNOWN,
                'meldcode' => null,
            ],
            'indicaties' => [
                'overstapservice_indicatie' => self::FALSE_STRING,     // Optional
            ],
        ];
    }

    public function setParams(Array $params)
    {
        $this->inputParams = $params;

        // Do some input value transformation
        $inputParams = $this->inputParams;
        if (isset($inputParams[ResourceInterface::GENDER]))
            $inputParams[ResourceInterface::GENDER] = self::GENDER_TO_CODE[$inputParams[ResourceInterface::GENDER]];
        if (isset($inputParams[ResourceInterface::CAR_OWNER_GENDER]))
            $inputParams[ResourceInterface::CAR_OWNER_GENDER] = self::GENDER_TO_CODE[$inputParams[ResourceInterface::CAR_OWNER_GENDER]];

        // Date formatting
        if (isset($inputParams[ResourceInterface::BIRTHDATE]))
            $inputParams[ResourceInterface::BIRTHDATE] = $this->formatDate($inputParams[ResourceInterface::BIRTHDATE]);
        if (isset($inputParams[ResourceInterface::CAR_OWNER_BIRTHDATE]))
            $inputParams[ResourceInterface::CAR_OWNER_BIRTHDATE] = $this->formatDate($inputParams[ResourceInterface::CAR_OWNER_BIRTHDATE]);
        if (isset($inputParams[ResourceInterface::START_DATE]))
            $inputParams[ResourceInterface::START_DATE] = $this->formatDate($inputParams[ResourceInterface::START_DATE]);

        if (!empty($inputParams[ResourceInterface::CAR_OWNER_SAME_ADDRESS]))
        {
            if (!empty($inputParams[ResourceInterface::CAR_OWNER_POSTAL_CODE]))
                $inputParams[ResourceInterface::CAR_OWNER_POSTAL_CODE] = $inputParams[ResourceInterface::POSTAL_CODE];
            if (!empty($inputParams[ResourceInterface::CAR_OWNER_HOUSE_NUMBER]))
                $inputParams[ResourceInterface::CAR_OWNER_HOUSE_NUMBER] = $inputParams[ResourceInterface::HOUSE_NUMBER];
            if (!empty($inputParams[ResourceInterface::CAR_OWNER_HOUSE_NUMBER_SUFFIX]))
                $inputParams[ResourceInterface::CAR_OWNER_HOUSE_NUMBER_SUFFIX] = $inputParams[ResourceInterface::HOUSE_NUMBER_SUFFIX];
        }
        if (isset($inputParams[ResourceInterface::MILEAGE]))
            $inputParams[ResourceInterface::MILEAGE] = $this->mapMileageToGroup($inputParams[ResourceInterface::MILEAGE]);

        if (!empty($inputParams[ResourceInterface::CAR_OWNER_RELATION]))
            $inputParams[ResourceInterface::CAR_OWNER_RELATION] = self::RELATION_TO_CODE[$inputParams[ResourceInterface::CAR_OWNER_RELATION]];

        if (!empty($inputParams[ResourceInterface::FAMILY_COMPOSITION]))
            $inputParams[ResourceInterface::FAMILY_COMPOSITION] = self::FAMILY_COMPOSITION_TO_CODE[$inputParams[ResourceInterface::FAMILY_COMPOSITION]];

        // Normalize license plate to uppercase,
        if (isset($inputParams[ResourceInterface::LICENSEPLATE]))
            $inputParams[ResourceInterface::LICENSEPLATE] = ResourceFilterHelper::filterAlfaNumber($inputParams[ResourceInterface::LICENSEPLATE]);

        // All Booleans to 'J'/'N'
        foreach ($this->booleanInputFields as $key)
            if (isset($inputParams[$key]))
                $inputParams[$key] = ResourceFilterHelper::strToBool((string)$inputParams[$key]) ? self::TRUE_STRING : self::FALSE_STRING;

        // Map all 1-to-1 fields
        $params = $this->mapInputToParams($inputParams, $this->getDefaultParams());

        // Do all 1-to-many & complex mappings
        if (!empty($inputParams[ResourceInterface::BANK_ACCOUNT_IBAN]))
        {
            $iban = new \IBAN($inputParams[ResourceInterface::BANK_ACCOUNT_IBAN]);
            $params['relatie']['bank_machtiging']['iban_land_code'] = $iban->Country();
            $params['relatie']['bank_machtiging']['iban_controle_getal'] = $iban->Checksum();
            $params['relatie']['bank_machtiging']['iban_bank_code'] = $iban->Bank();
            $params['relatie']['bank_machtiging']['iban_rekeningnummer'] = $iban->Account();
        }
        if (isset($inputParams[ResourceInterface::LICENSEPLATE]) && $params['object']['uitvoering_id'] == self::CAR_TYPE_ID_UNKNOWN)
        {
            // TODO: Insert 'uitvoering_id' lookup here (from providers A2Sp or VWE)
        }
        if (!empty($inputParams[ResourceInterface::COVERAGE]))
        {
            // Always include 'WA', other coverages get added onto that
            $params['modules']['item'][] = ['module_id' => self::COVERAGE_TO_MODULE_ID[ResourceValue::CAR_COVERAGE_MINIMUM]];

            switch ($inputParams[ResourceInterface::COVERAGE])
            {
                case ResourceValue::CAR_COVERAGE_LIMITED:
                case ResourceValue::CAR_COVERAGE_COMPLETE:
                    $params['modules']['item'][] = ['module_id' => self::COVERAGE_TO_MODULE_ID[$inputParams[ResourceInterface::COVERAGE]]];
                    break;
            }
        }
        // Add additional insurance modules
        foreach ($this->moduleIdToResourceValue as $moduleId => $paramName)
        {
            if (!empty($inputParams[$paramName]))
            {
                $params['modules']['item'][] = ['module_id' => $moduleId];
            }
        }

        // Ditch all CAR_OWNER_ stuff if we are the owner
        if (!empty($inputParams[ResourceInterface::IS_CAR_OWNER]))
        {
            $params['afwijkende_bestuurder'] = null;
        }

        parent::setParams($params);
    }

    public function getResult()
    {
        return parent::getResult();
    }
}