<?php

namespace App\Interfaces;


interface ResourceInterface
{
    const SEPARATOR = '.';

    //standard fields
    const ID = 'id';
    const _ID = '_id';
    const __ID = '__id';
    const RANGE = 'range';
    const VALUE = 'value';
    const CONTRACT = 'contract';
    const RESOURCE = 'resource';
    const DETAILS = 'details';
    const RESOURCE_KEY = 'resource_key';
    const RESOURCE_ID = 'resource.id';
    const RESOURCE__ID = 'resource_id';
    const RESOURCE_NAME = 'resource.name';
    const RESOURCE__NAME = 'resource_name';
    const CODE = 'code';
    const CONTRACT_ID = 'contract_id';
    const NAME = 'name';
    const POINT_OF_INTEREST = 'point_of_interest';
    const LABEL = 'label';
    const VALID = 'valid';
    const REQUIRED = 'required';
    const PLACEHOLDER = 'placeholder';
    const VALIDATION_MESSAGE = 'validation_message';
    const VALIDATION_KEY = 'validation_key';
    const ENABLED = 'enabled';
    const IMAGE = 'image';
    const LOGO = 'logo';
    const IMAGES = 'images';
    const DESCRIPTION = 'description';
    const DESCRIPTION_EN = 'description_en';
    const DESCRIPTION_FR = 'description_fr';
    const DESCRIPTION_DE = 'description_de';
    const USPS = 'usps';
    const PDF = 'pdf';
    const SPEC_NAME = 'spec_name';
    const COMPANY = 'company';
    const COMPANIES = 'companies';
    const COMP_NAME = 'company.name';
    const COMP_ID = 'company.id';
    const COMP_IMAGE = 'company.image';
    const COMP_TITLE = 'company.title';
    const COMP_DESCRIPTION = 'company.description';
    const PRODUCT = 'product';
    const PRODUCT_ID = 'product_id';
    const PRODUCT_IDS = 'product_ids';
    const PRODUCTS = 'products';
    const GROUP_ID = 'group_id';
    const GROUP_BY = 'group_by';
    const DISCOUNT_GROUP_ID = 'discount_group_id';
    const DISCOUNT_APPLY = 'discount_apply';
    const ICON = 'icon';
    const COLOR = 'color';
    const SLUG = 'slug';
    const ALIAS = 'alias';
    const UNIT = 'unit';
    const MAPPING = 'mapping';
    const LABEL_MAPPING = 'label_mapping';
    const DAISYCON_MEDIA_ID = 'daisycon_media_id';

    const PRODUCT_SPEC = 'product_spec';

    const RATING = 'rating';
    const REVIEW_COUNT = 'review_count';
    const SCORE = 'score';
    //list of ids
    const IDS = 'ids';

    const TOTAL_RATING = 'total_rating';
    const ROLE = 'role';
    const USERNAME = 'username';
    const PASSWORD = 'password';
    const MANAGING_USER = 'managing_user';

    //price
    const PRICE = 'price';

    const PRICE_ACTUAL = 'price_actual';
    const PRICE_YIELDED = 'price_yielded';
    const PRICE_INITIAL = 'price_initial';
    const PRICE_DEFAULT = 'price_default';
    const PRICE_SUPPLY = 'price_supply';
    const PRICE_SUPPLY_TOTAL = 'price_supply_total';
    const PRICE_TOTAL_VAT = 'price_total_vat';
    const PRICE_VAT = 'price_vat';
    const PRICE_SAVINGS = 'price_savings';
    const PRICE_DISCOUNT = 'price_discount';
    const PRICE_BEFORE_DISCOUNT = 'price_before_discount';
    const PRICE_FEE = 'fee';
    const PRICE_CORRECTION = 'price_correction';
    const PRICE_MULTIPLIER = 'price_multiplier';
    const PRICE_GLOBAL_MULTIPLIER = 'price_global_multiplier';
    const PRICE_AVERAGE = 'price_average';
    const PRICE_MONTHLY = 'price_monthly';
    const INSURED_AMOUNT = 'insured_amount';
    const DISCOUNT_SCRIPT = 'discount_script';
    const DISCOUNT_PERCENTAGE = 'discount_percentage';


    //text
    const DISCOUNT_TEXT = 'discount_text';
    const DISCOUNT_TEXT_SHORT = 'discount_text_short';
    const PRODUCT_SUMMARY = 'product_summary';
    const BREADCRUMBS = 'breadcrumbs';


    //CONTRACT
    const TIME = 'time';
    const TITLE = 'title';
    const HEADING = 'heading';
    const RENEWAL = 'renewal';
    const RENEWAL_TYPE = 'renewal_type';
    const BILLING_METHOD = 'billing_method';
    const CONTRACT_TYPE = 'contract_type';
    const CONTRACT_CONDITIONS = 'contract_conditions';
    const VARIABLE = 'variable';
    const FIXED = 'fixed';
    const APPLY = 'apply';
    const FORCE = 'force';
    const TAG = 'tag';
    const DELETED = 'deleted';

    //conditions
    const CONDITION_NAME = 'condition.name';
    const CONDITION_LABEL = 'condition.label';


    const OWN_RISK = 'own_risk';
    const OWN_RISK_STORM_DAMAGE = 'own_risk_storm_damage';
    const OWN_RISK_DISASTER = 'own_risk_disaster';
    const OWN_RISK_THEFT = 'own_risk_theft';
    const PRODUCT_OWN_RISK = 'product_own_risk';
    const FRANCHISE = 'franchise';
    const ACTIVE = 'active';
    const RESELLER_ACTIVE = 'reseller_active';
    const DISABLED = 'disabled';

    const REHOUSING = 'rehousing';
    const USE_HOUSE_FOR_WORK = 'use_house_for_work';
    const ASAP = 'asap';
    const AGREE = 'agree';
    const STATUS = 'status';
    const SKIP_PAYMENT = 'skip_payment';
    const START_DATE = 'start_date';

    //address
    const ADDRESS = 'address';
    const POSTAL_CODE = 'postal_code';
    const REGION = 'region';
    const STREET = 'street';
    const CITY = 'city';
    const STATE = 'state';
    const HOUSE_NUMBER = 'house_number';
    const SUFFIX = 'suffix';
    const SUFFIXES = 'suffixes';
    const HOUSE_NUMBER_SUFFIX = 'house_number_suffix';
    const CONNECTIONS = 'connections';
    const KANTON = 'kanton';

    const POSTAL_ADDRESS_OTHER = 'postal_address_other';
    const POSTAL_ADDRESS_POSTAL_CODE = 'postal_address_postal_code';
    const POSTAL_ADDRESS_HOUSE_NUMBER = 'postal_address_house_number';
    const POSTAL_ADDRESS_SUFFIX = 'postal_address_suffix';
    const POSTAL_ADDRESS_STREET = 'postal_address_street';
    const POSTAL_ADDRESS_CITY = 'postal_address_city';


    const BUSINESS = 'business'; // Boolean
    const BUSINESS_TAX = 'business_tax'; // Kan declareren? ja,nee,nvt
    const INCLUDE_VAT = 'include_vat'; // Kan declareren? ja,nee,nvt

    //personal
    const INITIALS = 'initials';
    const INSERTION = 'insertion';
    const LAST_NAME = 'last_name';
    const FIRST_NAME = 'first_name';
    const FULL_NAME = 'full_name';
    const GENDER = 'gender';
    const PHONE = 'phone';
    const PHONE_PREFIX = 'phone_prefix';
    const PHONE_LANDLINE = 'phone_landline';
    const PHONE_MOBILE = 'phone_mobile';
    const IP = 'ip';
    const EMAIL = 'email';
    const EMAIL1 = 'email1';
    const EMAIL2 = 'email2';
    const EMAIL_FROM = 'email_from';
    const MAIL = 'mail';
    const MAIL_EN = 'mail_en';
    const MAIL_DE = 'mail_de';
    const MAIL_FR = 'mail_fr';
    const RECOVERY_EMAIL = 'recovery_email';
    const IDENTIFICATION_TYPE = 'identification_type';
    const IDENTIFICATION_NUMBER = 'identification_number';
    const IDENTIFICATION_COUNTRY_CODE = 'identification_country_code';
    const EXPIRATION_DATE = 'expiration_date';
    const COUNTRY_CODE = 'country_code';
    const COUNTRY_NAME = 'country_name';

    const FAMILY_COMPOSITION = 'family_composition';
    const PERSON_SINGLE = 'person_single';

    //birthdates
    const BIRTHDATE = 'birthdate';
    const BIRTHDATE_PARTNER = 'birthdate_partner';
    const BIRTHDATE_CHILD_1 = 'birthdate_child_1';
    const BIRTHDATE_CHILD_2 = 'birthdate_child_2';
    const BIRTHDATE_CHILD_3 = 'birthdate_child_3';
    const BIRTHDATE_CHILD_4 = 'birthdate_child_4';
    const BIRTHDATE_CHILD_5 = 'birthdate_child_5';

    //policy dates
    const EFFECTIVE_DATE = 'effective_date';
    const END_DATE = 'end_date';


    const OPTIONLIST = 'list';
    const MONTHLY_NET_INCOME = 'monthly_net_income';
    const SLEEPHOBBYSTUDYWORK_ROOM_COUNT = 'sleephobbystudywork_room_count';
    const CONTENTS_ESTIMATE = 'contents_estimate';
    const CONTENTS_ESTIMATE_VALUE = 'contents_estimate_value';
    const CONTENTS_ESTIMATE_ROUNDED = 'contents_estimate_rounded';
    const HOUSE_ALARM = 'house_alarm';
    const HOUSE_ALARM_NOTIFY_EMERGENCY_ROOM = 'house_alarm_notify_emergency_room';
    const POLICE_MARK = 'police_mark';
    const CONTENTS = 'contents';
    const WARRANTY = 'warranty';
    const UNDERINSURANCE = 'underinsurance';
    const USE_CONTENTS_VALUE_MEASUREMENT = 'use_contents_value_measurement';

    // These two exist to have an 'input' variant of the minus CALCULCATION_ fields, to prevent input & output fields having the same names
    const CALCULATION_OWN_RISK = 'calculation_own_risk';
    const CALCULATION_FRANCHISE = 'calculation_franchise';
    const CALCULATION_OWN_RISK_LEGALEXPENSESINSURANCE = 'calculation_own_risk_legalexpensesinsurance';
    const CALCULATION_OWN_RISK_HOMEINSURANCE = 'calculation_own_risk_homeinsurance';
    const CALCULATION_OWN_RISK_CONTENTSINSURANCE = 'calculation_own_risk_contentsinsurance';
    const CALCULATION_OWN_RISK_LIABILITYINSURANCE = 'calculation_own_risk_liabilityinsurance';

