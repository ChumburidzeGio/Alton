<?php
/**
 * User: Roeland Werring
 * Date: 17/02/15
 * Time: 11:50
 */

$app->bind('resource.carinsurance', App\Resources\Rolls\CarInsurance::class);
$app->bind('resource.carinsurance.inshared', App\Resources\Inshared\CarInsurance::class);
$app->bind('resource.carinsurance.moneyview', App\Resources\Moneyview\CarInsurance::class);
$app->bind('resource.carinsurance.paston', App\Resources\Paston\CarInsurance::class);
$app->bind('resource.carinsurance.meeusccs', App\Resources\MeeusCCS\CarInsurance::class);

$app->bind('resource.contentsinsurance', App\Resources\Moneyview\ContentsInsurance::class);
$app->bind('resource.contentsinsurance.rolls', App\Resources\Rolls\ContentsInsurance::class);

$app->bind('resource.nedvol', App\Resources\Nedvol\NedvolService::class);

$app->bind('resource.disabilityinsurance', App\Resources\Rolls\DisabilityInsurance::class);

$app->bind('resource.energy', App\Resources\Easyswitch\Energy::class);

$app->bind('resource.general', App\Resources\General\General::class);

$app->bind('resource.homeinsurance', App\Resources\Moneyview\HomeInsurance::class);
$app->bind('resource.homeinsurance.moneyview', App\Resources\Moneyview\HomeInsurance::class);
$app->bind('resource.homeinsurance.rolls', App\Resources\Rolls\HomeInsurance::class);

$app->bind('resource.homestudy', App\Resources\Zanox\HomeStudy::class);

$app->bind('resource.ideal', App\Resources\Buckaroo\Ideal::class);

//parking
$app->bind('resource.ipparking', App\Resources\Ipparking\Parking::class);
$app->bind('resource.parkingpro', App\Resources\Parkingpro\Parking::class);
$app->bind('resource.schipholparking', App\Resources\Schipholparking\Parking::class);
$app->bind('resource.parkandfly', App\Resources\Parkandfly\Parking::class);
$app->bind('resource.quickparking', App\Resources\Quickparking\Parking::class);
$app->bind('resource.parkingci', App\Resources\Parkingci\Parking::class);

$app->bind('resource.parking2', App\Resources\Parking2\Parking::class);
$app->bind('resource.travel', App\Resources\Travel\Travel::class);

$app->bind('resource.legalexpensesinsurance', App\Resources\Moneyview\LegalExpensesInsurance::class);
$app->bind('resource.liabilityinsurance', App\Resources\Moneyview\LiabilityInsurance::class);
$app->bind('resource.liabilityinsurance.rolls', App\Resources\Rolls\LiabilityInsurance::class);

$app->bind('resource.mobile1', App\Resources\Telecombinatie\Mobile::class);
$app->bind('resource.motorcycleinsurance', App\Resources\Rolls\MotorcycleInsurance::class);

$app->bind('resource.shorttermtravelinsurance', App\Resources\Moneyview\ShorttermTravelInsurance::class);

$app->bind('resource.simonly1', App\Resources\Zanox\SimOnly::class);
$app->bind('resource.simonly2', App\Resources\Combiner\SimOnly::class);
$app->bind('resource.simonly3', App\Resources\Telecombinatie\SimOnly::class);
$app->bind('resource.simonly4', App\Resources\Combiner\SimOnly::class);
$app->bind('resource.simonly5', App\Resources\Stat\SimOnly::class);
$app->bind('resource.simonly6', App\Resources\Daisycon\SimOnly::class);
$app->bind('resource.simonly7', App\Resources\Combiner\SimOnlyAffiliate::class);

$app->bind('resource.travelinsurance', App\Resources\Moneyview\TravelInsurance::class);

$app->bind('resource.vaninsurance', App\Resources\Rolls\VanInsurance::class);

$app->bind('resource.foodbox1', App\Resources\Csv\Foodbox::class);
$app->bind('resource.shoes1', App\Resources\Zanox\Shoes::class);


//old Zorgweb
$app->bind('resource.healthcare2', App\Resources\Zorgweb\HealthcareLegacy::class);
//new style
$app->bind('resource.healthcare', App\Resources\Zorgweb\Healthcare::class);
// new healthcare (2017+), used in 'product.healthcare'
$app->bind('resource.healthcaredb', App\Resources\Healthcare\Healthcare::class);
$app->bind('resource.vvghealthcarech', App\Resources\VVGHealthcarech\VVGHealthcarech::class);

$app->bind('resource.knip', App\Resources\Knip\Knip::class);

$app->bind('resource.iak', App\Resources\Nearshoring\Iak::class);

$app->bind('resource.doginsurance1', App\Resources\Csv\Doginsurance::class);

$app->bind('resource.healthcarech', App\Resources\Healthcarech\Healthcarech::class);

//Blaudirekt & Moneyview
$app->bind('resource.blaudirekt', App\Resources\Blaudirekt\MethodMap::class);
$app->bind('resource.moneyview2', App\Resources\Moneyview2\MethodMap::class);

$app->bind('resource.rdw', App\Resources\Rdw\CarData::class);
$app->bind('resource.isa', App\Resources\Isa\CarData::class);

$app->bind('resource.payment.multisafepay', App\Resources\Multisafepay\Payment::class);

$app->bind('resource.geocoding.google', App\Resources\Google\Geocoding\Geocoding::class);
$app->bind('resource.api.google', App\Resources\Google\Api\Api::class);

$app->bind('resource.travel.rome2rio', App\Resources\Rome2Rio\Travel\Travel::class);


$app->bind('resource.taxitender', App\Resources\Taxitender\Taxitender::class);
$app->bind('resource.taxiboeken', App\Resources\Taxiboeken\Taxiboeken::class);
$app->bind('resource.infofolio', App\Resources\Infofolio\Infofolio::class);

$app->bind('resource.postcodeapi', App\Resources\PostcodeApi\Postcode::class);

$app->bind('resource.inrix', App\Resources\Inrix\Inrix::class);

//Elipslife
$app->bind('resource.elipslife', App\Resources\Elipslife\Elipslife::class);