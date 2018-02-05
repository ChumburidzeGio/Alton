<?php
/**
 * User: Roeland Werring
 * Date: 13/03/15
 * Time: 13:01
 *
 */

namespace App\Resources\Easyswitch;

use App\Resources\AbstractServiceRequest;

class EasyswitchServiceRequest extends AbstractServiceRequest
{
    protected $serviceProvider = 'energy';


    protected $filterKeyMapping = [
        //banking
        self::BANK_ACCOUNT_PAYMENT_TYPE        => 'rekening_betalingsmethode',
        self::BANK_ACCOUNT_ACCOUNT_HOLDER_NAME => 'rekening_tenaamstelling',
        self::BANK_ACCOUNT_BBAN                => 'rekening_bban',
        self::BANK_ACCOUNT_IBAN                => 'rekening_iban',
        self::BANK_ACCOUNT_BIC                 => 'rekening_bic',
        self::BANK_ACCOUNT_NAME                => 'rekening_banknaam',
        self::INITIALS                         => 'persoon_initialen',
        self::INSERTION                        => 'persoon_tussenvoegsel',
        self::LAST_NAME                        => 'persoon_achternaam',
        self::GENDER                           => 'persoon_geslacht',
        self::EMAIL                            => 'persoon_email',
        self::BIRTHDATE                        => 'persoon_geboortedatum',
        self::PHONE                            => 'persoon_telefoon',
        self::IP                               => 'persoon_ip',
        self::REHOUSING                        => 'verhuizing',
        self::USE_HOUSE_FOR_WORK               => 'gebruik_woon_werk',
        self::ASAP                             => 'zsm',
        self::START_DATE                       => 'startdatum',
        self::AGREE                            => 'akkoord',
        self::COMPANY_REGISTRATION_NUMBER      => 'bedrijf_kvk',
        self::COMPANY_NAME                     => 'bedrijf_naam',
        self::COMPANY_CONTACT_INITIALS         => 'bedrijf_contact_initialen',
        self::COMPANY_CONTACT_INSERTION        => 'bedrijf_contact_tussenvoegsel',
        self::COMPANY_CONTACT_LASTNAME         => 'bedrijf_contact_achternaam',
        //locations
        self::POSTAL_CODE                      => 'adres_postcode',
        self::HOUSE_NUMBER                     => 'adres_huisnummer',
        self::STREET                           => 'adres_straat',
        self::CITY                             => 'adres_plaats',
        self::SUFFIX                           => 'adres_toevoeging',
        self::HOUSE_NUMBER_SUFFIX              => 'adres_toevoeging',
        self::CURRENT_PROVIDER                 => 'leverancier_huidig',
        self::CONTRACT_DURATION_MONHTS         => 'looptijd',
        self::TARIFF_TYPE                      => 'tarief',
        self::ELECTRICITY_TYPE                 => 'typestroom',
        self::ELECTRICITY_USAGE_HIGH           => 'verbruik_stroom_1',
        self::ELECTRICITY_USAGE_LOW            => 'verbruik_stroom_2',
        self::GAS_USAGE                        => 'verbruik_gas',
        self::BUSINESS                         => 'klant_type',
        self::CONNECTIONS                      => 'connections',
        self::SUFFIXES                         => 'suffixes',
        self::PRODUCT_ELECTRICITY_ID           => 'product_stroom_id',
        self::PRODUCT_GAS_ID                   => 'product_gas_id',
        self::PRODUCT_COMBI_ID                 => 'product_combi_id',
        self::PRODUCT_TYPE                     => 'product_type',
        self::POSTAL_ADDRESS_OTHER             => 'post_afwijkend',
        self::POSTAL_ADDRESS_POSTAL_CODE       => 'post_postcode',
        self::POSTAL_ADDRESS_HOUSE_NUMBER      => 'post_huisnummer',
        self::POSTAL_ADDRESS_SUFFIX            => 'post_toevoeging',
        self::POSTAL_ADDRESS_STREET            => 'post_straat',
        self::POSTAL_ADDRESS_CITY              => 'post_plaats',


    ];

    protected $fieldMapping = [
        'id'     => self::RESOURCE_ID,
        'alias'  => self::TITLE,
        'naam'   => self::NAME,
        'street' => self::STREET,
        'city'   => self::CITY,
    ];

    protected $filterMapping = [
        self::PRICE_SUPPLY_TOTAL => 'filterRoundToCent',
        self::PRICE_TOTAL_VAT    => 'filterRoundToCent',
        self::PRICE_SAVINGS      => 'filterRoundToCent',
    ];


}