    // Basic premium method only
    const INSURE_BASIC_COVERAGE = 'insure_basic_coverage';
    const INSURE_CAPITAL = 'insure_capital';
    const INSURE_TAX_LAW = 'insure_tax_law';
    const INSURE_DAMAGE_REDRESS = 'insure_damage_redress';
    const INSURE_YACHT = 'insure_yacht';
    // Basic and extented premium method
    const INSURE_INCOME = 'insure_income';
    const INSURE_CONSUMER = 'insure_consumer';
    const INSURE_TRAFFIC = 'insure_traffic';
    const INSURE_HOUSING = 'insure_housing';
    // Extended premium only
    const INSURE_DIVORCE_MEDIATION = 'insure_divorce_mediation';
    const INSURE_FAMILY_LAW = 'insure_family_law';
    const INSURE_WORK = 'insure_work';
    const INSURE_MEDICAL = 'insure_medical';
    const INSURE_NEIGHBOUR_DISPUTES = 'insure_neighbour_disputes';
    const INSURE_TAXES_AND_CAPITAL = 'insure_taxes_and_capital';

    // Prices of the insurances
    const PRICE_INSURE_INCOME = 'price_insure_income';
    const PRICE_INSURE_CONSUMER = 'price_insure_consumer';
    const PRICE_INSURE_TRAFFIC = 'price_insure_traffic';
    const PRICE_INSURE_HOUSING = 'price_insure_housing';
    const PRICE_INSURE_DIVORCE_MEDIATION = 'price_insure_divorce_mediation';
    const PRICE_INSURE_FAMILY_LAW = 'price_insure_family_law';
    const PRICE_INSURE_WORK = 'price_insure_work';
    const PRICE_INSURE_MEDICAL = 'price_insure_medical';
    const PRICE_INSURE_NEIGHBOUR_DISPUTES = 'price_insure_neighbour_disputes';
    const PRICE_INSURE_HOUSING_OWNED_HOUSE = 'price_insure_housing_owned_house';
    const PRICE_INSURE_TAXES_AND_CAPITAL = 'price_insure_taxes_and_capital';
    const PRICE_INSURE_HOUSING_FOR_RENT = 'price_insure_housing_for_rent';
    const PRICE_INSURE_HOUSING_RENTED_LIVINGUNITS = 'price_insure_housing_rented_livingunits';
    const PRICE_INSURE_HOUSING_RENTED_WORKUNITS = 'price_insure_housing_rented_workunits';
    const PRICE_INSURE_TRAFFIC_ROADVEHICLE_ACCIDENT = 'price_insure_traffic_roadvehicle_accident';
    const PRICE_INSURE_TRAFFIC_ROADVEHICLE_OTHER = 'price_insure_traffic_roadvehicle_other';
    const PRICE_INSURE_TRAFFIC_WATERVEHICLE_ACCIDENT = 'price_insure_traffic_watervehicle_accident';
    const PRICE_INSURE_TRAFFIC_WATERVEHICLE_OTHER = 'price_insure_traffic_watervehicle_other';
    const PRICE_INSURE_TRAFFIC_TRAFFIC_WATERVEHICLE_OTHER = 'price_insure_traffic_traffic_watervehicle_other';
    const PRICE_INSURE_HOUSING_VACATIONHOME_NL = 'price_insure_housing_vacationhome_nl';
    const PRICE_INSURE_HOUSING_VACATIONHOME_OTHER = 'price_insure_housing_vacationhome_other';


    //complicated fields
    const INSURE_CONSUMER_DAMAGE_REDRESS_HOUSING = 'insure_consumer_damage_redress_housing';
    const INSURE_CONSUMER_DAMAGE_REDRESS_HOUSING_INCOME_TRAFFIC_YACHT = 'insure_consumer_damage_redress_housing_income_traffic_yacht';
    const INSURE_CONSUMER_DAMAGE_REDRESS_HOUSING_TRAFFIC_YACHT = 'insure_consumer_damage_redress_housing_traffic_yacht';
    const INSURE_CONSUMER_DAMAGE_REDRESS_INCOME_YACHT = 'insure_consumer_damage_redress_income_yacht';
    const INSURE_CONSUMER_DAMAGE_REDRESS_TRAFFIC_YACHT = 'insure_consumer_damage_redress_traffic_yacht';
    const INSURE_CONSUMER_DAMAGE_REDRESS_YACHT = 'insure_consumer_damage_redress_yacht';
    const INSURE_CONSUMER_HOUSING = 'insure_consumer_housing';
    const INSURE_CONSUMER_HOUSING_YACHT = 'insure_consumer_housing_yacht';
    const INSURE_DAMAGE_REDRESS_TRAFFIC_YACHT = 'insure_damage_redress_traffic_yacht';
    const INSURE_TRAFFIC_YACHT = 'insure_traffic_yacht';


    const REMARK = 'remark';
    const REMARK_INCOME = 'remark_income';
    const REMARK_CAPITAL = 'remark_capital';
    const REMARK_CONSUMER = 'remark_consumer';
    const REMARK_TRAFFIC = 'remark_traffic';
    const REMARK_TAX_LAW = 'remark_tax_law';
    const REMARK_HOUSING = 'remark_housing';
    const REMARK_DAMAGE_REDRESS = 'remark_damage_redress';
    const REMARK_YACHT = 'remark_yacht';


    const TRAFFIC_COVERAGE = 'traffic_coverage';
    const BOAT_COVERAGE = 'boat_coverage';
    const HOUSE_OWNER = 'house_owner';

    const IS_HOUSE_FOR_RENT = 'is_house_for_rent';
    const HOUSE_RENTED_LIVINGUNITS = 'house_rented_livingunits';
    const HOUSE_RENTED_WORKUNITS = 'house_rented_workunits';
    const VACATIONHOME_LOCATION = 'vacationhome_location';
    const INSURE_TRAFFIC_ROADVEHICLE_ACCIDENT = 'insure_traffic_roadvehicle_accident';
    const INSURE_TRAFFIC_ROADVEHICLE_OTHER = 'insure_traffic_roadvehicle_other';
    const INSURE_TRAFFIC_WATERVEHICLE_ACCIDENT = 'insure_traffic_watervehicle_accident';
    const INSURE_TRAFFIC_WATERVEHICLE_OTHER = 'insure_traffic_watervehicle_other';
    const PRICE_WATERVEHICLE_CATALOGUS = 'price_watervehicle_catalogus';
    const RETURN_ALL_PRODUCTS = 'return_all_products';

    const CALCULATION_INSURED_AMOUNT = 'calculation_insured_amount';
    const CALCULATION_INSURED_AMOUNT_LEGALEXPENSESINSURANCE = 'calculation_insured_amount_legalexpensesinsurance';
    const CALCULATION_INSURED_AMOUNT_LIABILITYINSURANCE = 'calculation_insured_amount_liabilityinsurance';

    //damage insurances
    const CONSTRUCTION_DATE = 'construction_date';
    const CONSTRUCTION_DATE_MONTH = 'construction_date_month';
    const CONSTRUCTION_DATE_YEAR = 'construction_date_year';
    const COVERAGE = 'coverage';
    const COVERAGE_TYPE = 'coverage_type';
    const COVERAGE_ID = 'coverage_id';
    const COVERAGE_LABEL = 'coverage_label';
    const YEARS_WITHOUT_DAMAGE = 'years_without_damage';
    const YEARS_INSURED = 'years_insured';
    const DAMAGES = 'damages';
    const TYPE = 'type';
    const TYPE_OF_CONSTRUCTION = 'type_of_construction';
    const OUTSIDE = 'outside';
    const ROOMS = 'rooms';
    const OWNER = 'owner';
    const SECURITY = 'security';
    const SURFACE = 'surface';
    const CAPACITY = 'capacity';


    const SECURITY_ERROR = 'security_error';
    const SECURITY_MINIMAL = 'security_minimal';

    //car
    const COACHWORK_TYPE_ID = 'coachwork_type_id';
    const WEIGHT = 'weight';
    const FUEL_TYPE_ID = 'fuel_type_id';
    const FUEL_TYPE_NAME = 'fuel_type_name';
    const CATALOG_FUEL_TYPE_ID = 'catalog_fuel_type_id';
    const CATALOG_FUEL_TYPE_NAME = 'catalog_fuel_type_name';
    const PRIMARY_FUEL_TYPE_ID = 'primary_fuel_type_id';
    const PRIMARY_FUEL_TYPE_NAME = 'primary_fuel_type_name';
    const SECONDARY_FUEL_TYPE_ID = 'secondary_fuel_type_id';
    const SECONDARY_FUEL_TYPE_NAME = 'secondary_fuel_type_name';
    const BRAND_ID = 'brand_id';
    const BRAND_NAME = 'brand_name';
    const MODEL_ID = 'model_id';
    const MODEL_NAME = 'model_name';
    const TYPE_NAME = 'type_name';
    const TYPE_ID = 'type_id';
    const TYPES = 'types';
    const RATINGS = 'ratings';
    const AVERAGE_RATING = 'average_rating';

    const SECURITY_CLASS = 'security_class';
    const AMOUNT_OF_DOORS = 'amount_of_doors';
    const POWER = 'power';
    const DAILY_VALUE = 'daily_value';
    const REPLACEMENT_VALUE = 'replacement_value';
    const OLD_POLICY_NUMBER = 'old_policy_number';
    const LICENSEPLATE = 'licenseplate';
    const LICENSEPLATE2 = 'licenseplate2';
    const LICENSEPLATE3 = 'licenseplate3';
    const LICENSEPLATE4 = 'licenseplate4';
    const LICENSEPLATE5 = 'licenseplate5';
    const LICENSEPLATE6 = 'licenseplate6';
    const USED_MILEAGE = 'used_mileage';
    const MILEAGE = 'mileage';
    const DRIVERS_LICENSE_AGE = 'drivers_license_age';
    const LICENSEPLATE_WEIGHT = 'licenseplate_weight';
    const LICENSEPLATE_FUEL_ID = 'licenseplate_fuel_id';
    const LICENSEPLATE_COLOR = 'licenseplate_color';
    const TURBO = 'turbo';
    const TRANSMISSION_ID = 'transmission_id';
    const CYLINDERS = 'cylinders';
    const CYLINDER_VOLUME = 'cylinder_volume';
    const AMOUNT_OF_SEATS = 'amount_of_seats';
    const SECURITY_CLASS_ID = 'security_class_id';
    const CO2_EMISSION = 'co2_emission';
    const ENERGY_LABEL = 'energy_label';
    const TOP_SPEED = 'top_speed';
    const ACCELERATION = 'acceleration';
    const BPM_VALUE = 'bpm_value';
    const NET_VALUE = 'net_value';
    const NEW_OWNER_DATE = 'new_owner_date';
    const SECOND_COLOR = 'second_color';
    const IMPORTED_CAR = 'imported_car';
    const AGREE_INSURANCE = 'agree_insurance';
    const AGREE_TRUTH = 'agree_truth';
    const AGREE_POLICY_CONDITIONS = 'agree_policy_conditions';
    const AGREE_DIGITAL_DISPATCH = 'agree_digital_dispatch';
    const AGREE_MARKETING_OPT_IN = 'agree_marketing_opt_in';
    const AGREE_REFLECTION_PERIOD = 'agree_reflection_period';
    const USE_SWITCHING_SERVICE = 'use_switching_service';
    const POLICY_CONDITIONS = 'conditions';
    const GET_ADDITIONAL_COVERAGES = 'get_additional_coverages';

