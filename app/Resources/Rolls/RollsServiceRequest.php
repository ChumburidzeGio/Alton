<?php
/**
 * User: Roeland Werring
 * Date: 13/03/15
 * Time: 13:01
 *
 */

namespace App\Resources\Rolls;

use App\Resources\AbstractServiceRequest;

class RollsServiceRequest extends AbstractServiceRequest
{
    public $fieldMapping = [
        'Naam'                  => self::TITLE,
        'Id'                    => self::RESOURCE_ID,
        'Productid'             => self::RESOURCE_ID,
        'Productnaam'           => self::TITLE,
        'PremiebedragInCenten'  => self::PRICE_DEFAULT,
        'PoliskostenInCenten'   => self::PRICE_INITIAL,
        'Eigenrisico'           => self::OWN_RISK,
        'Acceptatie'            => self::ACTIVE,
        'Score'                 => self::SCORE,
        'Totaalscore'           => self::TOTAL_RATING,
        'Fee'                   => self::PRICE_FEE,
        'Keynaam'               => self::NAME,
        //extra new fields


        //Car specific
        'Gewicht'               => self::WEIGHT,
        'Aantaldeuren'          => self::AMOUNT_OF_DOORS,
        'Vermogen'              => self::POWER,
        'Dagwaardeinclbtw'      => self::DAILY_VALUE,
        'Rollsnieuwwaardebruto' => self::REPLACEMENT_VALUE,
        'Kentekengewicht'       => self::LICENSEPLATE_WEIGHT,

        // modified here, fuel type stored in this var for some fucked up reason
        'Kentekenbrandstofid'   => self::FUEL_TYPE_ID,

        'Kentekenkleur'       => self::LICENSEPLATE_COLOR,
        'Turbo'               => self::TURBO,
        'Koetswerk'           => self::COACHWORK_TYPE_ID,
        //'Brandstof'             => self::FUEL_TYPE_ID,
        'Transmissie'         => self::TRANSMISSION_ID,
        'Beveiligingsklasse'  => self::SECURITY_CLASS_ID,
        'Cylinders'           => self::CYLINDERS,
        'Cilinderinhoud'      => self::CYLINDER_VOLUME,
        'Aantalzitplaatsen'   => self::AMOUNT_OF_SEATS,
        'Co2emissie'          => self::CO2_EMISSION,
        'Topsnelheid'         => self::TOP_SPEED,
        'Acceleratie'         => self::ACCELERATION,
        'Importauto'          => self::IMPORTED_CAR,
        'Energielabel'        => self::ENERGY_LABEL,
        //polis lines
        'Regels'              => self::ROWS,
        'Regel'               => self::ROW,
        'Cellen'              => self::COLS,
        'Cel'                 => self::COL,
        'Tekst'               => self::TEXT,
        'Style'               => self::STYLE,
        //ratings
        'Rubrieken'           => self::RATINGS,
        'Transmissies'        => self::TRANSMISSION_ID,
        'Koetswerken'         => self::COACHWORK_TYPE_ID,
        'Beveiligingsklasses' => self::SECURITY_CLASS_ID,
        'Label'               => self::LABEL,

        // Lists (car_option_list, van_option_list)
        'Brandstoffen'          => self::FUEL_TYPE,
        'Eigenrisicos'          => self::OWN_RISK,
        'Beroepen'              => self::OCCUPATION,
        'Ladingen'              => self::TRANSPORT_GOODS_TYPE,
        'Contractsduren'        => self::CONTRACT_DURATION,
        'Werkgevers'            => self::EMPLOYER,
        'Gezinssituaties'       => self::FAMILY_COMPOSITION,
        'Opleidingsniveaus'     => self::EDUCATION_LEVEL,
        'Branches'              => self::BRANCH,
        // Known but not mapped lists:
        //'Poiverzekerdebedragenbijoverlijden' => '',
        //'Poiverzekerdebedragenbijblijvendeinvaliditeit' => '',
        //'Poiverzekerdebedragengeneeskundigebehandeling' => '',
        //'Sviverzekerdebedragen' => '',

        // Lists (houses)
        'Bouwaardenmuur'        => self::HOUSE_WALL_MATERIAL,
        'Bouwaardendak'         => self::HOUSE_ROOF_MATERIAL,
        'Dakenconstructies'     => self::HOUSE_ROOF_CONSTRUCTION,
        'Geslotenconstructiesonderrietendak' => self::HOUSE_THATCHEDROOF_CLOSED,
        'Verdiepingsvloeren'    => self::HOUSE_ABOVEGROUND_FLOOR_MATERIAL,
        'Beganegrondvloeren'    => self::HOUSE_GROUND_FLOOR_MATERIAL,
        'Soortenwoning'         => self::HOUSE_TYPE,
        'Belendingen'           => self::HOUSE_ABUTMENT,
        'Bestemmingen'          => self::HOUSE_USAGE,
        'Afwerkingengevel'      => self::HOUSE_FACADE_TYPE,
        'Afwerkingenkeuken'     => self::HOUSE_KITCHEN_TYPE,
        'Afwerkingenbadkamertoilet' => self::HOUSE_BATHROOM_TYPE,
        'Afwerkingenwoonkamer'  => self::HOUSE_LIVINGROOM_TYPE,
        'Functiesbijgebouw'     => self::HOUSE_ANNEX_FUNCTION,
        'Bouwaardenbijgebouw'   => self::HOUSE_ANNEX_MATERIAL,
        'Funderingen'           => self::HOUSE_FOUNDATION,
        'Luxegradengebouw'      => self::HOUSE_LUXURY_LEVEL,
        'Herkomsteninhoud'      => self::HOUSE_VOLUME_SOURCE,
        'Gezinssamenstellingen' => self::FAMILY_COMPOSITION,


        // Lists liability
        'Verzekerdebedragen'    => self::INSURED_AMOUNT,

        // ContentsInsurance (Inboedel)
        'Premiebedragincenten'  => self::PRICE_DEFAULT,
        'Poliskostenincenten'   => self::PRICE_INITIAL,
        'Verzekerdesom'         => self::INSURED_AMOUNT,
        'Eigenrisicostormschade'=> self::OWN_RISK_STORM_DAMAGE,
        'Eigenrisicoonheil'     => self::OWN_RISK_DISASTER,
        'Eigenrisicodiefstal'   => self::OWN_RISK_THEFT,

        // Liabilityinsurance (AVP)
        'Eigenrisicooverall'            => self::OWN_RISK,
        'Eigenrisicobijkinderschaden'   => self::OWN_RISK_CHILDREN,
    ];


    /**
     * output filter mapping
     * @var array
     */
    protected $filterMapping = [
        self::PRICE_DEFAULT => 'filterCentToEuro',
        self::PRICE_ACTUAL  => 'filterCentToEuro',
        self::PRICE_INITIAL => 'filterCentToEuro',
        //        self::LEGALEXPENSES                => 'filterCentToEuro',
        //        self::NO_CLAIM                     => 'filterCentToEuro',
        //        self::PASSENGER_INSURANCE_ACCIDENT => 'filterCentToEuro',
        //        self::PASSENGER_INSURANCE_DAMAGE   => 'filterCentToEuro',
        self::STYLE         => 'filterToLowercase',
        self::SCORE        => 'filterDivide1000'
    ];

    protected $serviceProvider = 'rolls';

}
