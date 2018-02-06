<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Zorgweb\Methods;

use App\Interfaces\ResourceInterface;
use Config;
use Log;

class ContractFields extends ZorgwebAbstractRequest
{

    //push fucker

    protected $cacheType = 'internal';
    protected $cacheDays = false;
    private $contractId;

    private $backupUrl;

    private $launchBackup = false;

    protected $arguments = [
        ResourceInterface::CONTRACT_ID      => [
            'rules'   => 'required',
            'example' => 'H52244094',
        ],
        ResourceInterface::CREATE_ARGUMENTS => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ],
        ResourceInterface::DUMP_FIELDS      => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ],
        ResourceInterface::COLLECTIVITY_ID  => [
            'rules' => 'number',
        ],
    ];


    protected $mapping = [
        'hoofdadres.postcode'   => ResourceInterface::POSTAL_CODE,
        'hoofdadres.huisnummer' => ResourceInterface::HOUSE_NUMBER,
        'hoofdadres.straat'     => ResourceInterface::STREET,
        'hoofdadres.woonplaats' => ResourceInterface::CITY,
    ];

    protected $collectivityFields = [
        99999 => [
            "zorgvragenMap.zorgvragen_dela_collectiviteit_99999" => [
                "label" => "Ik verklaar dat ik de gegevens naar waarheid heb ingevuld en geef toestemming voor automatische incassering van de premie. Ook geef ik toestemming om mijn gegevens uit te wisselen tussen IAK, DELA en Stichting Coulance Fonds DELA leden.",
                "rules" => self::VALIDATION_ACTIVATE
            ]
        ]
    ];

    protected $additionalContractFields = [
        // ONVZ Optifit, Topfit, Superfit
        'H99608943A96881979,H99608943A96881981,H99608943A96881982,H99608943A97467577,H99608943A97468234,H99608943A97468891,H99608943A97469548,H99608943A97470272,H99608943A97470996,H99608943A97471720,H99608943A99608943'                                                                                                                                                                                                                                                        => [
            'zorgvragenMap.vraag_huidige_aamvullende_verzekering' => [
                'label' => "Huidige aanvullende verzekering",
                'rules' => 'choice:vvaaTop=VvAA Zorgverzekering Top,aevitaeExcellent=Aevitae Excellent,averoExcellent=Avero Achmea Excellent,averoNedascoExcellent=Avero Achmea (Nedasco) Excellent,turienExcellent=Turien Co (Avero Achmea) Excellent,none=Geen van de bovenstaande'
            ],

            'zorgvragenMap.vraag_heeft_u_een_vergelijkbare_aanvullende_verzekering_bij_uw_huidige_verzekeraar' => [
                'label' => "Heeft u een vergelijkbare aanvullende verzekering bij uw huidige verzekeraar?",
                'rules' => self::VALIDATION_NEE_JA
            ],

            'zorgvragenMap.vraag_twee_jaar_andere_arts_behandelaar'                                     => [
                'label' => "Is iemand de afgelopen twee jaar bij een arts of andere behandelaar geweest?",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar'                               => [
                'label' => "Bij wie?",
                'rules' => 'choice:artsAlternatief=Bij een arts of alternatief behandelaar.,physio=Op het gebied van fysiotherapie, manueel therapie of Mensendieck/Cesar oefentherapie.,chiropracter=Op het gebied van chiropractie, podotherapie, huidtherapie of osteopathie.'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_alternatief_soort_arts'        => [
                'label' => "Wat voor arts of alternatief behandelaar betreft het?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_alternatief_soort_behandeling' => [
                'label' => "Wat voor soort behandeling betreft het?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_alternatief_waarvoor'          => [
                'label' => "Waarvoor onderging de persoon de behandeling?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_alternatief_wanneer'           => [
                'label' => "Wanneer onderging de persoon de behandeling?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_alternatief_aantal'            => [
                'label' => "Noem het aantal behandelingen of afspraken bij de zorgverlener.",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_fysio_wanneer'                 => [
                'label' => "Wanneer was de laatste behandeling?",
                'rules' => 'choice:6=Langer dan 6 maanden geleden,3=Tussen de 3 en 6 maanden geleden,0=Tussen de 0 en 3 maanden geleden'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_fysio_soort_behandelaar'       => [
                'label' => "Welk soort behandelaar betreft het?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_fysio_soort_behandeling'       => [
                'label' => "Wat voor soort behandeling betreft het?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_fysio_waarvoor'                => [
                'label' => "Waarvoor onderging de persoon de behandeling?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_fysio_aantal'                  => [
                'label' => "Noem het aantal behandelingen of afspraken bij de zorgverlener.",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_fysio_behandelaar'             => [
                'label' => 'Om welke behandeling gaat het?',
                'rules' => 'choice:chiropractie=Chiropractie,podotherapie=Podotherapie,huidtherapie=Huidtherapie,osteopathie=Osteopathie'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_chiro_wanneer'                 => [
                'label' => 'Wanneer was de laatste behandeling?',
                'rules' => 'choice:6=Langer dan 6 maanden geleden,3=Tussen de 6 en 3 maanden geleden,0=Tussen de 0 en 3 maanden geleden'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_chiro_soort'                   => [
                'label' => "Wat voor soort behandelaar en behandeling betreft het?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_chiro_waarvoor'                => [
                'label' => "Waarvoor onderging de persoon de behandeling?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_chiro_aantal'                  => [
                'label' => "Noem het aantal behandelingen of afspraken bij de zorgverlener.",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_podo_wanneer'                  => [
                'label' => 'Wanneer was de laatste behandeling?',
                'rules' => 'choice:6=Langer dan 6 maanden geleden,3=Tussen de 6 en 3 maanden geleden,0=Tussen de 0 en 3 maanden geleden'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_podo_waarvoor'                 => [
                'label' => 'Waarvoor onderging de persoon de behandeling?',
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_podo_aantal'                   => [
                'label' => 'Noem het aantal behandelingen of afspraken bij de zorgverlener.',
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_huid_wanneer'                  => [
                'label' => 'Wanneer was de laatste behandeling?',
                'rules' => 'choice:6=Langer dan 6 maanden geleden,3=Tussen de 6 en 3 maanden geleden,0=Tussen de 0 en 3 maanden geleden'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_huid_waarvoor'                 => [
                'label' => 'Waarvoor onderging de persoon de behandeling?',
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_huid_aantal'                   => [
                'label' => 'Noem het aantal behandelingen of afspraken bij de zorgverlener.',
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_osteo_wanneer'                 => [
                'label' => 'Wanneer was de laatste behandeling?',
                'rules' => 'choice:6=Langer dan 6 maanden geleden,3=Tussen de 6 en 3 maanden geleden,0=Tussen de 0 en 3 maanden geleden'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_osteo_waarvoor'                => [
                'label' => 'Waarvoor onderging de persoon de behandeling?',
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_twee_jaar_andere_arts_behandelaar_osteo_aantal'                  => [
                'label' => 'Noem het aantal behandelingen of afspraken bij de zorgverlener.',
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_gebruik_medicijnen'                                                    => [
                'label' => "Gebruikt iemand medicijnen? Het kan om één soort medicijn gaan, of om verschillende soorten.",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.ext_antword_gebruik_hulpmiddelen'                                            => [
                'label' => "Om welke hulpmiddelen gaat het? Het kan om meer dan E\u00e9E\u00e9n hulpmiddel gaan.",
                'rules' => 'in:bril,hoortoestel,haarstukje,steunzolen,prothese,overige'
            ],
            'zorgvragenMap.ext_antword_ext_antword_hoortoestel'                                         => [
                'label' => "Hoe oud is je hoortoestel?",
                'rules' => 'in:1,2,3,4,5'
            ],
            'zorgvragenMap.ext_antword_ext_antword_prothese'                                            => [
                'label' => "Om welke prothese gaat het?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_ext_antword_overige'                                             => [
                'label' => "Om welk hulpmiddel gaat het?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_gebruik_medecijnen_a'                                            => [
                'label' => "Welke medecijnen zijn dit?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_gebruik_medecijnen_b'                                            => [
                'label' => "Waarvoor wordt het medecijn gebruikt?",
                'rules' => 'string'
            ],
            'zorgvragenMap.ext_antword_gebruik_medecijnen_c'                                            => [
                'label' => "Wat is de dosering?",
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_gebruik_hulpmiddelen'                                                  => [
                'label' => "Draagt iemand een bril, contactlenzen, hoortoestel, pruik, haarstukje, steunzolen of prothese? Of een hulpmiddel dat niet is genoemd?",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.vraag_behandeling_orthodontist'                                              => [
                'label' => "Is iemand onder behandeling bij een orthodontist? Bijvoorbeeld omdat hij of zij een beugel voor tanden of kiezen draagt. Of verwacht iemand zo'n behandeling binnen één jaar?",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.ext_antword_behandeling_orthodontist'                                        => [
                'label' => "In welk stadium is de behandeling of wanneer start de behandeling?",
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_controle_jaar'                                                         => [
                'label' => "Is er voor iemand een afspraak, behandeling, onderzoek of controle nodig of gewenst, of is dit binnen één jaar te verwachten?",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.ext_antword_controle_jaar'                                                   => [
                'label' => "Voor welke klachten, aandoening of ziekte en bij welk soort behandelaar?",
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_vrij_van_klachten'                                                     => [
                'label' => "Zijn alle personen lichamelijk en geestelijk gezond en vrij van klachten?",
                'rules' => self::VALIDATION_JA_NEE
            ],
            'zorgvragenMap.ext_antword_vrij_van_klachten'                                               => [
                'label' => "Geef aan om welke klachten, aandoening of ziekte het gaat.",
                'rules' => 'string'
            ],
        ],
        'H99608943A96881977,H99608943A96881978,H99608943A96881989,H99608943A97463807,H99608943A97464213,H99608943A97465072,H99608943A97465525,H99608943A97466511,H99608943A97467044,H99608943A97468234,H99608943A97468891,H99608943A97470272,H99608943A97470996,H99608943A97471720'                                                                                                                                                                                               => [

            'zorgvragenMap.vraag_heeft_u_de_meest_uitgebreide_tandarts_verzekering_bij_uw_huidige_verzekeraar' => [
                'label' => "Heeft u de meest uitgebreide tandarts verzekering bij uw huidige verzekeraar?",
                'rules' => self::VALIDATION_NEE_JA
            ],


            'zorgvragenMap.vraag_behandeling_tandarts_aanbrengen'          => [
                'label' => "Heeft iemand een behandeling bij een tandarts gehad voor het aanbrengen van een facing, kroon, inlay, brug, implantaat of (gedeeltelijke) prothese?",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.ext_antword_behandeling_tandarts_aanbrengen'    => [
                'label' => "Om welke behandeling gaat het? En om hoeveel tanden en/of kiezen",
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_ontbrekende_tanden'                       => [
                'label' => "Heeft iemand ontbrekende tanden of kiezen? (met uitzondering van verstandskiezen)",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.ext_antword_ontbrekende_tanden'                 => [
                'label' => "Om hoeveel tanden of hoeveel kiezen gaat het?",
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_behandeling_tandarts_binnen_1_jaar'       => [
                'label' => "Verwacht iemand binnen 1 jaar een behandeling bij een tandarts voor het aanbrengen van een facing, kroon, inlay, brug, implantaat of (gedeeltelijke) prothese?",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.ext_antword_behandeling_tandarts_binnen_1_jaar' => [
                'label' => "Om welke behandeling gaat het? En om hoeveel tanden en/of kiezen.",
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_behandeling_tandvlees'                    => [
                'label' => "Heeft iemand een behandeling aan het tandvlees gehad? Of verwacht iemand een behandeling aan het tandvlees?",
                'rules' => self::VALIDATION_NEE_JA
            ],
            'zorgvragenMap.ext_antword_behandeling_tandvlees'              => [
                'label' => "Wat zijn de klachten?",
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_vrij_van_klachten_tand'                   => [
                'label' => "Is iedereen vrij van klachten als het gaat om tanden, kiezen en tandvlees?",
                'rules' => self::VALIDATION_JA_NEE
            ],
            'zorgvragenMap.ext_antword_vrij_van_klachten_tand'             => [
                'label' => "Geef aan wat nog niet aan de orde is gekomen. Wat zijn de klachten?",
                'rules' => 'string'
            ],
        ],
        'H99611907A97472710,H99611907A97472715,H99611907A97492600,H99611907A97493179,H99611907A97494263,H99611907A97494876,H99611907A97495997,H99611907A97496613,H96919009A97472710,H96919009A97472715,H96919009A97492600,H96919009A97493179,H96919009A97494263,H96919009A97494876,H96919009A97495997,H96919009A97496613,H96919012A97472710,H96919012A97472715,H96919012A97492600,H96919012A97493179,H96919012A97494263,H96919012A97494876,H96919012A97495997,H96919012A97496613' => [


            'zorgvragenMap.vraag_heeft_u_de_meest_uitgebreide_tandarts_verzekering_bij_uw_huidige_verzekeraar' => [
                'label' => "Heeft u de meest uitgebreide tandarts verzekering bij uw huidige verzekeraar?",
                'rules' => self::VALIDATION_NEE_JA
            ],


            'zorgvragenMap.vraag_verwachten_behandelingen'       => [
                'label' => "Verwachten de te verzekeren personen binnen nu en twee jaar één of meer van de volgende behandelingen? Of zijn de te verzekeren personen gestart met één of meer behandelingen voor: - vervanging van 6 of meer vullingen - twee of meer kronen - één of meer brug(gen) - één of meer implanta(a)t(en) - een gedeeltelijke gebitsprothese (plaatje of frame) - een uitgebreide tandvleesbehandeling (parodontale behandeling) - voor orthodontie",
                'rules' => self::VALIDATION_JA_NEE
            ],
            'zorgvragenMap.ext_antword_verwachten_behandelingen' => [
                'label' => "Ondergaat bovengenoemde persoon momenteel een behandeling of verwacht bovengenoemde persoon binnen nu en twee jaar een behandeling? Vul alleen JA of alleen NEE in.",
                'rules' => 'string'
            ],
            'zorgvragenMap.vraag_controle_gemist'                => [
                'label' => "Heeft u of één van de te verzekeren personen van 18 jaar en ouder de afgelopen twee jaar een jaarlijkse controle bij de tandarts gemist?",
                'rules' => self::VALIDATION_JA_NEE
            ],
            'zorgvragenMap.ext_antword_controle_gemist'          => [
                'label' => "Heeft bovengenoemde persoon een jaarlijkse tandcontrole gemist? Vul alleen JA of alleen NEE in.",
                'rules' => 'string'
            ],
        ],
        'H96962680A96962681,H96962680A97506540,H96962680A97514593,H96962680A97523406,H96962680A97535054,H96962680A97548265,H99617835A96962681,H99617835A97506540,H99617835A97514593,H99617835A97523406,H99617835A97535054,H99617835A97548265,H96962691A96962681,H96962691A96962706,H96962691A97506136,H96962691A97506540,H96962691A97514177,H96962691A97514593,H96962691A97522857,H96962691A97523406,H96962691A97534428,H96962691A97535054,H96962691A97547614,H96962691A97548265' => [


            'zorgvragenMap.vraag_heeft_u_de_meest_uitgebreide_tandarts_verzekering_bij_uw_huidige_verzekeraar' => [
                'label' => "Heeft u de meest uitgebreide tandarts verzekering bij uw huidige verzekeraar?",
                'rules' => self::VALIDATION_NEE_JA
            ],


            'zorgvragenMap.heeft_u_tandverzekering'                         => [
                'label' => "Heeft u op dit moment een tandartsverzekering?",
                'rules' => self::VALIDATION_JA_NEE
            ],
            'zorgvragenMap.vraag_mist_u_tanden'                             => [
                'label' => "Mist u tanden/kiezen?",
                'rules' => self::VALIDATION_MIST_U_TANDEN
            ],
            'zorgvragenMap.ext_antword_mist_u_tanden'                       => [
                'label' => "Ontbreken deze door een beugel of ruimtegebrek?",
                'rules' => self::VALIDATION_JA_NEE
            ],
            'zorgvragenMap.ext_antword_ext_antword_mist_u_tanden'           => [
                'label' => "Zijn deze vervangen door een kroon of brug?",
                'rules' => self::VALIDATION_JA_NEE
            ],
            'zorgvragenMap.vraag_tandvoorzieningen'                         => [
                'label' => "Heeft u één of meer van deze tandartsvoorzieningen?",
                'rules' => self::VALIDATION_TANDVOORZIENINGEN
            ],
            'zorgvragenMap.ext_antword_tandvoorzieningen_a'                 => [
                'label' => "Hoeveel kronen en\/of stifttanden?",
                'rules' => self::VALIDATION_HOEVEEL_KRONEN
            ],
            'zorgvragenMap.ext_antword_tandvoorzieningen_b'                 => [
                'label' => "Hoeveel zijn er ouder dan 10 jaar?",
                'rules' => self::VALIDATION_HOEVEEL_OUD_KRONEN
            ],
            'zorgvragenMap.ext_antword_tandvoorzieningen_c'                 => [
                'label' => "Hoeveel bruggen?",
                'rules' => self::VALIDATION_HOEVEEL_BRUGGEN
            ],
            'zorgvragenMap.vraag_wortelkanaalbehandeling'                   => [
                'label' => "Heeft u wel eens een wortelkanaalbehandeling of een tandvleesbehandeling gehad?",
                'rules' => self::VALIDATION_WORTELKANAALBEHANDELING
            ],
            'zorgvragenMap.ext_antword_wortelkanaalbehandeling'             => [
                'label' => "Waar bent u geholpen?",
                'rules' => self::VALIDATION_WAAR_GEHOLPEN
            ],
            'zorgvragenMap.ext_antword_ext_antword_wortelkanaalbehandeling' => [
                'label' => "Was deze behandeling preventief?",
                'rules' => self::VALIDATION_JA_NEE
            ],
            'zorgvragenMap.vraag_volgende_behandelingen'                    => [
                'label' => "Verwacht u één of meer van de volgende behandelingen?",
                'rules' => self::VALIDATION_TANDARTS_BEHANDELINGEN
            ],
        ],
        self::DEFAULT_CONTRACT                                                                                                                                                                                                                                                                                                                                                                                                                                                    => [
            'reason_different_start_date'                                                  => [
                //{"net_18_jaar":"Ik heb de leeftijd van 18 jaar bereikt","echtscheiding":"In verband met echtscheiding","immigratie":"Ik kom uit het buitenland en dien me nu (weer) verplicht te verzekeren","exmilitair":"Ik kom uit een militaire dienstverband en dien me nu weer te verzekeren","niet_verzekerd":"Ik ben momenteel niet verzekerd"}
                'rules' => 'choice:net_18_jaar=18 jaar geworden,echtscheiding=Echtscheiding,exmilitair=Ex-militair geworden,immigratie=Overstap vanuit het buitenland,niet_verzekerd=Momenteel onverzekerd'
            ],
            'verzekeringsgegevens.betalingstermijn'                                        => [
                'rules' => 'in:Per maand,Per kwartaal,Per half jaar,Per jaar'
            ],
            'verzekeringsgegevens.betalingsmethode'                                        => [
                'rules' => 'in:Automatisch incasso,Acceptgiro,Digitale nota'
            ],
            ResourceInterface::AGREE_DIGITAL_DISPATCH                                      => [
                'rules' => self::VALIDATION_BOOLEAN,
            ],
            ResourceInterface::HOUSE_NUMBER_SUFFIX                                         => [
                'rules'   => 'string',
                'example' => '1E'
            ],
            'currently_insured'                                                            => [
                'rules' => self::VALIDATION_BOOLEAN
            ],
            'agree_transfer_account'                                                       => [
                'rules' => self::VALIDATION_BOOLEAN
            ],
            'account_number'                                                               => [
                'rules' => 'string'
            ],
            'aanvrager.zorgvragenMap.hebben_alle_gezinsleden_de_Nederlandse_nationaliteit' => [
                'rules' => self::VALIDATION_BOOLEAN
            ],
        ]
    ];

    protected $conditionalContractFields = [
        'Menzis' => [
            'zorgvragenMap.zorgvragen_Menzis_incasso'    => [
                'rules' => self::VALIDATION_BOOLEAN
            ],
            'zorgvragenMap.zorgvragen_Menzis_mededeling' => [
                'rules' => self::VALIDATION_BOOLEAN
            ],
        ],
        'Anderzorg' => [
            'zorgvragenMap.zorgvragen_Anderzorg_incasso'    => [
                'rules' => self::VALIDATION_BOOLEAN
            ],
            'zorgvragenMap.zorgvragen_Anderzorg_mededeling' => [
                'rules' => self::VALIDATION_BOOLEAN
            ],
        ]
    ];

    const MAX_CHILD = 8;

    const DEFAULT_CONTRACT = 'DEFAULT';

    public function __construct()
    {
        parent::__construct('/aanvraagformulier/voor_advies_' . ((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.year')) . '/', 'get_no_auth');
        $this->strictStandardFields = false;
    }


    public function setParams(Array $params)
    {
        /**
         * BACKUP URL! If an error occured, use this hardcoded ID to give some results. Currently CZ ID.
         */
        $this->backupUrl = $this->basicAuthService['method_url'] . 'H59876393';

        $this->basicAuthService['method_url'] .= $params[ResourceInterface::CONTRACT_ID];
        $this->contractId = $params[ResourceInterface::CONTRACT_ID];
        unset($params[ResourceInterface::CONTRACT_ID]);
        cw('set Params');
        cw($params);
        parent::setParams($params);
    }


    public function getResult()
    {
        $data = $this->result;
        if($this->params[ResourceInterface::DUMP_FIELDS]){
            return $data;
        }

        $vragen = $data['vragen'];
        return $this->generateArguments($vragen, $this->params['create_arguments']);
    }

    public function executeFunction()
    {
        parent::executeFunction();
        if($this->getErrorString()){
            Log::warning($this->getErrorString());
            $this->setErrorString(null);
            $this->launchBackup = true;
        }
    }

    private function generateArguments($vragen, $createArguments)
    {
        $result = [];
        if($this->launchBackup){
            Log::warning('Could not connect to ZorgWeb! Using backup');
            $vragen = json_decode($this->vragenBackup, true);
        }
        foreach($vragen as $vraag){
            $type = $vraag['soort'];

            //skip
            if(in_array($type, ['REEKS', 'VALIDATIE', 'AUTO_ADRES', 'AUTO_REEKS'/*, 'MEERKEUZE'*/])){
                continue;
            }

            if (!isset($vraag['property']))
                throw new \Exception('Unknown Zorgweb vraag without `property`: '. json_encode($vraag));

            if(strpos($vraag['property'], 'kinderen[0]') !== false){
                for($ch = 0; $ch < self::MAX_CHILD; $ch ++){
                    $childKey = str_replace("0", $ch, $vraag['property']);
                    if( ! $createArguments){
                        $result[] = $childKey;
                        continue;
                    }
                    $result[$childKey] = $this->buildArgument($vraag);
                }
                continue;
            }
            $propertyName = isset($this->mapping[$vraag['property']]) ? $this->mapping[$vraag['property']] : $vraag['property'];
            if( ! $createArguments){
                $result[] = $propertyName;
                continue;
            }
            $result[$propertyName] = $this->buildArgument($vraag);

        }
        $result = $this->addCollectivityElements($result, $createArguments);
        //$result = $this->addAdditionalContractFields($result, $createArguments) + ["reason_different_start_date", "agree_digital_dispatch"];
        $result = $this->addConditionalContractFields($result, $createArguments);
        $result = $this->addAdditionalContractFields($result, $createArguments);
        return $result;
    }


    /**
     * Add fields based on a key existing in arguments. This is typically a company name, so to add something company wide
     *
     * @param $result
     * @param $createarguments
     *
     * @return array
     */
    private function addConditionalContractFields($result, $createarguments)
    {
        foreach(array_keys($this->conditionalContractFields) as $key){
            $searchStr = json_encode($result);
            if(str_contains($searchStr, $key)){
                if($createarguments){
                    foreach($this->conditionalContractFields[$key] as $name => $fields){
                        $result[$name] = $fields;
                    }
                    return $result;
                }
                foreach($this->conditionalContractFields[$key] as $name => $fields){
                    $result[] = $name;

                }
                return $result;
            }
        }
        return $result;
    }


    private function buildArgument($field)
    {
        $type     = $field['soort'];
        $argument = [];

        if(isset($field['vraag'])){
            $argument['label'] = $field['vraag'];
        }

        //        if(isset($field['waarde'])){
        //            $argument['default'] = $field['waarde'];
        //        }

        if(isset($field['uitleg'])){
            $argument['description'] = $field['uitleg'];
        }

        if(isset($field['hint'])){
            $argument['placeholder'] = $field['hint'];
        }

        $rules = [];


        if(isset($field['verplicht']) && $field['verplicht'] && (strpos($field['property'], 'kinderen') === false) && (strpos($field['property'], 'partner') === false) && (strpos($field['property'], 'zorgvragenMap') === false)){
            $rules[] = 'required';
        }

        if(in_array($type, ['MEERKEUZE', 'GESLACHT', 'JANEE'])){
            $choices = [];
            foreach($field['opties'] as $key => $val){
                if($field['vraag'] == 'Betalingstermijn'){
                    $arr = explode(':', $val);
                    $val = $arr[0];
                }
                $choices[] = $key . '=' . $val;
            }
            $rules[]           = 'choice:' . implode(',', $choices);
            $argument['rules'] = implode(' | ', $rules);
            return $argument;
        }


        if($type == 'GETAL'){
            $rules[] = 'number';
        }elseif(in_array($type, ['VERKLARING'])){
            $rules   = [];
            $rules[] = self::VALIDATION_ACTIVATE;
            //this is fucking filthy ass porn hack WTF!
            $argument['default'] = $argument['label'];
        }elseif(in_array($type, ['GEBOORTEDATUM', 'DATUM'])){
            $rules[] = 'date';
        }elseif(in_array($type, ['AUTO_IBAN'])){
            $rules[] = 'iban';
        }elseif(strpos($field['abstracteVraagId'], 'burgerservicenummer') !== false){
            $rules[] = 'bsn';
        }elseif(in_array($type, ['TELNR'])){
            $rules[] = 'phonenumber';
        }elseif(in_array($type, ['EMAIL'])){
            $rules[] = 'email';
        }elseif($field['abstracteVraagId'] == 'postcode'){
            $rules[] = 'postalcode';
        }else{
            $rules[] = 'string';
        }
        $argument['rules'] = implode(' | ', $rules);
        return $argument;
    }


    /**
     * Add elements only for this contract
     *
     * @param $result
     * @param $createarguments
     *
     * @return array
     * @internal param $collectivity
     *
     * @internal param $resultA
     */
    private function addAdditionalContractFields($result, $createarguments)
    {

        if($createarguments){
            foreach($this->additionalContractFields as $contractId => $fields){
                foreach($this->additionalContractFields[$contractId] as $label => $rules){
                    $result[$label] = $rules;
                }
            }
            return $result;
        }

        if($this->contractId){
            foreach($this->additionalContractFields as $multiKey => $settings){
                $contractIds = explode(',', $multiKey);
                if(in_array($this->contractId, $contractIds) || $multiKey == self::DEFAULT_CONTRACT){
                    foreach($settings as $label => $rules){
                        $result[] = $label;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Add elements only for this collectivity
     *
     * @param $result
     * @param $createarguments
     *
     * @return array
     * @internal param $collectivity
     *
     * @internal param $resultA
     */
    private function addCollectivityElements($result, $createarguments)
    {
        if($createarguments){
            foreach($this->collectivityFields as $collectivityId => $fields){
                foreach($this->collectivityFields[$collectivityId] as $label => $rules){
                    $result[$label] = $rules;
                }
            }
            return $result;
        }
        if(isset($this->params[ResourceInterface::COLLECTIVITY_ID], $this->collectivityFields[$this->params[ResourceInterface::COLLECTIVITY_ID]])){
            foreach($this->collectivityFields[$this->params[ResourceInterface::COLLECTIVITY_ID]] as $label => $rules){
                $result[] = $label;
            }
        }
        return $result;
    }

    private $vragenBackup = '[{"soort":"REEKS","kopje":"Gegevens aanvrager","abstracteVraagId":"aanvrager"},{"soort":"GESLACHT","vraag":"Geslacht","geldig":true,"waarde":"MAN","verplicht":true,"opties":{"MAN":"Man","VROUW":"Vrouw"},"abstracteVraagId":"geslacht","property":"aanvrager.geslacht"},{"soort":"OPEN","vraag":"Voorletters","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"voorletters","property":"aanvrager.voorletters"},{"soort":"OPEN","vraag":"Tussenvoegsel","geldig":true,"verplicht":false,"abstracteVraagId":"tussenvoegsel-standaard","property":"aanvrager.tussenvoegsel"},{"soort":"OPEN","vraag":"Achternaam","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"achternaam","property":"aanvrager.achternaam"},{"soort":"BSN","uitleg":"Door het invullen van het burgerservicenummer of sofinummer wordt uw aanvraag sneller verwerkt.
<br\/>Uw persoonsgegevens worden door ons geverifieerd bij de Gemeentelijke Basis Administratie.
<br\/>U kunt het burgerservicenummer vinden op uw rijbewijs, paspoort of salarisspecificatie.","vraag":"Burgerservicenummer","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"burgerservicenummer","property":"aanvrager.burgerservicenummer"},{"soort":"VALIDATIE","geldig":true,"waarde":"Verzekerde{type=nl.zorgweb.medinetapps.domain.offerte.Verzekerde$Type@8ab23dc3, voorletters=null, tussenvoegsel=null, achternaam=null, geboortedatum=Tue Jan 01 00:00:00 CET 1980, geslacht=MAN, nationaliteit=Nederlandse, burgerservicenummer=null}","abstracteVraagId":"bsn-uniek"},{"soort":"MEERKEUZE","vraag":"Nationaliteit","geldig":true,"waarde":"Nederlandse","verplicht":true,"opties":{"Afgaanse":"Afgaanse","Albanese":"Albanese","Algerijnse":"Algerijnse","Amerikaans burger":"Amerikaans burger","Andorrese":"Andorrese","Angolese":"Angolese","Burger van Antigua en Barbuda":"Burger van Antigua en Barbuda","Burger van de Ver. Arabische Emiraten":"Burger van de Ver. Arabische Emiraten","Argentijnse":"Argentijnse","Burger van Armeni\u00eb":"Burger van Armeni\u00eb","Australische":"Australische","Burger van Azerbajdsjan":"Burger van Azerbajdsjan","Bahamaanse":"Bahamaanse","Bahreinse":"Bahreinse","Barbadaanse":"Barbadaanse","Burger van Belarus (Wit-Rusland)":"Burger van Belarus (Wit-Rusland)","Belgische":"Belgische","Belizaanse":"Belizaanse","Burger van Bangladesh":"Burger van Bangladesh","Beninse":"Beninse","Bhutaanse":"Bhutaanse","Boliviaanse":"Boliviaanse","Burger van Bosni\u00eb-Herzegovina":"Burger van Bosni\u00eb-Herzegovina","Botswaanse":"Botswaanse","Braziliaanse":"Braziliaanse","Brits burger":"Brits burger","Brits overzees burger":"Brits overzees burger","Bruneise":"Bruneise","Bulgaarse":"Bulgaarse","Burger van Burkina Faso":"Burger van Burkina Faso","Burundische":"Burundische","Kambodjaanse":"Kambodjaanse","Canadese":"Canadese","Centrafrikaanse":"Centrafrikaanse","Chileense":"Chileense","Chinese":"Chinese","Colombiaanse":"Colombiaanse","Comorese":"Comorese","Costaricaanse":"Costaricaanse","Cubaanse":"Cubaanse","Cyprische":"Cyprische","Deense":"Deense","Djiboutiaanse":"Djiboutiaanse","Burger van Dominicaanse Republiek":"Burger van Dominicaanse Republiek","Burger van Dominica":"Burger van Dominica","Burger van de Bondsrepubliek Duitsland":"Burger van de Bondsrepubliek Duitsland","Ecuadoraanse":"Ecuadoraanse","Egyptische":"Egyptische","Equatoriaalguinese":"Equatoriaalguinese","Eritrese":"Eritrese","Estnische":"Estnische","Etiopische":"Etiopische","Fijische":"Fijische","Filipijnse":"Filipijnse","Finse":"Finse","Franse":"Franse","Gabonese":"Gabonese","Gambiaanse":"Gambiaanse","Burger van Georgi\u00eb":"Burger van Georgi\u00eb","Ghanese":"Ghanese","Grenadaanse":"Grenadaanse","Griekse":"Griekse","Guatemalteekse":"Guatemalteekse","Guineebissause":"Guineebissause","Guinese":"Guinese","Guyaanse":"Guyaanse","Ha\u00eftiaanse":"Ha\u00eftiaanse","Hondurese":"Hondurese","Hongaarse":"Hongaarse","Ierse":"Ierse","IJslandse":"IJslandse","Burger van India":"Burger van India","Indonesische":"Indonesische","Iraakse":"Iraakse","Iraanse":"Iraanse","Isra\u00eblische":"Isra\u00eblische","Italiaanse":"Italiaanse","Ivoriaanse":"Ivoriaanse","Jamaicaanse":"Jamaicaanse","Japanse":"Japanse","Jemenitische":"Jemenitische","Jordaanse":"Jordaanse","Kaapverdische":"Kaapverdische","Kameroense":"Kameroense","Burger van Kazachstan":"Burger van Kazachstan","Kenyaanse":"Kenyaanse","Kiribatische":"Kiribatische","Koeweitse":"Koeweitse","Kongolese":"Kongolese","Burger van Kosovo":"Burger van Kosovo","Burger van Kroati\u00eb":"Burger van Kroati\u00eb","Burger van Kyrgyzstan":"Burger van Kyrgyzstan","Laotiaanse":"Laotiaanse","Lesothaanse":"Lesothaanse","Letse":"Letse","Libanese":"Libanese","Liberiaanse":"Liberiaanse","Libische":"Libische","Liechtensteinse":"Liechtensteinse","Litouwse":"Litouwse","Luxemburgse":"Luxemburgse","Macedonische":"Macedonische","Malagassische":"Malagassische","Malawische":"Malawische","Maldivische":"Maldivische","Maleisische":"Maleisische","Malinese":"Malinese","Maltese":"Maltese","Marokkaanse":"Marokkaanse","Burger van de Marshalleilanden":"Burger van de Marshalleilanden","Burger van Mauritani\u00eb":"Burger van Mauritani\u00eb","Burger van Mauritius":"Burger van Mauritius","Mexicaanse":"Mexicaanse","Burger van Moldavi\u00eb":"Burger van Moldavi\u00eb","Monegaskische":"Monegaskische","Mongolische":"Mongolische","Burger van Montenegro":"Burger van Montenegro","Mozambiquaanse":"Mozambiquaanse","Myanmarese":"Myanmarese","Namibische":"Namibische","Nauruaanse":"Nauruaanse","Nederlandse":"Nederlandse","Nepalese":"Nepalese","Nicaraguaanse":"Nicaraguaanse","Nieuwzeelandse":"Nieuwzeelandse","Burger van Nigeria":"Burger van Nigeria","Burger van Niger":"Burger van Niger","Noordkoreaanse":"Noordkoreaanse","Noorse":"Noorse","Ugandese":"Ugandese","Burger van Oekraine":"Burger van Oekraine","Burger van Oezbekistan":"Burger van Oezbekistan","Omanitische":"Omanitische","Oostenrijkse":"Oostenrijkse","Pakistaanse":"Pakistaanse","Panamese":"Panamese","Burger van Papua-Nieuwguinea":"Burger van Papua-Nieuwguinea","Paraguayaanse":"Paraguayaanse","Peruaanse":"Peruaanse","Poolse":"Poolse","Portugese":"Portugese","Amerikaans onderdaan":"Amerikaans onderdaan","Katarese":"Katarese","Roemeense":"Roemeense","Burger van Rusland":"Burger van Rusland","Rwandese":"Rwandese","Burger van Saint Kitts-Nevis":"Burger van Saint Kitts-Nevis","Sintluciaanse":"Sintluciaanse","Burger van Sint Vincent en de Grenadinen":"Burger van Sint Vincent en de Grenadinen","Salvadoraanse":"Salvadoraanse","Sanmarinese":"Sanmarinese","Saoediarabische":"Saoediarabische","Burger van S\u00e3o Tom\u00e9 en Principe":"Burger van S\u00e3o Tom\u00e9 en Principe","Senegalese":"Senegalese","Burger van Servi\u00eb":"Burger van Servi\u00eb","Seychelse":"Seychelse","Sierraleoonse":"Sierraleoonse","Singaporaanse":"Singaporaanse","Burger van Sloveni\u00eb":"Burger van Sloveni\u00eb","Slowaakse":"Slowaakse","Soedanese":"Soedanese","Solomoneilandse":"Solomoneilandse","Somalische":"Somalische","Spaanse":"Spaanse","Srilankaanse":"Srilankaanse","Surinaamse":"Surinaamse","Swazische":"Swazische","Syrische":"Syrische","Burger van Tadzjikistan":"Burger van Tadzjikistan","Taiwanese":"Taiwanese","Tanzaniaanse":"Tanzaniaanse","Thaise":"Thaise","Burger van Timor Leste":"Burger van Timor Leste","Togolese":"Togolese","Tongaanse":"Tongaanse","Burger van Trinidad en Tobago":"Burger van Trinidad en Tobago","Tsjadische":"Tsjadische","Tsjechische":"Tsjechische","Tunesische":"Tunesische","Burger van Toerkmenistan":"Burger van Toerkmenistan","Turkse":"Turkse","Tuvaluaanse":"Tuvaluaanse","Uruguayaanse":"Uruguayaanse","Vanuatuse":"Vanuatuse","Vaticaanse":"Vaticaanse","Venezolaanse":"Venezolaanse","Vi\u00ebtnamese":"Vi\u00ebtnamese","Westsamoaanse":"Westsamoaanse","Za\u00efrese":"Za\u00efrese","Zambiaanse":"Zambiaanse","Zimbabwaanse":"Zimbabwaanse","Zuidafrikaanse":"Zuidafrikaanse","Zuidkoreaanse":"Zuidkoreaanse","Zuidsoedanese":"Zuidsoedanese","Zweedse":"Zweedse","Zwitserse":"Zwitserse"},"abstracteVraagId":"nationaliteit","property":"aanvrager.nationaliteit"},{"soort":"GEBOORTEDATUM","vraag":"Geboortedatum","geldig":true,"waarde":"1980-01-01","hint":"dd-mm-jjjj","verplicht":true,"abstracteVraagId":"geboortedatum","property":"aanvrager.geboortedatum"},{"soort":"REEKS","kopje":"Overige vragen","abstracteVraagId":"zorgvragen-verzekerde"},{"soort":"JANEE","vraag":"Ontvangt u inkomsten uit het buitenland?","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"opties":{"Ja":"Ja","Nee":"Nee"},"abstracteVraagId":"zorgvragen-VGZ\/inkomsten-buitenland","property":"aanvrager.zorgvragenMap.zorgvragen_VGZ_inkomsten_buitenland"},{"soort":"REEKS","kopje":"Gegevens partner","abstracteVraagId":"partner"},{"soort":"GESLACHT","vraag":"Geslacht","geldig":true,"waarde":"VROUW","verplicht":true,"opties":{"MAN":"Man","VROUW":"Vrouw"},"abstracteVraagId":"geslacht","property":"partner.geslacht"},{"soort":"OPEN","vraag":"Voorletters","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"voorletters","property":"partner.voorletters"},{"soort":"OPEN","vraag":"Tussenvoegsel","geldig":true,"verplicht":false,"abstracteVraagId":"tussenvoegsel-standaard","property":"partner.tussenvoegsel"},{"soort":"OPEN","vraag":"Achternaam","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"achternaam","property":"partner.achternaam"},{"soort":"BSN","uitleg":"Door het invullen van het burgerservicenummer of sofinummer wordt uw aanvraag sneller verwerkt.
<br\/>Uw persoonsgegevens worden door ons geverifieerd bij de Gemeentelijke Basis Administratie.
<br\/>U kunt het burgerservicenummer vinden op uw rijbewijs, paspoort of salarisspecificatie.","vraag":"Burgerservicenummer","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"burgerservicenummer","property":"partner.burgerservicenummer"},{"soort":"VALIDATIE","geldig":true,"waarde":"Verzekerde{type=nl.zorgweb.medinetapps.domain.offerte.Verzekerde$Type@46debc38, voorletters=null, tussenvoegsel=null, achternaam=null, geboortedatum=Tue Jan 01 00:00:00 CET 1980, geslacht=VROUW, nationaliteit=Nederlandse, burgerservicenummer=null}","abstracteVraagId":"bsn-uniek"},{"soort":"MEERKEUZE","vraag":"Nationaliteit","geldig":true,"waarde":"Nederlandse","verplicht":true,"opties":{"Afgaanse":"Afgaanse","Albanese":"Albanese","Algerijnse":"Algerijnse","Amerikaans burger":"Amerikaans burger","Andorrese":"Andorrese","Angolese":"Angolese","Burger van Antigua en Barbuda":"Burger van Antigua en Barbuda","Burger van de Ver. Arabische Emiraten":"Burger van de Ver. Arabische Emiraten","Argentijnse":"Argentijnse","Burger van Armeni\u00eb":"Burger van Armeni\u00eb","Australische":"Australische","Burger van Azerbajdsjan":"Burger van Azerbajdsjan","Bahamaanse":"Bahamaanse","Bahreinse":"Bahreinse","Barbadaanse":"Barbadaanse","Burger van Belarus (Wit-Rusland)":"Burger van Belarus (Wit-Rusland)","Belgische":"Belgische","Belizaanse":"Belizaanse","Burger van Bangladesh":"Burger van Bangladesh","Beninse":"Beninse","Bhutaanse":"Bhutaanse","Boliviaanse":"Boliviaanse","Burger van Bosni\u00eb-Herzegovina":"Burger van Bosni\u00eb-Herzegovina","Botswaanse":"Botswaanse","Braziliaanse":"Braziliaanse","Brits burger":"Brits burger","Brits overzees burger":"Brits overzees burger","Bruneise":"Bruneise","Bulgaarse":"Bulgaarse","Burger van Burkina Faso":"Burger van Burkina Faso","Burundische":"Burundische","Kambodjaanse":"Kambodjaanse","Canadese":"Canadese","Centrafrikaanse":"Centrafrikaanse","Chileense":"Chileense","Chinese":"Chinese","Colombiaanse":"Colombiaanse","Comorese":"Comorese","Costaricaanse":"Costaricaanse","Cubaanse":"Cubaanse","Cyprische":"Cyprische","Deense":"Deense","Djiboutiaanse":"Djiboutiaanse","Burger van Dominicaanse Republiek":"Burger van Dominicaanse Republiek","Burger van Dominica":"Burger van Dominica","Burger van de Bondsrepubliek Duitsland":"Burger van de Bondsrepubliek Duitsland","Ecuadoraanse":"Ecuadoraanse","Egyptische":"Egyptische","Equatoriaalguinese":"Equatoriaalguinese","Eritrese":"Eritrese","Estnische":"Estnische","Etiopische":"Etiopische","Fijische":"Fijische","Filipijnse":"Filipijnse","Finse":"Finse","Franse":"Franse","Gabonese":"Gabonese","Gambiaanse":"Gambiaanse","Burger van Georgi\u00eb":"Burger van Georgi\u00eb","Ghanese":"Ghanese","Grenadaanse":"Grenadaanse","Griekse":"Griekse","Guatemalteekse":"Guatemalteekse","Guineebissause":"Guineebissause","Guinese":"Guinese","Guyaanse":"Guyaanse","Ha\u00eftiaanse":"Ha\u00eftiaanse","Hondurese":"Hondurese","Hongaarse":"Hongaarse","Ierse":"Ierse","IJslandse":"IJslandse","Burger van India":"Burger van India","Indonesische":"Indonesische","Iraakse":"Iraakse","Iraanse":"Iraanse","Isra\u00eblische":"Isra\u00eblische","Italiaanse":"Italiaanse","Ivoriaanse":"Ivoriaanse","Jamaicaanse":"Jamaicaanse","Japanse":"Japanse","Jemenitische":"Jemenitische","Jordaanse":"Jordaanse","Kaapverdische":"Kaapverdische","Kameroense":"Kameroense","Burger van Kazachstan":"Burger van Kazachstan","Kenyaanse":"Kenyaanse","Kiribatische":"Kiribatische","Koeweitse":"Koeweitse","Kongolese":"Kongolese","Burger van Kosovo":"Burger van Kosovo","Burger van Kroati\u00eb":"Burger van Kroati\u00eb","Burger van Kyrgyzstan":"Burger van Kyrgyzstan","Laotiaanse":"Laotiaanse","Lesothaanse":"Lesothaanse","Letse":"Letse","Libanese":"Libanese","Liberiaanse":"Liberiaanse","Libische":"Libische","Liechtensteinse":"Liechtensteinse","Litouwse":"Litouwse","Luxemburgse":"Luxemburgse","Macedonische":"Macedonische","Malagassische":"Malagassische","Malawische":"Malawische","Maldivische":"Maldivische","Maleisische":"Maleisische","Malinese":"Malinese","Maltese":"Maltese","Marokkaanse":"Marokkaanse","Burger van de Marshalleilanden":"Burger van de Marshalleilanden","Burger van Mauritani\u00eb":"Burger van Mauritani\u00eb","Burger van Mauritius":"Burger van Mauritius","Mexicaanse":"Mexicaanse","Burger van Moldavi\u00eb":"Burger van Moldavi\u00eb","Monegaskische":"Monegaskische","Mongolische":"Mongolische","Burger van Montenegro":"Burger van Montenegro","Mozambiquaanse":"Mozambiquaanse","Myanmarese":"Myanmarese","Namibische":"Namibische","Nauruaanse":"Nauruaanse","Nederlandse":"Nederlandse","Nepalese":"Nepalese","Nicaraguaanse":"Nicaraguaanse","Nieuwzeelandse":"Nieuwzeelandse","Burger van Nigeria":"Burger van Nigeria","Burger van Niger":"Burger van Niger","Noordkoreaanse":"Noordkoreaanse","Noorse":"Noorse","Ugandese":"Ugandese","Burger van Oekraine":"Burger van Oekraine","Burger van Oezbekistan":"Burger van Oezbekistan","Omanitische":"Omanitische","Oostenrijkse":"Oostenrijkse","Pakistaanse":"Pakistaanse","Panamese":"Panamese","Burger van Papua-Nieuwguinea":"Burger van Papua-Nieuwguinea","Paraguayaanse":"Paraguayaanse","Peruaanse":"Peruaanse","Poolse":"Poolse","Portugese":"Portugese","Amerikaans onderdaan":"Amerikaans onderdaan","Katarese":"Katarese","Roemeense":"Roemeense","Burger van Rusland":"Burger van Rusland","Rwandese":"Rwandese","Burger van Saint Kitts-Nevis":"Burger van Saint Kitts-Nevis","Sintluciaanse":"Sintluciaanse","Burger van Sint Vincent en de Grenadinen":"Burger van Sint Vincent en de Grenadinen","Salvadoraanse":"Salvadoraanse","Sanmarinese":"Sanmarinese","Saoediarabische":"Saoediarabische","Burger van S\u00e3o Tom\u00e9 en Principe":"Burger van S\u00e3o Tom\u00e9 en Principe","Senegalese":"Senegalese","Burger van Servi\u00eb":"Burger van Servi\u00eb","Seychelse":"Seychelse","Sierraleoonse":"Sierraleoonse","Singaporaanse":"Singaporaanse","Burger van Sloveni\u00eb":"Burger van Sloveni\u00eb","Slowaakse":"Slowaakse","Soedanese":"Soedanese","Solomoneilandse":"Solomoneilandse","Somalische":"Somalische","Spaanse":"Spaanse","Srilankaanse":"Srilankaanse","Surinaamse":"Surinaamse","Swazische":"Swazische","Syrische":"Syrische","Burger van Tadzjikistan":"Burger van Tadzjikistan","Taiwanese":"Taiwanese","Tanzaniaanse":"Tanzaniaanse","Thaise":"Thaise","Burger van Timor Leste":"Burger van Timor Leste","Togolese":"Togolese","Tongaanse":"Tongaanse","Burger van Trinidad en Tobago":"Burger van Trinidad en Tobago","Tsjadische":"Tsjadische","Tsjechische":"Tsjechische","Tunesische":"Tunesische","Burger van Toerkmenistan":"Burger van Toerkmenistan","Turkse":"Turkse","Tuvaluaanse":"Tuvaluaanse","Uruguayaanse":"Uruguayaanse","Vanuatuse":"Vanuatuse","Vaticaanse":"Vaticaanse","Venezolaanse":"Venezolaanse","Vi\u00ebtnamese":"Vi\u00ebtnamese","Westsamoaanse":"Westsamoaanse","Za\u00efrese":"Za\u00efrese","Zambiaanse":"Zambiaanse","Zimbabwaanse":"Zimbabwaanse","Zuidafrikaanse":"Zuidafrikaanse","Zuidkoreaanse":"Zuidkoreaanse","Zuidsoedanese":"Zuidsoedanese","Zweedse":"Zweedse","Zwitserse":"Zwitserse"},"abstracteVraagId":"nationaliteit","property":"partner.nationaliteit"},{"soort":"GEBOORTEDATUM","vraag":"Geboortedatum","geldig":true,"waarde":"1980-01-01","hint":"dd-mm-jjjj","verplicht":true,"abstracteVraagId":"geboortedatum","property":"partner.geboortedatum"},{"soort":"REEKS","kopje":"Overige vragen","abstracteVraagId":"zorgvragen-verzekerde"},{"soort":"JANEE","vraag":"Ontvangt u inkomsten uit het buitenland?","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"opties":{"Ja":"Ja","Nee":"Nee"},"abstracteVraagId":"zorgvragen-VGZ\/inkomsten-buitenland","property":"partner.zorgvragenMap.zorgvragen_VGZ_inkomsten_buitenland"},{"soort":"REEKS","kopje":"Kind 1","uitleg":"Geef persoongsgegevens van kind met geboortedatum 1-1-2008","abstracteVraagId":"persoonsgegevens-kind"},{"soort":"GESLACHT","vraag":"Geslacht","geldig":true,"waarde":"VROUW","verplicht":true,"opties":{"MAN":"Man","VROUW":"Vrouw"},"abstracteVraagId":"geslacht","property":"kinderen[0].geslacht"},{"soort":"OPEN","vraag":"Voorletters","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"voorletters","property":"kinderen[0].voorletters"},{"soort":"OPEN","vraag":"Tussenvoegsel","geldig":true,"verplicht":false,"abstracteVraagId":"tussenvoegsel-standaard","property":"kinderen[0].tussenvoegsel"},{"soort":"OPEN","vraag":"Achternaam","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"achternaam","property":"kinderen[0].achternaam"},{"soort":"BSN","uitleg":"Door het invullen van het burgerservicenummer of sofinummer wordt uw aanvraag sneller verwerkt.
<br\/>Uw persoonsgegevens worden door ons geverifieerd bij de Gemeentelijke Basis Administratie.
<br\/>U kunt het burgerservicenummer vinden op uw rijbewijs, paspoort of salarisspecificatie.","vraag":"Burgerservicenummer","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"burgerservicenummer","property":"kinderen[0].burgerservicenummer"},{"soort":"VALIDATIE","geldig":true,"waarde":"Verzekerde{type=nl.zorgweb.medinetapps.domain.offerte.Verzekerde$Type@61536ed, voorletters=null, tussenvoegsel=null, achternaam=null, geboortedatum=Tue Jan 01 00:00:00 CET 2008, geslacht=VROUW, nationaliteit=Nederlandse, burgerservicenummer=null}","abstracteVraagId":"bsn-uniek"},{"soort":"MEERKEUZE","vraag":"Nationaliteit","geldig":true,"waarde":"Nederlandse","verplicht":true,"opties":{"Afgaanse":"Afgaanse","Albanese":"Albanese","Algerijnse":"Algerijnse","Amerikaans burger":"Amerikaans burger","Andorrese":"Andorrese","Angolese":"Angolese","Burger van Antigua en Barbuda":"Burger van Antigua en Barbuda","Burger van de Ver. Arabische Emiraten":"Burger van de Ver. Arabische Emiraten","Argentijnse":"Argentijnse","Burger van Armeni\u00eb":"Burger van Armeni\u00eb","Australische":"Australische","Burger van Azerbajdsjan":"Burger van Azerbajdsjan","Bahamaanse":"Bahamaanse","Bahreinse":"Bahreinse","Barbadaanse":"Barbadaanse","Burger van Belarus (Wit-Rusland)":"Burger van Belarus (Wit-Rusland)","Belgische":"Belgische","Belizaanse":"Belizaanse","Burger van Bangladesh":"Burger van Bangladesh","Beninse":"Beninse","Bhutaanse":"Bhutaanse","Boliviaanse":"Boliviaanse","Burger van Bosni\u00eb-Herzegovina":"Burger van Bosni\u00eb-Herzegovina","Botswaanse":"Botswaanse","Braziliaanse":"Braziliaanse","Brits burger":"Brits burger","Brits overzees burger":"Brits overzees burger","Bruneise":"Bruneise","Bulgaarse":"Bulgaarse","Burger van Burkina Faso":"Burger van Burkina Faso","Burundische":"Burundische","Kambodjaanse":"Kambodjaanse","Canadese":"Canadese","Centrafrikaanse":"Centrafrikaanse","Chileense":"Chileense","Chinese":"Chinese","Colombiaanse":"Colombiaanse","Comorese":"Comorese","Costaricaanse":"Costaricaanse","Cubaanse":"Cubaanse","Cyprische":"Cyprische","Deense":"Deense","Djiboutiaanse":"Djiboutiaanse","Burger van Dominicaanse Republiek":"Burger van Dominicaanse Republiek","Burger van Dominica":"Burger van Dominica","Burger van de Bondsrepubliek Duitsland":"Burger van de Bondsrepubliek Duitsland","Ecuadoraanse":"Ecuadoraanse","Egyptische":"Egyptische","Equatoriaalguinese":"Equatoriaalguinese","Eritrese":"Eritrese","Estnische":"Estnische","Etiopische":"Etiopische","Fijische":"Fijische","Filipijnse":"Filipijnse","Finse":"Finse","Franse":"Franse","Gabonese":"Gabonese","Gambiaanse":"Gambiaanse","Burger van Georgi\u00eb":"Burger van Georgi\u00eb","Ghanese":"Ghanese","Grenadaanse":"Grenadaanse","Griekse":"Griekse","Guatemalteekse":"Guatemalteekse","Guineebissause":"Guineebissause","Guinese":"Guinese","Guyaanse":"Guyaanse","Ha\u00eftiaanse":"Ha\u00eftiaanse","Hondurese":"Hondurese","Hongaarse":"Hongaarse","Ierse":"Ierse","IJslandse":"IJslandse","Burger van India":"Burger van India","Indonesische":"Indonesische","Iraakse":"Iraakse","Iraanse":"Iraanse","Isra\u00eblische":"Isra\u00eblische","Italiaanse":"Italiaanse","Ivoriaanse":"Ivoriaanse","Jamaicaanse":"Jamaicaanse","Japanse":"Japanse","Jemenitische":"Jemenitische","Jordaanse":"Jordaanse","Kaapverdische":"Kaapverdische","Kameroense":"Kameroense","Burger van Kazachstan":"Burger van Kazachstan","Kenyaanse":"Kenyaanse","Kiribatische":"Kiribatische","Koeweitse":"Koeweitse","Kongolese":"Kongolese","Burger van Kosovo":"Burger van Kosovo","Burger van Kroati\u00eb":"Burger van Kroati\u00eb","Burger van Kyrgyzstan":"Burger van Kyrgyzstan","Laotiaanse":"Laotiaanse","Lesothaanse":"Lesothaanse","Letse":"Letse","Libanese":"Libanese","Liberiaanse":"Liberiaanse","Libische":"Libische","Liechtensteinse":"Liechtensteinse","Litouwse":"Litouwse","Luxemburgse":"Luxemburgse","Macedonische":"Macedonische","Malagassische":"Malagassische","Malawische":"Malawische","Maldivische":"Maldivische","Maleisische":"Maleisische","Malinese":"Malinese","Maltese":"Maltese","Marokkaanse":"Marokkaanse","Burger van de Marshalleilanden":"Burger van de Marshalleilanden","Burger van Mauritani\u00eb":"Burger van Mauritani\u00eb","Burger van Mauritius":"Burger van Mauritius","Mexicaanse":"Mexicaanse","Burger van Moldavi\u00eb":"Burger van Moldavi\u00eb","Monegaskische":"Monegaskische","Mongolische":"Mongolische","Burger van Montenegro":"Burger van Montenegro","Mozambiquaanse":"Mozambiquaanse","Myanmarese":"Myanmarese","Namibische":"Namibische","Nauruaanse":"Nauruaanse","Nederlandse":"Nederlandse","Nepalese":"Nepalese","Nicaraguaanse":"Nicaraguaanse","Nieuwzeelandse":"Nieuwzeelandse","Burger van Nigeria":"Burger van Nigeria","Burger van Niger":"Burger van Niger","Noordkoreaanse":"Noordkoreaanse","Noorse":"Noorse","Ugandese":"Ugandese","Burger van Oekraine":"Burger van Oekraine","Burger van Oezbekistan":"Burger van Oezbekistan","Omanitische":"Omanitische","Oostenrijkse":"Oostenrijkse","Pakistaanse":"Pakistaanse","Panamese":"Panamese","Burger van Papua-Nieuwguinea":"Burger van Papua-Nieuwguinea","Paraguayaanse":"Paraguayaanse","Peruaanse":"Peruaanse","Poolse":"Poolse","Portugese":"Portugese","Amerikaans onderdaan":"Amerikaans onderdaan","Katarese":"Katarese","Roemeense":"Roemeense","Burger van Rusland":"Burger van Rusland","Rwandese":"Rwandese","Burger van Saint Kitts-Nevis":"Burger van Saint Kitts-Nevis","Sintluciaanse":"Sintluciaanse","Burger van Sint Vincent en de Grenadinen":"Burger van Sint Vincent en de Grenadinen","Salvadoraanse":"Salvadoraanse","Sanmarinese":"Sanmarinese","Saoediarabische":"Saoediarabische","Burger van S\u00e3o Tom\u00e9 en Principe":"Burger van S\u00e3o Tom\u00e9 en Principe","Senegalese":"Senegalese","Burger van Servi\u00eb":"Burger van Servi\u00eb","Seychelse":"Seychelse","Sierraleoonse":"Sierraleoonse","Singaporaanse":"Singaporaanse","Burger van Sloveni\u00eb":"Burger van Sloveni\u00eb","Slowaakse":"Slowaakse","Soedanese":"Soedanese","Solomoneilandse":"Solomoneilandse","Somalische":"Somalische","Spaanse":"Spaanse","Srilankaanse":"Srilankaanse","Surinaamse":"Surinaamse","Swazische":"Swazische","Syrische":"Syrische","Burger van Tadzjikistan":"Burger van Tadzjikistan","Taiwanese":"Taiwanese","Tanzaniaanse":"Tanzaniaanse","Thaise":"Thaise","Burger van Timor Leste":"Burger van Timor Leste","Togolese":"Togolese","Tongaanse":"Tongaanse","Burger van Trinidad en Tobago":"Burger van Trinidad en Tobago","Tsjadische":"Tsjadische","Tsjechische":"Tsjechische","Tunesische":"Tunesische","Burger van Toerkmenistan":"Burger van Toerkmenistan","Turkse":"Turkse","Tuvaluaanse":"Tuvaluaanse","Uruguayaanse":"Uruguayaanse","Vanuatuse":"Vanuatuse","Vaticaanse":"Vaticaanse","Venezolaanse":"Venezolaanse","Vi\u00ebtnamese":"Vi\u00ebtnamese","Westsamoaanse":"Westsamoaanse","Za\u00efrese":"Za\u00efrese","Zambiaanse":"Zambiaanse","Zimbabwaanse":"Zimbabwaanse","Zuidafrikaanse":"Zuidafrikaanse","Zuidkoreaanse":"Zuidkoreaanse","Zuidsoedanese":"Zuidsoedanese","Zweedse":"Zweedse","Zwitserse":"Zwitserse"},"abstracteVraagId":"nationaliteit","property":"kinderen[0].nationaliteit"},{"soort":"GEBOORTEDATUM","vraag":"Geboortedatum","geldig":true,"waarde":"2008-01-01","hint":"dd-mm-jjjj","verplicht":true,"abstracteVraagId":"geboortedatum","property":"kinderen[0].geboortedatum"},{"soort":"REEKS","kopje":"Adres","abstracteVraagId":"alleen-hoofdadres"},{"soort":"OPEN","vraag":"Postcode (bijv. 1234 AB)","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"postcode","property":"hoofdadres.postcode"},{"soort":"GETAL","vraag":"Huisnummer","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"huisnummer","property":"hoofdadres.huisnummer"},{"soort":"OPEN","vraag":"Huisnummertoevoeging","geldig":true,"verplicht":false,"abstracteVraagId":"huisnummertoevoeging","property":"hoofdadres.huisnummertoevoeging"},{"soort":"OPEN","vraag":"Straat","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"straat","property":"hoofdadres.straat"},{"soort":"OPEN","vraag":"Woonplaats","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"woonplaats","property":"hoofdadres.woonplaats"},{"soort":"TELNR","vraag":"Telefoonnummer","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"telefoonnummer","property":"hoofdadres.telefoonnummer"},{"soort":"TELNR","vraag":"Mobiel telefoonnummer","geldig":true,"verplicht":false,"abstracteVraagId":"telefoonnummer2","property":"hoofdadres.telefoonnummer2"},{"soort":"VASTE_WAARDE","vraag":"Land","geldig":true,"waarde":"NEDERLAND","verplicht":false,"abstracteVraagId":"land","property":"hoofdadres.land"},{"soort":"EMAIL","vraag":"E-mailadres","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"emailAdres","property":"hoofdadres.emailAdres"},{"soort":"REEKS","kopje":"Gegevens verzekering","abstracteVraagId":"verzekeringsgegevens"},{"soort":"DATUM","vraag":"Ingangsdatum","geldig":true,"waarde":"2016-09-27","hint":"dd-mm-jjjj","verplicht":false,"abstracteVraagId":"ingangsdatum","property":"verzekeringsgegevens.ingangsdatum"},{"soort":"MEERKEUZE","uitleg":"","vraag":"Reden afwijkende ingangsdatum","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"opties":{"net_18_jaar":"Ik heb de leeftijd van 18 jaar bereikt","echtscheiding":"In verband met echtscheiding","immigratie":"Ik kom uit het buitenland en dien me nu (weer) verplicht te verzekeren","exmilitair":"Ik kom uit een militaire dienstverband en dien me nu weer te verzekeren","niet_verzekerd":"Ik ben momenteel niet verzekerd"},"abstracteVraagId":"ingangsdatum-reden","property":"verzekeringsgegevens.ingangsdatumAndersReden"},{"soort":"AUTO_IBAN","uitleg":"Rekeningnummer wordt automatisch omgezet naar IBAN","vraag":"Rekeningnummer of IBAN","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"abstracteVraagId":"iban","property":"verzekeringsgegevens.iban"},{"soort":"MEERKEUZE","uitleg":"Betaling per automatische incasso is gratis. Voor betaling
<br\/>van de maandpremie per acceptgiro rekenen wij \u20ac 1,50 per acceptgiro.","vraag":"Incassowijze","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"opties":{"INCASSO":"Automatische incasso","ACCEPTGIRO":"Acceptgiro"},"abstracteVraagId":"incassowijze","property":"verzekeringsgegevens.incassoWijze"},{"soort":"MEERKEUZE","vraag":"Betalingstermijn","geldig":true,"waarde":"MAAND","verplicht":false,"opties":{"MAAND":"Per maand: \u20ac 199,90","KWARTAAL":"Per kwartaal: \u20ac 599,70","HALF_JAAR":"Per half jaar: \u20ac 1.199,40","JAAR":"Per jaar: \u20ac 2.374,80 (U bespaart \u20ac 2,00 per maand)"},"abstracteVraagId":"betalingstermijn","property":"verzekeringsgegevens.betalingstermijnString"},{"soort":"VERKLARING","vraag":"Ik verklaar hiermee dat ik de gegevens naar waarheid heb ingevuld en akkoord ga met onderstaande polisvoorwaarden.","geldig":false,"validatieMessageKey":"waarde_verplicht","validatieMessage":"Maak een keuze","verplicht":true,"opties":{"Ja, dat verklaar ik.":"Ja, dat verklaar ik."},"abstracteVraagId":"zorgvragen-VGZ\/naar-waarheid-ingevuld","property":"zorgvragenMap.zorgvragen_VGZ_naar_waarheid_ingevuld"},{"soort":"REEKS","uitleg":"
<ul>
    <li>Bekijk de 
        <a target=\"_blank\" href=\"https:\/\/www.vgz.nl\/klantenservice\/downloaden\/voorwaarden-en-reglementen\">polisvoorwaarden<\/a> van VGZ.<\/li>
            <li>U kunt de verzekering schriftelijk opzeggen binnen 14 dagen na het sluiten van de overeenkomst.<\/li><\/ul>","abstracteVraagId":"eindverklaring"},{"soort":"VALIDATIE","geldig":true,"abstracteVraagId":"geldige-premie"}]';

}