    const CRIMINAL_PAST = 'criminal_past';
    const CRIMINAL_PAST_EXPLANATION = 'criminal_past_explanation';
    const CRIMINAL_PAST_INFO = 'criminal_past_info';
    const LEGAL_HELP_REQUIRED = 'legal_help_required';
    const LEGAL_HELP_REQUIRED_EXPLANATION = 'legal_help_required_explanation';

    const SOCIAL_SECURITY_RECIPIENT = 'social_security_recipient';

    const INSURANCE_PREVIOUS_CLAIMS = 'insurance_previous_claims';
    const INSURANCE_PREVIOUS_CLAIMS_EXPLANATION = 'insurance_previous_claims_explanation';

    const INSURANCE_DIRECT_HELP = 'insurance_direct_help';
    const INSURANCE_LEGAL_CONFLICT = 'insurance_legal_conflict';
    const INSURANCE_REFUSED = 'insurance_refused';
    const INSURANCE_REFUSED_INFO = 'insurance_refused_info';
    const INSURANCE_REFUSED_EXPLANATION = 'insurance_refused_explanation';
    const INSURANCE_LEGAL_HISTORY = 'insurance_legal_history';
    const INSURANCE_LEGAL_HISTORY_INFO = 'insurance_legal_history_info';

    const REGULAR_DRIVER = 'regular_driver';

    const IS_CAR_OWNER = 'is_car_owner';
    const CAR_OWNER_GENDER = 'car_owner_gender';
    const CAR_OWNER_INITIALS = 'car_owner_initials';
    const CAR_OWNER_INSERTION = 'car_owner_insertion';
    const CAR_OWNER_LAST_NAME = 'car_owner_last_name';
    const CAR_OWNER_BIRTHDATE = 'car_owner_birthdate';
    const CAR_OWNER_YEARS_WITHOUT_DAMAGE = 'car_owner_years_without_damage';
    const CAR_OWNER_RELATION = 'car_owner_relation';
    const CAR_OWNER_SAME_ADDRESS = 'car_owner_same_address';
    const CAR_OWNER_HOUSE_NUMBER = 'car_owner_house_number';
    const CAR_OWNER_HOUSE_NUMBER_SUFFIX = 'car_owner_house_number_suffix';
    const CAR_OWNER_POSTAL_CODE = 'car_owner_postal_code';
    const CAR_OWNER_EMAIL = 'car_owner_email';
    const COMPANY_POSTAL_CODE = 'company_postal_code';
    const COMPANY_HOUSE_NUMBER = 'company_house_number';
    const COMPANY_HOUSE_NUMBER_SUFFIX = 'company_house_number_suffix';
    //    const CAR_OWNER_STREET = 'car_owner_street';
    const CAR_OWNER_BUSINESS_FUNCTION = 'car_owner_business_function';
    const CAR_LICENSE_SUSPENSION_HISTORY = 'car_license_suspension_history';
    const CAR_LICENSE_SUSPENSION_REASON = 'car_license_suspension_reason';
    const CAR_LICENSE_SUSPENSION_YEAR = 'car_license_suspension_year';
    const CAR_LICENSE_SUSPENSION_DURATION = 'car_license_suspension_duration';
    const CAR_PHYSICAL_DISABILITIES = 'car_physical_disibilities';
    const CAR_PHYSICAL_DISABILITIES_NOTED = 'car_physical_disibilities_noted';
    const CAR_CRIMINAL_PAST = 'car_criminal_past';
    const CAR_CRIMINAL_PAST_YEAR = 'car_criminal_past_year';
    const CAR_CRIMINAL_PAST_INFO = 'car_criminal_past_info';
    const CAR_MOTOR_VEHICLE_DAMAGE = 'car_motor_vehicle_damage';
    const CAR_MOTOR_VEHICLE_DAMAGE_INFO = 'car_motor_vehicle_damage_info';
    const CAR_INSURANCE_REFUSED = 'car_insurance_refused';
    const CAR_INSURANCE_REFUSED_YEAR = 'car_insurance_refused_year';
    const CAR_INSURANCE_REFUSED_INFO = 'car_insurance_refused_info';
    const CAR_INSURANCE_WITHDRAWAL = 'car_insurance_withdrawal';
    const CAR_INSURANCE_WITHDRAWAL_YEAR = 'car_insurance_withdrawal_year';
    const CAR_INSURANCE_WITHDRAWAL_INFO = 'car_insurance_withdrawal_info';
    const CAR_INSURANCE_SPECIAL_CONDITIONS = 'car_insurance_special_conditions';
    const CAR_INSURANCE_SPECIAL_CONDITIONS_YEAR = 'car_insurance_special_conditions_year';
    const CAR_INSURANCE_SPECIAL_CONDITIONS_INFO = 'car_insurance_special_conditions_info';
    const CAR_REPORTING_CODE = 'car_reporting_code';
    const CAR_INSURANCE_MEASURE = 'car_insurance_measure';
    const CAR_INSURANCE_MEASURE_INFO = 'car_insurance_measure_info';
    const CAR_INSURANCE_MEASURE_DURATION = 'car_insurance_measure_duration';
    const CAR_INSURANCE_BANKRUPT = 'car_insurance_bankrupt';
    const CAR_INSURANCE_OTHER_INFO = 'car_insurance_other_info';

    const DAMAGE_TO_OTHERS = 'damage_to_others';
    const THEFT = 'theft';
    const FIRE_AND_STORM = 'fire_and_storm';
    const WINDOW_DAMAGE = 'window_damage';
    const VANDALISM = 'vandalism';
    const OWN_FAULT = 'own_fault';

    // QUESTIONS
    const QUESTIONS_INFO = 'questions_info';
    const QUESTIONS_CRIMINAL_PAST = 'questions_criminal_past';
    const QUESTIONS_CRIMINAL_PAST_YEAR = 'questions_criminal_past_year';
    const QUESTIONS_CRIMINAL_PAST_INFO = 'questions_criminal_past_info';
    const QUESTIONS_MEASURE = 'questions_measure';
    const QUESTIONS_MEASURE_DURATION = 'questions_measure_duration';
    const QUESTIONS_MEASURE_INFO = 'questions_measure_info';
    const QUESTIONS_VEHICLE_DAMAGE = 'questions_vehicle_damage';
    const QUESTIONS_DAMAGE_INFO = 'questions_damage_info';
    const QUESTIONS_DAMAGE_CAUSE_1 = 'questions_damage_cause_1';
    const QUESTIONS_DAMAGE_YEAR_1 = 'questions_damage_year_1';
    const QUESTIONS_DAMAGE_AMOUNT_1 = 'questions_damage_amount_1';
    const QUESTIONS_DAMAGE_CAUSE_2 = 'questions_damage_cause_2';
    const QUESTIONS_DAMAGE_YEAR_2 = 'questions_damage_year_2';
    const QUESTIONS_DAMAGE_AMOUNT_2 = 'questions_damage_amount_2';
    const QUESTIONS_DAMAGE_CAUSE_3 = 'questions_damage_cause_3';
    const QUESTIONS_DAMAGE_YEAR_3 = 'questions_damage_year_3';
    const QUESTIONS_DAMAGE_AMOUNT_3 = 'questions_damage_amount_3';
    const QUESTIONS_REFUSED = 'questions_refused';
    const QUESTIONS_REFUSED_YEAR = 'questions_refused_year';
    const QUESTIONS_REFUSED_INFO = 'questions_refused_info';
    const QUESTIONS_WITHDRAWAL = 'questions_withdrawal';
    const QUESTIONS_WITHDRAWAL_YEAR = 'questions_withdrawal_year';
    const QUESTIONS_WITHDRAWAL_INFO = 'questions_withdrawal_info';
    const QUESTIONS_SPECIAL_CONDITIONS = 'questions_special_conditions';
    const QUESTIONS_SPECIAL_CONDITIONS_YEAR = 'questions_special_conditions_year';
    const QUESTIONS_SPECIAL_CONDITIONS_INFO = 'questions_special_conditions_info';
    const QUESTIONS_HOUSE_LIVING = 'questions_house_living';
    const QUESTIONS_HOUSE_USAGE = 'questions_house_usage';
    const QUESTIONS_HOUSE_STARTDATE = 'questions_house_startdate';
    const QUESTIONS_HOUSE_USAGE_INFO = 'questions_house_usage_info';
    const QUESTIONS_HOUSE_RENTAL = 'questions_house_rental';
    const QUESTIONS_STUDENT = 'questions_student';
    const QUESTIONS_RENTAL_OTHER = 'questions_rental_other';
    const QUESTIONS_HOUSE_MONUMENTAL = 'questions_house_monumental';


    //prices
    const NO_CLAIM = 'no_claim';
    const PASSENGER_INSURANCE_ACCIDENT = 'passenger_insurance_accident';
    const PASSENGER_INSURANCE_DAMAGE = 'passenger_insurance_damage';
    const LEGALEXPENSES = 'legalexpenses';
    const ACCESSOIRES_COVERAGE = 'accessoires_coverage';
    const ROADSIDE_ASSISTANCE = 'roadside_assistance';

