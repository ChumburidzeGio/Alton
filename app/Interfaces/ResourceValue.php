<?php

namespace App\Interfaces;


interface ResourceValue
{
    // Gender
    const MALE = 'male';
    const FEMALE = 'female';

    // Personal relationships
    const CHILD = 'child';
    const PARTNER = 'partner';

    // Car insurance coverage
    const CAR_COVERAGE_MINIMUM = 'wa';  // 'Wettelijk Aansprakelijkheids'
    const CAR_COVERAGE_LIMITED = 'bc';  // 'Beperkt Casco'
    const CAR_COVERAGE_COMPLETE = 'vc'; // 'Volledig Casco' / 'Allrisk'
    const CAR_COVERAGE_ALL = 'all'; // all three of the above

    // Legal expenses coverage (or others)
    // TODO: translate
    const SINGLE_NO_KIDS = 'alleenstaande zonder kinderen';
    const SINGLE_WITH_KIDS = 'alleenstaande met kinderen';
    const FAMILY_NO_KIDS = 'gezin zonder kinderen';
    const FAMILY_WITH_KIDS = 'gezin met kinderen';

    // Payment status (high level & generic)
    const PAYMENT_IN_PROGRESS = 'payment_in_progress';
    const PAYMENT_SUCCESS = 'payment_success';
    const PAYMENT_FAILED = 'payment_failed';
    const PAYMENT_STATUS_UNKNOWN = 'payment_status_unknown';
    const PAYMENT_DEFERRED = 'payment_deferred';

    // Own risk types
    const OWN_RISK_GENERIC = 'own_risk_generic';
    const OWN_RISK_THEFT = 'own_risk_theft';
    const OWN_RISK_FREE_BODY_SHOP = 'own_risk_free_body_shop';

    // Car deprecation
    const DEPRECIATION_CURRENT_NEW_VALUE = 'depreciation_current_new_value';
    const DEPRECIATION_PURCHASED_VALUE = 'depreciation_purchased_value';
    const DEPRECIATION_STANDARD = 'depreciation_standard';

    const REGULAR_DRIVER_MYSELF = 'Ik';
    const REGULAR_DRIVER_PARTNER = 'Partner';
    const REGULAR_DRIVER_CHILD = 'Kind';

    //insurance types
    const BASE = 'base';
    const EXTENDED = 'extended';

    //QUESTIONS
    const YES = 'yes';
    const NO = 'no';
    const NOT_YET = 'not_yet';
    const YES_PART_BUSINESS = 'yes_part_business';
    const SOMETIMES = 'sometimes';
    const HOUSE_RENTED = 'house_rented';
    const HOUSE_EMPTY = 'house_empty';
    const HOUSE_BUSINESS = 'house_business';
    const RENTAL_VARIABLE = 'rental_variable';
    const RENTAL_ONE = 'rental_one';
    const RENTAL_FAMILY = 'rental_family';
    const RENTAL_OTHER  = 'rental_other';

}