    const NO_CLAIM_VALUE = 'no_claim_value';
    const DRIVER_INSURANCE_DAMAGE_VALUE = 'driver_insurance_damage_value';
    const PASSENGER_INSURANCE_DAMAGE_VALUE = 'passenger_insurance_damage_value';
    const PASSENGER_INSURANCE_ACCIDENT_VALUE = 'passenger_insurance_accident_value';
    const LEGALEXPENSES_VALUE = 'legalexpenses_value';
    const LEGALEXPENSES_EXTENDED_VALUE = 'legalexpenses_extended_value';
    const ACCESSOIRES_COVERAGE_VALUE = 'accessoires_coverage_value';
    const ROADSIDE_ASSISTANCE_VALUE = 'roadside_assistance_value';
    const REDRESS_SERVICE_VALUE = 'redress_service_value';
    const ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE = 'roadside_assistance_netherlands_value';
    const ROADSIDE_ASSISTANCE_ABROAD_VALUE = 'roadside_assistance_abroad_value';
    const ROADSIDE_ASSISTANCE_EUROPE_VALUE = 'roadside_assistance_europe_value';
    const TIRES_BUNDLE_VALUE = 'tires_bundle_value';
    const ACCIDENT_AND_DEATH_VALUE = 'accident_and_death_value';
    const ACCIDENT_AND_DISABLED_VALUE = 'accident_and_disabled_value';
    const REPLACEMENT_VEHICLE_VALUE = 'replacement_vehicle_value';
    const ACCESSOIRES_COVERAGE_SINGLE_VALUE = 'accessoires_coverage_single_value';

    const VALUE_ACCESSOIRES = 'value_accessoires';
    const VALUE_AUDIO = 'value_audio';

    const BODY_TYPE = 'body_type';
    const BODY_TYPE_DESCRIPTION = 'body_type_description';
    const PRICE_EXPIRATION_DATE = 'price_expiration_date';
    const CATALOGUS_VALUE = 'catalogus_value';
    const VEHICLE_TYPE_ID = 'vehicle_type_id';
    const LOAD_CAPACITY = 'load_capacity';
    const GEAR_AMOUNT_FORWARD = 'gear_amount_forward';
    const TRAIN_WEIGHT = 'train_weight';
    const TRANSMISSION_TYPE = 'transmission_type';
    const DRIVE_TYPE = 'drive_type';
    const VEHICLE_TYPE = 'vehicle_type';
    const MODEL_NAME_DESCRIPTION = 'model_name_description';

    const COMPANY_CAR_USAGE = 'company_car_usage';
    const COMPANY_CAR_LEASE = 'company_car_lease';

    const OWN_RISK_APPROXIMATION = 'own_risk_approximation';
    const NO_CLAIM_DECLARATION = 'no_claim_declaration';

    // Paston (Meeus) carinsurance
    const CAR_DEPRECIATION_SCHEME = 'car_depreciation_scheme';
    const ACCIDENT_AND_DEATH = 'accident_and_death';
    const ACCIDENT_AND_DISABLED = 'accident_and_disabled';
    const GENERIC = 'generic';
    const YEARLY_INCOME = 'yearly_income';
    const ACCESSOIRES_COVERAGE_AMOUNT = 'accessoires_coverage_amount';

    // Vaninsurance
    const TRANSPORT_GOODS_TYPE = 'transport_goods_type';
    const TRANSPORT_GOODS_OTHER = 'transport_goods_other';
    const EXCLUDE_BPM = 'exclude_bpm';
    const PRIVATE_USE = 'private_use';
    const BEARLOCK = 'bearlock';

    // Van/Car insurance lists
    const EMPLOYER = 'employer';
    const EDUCATION_LEVEL = 'education_level';
    const OCCUPATION = 'occupation';
    const CONTRACT_DURATION = 'contract_duration';
    const FUEL_TYPE = 'fuel_type';
    const BRANCH = 'branch';

    //CH
    const ACCIDENT = 'accident';
    const TENANT = 'tenant';

    const SELECTED_COVERAGES = 'selected_coverages';
    const COVERAGES_CONFIGURATION = 'coverages_configuration';


    const LEGAL_PROTECTION = 'legal_protection';
    const FATALITY = 'fatality';
    const LONG_TERM_CARE = 'long_term_care';
    const DENTAL_CARE = 'dental_care';
    const HOSPITAL_TREATMENT = 'hospital_treatment';
    const OUTPATIENT_TREATMENT = 'outpatient_treatment';
    const SIGNATURE = 'signature';
    const ACCOUNT_ID = 'account_id';


    //housing
    const PERSONAL_CIRCUMSTANCES = 'personal_circumstances';
    const KITCHEN_VALUE = 'kitchen_value';
    const BATHROOM_VALUE = 'bathroom_value';
    const FINISH = 'finish';
    const FOUNDATION = 'foundation';
    const CONSTRUCTION = 'construction';

    //travel insurance
    const COVERAGE_PERIOD = 'coverage_period';
    const COVERAGE_AREA = 'coverage_area';
    const COVERAGE_LUGGAGE = 'coverage_luggage';
    const COVERAGE_CASH_CHEQUES = 'coverage_cash_cheques';

    const TOTAL_LUGGAGE = 'total_luggage';
    const TOTAL_SCUBA_DIVING = 'total_scuba_diving';
    const TOTAL_CASH_CHEQUES = 'total_cash_cheques';


    const COVERAGE_SCUBA_DIVING = 'coverage_scuba_diving';
    const COVERAGE_WINTER_SPORTS = 'coverage_winter_sports';
    const COVERAGE_DANGEROUS_SPORTS = 'coverage_dangerous_sports';
    const COVERAGE_HEALTHCARE = 'coverage_healthcare';
    const COVERAGE_ACCIDENTS = 'coverage_accidents';
    const COVERAGE_DRIVERS_HELP = 'coverage_drivers_help';
    const COVERAGE_BUSINESS_TRIPS = 'coverage_business_trips';
    const COVERAGE_CANCELLATION = 'coverage_cancellation';
    const COVERAGE_REPATRIATION = 'coverage_repatriation';
    const COVERAGE_REPLACEMENT_TRANSPORT = 'coverage_replacement_transport';

    //legal expenses
    const COVERAGE_DAMAGE_REDRESS = 'coverage_damage_redress';
    const COVERAGE_AMOUNT = 'coverage_amount';
    const COVERAGE_YACHT = 'coverage_yacht';
    const COVERAGE_TAX_LAW = 'coverage_tax_law';
    const COVERAGE_LEASE = 'coverage_lease';
    //for both
    const COVERAGE_CONSUMER = 'coverage_consumer';
    const COVERAGE_INCOME = 'coverage_income';
    const COVERAGE_TRAFFIC = 'coverage_traffic';
    const COVERAGE_HOUSING = 'coverage_housing';

    //legal expenses extended
    const COVERAGE_DIVORCE_MEDIATION = 'coverage_divorce_mediation';
    const COVERAGE_FAMILY_LAW = 'coverage_family_law';
    const COVERAGE_WORK = 'coverage_work';
    const COVERAGE_MEDICAL = 'coverage_medical';
    const COVERAGE_NEIGHBOUR_DISPUTES = 'coverage_neighbour_disputes';
    const COVERAGE_TAXES_AND_CAPITAL = 'coverage_taxes_and_capital';
    const COVERAGE_HOUSING_OWNED_HOUSE = 'coverage_housing_owned_house';
    const COVERAGE_HOUSING_FOR_RENT = 'coverage_housing_for_rent';
    const COVERAGE_HOUSING_RENTED_LIVINGUNITS = 'coverage_housing_rented_livingunits';
    const COVERAGE_HOUSING_RENTED_WORKUNITS = 'coverage_housing_rented_workunits';
    const COVERAGE_TRAFFIC_ROADVEHICLE_ACCIDENT = 'coverage_traffic_roadvehicle_accident';
    const COVERAGE_TRAFFIC_ROADVEHICLE_OTHER = 'coverage_traffic_roadvehicle_other';
    const COVERAGE_TRAFFIC_WATERVEHICLE_ACCIDENT = 'coverage_traffic_watervehicle_accident';
    const COVERAGE_TRAFFIC_WATERVEHICLE_OTHER = 'coverage_traffic_watervehicle_other';
    const COVERAGE_HOUSING_VACATIONHOME_NL = 'coverage_housing_vacationhome_nl';
    const COVERAGE_HOUSING_VACATIONHOME_OTHER = 'coverage_housing_vacationhome_other';

    const REMARK_DIVORCE_MEDIATION = 'remark_divorce_mediation';
    const REMARK_FAMILY_LAW = 'remark_family_law';
    const REMARK_WORK = 'remark_work';
    const REMARK_MEDICAL = 'remark_medical';
    const REMARK_NEIGHBOUR_DISPUTES = 'remark_neighbour_disputes';
    const REMARK_TAXES_AND_CAPITAL = 'remark_taxes_and_capital';
    const REMARK_HOUSING_OWNED_HOUSE = 'remark_housing_owned_house';
    const REMARK_HOUSING_FOR_RENT = 'remark_housing_for_rent';
    const REMARK_HOUSING_RENTED_LIVINGUNITS = 'remark_housing_rented_livingunits';
    const REMARK_HOUSING_RENTED_WORKUNITS = 'remark_housing_rented_workunits';
    const REMARK_TRAFFIC_ROADVEHICLE_ACCIDENT = 'remark_traffic_roadvehicle_accident';
    const REMARK_TRAFFIC_ROADVEHICLE_OTHER = 'remark_traffic_roadvehicle_other';
    const REMARK_TRAFFIC_WATERVEHICLE_ACCIDENT = 'remark_traffic_watervehicle_accident';
    const REMARK_TRAFFIC_WATERVEHICLE_OTHER = 'remark_traffic_watervehicle_other';
    const REMARK_HOUSING_VACATIONHOME = 'remark_housing_vacationhome';
    const REMARK_HOUSING_VACATIONHOME_NL = 'remark_housing_vacationhome_nl';
    const REMARK_HOUSING_VACATIONHOME_OTHER = 'remark_housing_vacationhome_other';

    const REMARK_PRICE_SURCHARGES = 'remark_price_surcharges';

    const RESOURCE_PREMIUM_EXTENDED_ID = 'resource_premium_extended_id';
    const CONTRACT_RESOURCE_NAME = 'contract_resource_name';

    const TAX_TARIFF = 'tax_tariff';
    const PRICE_MANUAL_BILLING = 'price_manual_billing';
    const IS_MANDATORY = 'is_mandatory';
    const IS_PRESELECTED = 'is_preselected';

    //Liability
    const OWN_RISK_TYPE = 'own_risk_type';
    const OWN_RISK_CHILDREN = 'own_risk_children';
    const OWN_RISK_GENERAL = 'own_risk_general';


    //providers
    const PROVIDER_NAME = 'provider_name';
    const PROVIDER_PHONE = 'provider_phone';
    const PROVIDER_PHONE_COSTS = 'provider_phone_costs';
    const PROVIDER_STREET = 'provider_street';
    const PROVIDER_HOUSE_NUMBER = 'provider_house_number';
    const PROVIDER_SUFFIX = 'provider_suffix';
    const PROVIDER_POSTAL_CODE = 'provider_postal_code';
    const PROVIDER_CITY = 'provider_city';
    const PROVIDER_CONDITIONS = 'provider_conditions';
    const PROVIDER_ACCEPTGIRO_COSTS = 'provider_acceptgiro_costs';
    const PROVIDER_LICENSEE = 'provider_licensee';
    const PROVIDER_COLLECTIVITY_ID = 'provider_collectivity_id';
    const INSURANCE_PROVIDER_ID = 'insurance_provider_id';
    const INSURANCE_PROVIDER = 'insurance_provider';


    const PROVIDER_EMAIL = 'provider_email';
    const PROVIDER_WEBSITE = 'provider_website';
    const PROVIDER_ID = 'provider_id';
    const PROVIDER_DESCRIPTION = 'provider_description';

    //polis details
    const POLIS_EMAIL_BCC = 'polis_email_bcc';
    const POLIS_EMAIL_TO = 'polis_email_to';
    const POLIS_EMAIL_SUBJECT = 'polis_email_subject';


    //
    //- De naam van de leverancier
    //- Telefoonnummer
    //- Telefoonkosten
    //- Adres
    //- Algemene voorwaarden aanbieder
    //- Kosten acceptgiro
    //- Vergunninghouder


    const CURRENT_PROVIDER = 'current_provider';
    const CURRENT_PROVIDER_ID = 'current_provider_id';
    const NEW_PROVIDER_ID = 'new_provider_id';
    const CONTRACT_DURATION_MONHTS = 'contract_duration_monhts';
    const TARIFF_TYPE = 'tariff_type';

    //energy contracts
    const ENERGY_TYPE = 'energy_type';
    const ELECTRICITY_TYPE = 'electricity_type';
    const ELECTRICITY_USAGE_HIGH = 'electricity_usage_high';
    const ELECTRICITY_USAGE_LOW = 'electricity_usage_low';
    const ELECTRICITY_USAGE_TOTAL = 'electricity_usage_total';


    const ELECTRICITY_USAGE_DISABLED = 'electricity_usage_disabled';
    const GAS_USAGE_DISABLED = 'gas_usage_disabled';


    const ELECTRICITY_TARRIF_LOW = 'electricity_tarrif_low';
    const ELECTRICITY_STANDING_CHARGE = 'electricity_standing_charge';
    const ELECTRICITY_TARRIF_HIGH = 'electricity_tarrif_high';
    const ELECTRICITY_TAX = 'electricity_tax';
    const TAX_DISCOUNT = 'tax_discount';
    const GAS_USAGE = 'gas_usage';
    const GAS_TYPE = 'gas_type';

    const ELECTRICITY_TOTAL_COSTS = 'electricity_total_costs';
    const ELECTRICITY_NETWORK_COSTS = 'electricity_network_costs';
    const GAS_TARRIF = 'gas_tarrif';
    const GAS_STANDING_CHARGE = 'gas_standing_charge';
    const GAS_TAX = 'gas_tax';
    const GAS_TOTAL_COSTS = 'gas_total_costs';
    const GAS_NETWORK_COSTS = 'gas_network_costs';
    const DISCOUNT = 'discount';
    const DISCOUNT_DESCRIPTION = 'discount_description';
    const DISCOUNT_DESCRIPTION_SHORT = 'discount_description_short';
    const TOTAL_COSTS_NO_DISCOUNT = 'total_costs_no_discount';
    const TOTAL_COSTS = 'total_costs';

    const PRODUCT_ELECTRICITY_ID = 'product_electricity_id';
    const PRODUCT_GAS_ID = 'product_gas_id';
    const PRODUCT_COMBI_ID = 'product_combi_id';
    const PRODUCT_TYPE = 'product_type';
    const PRODUCT_TYPE_ID = 'product_type_id';
    const EVENT = 'event';
    const LOCKED = 'locked';
    const ORDER = 'order';

    //polis lines
    const ROWS = 'rows';
    const ROW = 'row';
    const COLS = 'cols';
    const COL = 'col';
    const STYLE = 'style';
    const STYLE_CLASS = 'style_class';
    const TEXT = 'text';


    // values
    const COVERAGE_ALL_RISK = 'coverage_all_risk';
    const COVERAGE_EXTENDED = 'coverage_extended';

    //misc
    const UNLIMITED = 'unlimited';
    const TRUE = 'true';
    const FALSE = 'false';
    const PAGE = 'page';
    const PAGE_TITLE = 'page_title';
    const NA = 'na';
    const METHOD = 'method';
    const EXTENDED = 'extended';
    const TOKEN = 'token';
    const WEBSITE_ID = 'website_id';
    const ORDER_ID = 'order_id';
    const SUCCESS = 'success';
    const ADD_NO_CHOICE = 'add_no_choice';
    const DUMP_FIELDS = 'dump_fields';
    const SKIP_DOWNLOAD = 'skip_download';
    const LIMIT = 'limit';
    const OFFSET = 'offset';
    const REF_URL = 'ref_url';
    const CREATE_ARGUMENTS = 'create_arguments';
    const OFFICE_ID = 'office_id';
    const DAISYCON = 'daisycon';
    const DAISYCON_FORWARD = 'daisycon_forward';

    const WEBSITE = 'website';
    const USER = 'user';
    const USER_ID = 'user_id';
    const SESSION = 'session';
    const HASH = 'hash';
    const SEND = 'send';
    const CLICK_ID = 'click_id';
    const OUID = 'ouid';

    const URL_IDENTIFIER = 'url_identifier';
    const URL_DEMO = 'url_demo';

    //users
    const IS_COMPANY = 'is_company';

    //company
    const COMPANY_REGISTRATION_NUMBER = 'company_registration_number';
    const COMPANY_NAME = 'company_name';
    const COMPANY_VAT = 'company_vat';
    const COMPANY_TYPE = 'company_type';
    const COMPANY_ID = 'company.id';
    const COMPANY__ID = 'company_id';
    const COMPANY_CONTACT_INITIALS = 'company_contact_initials';
    const COMPANY_CONTACT_INSERTION = 'company_contact_insertion';
    const COMPANY_CONTACT_LASTNAME = 'company_contact_lastname';
    const COMPANY_ACTIVITY = 'company_activity';
    const KVK_NUMBER = 'kvk_number';

    //bankaccount
    const BANK_ACCOUNT_PAYMENT_TYPE = 'bank_account_payment_type';
    const BANK_ACCOUNT_ACCOUNT_HOLDER_NAME = 'bank_account_account_holder_name';
    const BANK_ACCOUNT_BBAN = 'bank_account_bban';
    const BANK_ACCOUNT_IBAN = 'bank_account_iban';
    const BANK_ACCOUNT_BIC = 'bank_account_bic';
    const BANK_ACCOUNT_NAME = 'bank_account_name';

    //simonly
    const NETWORK = 'network';
    const NETWORK_CODE = 'network_code';
    const MINUTES = 'minutes';
    const SMS = 'sms';
    const DATA = 'data';
    const SPEED_DOWNLOAD = 'speed_download';
    const SPEED_UPLOAD = 'speed_upload';
    const PRICE_PER_DATA = 'price_per_data';
    const PRICE_PER_MINUTE = 'price_per_minute';
    const PRICE_PER_SMS = 'price_per_sms';
    const ACTION_DURATION = 'action_duration';

    const SIM_ONLY = 'sim_only';
    const SIMCARD_TYPE = 'simcard_type';
    const TRANSFER_UNUSED_UNITS = 'transfer_unused_units';
    const INTERNET_TYPE = 'internet_type';
    const NUMBER_PORTABILITY = 'number_portability';
    const NUMBER_PORTABILITY_PREPAY = 'number_portability_prepay';
    const NUMBER_PORTABILITY_SERVICE_PROVIDER = 'number_portability_service_provider';
    const NUMBER_PORTABILITY_CURRENT_PHONE = 'number_portability_current_phone';
    const NUMBER_PORTABILITY_CURRENT_SIM = 'number_portability_current_sim';
    const NUMBER_PORTABILITY_PREFERED_START_DATE = 'number_portability_prefered_start_date';

    //mobile
    const MOBILE_BRAND = 'mobile_brand';
    const MOBILE_MODEL = 'mobile_model';
    const MOBILE_COLOR = 'mobile_color';

    //parking
    const ARRIVAL_DATE = "arrival_date";
    const DESTINATION_ARRIVAL_DATE = "destination_arrival_date";
    const ARRIVAL_TIME = "arrival_time";
    const DESTINATION_ARRIVAL_TIME = "destination_arrival_time";
    const DEPARTURE_DATE = "departure_date";
    const DESTINATION_DEPARTURE_DATE = "destination_departure_date";
    const DEPARTURE_TIME = "departure_time";
    const DESTINATION_DEPARTURE_TIME = "destination_departure_time";
    const OPTIONS = 'options';
    const PRODUCT_OPTIONS = 'product_options';
    const PRODUCT_OPTIONS_IDS = 'product_options_ids';
    const AVAILABLE_OPTIONS = 'available_options';
    const REMOTE_OPTIONS = 'remote_options';
    const REMOTE_OPTION_IDS = 'remote_option_ids';
    const AVAILABILITY_COUNT = 'availability_count';
    const OPTION_ID = 'option_id';
    const PRICE_OPTIONS = 'price_options';
    const PRICE_RESELLER = 'price_reseller';
    const PARKING_ID = "parking_id";
    const DESTINATION = "destination";
    const LOCATION = "location";
    const LOCATION_ID = "location_id";
    const NUMBER_OF_SPOTS = "number_of_spots";
    const RESERVATION_CODE = "reservation_code";
    const LOCATIONS = 'locations';
    const NUMBER_OF_PERSONS = 'number_of_persons';
    const NUMBER_OF_CARS = 'number_of_cars';
    const LICENSEPLATE_UNKNOWN = 'licenseplate_unknown';
    const RADIUS = 'radius';
    const LOCATION_GEOJSON = 'location_geojson';
    const LOCATION_LATITUDE = 'location_latitude';
    const LOCATION_LONGITUDE = 'location_longitude';
    const ROUTES = 'routes';
    const SEGMENTS = 'segments';
    const ARRIVAL_PLACE = 'arrPlace';
    const DEPARTURE_PLACE = 'depPlace';
    const TOTAL_DURATION = 'totalDuration';
    const PARKING_SPACES_TOTAL = 'parking_spaces_total';

    const TRANSIT_DURATION = 'transitDuration';
    const VEHICLE = 'vehicle';
    const VEHICLE_ICON = 'vehicle_icon';

    const SERVICES = 'services';
    const SERVICE_ID = 'service_id';
    const SERVICE_NAME = 'service_name';

    const AIRPORT_CODE = "airport_code";
    const AIRPORT_ID = "airport_id";
    const AREA_ID = "area_id";
    const AREA = "area";
    const FLIGHT_NUMBER = "flight_number";
    const RETURN_FLIGHT_NUMBER = "return_flight_number";
    const OUTBOUND_FLIGHT_NUMBER = 'outbound_flight_number';
    const IS_UNAVAILABLE = "is_unavailable";
    const RESERVATION_BARCODE_IMAGE = "reservation_barcode_image";
    const RESERVATION_KEY = "reservation_key";
    const RESERVATION_SESSION_KEY = "reservation_session_key";
    const RESERVATION_SESSION_KEY_EXPIRATION = "reservation_session_key_expiration";

    const INTERNAL_REMARKS = 'internal_remarks';
    const CUSTOMER_REMARKS = 'customer_remarks';

    const PAYMENT_AMOUNT = 'payment_amount';
    const PAYMENT_STATUS = 'payment_status';
    const PAYMENT_STATUS_MULTISAFEPAY = 'payment_status_multisafepay';
    const RESERVATION_STATUS = 'reservation_status';
    const RESERVATION_RESULT = 'reservation_result';

    const MAP_IMAGE = 'map_image';
    const ECO_POINTS = 'eco_points';

    //iak
    const PACKAGES = 'packages';
    const IS_IAK = 'is_iak';
    const IS_TOPPING = 'is_topping';
    const HIDE_FREE_TOPPINGS = 'hide_free_toppings';
    const PACKAGES_PARTNER = 'packages_partner';
    const PACKAGES_CHILD_1 = 'packages_child_1';
    const PACKAGES_CHILD_2 = 'packages_child_2';
    const PACKAGES_CHILD_3 = 'packages_child_3';
    const PACKAGES_CHILD_4 = 'packages_child_4';
    const PACKAGES_CHILD_5 = 'packages_child_5';

    const COLLECTIVITY_ID = 'collectivity_id';
    const COLLECTIVITY_GROUP_ID = 'collectivity_group_id';
    const COLLECTIVITY_GROUP_ID_IAK = 'collectivity_group_id_iak';
    const COLLECTIVITY = 'collectivity';
    const COLLECTIVITY_OFFER = 'collectivity_offer';
    const EXTRAS = 'extras';
    const KENMERKEN = 'kenmerken';


    //zanox
    const CALL_LIMIT = 'call_limit';
    const ALL_IN_ONE = 'all_in_one';
    const BUNDLE_STRATEGY = 'bundle_strategy';
    const ADDITIONAL = 'additional';
    const URL = 'url';
    const CLICKABLE = 'clickable';

    //payments
    const KEY = 'key';
    const PAYMENT_KEY = 'payment_key';
    const PAYMENT_PERIOD = 'payment_period';
    const PAYMENT_CYCLE = 'payment_cycle';
    const PAYMENT_METHOD = 'payment_method';
    const PAYMENT_PREAUTHORIZED_DEBIT = 'payment_preauthorized_debit';
    const PAYMENT_PREAUTHORIZED_DEBIT_AGREED = 'payment_preauthorized_debit_agreed';
    const AUTHORIZE_YEAR_PERIOD_COSTS = 'authorize_year_period_costs';
    const CURRENCY = 'currency';
    const AMOUNT = 'amount';
    const TRANSACTION_COSTS = 'transaction_costs';
    const RETURN_URL_OK = 'return_url_ok';
    const RETURN_URL_CANCEL = 'return_url_cancel';
    const RETURN_URL_ERROR = 'return_url_error';
    const RETURN_URL_REJECT = 'return_url_reject';

    const PAYMENT_COMPLETE = 'payment_complete';
    const PAYMENT_AMOUNT_PAID = 'payment_amount_paid';

    const PAYMENT_INTERFACE_TYPE = 'payment_interface_type';
    const PAYMENT_METHOD_ISSUER = 'payment_method_issuer';
    const PAYMENT_TRANSACTION_ID = 'payment_transaction_id';
    const PAYMENT_INPUT = 'payment_input';
    const PAYMENT_RESULT = 'payment_result';
    const TRANSACTION_ID = 'transaction_id';

    const PAYMENT_CANCEL_URL = 'payment_cancel_url';
    const PAYMENT_NOTIFY_URL = 'payment_notify_url';
    const PAYMENT_RETURN_URL = 'payment_return_url';
    const REDIRECT_URL = 'redirect_url';

    const CLOSE_WINDOW = 'close_window';
    const VALIDITY_PAYMENT_URL_DAYS = 'validity_payment_url_days';
    const DISABLE_SEND_EMAIL = 'disable_send_email';
    const GOOGLE_ANALYTICS_CODE = 'google_analytics_code';
    const MANUAL_CREDITCARD_CHECK = 'manual_creditcard_check';
    const CUSTOM_VAR_1 = 'custom_var_1';
    const CUSTOM_VAR_2 = 'custom_var_2';
    const CUSTOM_VAR_3 = 'custom_var_3';


    //params
    const PARAM1 = 'param1';
    const PARAM2 = 'param2';

    //commiss   ions
    const COMMISSION_TOTAL = 'commission.total';
    const COMMISSION_PARTNER = 'commission.partner';

    const PRICE_INSURANCE_TAX = 'price_insurance_tax';
    const PRICE_SURCHARGES = 'price_surcharges';
    const PRICE_COVERAGE_SUB_TOTAL = 'price_coverage_sub_total';
    const PRICE_SUB_TOTAL = 'price_sub_total';

    //PHOTOS

    const BRAND_LOGO = 'brand_logo';
    const BRAND_LOGO_THUMB = 'brand_logo_thumb';
    const PHOTO_FRONT = 'photo_front';
    const PHOTO_FRONT_THUMB = 'photo_front_thumb';
    const PHOTO_REAR = 'photo_rear';
    const PHOTO_REAR_THUMB = 'photo_rear_thumb';
    const PHOTO_INTERIOR = 'photo_interior';
    const PHOTO_INTERIOR_THUMB = 'photo_interior_thumb';

    //knip
    const IS_EXISTING_CUSTOMER = 'is_existing_customer';
    const BAG_ID = 'bag_id';
    const POLICY_NUMBER = 'policy_number';
    const SUB_PRODUCTS = 'sub_products';
    const CALCULATION_CONTRACT_DURATION = 'calculation_contract_duration';


    // common things
    const ENABLE_COMPOSER = 'enable_composer';

    const SESSION_ID = 'session_id';

    const EXTERNAL_ID = 'external_id';

    const ORDER_NR = 'order_nr';
    const INTERNAL_ORDER_ID = 'internal_order_id';

    const DURATION = 'duration';

    const LOCALE = 'locale';
    const LANGUAGE = 'language';

    const API_KEY = 'api_key';
    const TEST_ENVIRONMENT = 'test_environment';

    const CREATION_DATE = 'creation_date';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const ADVISE = 'advise';

    const REJECTION_MESSAGE = 'rejection_message';

    // Geo
    const LATITUDE = 'latitude';
    const LONGITUDE = 'longitude';
    const ORIGIN_LATITUDE = 'origin_latitude';
    const ORIGIN_LONGITUDE = 'origin_longitude';
    const COMPOUND_ORIGIN_COORDINATES = 'compound_origin_coordinates';
    const DESTINATION_LATITUDE = 'destination_latitude';
    const DESTINATION_LONGITUDE = 'destination_longitude';
    const COMPOUND_DESTINATION_COORDINATES = 'compound_destination_coordinates';
    const GEO_WITHIN = 'geo_within';
    const LOC = 'loc';
    const GOOGLE_PLACE_ID = 'google_place_id';
    const ORIGIN_GOOGLE_PLACE_ID = 'origin_google_place_id';
    const ORIGIN_GOOGLE_PLACE_ID_VALUE = 'origin_google_place_id_value';
    const DESTINATION_GOOGLE_PLACE_ID = 'destination_google_place_id';
    const DESTINATION_GOOGLE_PLACE_ID_VALUE = 'destination_google_place_id_value';
    const GOOGLE_MAPS_URL = 'google_maps_url';
    const GEO_MODE = 'geo_mode';
    const FREEFORM_ADDRESS = 'freeform_address';
    const ORIGIN_ADDRESS_FOR_DISPLAY = 'origin_address_for_display';
    const DESTINATION_ADDRESS = 'destination_address';
    const DESTINATION_ADDRESS_FOR_DISPLAY = 'destination_address_for_display';

    // Misc

    const UNMAPPED = '@unmapped';
    const ACTION = 'action';
    const ALL_CALLBACKS = 'all_callbacks';

    const DEFAULT_VALUE = 'default_value';

    // Parking

    const SERVICE = 'service';
    const DISTANCE = 'distance';
    const NIGHT_SURCHARGE = 'night_surcharge';
    const PRICE_NIGHT_SURCHARGE = 'price_night_surcharge';
    const CONDITIONS = 'conditionz'; // n33dz 2B 1337
    const OFFICIAL = 'official';
    const SOURCE = 'source';
    const CATEGORY = 'category';
    const CATEGORY_NAME = 'category_name';
    const SEARCH_QUERY = 'search_query';
    const SEARCH_QUERY_ID = 'search_query_id';
    const SEARCH_QUERY_RESULT_ID = 'search_query_result_id';
    const BOOKING_ID = 'booking_id';
    const BOOKING_STATUS = 'booking_status';
    const COSTFREE_CANCELLATION = 'costfree_cancellation';
    const PRICE_COSTFREE_CANCELLATION = 'price_costfree_cancellation';
    const PRICE_ADMINISTRATION_FEE = 'price_administration_fee';

    const PASSENGERS = 'passengers';
    const PASSENGERS_CAPACITY = 'passengers_capacity';
    const LUGGAGE = 'luggage';
    const ONE_WAY = 'one_way';
    const LOYALTY_NUMBER = 'loyalty_number';

    // Travel
    const ORIGIN_CODE = 'origin_code';
    const ORIGIN_CITY = 'origin_city';
    const ORIGIN_STREET = 'origin_street';
    const ORIGIN_POSTAL_CODE = 'origin_postal_code';
    const ORIGIN_HOUSE_NUMBER = 'origin_house_number';
    const ORIGIN_COUNTRY = 'origin_country';
    const ORIGIN_COUNTRY_CODE = 'origin_country_code';
    const ORIGIN_POINT_OF_INTEREST = 'origin_point_of_interest';
    const ORIGIN_NAME = 'origin_name';
    const DESTINATION_CITY = 'destination_city';
    const DESTINATION_STREET = 'destination_street';
    const DESTINATION_POSTAL_CODE = 'destination_postal_code';
    const DESTINATION_HOUSE_NUMBER = 'destination_house_number';
    const DESTINATION_COUNTRY = 'destination_country';
    const DESTINATION_COUNTRY_CODE = 'destination_country_code';
    const DESTINATION_POINT_OF_INTEREST = 'destination_point_of_interest';
    const DESTINATION_NAME = 'destination_name';
    const ORIGIN_ADDRESS = 'origin_address';
    const DESTINATION_CODE = 'destination_code';
    const LOCATION_DESCRIPTION = 'location_description';
    const IS_OFFICIAL_FACILITY = 'is_official_facility';
    const DISTANCE_TO_DESTINATION = 'distance_to_destination';
    const TIME_TO_DESTINATION = 'time_to_destination';
    const REBOOK_ORDER_ID = 'rebook_order_id';
    const REBOOK_TO_ORDER_ID = 'rebook_to_order_id';
    const COST = 'cost';
    const REMOTE_ID = 'remote_id';
    const PRICE_BASE = 'price_base';
    const CANCELLED_BY = 'cancelled_by';
    const CANCELLATION_REASON = 'cancellation_reason';
    const USE_DIRECT_RESERVATION = 'use_direct_reservation';
    const IS_TEST_ORDER = 'is_test_order';
    const REMOTE_ORDER_ID = 'remote_order_id';
    const RESERVATION_UPDATE_RESULT = 'reservation_update_result';
    const PASSWORD_INPUT = 'password_input';
    const FORMAT = 'format';
    const TEMPLATE_ID = 'template_id';
    const VAT = 'vat';


    // Travel rights
    const ADMINISTRATION_FEE = 'administration_fee';
    const MULTISAFEPAY_API_KEY = 'multisafepay_api_key';
    const MULTISAFEPAY_TEST_ENVIRONMENT = 'multisafepay_test_environment';
    const EMBED_ADMINISTRATION_COST = 'embed_administration_cost';
    const PAGESTART = 'pagestart';
    const RESELLER_MAIL_NL = 'reseller_mail_nl';
    const RESELLER_MAIL_DE = 'reseller_mail_de';
    const RESELLER_MAIL_FR = 'reseller_mail_fr';
    const RESELLER_MAIL_EN = 'reseller_mail_en';
    const IS_CRM_TOOL = 'is_crm_tool';

    // House data
    const HOUSE_WALL_MATERIAL = 'house_wall_material';
    const HOUSE_ROOF_MATERIAL = 'house_roof_material';
    const HOUSE_ROOF_CONSTRUCTION = 'house_roof_construction';
    const HOUSE_THATCHEDROOF_CLOSED = 'house_thatchedroof_closed';
    const HOUSE_ABOVEGROUND_FLOOR_MATERIAL = 'house_aboveground_floor_material';
    const HOUSE_GROUND_FLOOR_MATERIAL = 'house_ground_floor_material';
    const HOUSE_TYPE = 'house_type';
    const HOUSE_ABUTMENT = 'house_abutment';
    const HOUSE_USAGE = 'house_usage';
    const HOUSE_FACADE_TYPE = 'house_facade_type';
    const HOUSE_KITCHEN_TYPE = 'house_kitchen_type';
    const HOUSE_BATHROOM_TYPE = 'house_bathroom_type';
    const HOUSE_LIVINGROOM_TYPE = 'house_livingroom_type';
    const HOUSE_ANNEX_FUNCTION = 'house_annex_function';
    const HOUSE_ANNEX_MATERIAL = 'house_annex_material';
    const HOUSE_LUXURY_LEVEL = 'house_luxury_level';
    const HOUSE_VOLUME_SOURCE = 'house_volume_source';
    const HOUSE_FOUNDATION = 'house_foundation';
    const HOUSE_IS_MONUMENT = 'house_is_monument';
    const HOUSE_REBUILD = 'house_rebuild';
    const HOUSE_IS_NEWLY_BUILT = 'house_is_newly_built';
    const LIVING_AREA_TOTAL = 'living_area_total';
    const LIVING_AREA_TOTAL_ROUNDED = 'living_area_total_rounded';
    const PARCEL_SIZE = 'parcel_size';
    const PARCEL_SIZE_ROUNDED = 'parcel_size_rounded';
    const SOLARPANELS_VALUE = 'solarpanels_value';
    const SOLARPANELS_QUESTION = 'solarpanels_question';
    const PARCEL_QUESTION = 'parcel_question';
    const SURFACE_AREA_MAIN_BUILDING = 'surface_area_main_building';
    const NUMBER_OF_ADDITIONAL_BUILDINGS = 'number_of_additional_buildings';
    const NUMBER_OF_FLOORS = 'number_of_floors';
    const VALUATION_OF_REAL_ESTATE = 'valuation_of_real_estate';


    const CATEGORIES = 'categories';
    const INFOFOLIO = 'infofolio';
    const GUARANTEE_FOR_UNDERINSURANCE_HOME = 'guarantee_for_underinsurance_home';
    const GUARANTEE_FOR_UNDERINSURANCE_CONTENTS = 'guarantee_for_underinsurance_contents';

    // House additional coverages

    const COVERAGE_JEWELRY_VALUE = 'coverage_jewelry_value';
    const COVERAGE_MOBILE_ELECTRONICS_VALUE = 'coverage_mobile_electronics_value';
    const COVERAGE_OUTDOORS_VALUE = 'coverage_outdoors_value';
    const COVERAGE_RENTAL_OR_APPARTMENT_OWNERSHIP_VALUE = 'coverage_rental_or_appartment_ownership_value';
    const COVERAGE_HOUSEOWNERSHIP_VALUE = 'coverage_houseownership_value';
    const COVERAGE_GLASS_VALUE = 'coverage_glass_value';
    const COVERAGE_OCCUPATIONAL_EQUIPMENT_VALUE = 'coverage_occupational_equipment_value';
    const COVERAGE_AUDIO_VISUAL_COMPUTER_VALUE = 'coverage_audio_visual_computer_value';
    const COVERAGE_VALUABLES_VALUE = 'coverage_valuables_value';

    const COVERAGE_DECONTAMINATION_VALUE = 'coverage_decontamination_value';
    const COVERAGE_GARDEN_VALUE = 'coverage_garden_value';
    const COVERAGE_DISASTER_VALUE = 'coverage_disaster_value';

    const COVERAGES = 'coverages';

    const PRICE_COVERAGES = 'price_coverages';


    const NEW_KITCHEN_BATHROOM = 'new_kitchen_bathroom';


    const FUNNEL = 'funnel';
    const SERVICE_CLASS = 'service_class';

    //email
    const SUBJECT = 'subject';
    const TO_NAME = 'to_name';
    const TO_EMAIL = 'to_email';
    const TO_EMAIL_OVERWRITE = 'to_email_overwrite';
    const BCC_EMAIL = 'bcc_email';
    const FROM_NAME = 'from_name';
    const FROM_EMAIL = 'from_email';
    const TEST_EMAIL = 'test_email';
    const TEMPLATE = 'template';
    const VIEW = 'view';

    // Inrix
    const APP_ID = 'app_id';
    const HASH_TOKEN = 'hash_token';

    // Healthcare
    const URL_CONTRACTED_CARE = 'url_contracted_care';
    const YEARLY_PAYMENT_REDUCTION = 'yearly_payment_reduction';
    const OPEN_HOURS = 'opening_hours';
    const IN_ZORGWEB = 'in_zorgweb';
    const UZOVI_CODE = 'uzovi_code';
    const SERVICE_PHONE = 'service_phone';
    const SERVICE_EMAIL = 'service_email';
    const PHONE_RATE = 'phone_rate';
    const CLIENT_COUNT = 'client_count';
    const SUPPLEMENT_CHOICE = 'supplement_choice';
    const CONCERN = 'concern';
    const BASE_ID  = 'base_id';
    const FREE_CHOICE  = 'free_choice';
    const ACCEPTATION = 'acceptation';
    const ACCEPTATION_ADDITIONAL = 'acceptation_additional';
    const ACCEPTATION_TKV = 'acceptation_tkv';
    const ACCEPTATION_WAITTIME = 'acceptation_waittime';
    const FYSIO_COVERAGE  = 'fysio_coverage';
    const HOSPITAL_COVERAGE  = 'hospital_coverage';
    const SUB_TYPE = 'sub_type';
    const IS_COMBO = 'is_combo';
    const CHILD = 'child';
    const COST_TYPE_ID = 'cost_type_id';
    const HAS_VALUATION = 'has_valuation';
    const HAS_COVERAGE = 'has_coverage';
    const MAX_RATE = 'max_rate';
    const DEAL = 'deal';
    const FILTER_KEYS = 'filter_keys';
    const REQUIRE_DESCRIPTION = 'require_description';
    const WEBSITE_URL = 'website_url';
    const REQUEST = 'request';
    const ZORGWEB_ORDER_ID = 'zorgweb_order_id';
    const XML = 'xml';
    const ERROR = 'error';
    const OVERLOAD = 'overload';
    const OVERLOAD_FIELDS = 'overload_fields';
    const COVERAGES_PDF = 'coverages_pdf';

    const AGE = 'age';
    const AGE_FROM = 'age_from';
    const AGE_TO = 'age_to';
    const CO_INSURED = 'co_insured';
    const CURRENTLY_INSURED = 'currently_insured';
    const OWN_RISK_AMOUNT = 'own_risk_amount';
    const PRICE_ADDITIONAL = 'price_additional';

    const BASE = 'base';
    const AANVULLEND = 'aanvullend';
    const BUITENLAND = 'buitenland';
    const ORTHO = 'ortho';
    const ALTERNATIEF = 'alternatief';
    const THERAPIE = 'therapie';
    const TAND = 'tand';
    const GEZINSPLANNING = 'gezinsplanning';
    const FYSIO = 'fysio';
    const KLASSE = 'klasse';
    const COMBO = 'combo';
    const PATH = 'path';

    const PRICE_3M = 'price_3m';
    const PRICE_6M = 'price_6m';
    const PRICE_12M = 'price_12m';
    const DISCOUNT_ORDER = 'discount_order';


    //HC SWISS
    const VALUE_TYPE_ID = 'value_type_id';
    const PERCENTAGE = 'percentage';
    const YEARS = 'years';
    const DAYS = 'days';
    const WAITING_PERIOD_MONTHS = 'waiting_period_months';
    const SUBSIDIARY_PRODUCT_ID = 'subsidiary_product_id';
    const COMMENTS = 'comments';
    const HAS_PARENT_WITH_SAME_COVERAGE = 'has_parent_with_same_coverage';
    const KNIP_ID = 'knip_id';
    const KNIP_ACCOUNT = 'knip_account';
    const CATEGORY_ID = 'category_id';
    const CATEGORY_DESCRIPTION = 'category_description';
    const INCLUDED_ID = 'included_id';
    const SUB_ID = 'sub_id';
    const KVG_COVERAGE = 'kvg_coverage';

    // VVG SWISS
    const SUPPLEMENTARY_HOSPITAL = 'supplementary_hospital'; // Spitalzusatzversicherung
    const PRECAUTION = 'precaution'; // Vorsorge
    const GLASSES = 'glasses'; // Brillen & Kontaktlinsen
    const ALTERNATIVE_MEDICINE = 'alternative_medicine'; // Alternativmedizin
    const AID = 'aid'; // Hilfsmittel
    const EMERGENCIES_ABROAD = 'emergencies_abroad'; // Notfall im Ausland
    const SEARCH_RESCUE = 'search_rescue'; // Suche & Rettung
    const DENTAL_TREATMENTS = 'dental_treatments'; // Zahnbehandlungen
    const ORTHODONTICS = 'orthodontics'; // Zahnstellungskorrekturen


    //Elipslife
    const SMOKER            = 'smoker'; //Raucher
    const YEARLY_COST       = 'yearly_cost';
    const BILLING_NAME      = 'billing_name';
    const BILLING_STREET    = 'billing_street';
    const BILLING_ZIPCODE   = 'billing_zipcode';
    const BILLING_CITY      = 'billing_city';
    const HEIGHT            = 'height';
    const COVERAGE_ON_DEATH = 'coverage_on_death'; //Remove
    const COVERAGE_PER_YEAR = 'coverage_per_year'; //Remove
    const CONTACT_DAY       = 'contact_day';
    const CONTACT_TIME      = 'contact_time';
    const BMI               = 'bmi';
    const ELIPSLIFE_NORMAL_FEMALE_APPLY    = 'normal_female_apply';
    const ELIPSLIFE_NORMAL_NONSMOKER_APPLY = 'normal_nonsmoker_apply';
    const ELIPSLIFE_NORMAL_APPLY           = 'normal_apply';

    //Elipslife Questionaire
    const ELIPSLIFE_20_ALCOHOL_UNITS_PER_WEEK = 'elipslife_20_alcohol_units_per_week';
    const ELIPSLIFE_MEDICINE_WEEKLY           = 'elipslife_medicine_weekly';
    const ELIPSLIFE_PAST_12_MONTHS_ILLNESS    = 'elipslife_past_12months_illness';
    const ELIPSLIFE_HOSPITAL_PAST_5_YEARS     = 'elipslife_hospital_past_5_years';
    const ELIPSLIFE_NOT_NORMAL_TEST_RESULTS   = 'elipslife_not_normal_test_results';
    const ELIPSLIFE_MENTAL_PAST_5_YEARS       = 'elipslife_mental_issues_past_5_years';
    const ELIPSLIFE_DISEASES                  = 'elipslife_diseases';
    const ELIPSLIFE_HIGH_RISK_WORK            = 'elipslife_high_risk_work';
    const RANK                                = 'rank';
    const HEIGHT_TO     = 'height_to';
    const HEIGHT_FROM   = 'height_from';
    const WEIGHT_TO     = 'weight_to';
    const WEIGHT_FROM   = 'weight_from';
    const PASS          = 'pass';

    //Blaudirekt Premium
    const RECENT_DAMAGE = 'recent_damage'; //Vertrag_schadensfrei_form
    const PREINSURANCE_CONTRACT = 'preinsurance_contract'; //Vertrag_vn_vorversicherung_form
    const CONTRACT_DEDUCTIBLE = 'contract_deductible'; //Vertrag_selbstbehalt

    const PREMIUM_GROSS = 'premium_gross'; //Vertrag_beitrag_brutto
    const COVERAGE_SUM = 'coverage_sum'; //Vertrag_ds
    const COVERAGE_SUM_ASSETS = 'coverage_sum_assets'; //Vertrag_ds_vermoegen
    const COVERAGE_SUM_RENTED_GOODS = 'coverage_sum_rented_goods'; //Vertrag_ds_mietsach
    const RENTAL_BUILDING_DAMAGE_OWNRISK = 'rental_building_damage_ownrisk'; //Vertrag_sb_mietsachschaeden_gebaeude
    const DAMAGES_BY_CHILD = 'damages_by_child'; //Vertrag_schaeden_durch_nicht_deliktfaehige_kinder
    const PERSONAL_DAMAGE_BY_CHILD = 'personal_damage_by_child'; //Vertrag_pers_schaeden_durch_nicht_deliktfaehige_kinder
    const OWNRISK_DAMAGE_BY_CHILD = 'ownrisk_damage_by_child'; //Vertrag_sb_schaeden_durch_nicht_deliktfaehige_kinder
    const OWNRISK_LOSS_OFF_DEBT_INCOME_COVERAGE = 'ownrisk_loss_off_debt_income_coverage'; //Vertrag_forderungsausfalldeckung_sb
    const LOSS_OFF_DEBT_INCOME_INSURANCE = 'loss_off_debt_income_insurance'; //Vertrag_forderungsausfallversicherung
    const LEGAL_PROTECTION_LOSS_OFF_DEBT_INCOME = 'legal_protection_loss_off_debt_income'; //Vertrag_rechtsschutz_forderungsausfall
    const PRIVATE_LOSS_OFF_KEYS = 'private_loss_off_keys'; //Vertrag_privater_schluesselverlust
    const OWNRISK_PRIVATE_LOSS_OFF_KEYS = 'ownrisk_private_loss_off_keys'; //Vertrag_sb_privater_schluesselverlust
    const BUSINESS_LOSS_OFF_KEYS = 'business_loss_off_keys'; //Vertrag_dienstlicher_schluesselverlust
    const OWNRISK_LOSS_OFF_KEYS = 'ownrisk_loss_off_keys'; //Vertrag_sb_dienstlicher_schluesselverlust
    const EXTERNAL_DOG_CARETAKER = 'external_dog_caretaker'; //Vertrag_fremdhuetung_hunde
    const DAMAGES_FROM_THIRDPARTY_HORSE_CARING = 'damages_from_thirdparty_horse_caring'; //Vertrag_schaeden_durch_hueten_fremder_pferde
    const SINGLE_PARENT = 'single_parent'; //Vertrag_alleinstehendes_elternteil
    const COINSURED_AUPAIR = 'co-insured_au-pair'; //Vertrag_mitversicherung_eines_au_pair
    const DAMAGES_FROM_CONDUCTING_NANNY_ACTIVITIES = 'damages_from_conducting_nanny_activities'; //Vertrag_schaeden_bei_der_taetigkeit_als_tagesmutter
    const VOLUNTARY_WORK = 'voluntary_work'; //Vertrag_ehrenamtliche_taetigkeiten
    const DAMAGES_FROM_MODEL_VEHICLES = 'damages_from_model_vehicles'; //Vertrag_schaeden_durch_modellfahrzeuge
    const BICYCLE_OWNING_USAGE = 'bicycle_owning_usage'; //Vertrag_fahrraeder_besitz_gebrauch
    const ONE_FAMILY_HOUSE_OCCUPIED_BY_OWNER = 'one_family_house_occupied_by_owner'; //Vertrag_einfamilienhaus_selbstgenutzt
    const LEASING_APARTMENT_IN_INHABITED_HOUSE = 'leasing_apartment_in_inhabited_house'; //Vertrag_vermieten_einer_wohnung_im_bewohnten_haus
    const VACATION_APARTMENT_OCCUPIED_BY_OWNER = 'vacation_apartment_occupied_by_owner'; //Vertrag_ferienwohnung_selbstgenutzt
    const BUILDING_PROJECTS_UNTIL_150000_EUROS = 'building_projects_until_150000_euros'; //Vertrag_bauvorhaben_bis_150k_euro
    const INSURANCE_SCOPE_EUROPE = 'insurance_scope_europe'; //Vertrag_geltungsbereich_europa
    const INSURANCE_SCOPE_WORLD = 'insurance_scope_world'; //Vertrag_geltungsbereich_welt
    const DOMESTIC_DRAINWATER_1 = 'domestic_drainwater_1'; //Vertrag_haeusliche_abwaesser_1
    const GRADUAL_DAMAGES = 'gradual_damages'; //Vertrag_allmaehlichkeitsschaeden
    const OIL_ABOVE_GROUND = 'oil_above_ground'; //Vertrag_oel_oberirdisch
    const OIL_BENEATH_GROUND = 'oil_beneath_ground'; //Vertrag_oel_unterirdisch
    const DAMAGES_TO_LEASED_PROPERTY_UNTIL_1000_EUROS = 'damages_to_leased_property_until_1000_euros'; //Vertrag_schaeden_an_gemieteten_sachen_1k
    const OWNRISK_LEASED_PROPERTY = 'ownrisk_leased_property'; //Vertrag_sb_gemietet
    const DAMAGE_FROM_COMPLACENCY_OWNRISK_100EUROS = 'damage_from_complacency_ownrisk_100euros'; //Vertrag_gefaelligkeitsschaeden_sb100
    const OWNRISK_DAMAGE_FROM_COMPLACENCY = 'ownrisk_damage_from_complacency'; //Vertrag_sb_gefaelligkeitsschaeden

    const SALUTATION = 'salutation'; //Vertrag_Kunde_anrede
    const PROFESSION = 'profession'; //Vertrag_Kunde_beruf_person
    const FAX = 'fax'; //Vertrag_Kunde_fax_privat
    const KNIP_HTTP_CODE = 'knip_http_code';

    //Moneyview2
    const BASE_POLICY_COSTS = 'base_policy_costs';
    const POLICY_COSTS = 'policy_costs';
    const PREMIUM_BASE = 'premium_base';
    const INSURANCE_TAX = 'insurance_tax';
    const COVERAGE_CAR_SUPPORT = 'coverage_car_support';



    /**
     * Output type mapping
     */

    /**
     * @param bool $typeInfo
     */
    public function info($typeInfo = false);


    /**
     * Returns source name, i.e. rolls, moneyview
     */
    public function serviceProvider();

}
