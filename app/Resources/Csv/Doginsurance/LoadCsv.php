<?php


namespace App\Resources\Csv\Doginsurance;

use App\Resources\BasicCsvRequest;
use App\Helpers\ResourceFilterHelper;

/**
 * Dus, resources laad echt de resource request in, in dit geval gaat ie dan naar de CSV importer
 *
 * hondenverzekeringen csv framework
 *
 *
 */
class LoadCsv extends BasicCsvRequest
{

    protected $headerMap = [];

    protected $getOHRACategories;
    protected $getBreedWeightClass;
    protected $getProviderBreedName;
    protected $getBreedData;


//the filter execute sequence is very important
    protected $processFields = [
        'product',
        'initialize',
        'weight',
        'breed',
        'single_case_ohra',
        'own_risk',
        'full_package',
        'remove_empty',


    ];

    protected $filterMap = [
    ];


    public function __construct()
    {
        parent::__construct(__DIR__);
        $this->initOHRACategories();
        $this->initProviderBreedName();
        $this->initBreedData();
        $this->updateBreedNames();
    }


    public function process_initialize($array)
    {

        //$this->getBreedWeightClass = $this->getBreedWeightClass();


        $arrayAllProducts = $array;

        $this->getBreedWeightClass();

        $feedAllPossibleArray = [];


        //create for each age/product a own product
        foreach ($arrayAllProducts as $productsKey => $productsVal) {

            $maxYear = 13;
            for ($count = 0; $count <= $maxYear; $count++) {
                $productsVal['price'] = (float)$productsVal[$count];
                $productsVal['age'] = $count;
                if (!empty($productsVal['price']))
                    $feedAllPossibleArray[] = $productsVal;
            }
        }


        $arrayAllProducts = $feedAllPossibleArray;

        $basicTariff = [];
        $additionalTariff = [];
        $newProductArray = [];
        $existsProducts = [];

        $pregSearchArray = ['/Chemo-radiotherapie/' => 'Chemo/radiotherapie'];

        foreach ($arrayAllProducts as $productsKey => $productsVal) {

            $productsVal['breed_old'] = $productsVal['breed'];
            if (!empty($productsVal['breed'])) {
                foreach ($this->getProviderBreedName as $masterBreedName => $subBreedNames) {
                    foreach ($subBreedNames as $subBreedName) {
                        if (strtolower(trim($productsVal['breed'])) == strtolower(trim($masterBreedName)) || strtolower(trim($productsVal['breed'])) == strtolower(trim($subBreedName))) {
                            $productsVal['breed'] = $masterBreedName;

                        }
                    }
                }
            }

            $productsVal['description'] = $productsVal['provider_product_id'];
            $productsVal['title'] = $productsVal['description'] . ' - ' . $productsVal['product'];

            if ($productsVal['provider_name'] == 'Proteq') {
                $productsVal['tariff'] = $productsVal['product'] . ' - ' . $productsVal['breed'];
            } else {
                $productsVal['tariff'] = $productsVal['product'];
            }


            $arrayAllProducts[$productsKey] = $productsVal;
            if (!in_array($productsVal['title'], $existsProducts)) {
                $existsProducts[] = $productsVal['title'];
            }


            if (empty($productsVal['additional_cover'])) {
                $basicTariff[$productsVal['product']][] = $productsVal;
            } else {
                $additionalTariff[$productsVal['product']][] = $productsVal;
            }

            $arrayAllProducts[$productsKey]['additional_cover'] = ResourceFilterHelper::multiPregReplace($productsVal['additional_cover'], $pregSearchArray);
        }


        $counter = 0;
        foreach ($existsProducts as $uniqueProduct) {

            $tempProductArray = [];
            foreach ($arrayAllProducts as $productsKey => $productsVal) {

                if ($productsVal['title'] == $uniqueProduct) {


                    $productsVal['resource_name'] = 'csv';
                    $productsVal['active'] = true;
                    $productsVal['clickable'] = true;
                    $productsVal['enabled'] = true;
                    $productsVal['resource.id'] = $counter;
                    $counter++;

                    //basic tariff
                    if (empty($productsVal['additional_cover'])) {
                        $productsVal['full_package'][] = 'basic';
                        $tempProductArray[] = $productsVal;
                        $newProductArray[] = $productsVal;
                        continue;
                    }


                    //add the additional tariff
                    foreach ($tempProductArray as $val) {

                        if ($val['weight'] == $productsVal['weight'] && $val['gender'] == $productsVal['gender'] && $val['age'] == $productsVal['age'] && $val['breed'] == $productsVal['breed']) {

                            $val['price'] = $productsVal['price'] + $val['price'];

                            $val['full_package'][] = $productsVal['additional_cover'];


                            if ($productsVal['combine_of_additionals'] == 'nee') {
                                $newProductArray[] = $val;
                                continue;
                            }

                            $newProductArray[] = $val;
                            $tempProductArray[] = $val;

                        }
                    }
                }
            }
        }
        return $newProductArray;
    }

    public function process_product($array)
    {
        $arrayAllProducts = $array;
        $pregRewriteRules = ['(BASIS)'  => 'Basis',
                             '(PLUS)'   => 'Plus',
                             '(TOTAAL)' => 'Totaal'];


        foreach ($arrayAllProducts as $productsKey => $productsVal) {

            $arrayAllProducts[$productsKey]['product'] = ResourceFilterHelper::multiPregReplace($productsVal['product'], $pregRewriteRules);
        }

        return $arrayAllProducts;
    }

    public function process_single_case_ohra($array)
    {
        $arrayAllProducts = $array;

        $pregMatchRules = [
            'Categorie1',
            'Categorie2',
            'Categorie3',
            'Categorie4',
        ];

        $breedCategories = $this->getOHRACategories();

        $category = [];
        foreach ($breedCategories as $breedName => $catNumber) {
            $category['Categorie' . $catNumber]['breed'][] = $breedName;
        }

        $breedWeightClass = $this->getBreedWeightClass();


        foreach ($category as $catName => $catValue) {

            $category[$catName]['weight_class'] = [];
            foreach ($catValue['breed'] as $breedName) {
                if (!array_key_exists($breedName, $breedWeightClass))
                    continue;

                foreach ($breedWeightClass[$breedName] as $weightClass) {

                    if (!in_array($weightClass, $category[$catName]['weight_class']))
                        $category[$catName]['weight_class'][] = $weightClass;
                }
            }
        }


        foreach ($arrayAllProducts as $productsKey => $productsVal) {

            if ($productsVal['provider_name'] != 'OHRA' || !empty($productsVal['breed']))
                continue;

            foreach ($pregMatchRules as $filterVal) {
                if (!preg_match('/(' . $filterVal . ')/', $productsVal['weight']))
                    continue;

                $arrayAllProducts[$productsKey]['breed'] = $category[$filterVal]['breed'];
                $arrayAllProducts[$productsKey]['weight_class'] = $category[$filterVal]['weight_class'];
                $arrayAllProducts[$productsKey]['weight_min'] = min($category[$filterVal]['weight_class']);
                $arrayAllProducts[$productsKey]['weight_max'] = max($category[$filterVal]['weight_class']);
                $arrayAllProducts[$productsKey]['weight'] = $arrayAllProducts[$productsKey]['weight_min'] . '-' . $arrayAllProducts[$productsKey]['weight_max'];
                for ($count = $arrayAllProducts[$productsKey]['weight_min']; $count <= $arrayAllProducts[$productsKey]['weight_max']; $count = $count + 0.5)
                    $arrayAllProducts[$productsKey]['weight_new'][] = $count;

                //$arrayAllProducts[$productsKey]['weight'] = $category[$filterVal]['weight_class'];
            }
        }

        return $arrayAllProducts;
    }

    public function process_weight($array)
    {

        $arrayAllProducts = $array;
        $pregRewriteRules = ['[\s*(kg)\s*]'             => '',
                             '[^(\d+)\s*(t/m)\s*(\d+)]' => '$1-$3',
                             '[^\s*(t/m)\s*(\d+)]'      => '0-$2',
                             '[^\s*(<)\s*(5)]'          => '0-5',
                             '[^\s*(50)\s*(\+)]'        => '50-100',
                             '[^\s*(>)\s*(50)]'         => '50-100',
                             '[^\s*(40)\s*(\+)]'        => '40-100'];

        //$arrayAllProducts[$productsKey]['weight']
        $breedData = $this->getBreedData();
        $breedClasses = $this->getBreedWeightClass();

        foreach ($arrayAllProducts as $productsKey => $productsVal) {

            $arrayWeight = [];
            $breed = (is_string($productsVal['breed']) ? $productsVal['breed'] : $productsVal['breed'][0]);

            if (empty($productsVal['weight'])) {


                if (!array_key_exists($breed, $breedClasses)) {
                    $productsVal['weight'] = '0-0';
                } else {
                    $productsVal['weight'] = $breedClasses[$breed][0] . '-' . end($breedClasses[$breed]);
                }

//                if (array_key_exists($productsVal['breed'], $breedData))
//                    $productsVal['weight'] = $breedData[$productsVal['breed']]['weight'][0] . '-' . $breedData[$productsVal['breed']]['weight'][1];
//                else
//                    $test[$productsVal['breed']] = 1;
            }

            $arrayAllProducts[$productsKey]['weight_old'] = $productsVal['weight'];
            $strWeight = ResourceFilterHelper::multiPregReplace($productsVal['weight'], $pregRewriteRules);
            $strWeight = str_replace(' ', '', $strWeight);
            $arrayAllProducts[$productsKey]['weight'] = $strWeight;

            if (!preg_match('[(\d+)-(\d+)]', $strWeight)) {
                $arrayAllProducts[$productsKey]['weight_new'] = $arrayWeight;
                continue;
            }


            $classMin = 0;
            $classMax = 100;
            $classStep = 5;

            $classTemp = [];
            $weightMinMax = explode('-', $strWeight);
            for ($count = (int)$weightMinMax[0]; $count <= (int)$weightMinMax[1]; $count = $count + 0.5) {
                $arrayWeight[] = $count;

                for ($step = $classMin; $step <= $classMax; $step = $step + $classStep) {
                    if (($count > $step) && ($count < ($step + $classStep)) || (($count == $step))) {
                        if (in_array($step, $classTemp))
                            continue;

                        $classTemp[] = $step;
                    }
                }

            }

            if(empty($classTemp))
            {
                if (array_key_exists($breed, $breedClasses)) {
                    foreach ($breedClasses[$breed] as $weightClasses) {
                        $classTemp[] = $weightClasses;
                    }
                } elseif (!empty($productsVal['weight'])) {

                    for ($step = (int)$weightMinMax[0]; $step <= (int)$weightMinMax[1]; $step = $step + $classStep) {
                        $classTemp[] = $step;
                    }
                }
            }



            $arrayAllProducts[$productsKey]['weight_min'] = (int)$weightMinMax[0];
            $arrayAllProducts[$productsKey]['weight_max'] = (int)$weightMinMax[1];

            $arrayAllProducts[$productsKey]['weight_new'] = $arrayWeight;
            $arrayAllProducts[$productsKey]['weight_class'] = $classTemp;
        }


        return $arrayAllProducts;
    }


    public function process_breed($array)
    {
        $arrayAllProducts = $array;
        $providerBreedException = [];

        $providerBreedNamesWithoutDoubles = $this->getProviderBreedNameWithoutDoubles();


        foreach ($arrayAllProducts as $productsKey => $productsVal) {
            $arrayBreedToWeight = $this->getBreedData();
            $arrayBreed = [];

            if ($productsVal['provider_name'] == 'OHRA' && empty($productsVal['breed']))
                continue;

            if (empty($productsVal['breed'])) {

                if ($productsVal['provider_name'] == 'Verzekeruzelf.nl') {

                    foreach ($providerBreedException['Verzekeruzelf.nl'] as $providerException) {
                        unset($arrayBreedToWeight[$providerException]);
                    }
                    unset($arrayBreedToWeight['Beierse bergzweethond (Bayrischer Gebirgsschweisshund)']);
                    unset($arrayBreedToWeight['Ca de Bou']);
                    unset($arrayBreedToWeight['Golden Doodle Groot)']);
                    unset($arrayBreedToWeight['Golden Doodle Klein']);
                    unset($arrayBreedToWeight['Ierse glen of iemaal terrier']);
                    unset($arrayBreedToWeight['Noorse lundehund']);
                    unset($arrayBreedToWeight['Song dog']);
                }


                foreach ($arrayBreedToWeight as $breedName => $breedData) {
                    foreach ($productsVal['weight_new'] as $value) {
                        if (($value >= $breedData['weight'][0]) && ($value <= $breedData['weight'][1])) {
                            if (in_array($breedName, $arrayBreed))
                                break;

                            $arrayBreed[] = $breedName;
                        }
                    }
                }
            }

            if (!array_key_exists($productsVal['provider_name'], $providerBreedException))
                $providerBreedException[$productsVal['provider_name']] = [];

            if (!in_array($productsVal['breed'], $providerBreedException[$productsVal['provider_name']]))
                $providerBreedException[$productsVal['provider_name']][] = $productsVal['breed'];

            $arrayBreed[] = $productsVal['breed'];
            $arrayAllProducts[$productsKey]['breed'] = $arrayBreed;

            $tempBreedNameVariations = [];
            foreach ($this->getProviderBreedName as $masterBreedName => $subBreedNames) {

                foreach ($subBreedNames as $breedName) {
                    if ($productsVal['breed_old'] == $breedName || $productsVal['breed_old'] == $masterBreedName) {
                        //if(!array_key_exists($masterBreedName,$tempBreedNameVariations))
                        //continue;

                        $tempBreedNameVariations[$masterBreedName] = $providerBreedNamesWithoutDoubles[$masterBreedName];
                        break;

                    }

                    foreach ($arrayAllProducts[$productsKey]['breed'] as $dataBreedName) {
                        if ($dataBreedName == $breedName || $dataBreedName == $masterBreedName) {
                            //if(array_key_exists($masterBreedName,$tempBreedNameVariations))
                            //    continue;

                            $tempBreedNameVariations[$masterBreedName] = $providerBreedNamesWithoutDoubles[$masterBreedName];
                            break 2;
                        }
                    }
                }
            }


            $arrayAllProducts[$productsKey]['breed_variations'] = [];
            foreach ($tempBreedNameVariations as $key => $breedNameVariations) {
                foreach ($breedNameVariations as $value) {
                    if (in_array($value, $arrayAllProducts[$productsKey]['breed_variations']))
                        continue;

                    $arrayAllProducts[$productsKey]['breed_variations'][] = $value;
                }
            }
        }

        return $arrayAllProducts;
    }


    public function process_full_package($array)
    {
        $arrayAllProducts = $array;

        foreach ($arrayAllProducts as $productsKey => $productsVal) {

            $arrayAllProducts[$productsKey]['full_package_string'] = '';
            foreach ($productsVal['full_package'] as $itemKey => $itemVal) {
                if (!empty($arrayAllProducts[$productsKey]['full_package_string'])) {
                    $arrayAllProducts[$productsKey]['full_package_string'] .= ',';
                }
                $arrayAllProducts[$productsKey]['full_package_string'] .= $itemVal;

//                $itemVal = str_replace(' ','',$itemVal);
//                $newFullPackage = explode('+',$itemVal);
//                var_dump($newFullPackage);
//                if(is_array($newFullPackage)){
//                    unset($productsVal['full_package'][$itemKey]);
//                    foreach($newFullPackage as $key => $val)
//                    {
//                        $productsVal['full_package'][] = $val;
//                    }
//                }

            }
//            $arrayAllProducts[$productsKey]['full_package'] = $productsVal['full_package'];
        }

        return $arrayAllProducts;
    }

    public function process_own_risk($array)
    {
        $arrayAllProducts = $array;


        foreach ($arrayAllProducts as $productsKey => $productsVal) {
            if ($productsVal['own_risk'] == '')
                $arrayAllProducts[$productsKey]['own_risk'] = 0;
        }

        return $arrayAllProducts;
    }

    public function process_remove_empty($array)
    {
        $arrayAllProducts = $array;
        $newAllProducts = [];


        foreach ($arrayAllProducts as $productsKey => $productsVal) {
            if (!empty($productsVal['price']))
                $newAllProducts[] = $productsVal;
        }

        return $newAllProducts;
    }

    public function getBreedWeightClass()
    {
        $breedData = $this->getBreedData();
        $classStep = 5;

        $arrayWeightClass = [];
        foreach ($breedData as $breedDataKey => $breedDataVal) {
            $tempBreed = [];
            foreach ($breedDataVal['classes'] as $classKey => $classVal) {
                if (empty($classVal))
                    continue;

                if (empty($tempBreed))
                    $tempBreed[] = $classKey * $classStep;

                $tempBreed[] = ($classKey + 1) * $classStep;

            }
            $arrayWeightClass[$breedDataKey] = $tempBreed;
        }

        return $arrayWeightClass;
    }


    public function updateBreedNames()
    {
        $tempBreedData = [];
        foreach ($this->getBreedData as $breedName => $value) {
            $elementName = null;
            foreach ($this->getProviderBreedName as $masterBreedName => $subBreedNames) {

                foreach ($subBreedNames as $subBreedName) {
                    if (strtolower(trim($breedName)) == strtolower(trim($masterBreedName)) || strtolower(trim($breedName)) == strtolower(trim($subBreedName))) {
                        $elementName = $masterBreedName;
                        break 2;
                    }
                    $elementName = $breedName;
                }
            }
            $tempBreedData[$elementName] = $value;
        }
        $this->getBreedData = $tempBreedData;


        $tempBreedData = [];
        foreach ($this->getOHRACategories as $breedName => $value) {
            $elementName = null;
            foreach ($this->getProviderBreedName as $masterBreedName => $subBreedNames) {

                foreach ($subBreedNames as $subBreedName) {
                    if (strtolower(trim($breedName)) == strtolower(trim($masterBreedName)) || strtolower(trim($breedName)) == strtolower(trim($subBreedName))) {
                        $elementName = $masterBreedName;
                        break 2;
                    }
                    $elementName = $breedName;
                }
            }
            $tempBreedData[$elementName] = $value;
        }
        $this->getOHRACategories = $tempBreedData;

    }

    public function getOHRACategories()
    {
        return $this->getOHRACategories;
    }

    public function initOHRACategories()
    {
        $this->getOHRACategories = [
            "Affenpinscher"                               => 1,
            "Afghaanse windhond"                          => 3,
            "Airedale Terrier"                            => 3,
            "Akbash (Turkse herder)"                      => 4,
            "Akita"                                       => 3,
            "Alaska Malamute"                             => 3,
            "American Staffordshire Terrier"              => 2,
            "Amerikaans-canadese witte herdershond"       => 3,
            "Amerikaanse bulldog"                         => 3,
            "Amerikaanse Cocker Spaniel"                  => 2,
            "Amerikaanse Eskimohond"                      => 2,
            "Amerikaanse Waterspaniel"                    => 2,
            "Anatolische herder"                          => 4,
            "Anglo francais de petite venerie"            => 2,
            "Appenzeller Sennenhond"                      => 2,
            "Argentijnse dog"                             => 4,
            "Australian cattle dog"                       => 2,
            "Australian kelpie"                           => 2,
            "Australian shepherd"                         => 2,
            "Australian Silky Terrier"                    => 1,
            "Australische Terrier"                        => 1,
            "Azakwakh"                                    => 2,
            "Bandog"                                      => 4,
            "Barbet"                                      => 3,
            "Barsoi"                                      => 4,
            "Basenji"                                     => 2,
            "Basset artesien normand"                     => 2,
            "Basset bleu de gascogne"                     => 2,
            "Basset Fauve de Bretagne (Basset Fauve)"     => 2,
            "Basset Griffon vendeen (Grand)"              => 2,
            "Basset Griffon Vendeen (Petit)"              => 2,
            "Basset Hound"                                => 2,
            "Beagle"                                      => 2,
            "Bearded Collie"                              => 3,
            "Beauceron"                                   => 4,
            "Bedlington Terrier"                          => 2,
            "Beierse bergzweethond"                       => 2,
            "Bergamasco"                                  => 3,
            "Berghond van de Maremmen en Abruzzen"        => 4,
            "Berner Laufhund"                             => 2,
            "Berner Sennenhond"                           => 3,
            "Bichon Frise"                                => 1,
            "Black and Tan Coonhound"                     => 3,
            "Bloedhond"                                   => 3,
            "Bobtail (Old English Sheepsdog)"             => 3,
            "Boerboel"                                    => 4,
            "Boheemse terrier"                            => 2,
            "Bolognezer"                                  => 1,
            "Boomer"                                      => 2,
            "Bordeaux Dog"                                => 4,
            "Border Collie"                               => 2,
            "Border Terrier"                              => 1,
            "Boston Terrier"                              => 1,
            "Bouvier"                                     => 3,
            "Boxer"                                       => 8,
            "Bracco Italiano"                             => 3,
            "Brandl Brak"                                 => 2,
            "Braque d auvergne"                           => 2,
            "Braque de bourbonnais"                       => 2,
            "Briard"                                      => 3,
            "Brittany Spaniel"                            => 2,
            "Broholmer"                                   => 4,
            "Buhund"                                      => 3,
            "Bull Terrier"                                => 3,
            "Bull Terrier Miniatuur"                      => 2,
            "Bullmastiff"                                 => 4,
            "Ca de Bestiar"                               => 4,
            "Cairn Terrier"                               => 1,
            "Canadese Eskimohond"                         => 3,
            "Cane Corso"                                  => 4,
            "Cao da Serra da Estrela"                     => 3,
            "Cao da Serra de aires"                       => 3,
            "Cavalier King Charles Spaniel"               => 1,
            "Centraal-Aziatische Owcharka"                => 4,
            "Cesky fousek"                                => 3,
            "Cesky Terrier"                               => 1,
            "Chartpolski"                                 => 3,
            "Chesapeake Bay Retriever"                    => 3,
            "Chihuahua"                                   => 1,
            "Chinese Gekuifde Naakthond"                  => 1,
            "Chow-Chow"                                   => 3,
            "Cirneco dell etna"                           => 2,
            "Clumber Spaniel"                             => 2,
            "Coton de tulear"                             => 1,
            "Curlycoated Retriever"                       => 3,
            "Dalmatische Hond"                            => 3,
            "Dandie Dinmont Terrier"                      => 1,
            "Teckel (standaard)"                          => 1,
            "Deerhound"                                   => 4,
            "Dobermann"                                   => 3,
            "Dogo-Canario"                                => 3,
            "Drentse patrijshond"                         => 2,
            "Duitse brak"                                 => 3,
            "Duitse Dog"                                  => 4,
            "Duitse Herdershond"                          => 3,
            "Duitse jachthond"                            => 2,
            "Duitse Pinscher"                             => 2,
            "Duitse staande hond (glad en draadharig)"    => 3,
            "Teckel (dwerg)"                              => 1,
            "Dwergpinscher"                               => 1,
            "Poedel (dwerg, toy)"                         => 1,
            "Schnauzer (dwerg)"                           => 1,
            "El Perro de Pastor Garafiano"                => 3,
            "Engelse Bulldog"                             => 9,
            "Engelse Cocker Spaniel"                      => 2,
            "Engelse Setter"                              => 3,
            "Engelse Springer Spaniel"                    => 2,
            "Engelse Toy Terrier"                         => 1,
            "Entlebucher sennenhond"                      => 2,
            "Epagneul bleu de picardie"                   => 2,
            "Epagneul breton"                             => 2,
            "Epagneul francais"                           => 2,
            "Epagneul papillon"                           => 1,
            "Epagneul phalene"                            => 1,
            "Erdelyi kopo"                                => 3,
            "Eurasier"                                    => 3,
            "Field Spaniel"                               => 2,
            "Fila brasileiro"                             => 4,
            "Finse lappenhond (Lapinkoira)"               => 2,
            "Finse Spits"                                 => 2,
            "Flatcoated Retriever"                        => 3,
            "Foxhound (engelse)"                          => 3,
            "Foxterrier (glad en draadharig)"             => 1,
            "Franse Bulldog"                              => 7,
            "Friese stabij (Stabyhound)"                  => 2,
            "Galgo espanol"                               => 2,
            "Glen of Imaalterrier"                        => 2,
            "Golden Retriever"                            => 3,
            "Gordon Setter"                               => 3,
            "Gos d Atura"                                 => 2,
            "Grand Bleu de Cascogne"                      => 3,
            "Grat Japanese dog"                           => 3,
            "Greyhound"                                   => 3,
            "Griffon Belge"                               => 1,
            "Griffon Bruxellois"                          => 1,
            "Griffon Fauve de Bretagne"                   => 2,
            "Griffon Korthals"                            => 3,
            "Groenendaeler"                               => 3,
            "Groenlandse Hond"                            => 3,
            "Grote Munsterlander"                         => 3,
            "Grote Zwitserse Sennenhond"                  => 3,
            "Hamilton Stövare"                            => 3,
            "Harrier"                                     => 3,
            "Havanezer"                                   => 1,
            "Heidewachtel/Kl munsterlander"               => 2,
            "Hollandse herder"                            => 3,
            "Hollandse smoushond"                         => 1,
            "Hovawart"                                    => 3,
            "Ierse Setter"                                => 3,
            "Ierse Terrier"                               => 2,
            "Ierse Waterspaniel"                          => 3,
            "Ierse Wolfshond"                             => 4,
            "IJslandse hond"                              => 2,
            "Irish Glen of Imaal Terrier"                 => 2,
            "Irish Softcoated Wheaten Terrier"            => 2,
            "Italiaans Windhondje"                        => 1,
            "Jack Russell Terrier"                        => 1,
            "Japanse Spaniel"                             => 1,
            "Japanse Spits"                               => 1,
            "Jura Laufhund"                               => 2,
            "Kanaänhond"                                  => 2,
            "Teckel (kaninchen)"                          => 1,
            "Karabash"                                    => 3,
            "Karelische Berenhond"                        => 2,
            "Kaukasische herder"                          => 4,
            "Keeshond (dwerg, klein)"                     => 1,
            "Keeshond (groot)"                            => 3,
            "Keeshond (middel)"                           => 2,
            "Kerry Blue Terrier"                          => 2,
            "King Charles Spaniel"                        => 1,
            "Komondor"                                    => 4,
            "Koningspoedel"                               => 3,
            "Kooikerhondje"                               => 1,
            "Kraski Ovcar"                                => 3,
            "Kromfohrlander"                              => 2,
            "Kuvasz"                                      => 4,
            "Labradoodle-Medium"                          => 2,
            "Labradoodle-Miniatuur"                       => 2,
            "Labradoodle-Standaard"                       => 3,
            "Labrador retriever"                          => 3,
            "Laekense herder"                             => 3,
            "Lagotto Romagnolo"                           => 2,
            "Lakeland Terrier"                            => 1,
            "Lancashire Heeler"                           => 1,
            "Landseer"                                    => 4,
            "Lapinporokoira"                              => 2,
            "Laplandse Herdershond"                       => 2,
            "Leeuwhondje"                                 => 1,
            "Leonberger"                                  => 4,
            "Lhasa apso"                                  => 1,
            "Lurcher"                                     => 2,
            "Luzerner Laufhund"                           => 2,
            "Maltezer"                                    => 1,
            "Manchester Terrier"                          => 1,
            "Markiesje"                                   => 1,
            "Mastiff"                                     => 4,
            "Mastin de Los Pyreneos"                      => 4,
            "Mastin espanol"                              => 3,
            "Mastino Napoletano"                          => 4,
            "Mechelse herder"                             => 3,
            "Mexicaanse naakthond"                        => 1,
            "Poedel (middenslag)"                         => 2,
            "Schnauzer (middenslag)"                      => 2,
            "Miniature bull terrier"                      => 2,
            "Mopshond"                                    => 5,
            "Mudi (Hongaarse herdershond)"                => 2,
            "Newfoundlander"                              => 4,
            "Noorse Buhund"                               => 2,
            "Noorse elandhond"                            => 2,
            "Norsk Lundehund"                             => 1,
            "Norfolk Terrier"                             => 1,
            "Norbottenspets (pohjanpystykorva)"           => 1,
            "Norwich Terrier"                             => 1,
            "Nova Scotia Duck Tolling Retriever"          => 2,
            "Ogar Polski"                                 => 4,
            "Old English bulldog"                         => 3,
            "Oostenrijkse pinscher"                       => 2,
            "Otterhond"                                   => 3,
            "Oud Duitse Herdershond"                      => 3,
            "Parson jack russel terrier"                  => 1,
            "Patterdale terrier"                          => 1,
            "Pekinees"                                    => 1,
            "Perdigueiro Portigeus"                       => 3,
            "Perro de agua espagnol (Spaanse waterhond) " => 2,
            "Perro de Presa Canario"                      => 4,
            "Petit basset griffon vendeen"                => 2,
            "Petit bleu de gascogne"                      => 3,
            "Petite brabancon"                            => 1,
            "Pharaohond"                                  => 3,
            "Picardische herder"                          => 3,
            "Pittbull Terrier"                            => 2,
            "Plott Hound"                                 => 2,
            "Podenco Ibicenco"                            => 2,
            "Pointer"                                     => 3,
            "Polski Owczarek Nizinny"                     => 2,
            "Porcelaine"                                  => 3,
            "Portugese waterhond (Cao de Agua)"           => 2,
            "Pronkrug"                                    => 3,
            "Puli"                                        => 2,
            "Pumi"                                        => 2,
            "Pyreneese Berghond"                          => 4,
            "Pyreneese Herdershond"                       => 2,
            "Rat Terrier (dwerg-)"                        => 1,
            "Rat Terrier (grote-)"                        => 3,
            "Rat Terrier (middenslag-)"                   => 2,
            "Rhodesian Ridgeback"                         => 3,
            "Riesenschnauzer"                             => 3,
            "Rottweiler"                                  => 7,
            "Saarloos wolfhond"                           => 3,
            "Sabueso Espagnol"                            => 3,
            "Saluki"                                      => 2,
            "Samojeed"                                    => 3,
            "Saplaninac"                                  => 3,
            "Schapendoes"                                 => 2,
            "Schipperke"                                  => 1,
            "Schotse Herdershond (kort- en langharig)"    => 3,
            "Schotse Terrier"                             => 1,
            "Schweizer Laufhund"                          => 2,
            "Sealyham Terrier"                            => 1,
            "Segugio Italiano"                            => 3,
            "Shar-Pei"                                    => 10,
            "Sheltie/Shetland sheepdog"                   => 1,
            "Shiba"                                       => 1,
            "Shih-Tzu"                                    => 1,
            "Shiloh Shepherd"                             => 4,
            "Siberische Husky"                            => 2,
            "Silky Windhound"                             => 2,
            "Sint Bernard"                                => 3,
            "Sint Hubertushond"                           => 3,
            "Skye Terrier"                                => 1,
            "Sloughi"                                     => 3,
            "Slovenský Cuvac"                             => 3,
            "Spinone"                                     => 3,
            "Staffordshire Bull Terrier"                  => 2,
            "Sussex Spaniel"                              => 2,
            "Tatra"                                       => 4,
            "Tervuerense herder"                          => 3,
            "Thai ridgeback dog"                          => 2,
            "Tibetaanse Mastiff"                          => 3,
            "Tibetaanse Spaniel"                          => 1,
            "Tibetaanse Terrier"                          => 2,
            "Tosa"                                        => 4,
            "Toyfox terrier (American Toy Terrier)"       => 1,
            "Tsjechoslowaakse wolfhond"                   => 3,
            "Västgötaspets"                               => 2,
            "Valhund"                                     => 2,
            "Vizsla"                                      => 3,
            "Weimaraner"                                  => 3,
            "Welsh Corgi (Cardigan/Pembroke)"             => 2,
            "Welsh Springer Spaniel"                      => 2,
            "Welsh Terrier"                               => 1,
            "West Highland WhiteTerrier"                  => 6,
            "West siberische laika"                       => 3,
            "Wetterhoun"                                  => 2,
            "Whippet"                                     => 2,
            "Yorkshire Terrier"                           => 1,
            "Zuid-russische owcharka"                     => 4,
            "Zwart Russische Terrier"                     => 3,
            "Zweedse lappenhond"                          => 2,
            "White swiss shepherd dog"                    => 3];

    }


    public function initBreedData()
    {

        //'classes' =>['0-5','5-10','10-15','15-20','20-25','25-30','30-35','35-40','40-45','45-50','50-55','55-60','60-65','65-70','70-75','75-80','80-85','85-90','90-95','95-100']],
        $this->getBreedData = [

            "Affenpinscher"                               => ["height" => [25, 30], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Afghaanse windhond"                          => ["height" => [65, 75], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Aïdi"                                        => ["height" => [50, 60], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Airedale Terrier"                            => ["height" => [55, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Akita"                                       => ["height" => [60, 70], "weight" => [30, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Alaska Malamute"                             => ["height" => [58.5, 63.5], "weight" => [34, 38.5], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Alpenländische Dachsbracke"                  => ["height" => [35, 45], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "American Akita"                              => ["height" => [60, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "American Foxhound"                           => ["height" => [50, 65], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "American Staffordshire Terrier"              => ["height" => [40, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Amerikaanse Cocker Spaniel"                  => ["height" => [35, 40], "weight" => [5, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Amerikaanse Water Spaniel"                   => ["height" => [35, 45], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Anatolische Herdershond"                     => ["height" => [65, 78], "weight" => [54, 60], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", ""]],
            "Anglo-Français de petite vénerie"            => ["height" => [45, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Appenzeller Sennenhond"                      => ["height" => [50, 56], "weight" => [25, 32], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Argentijnse Dog"                             => ["height" => [60, 70], "weight" => [40, 45], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Ariégois"                                    => ["height" => [50, 60], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Australian Cattle Dog"                       => ["height" => [40, 50], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Australian Kelpie"                           => ["height" => [45, 50], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Australian Shepherd"                         => ["height" => [45, 60], "weight" => [15, 30], "classes" => ["", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Australian Silky Terrier"                    => ["height" => [20, 23], "weight" => [3.5, 4.5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Australian Stumpy Tail Cattle Dog"           => ["height" => [45, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Australian Terrier"                          => ["height" => [25, 25], "weight" => [4, 7.5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Azawakh"                                     => ["height" => [60, 75], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Barbet"                                      => ["height" => [55, 65], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Barsoi"                                      => ["height" => [68, 85], "weight" => [35, 48], "classes" => ["", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Basenji"                                     => ["height" => [40, 43], "weight" => [9, 11], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Basset Artésien Normand"                     => ["height" => [30, 35], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Basset bleu de Gascogne"                     => ["height" => [35, 40], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Basset fauve de Bretagne"                    => ["height" => [30, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Basset Hound"                                => ["height" => [30, 40], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bayrischer Gebirgsschweisshund"              => ["height" => [45, 50], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Beagle"                                      => ["height" => [30, 40], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Beagle Harrier"                              => ["height" => [35, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bearded Collie"                              => ["height" => [50, 55], "weight" => [18, 26], "classes" => ["", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Beauceron"                                   => ["height" => [61, 70], "weight" => [30, 50], "classes" => ["", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Bedlington Terrier"                          => ["height" => [35, 45], "weight" => [7, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Belgische Herdershond, Groenendaeler"        => ["height" => [56, 64], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Belgische Herdershond, Laekense"             => ["height" => [56, 64], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Belgische Herdershond, Mechelse"             => ["height" => [56, 64], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Belgische Herdershond, Tervuerense"          => ["height" => [56, 66], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bergamasco"                                  => ["height" => [54, 62], "weight" => [27, 37], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Berghond van de Maremmen"                    => ["height" => [60, 73], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Berner Laufhund"                             => ["height" => [45, 60], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Berner Niederlaufhund"                       => ["height" => [30, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Berner Sennenhond"                           => ["height" => [60, 70], "weight" => [40, 55], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Bichon frisé"                                => ["height" => [25, 30], "weight" => [5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Billy"                                       => ["height" => [55, 70], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Black and Tan Coonhound"                     => ["height" => [60, 70], "weight" => [23, 34], "classes" => ["", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bloedhond"                                   => ["height" => [60, 70], "weight" => [35, 40], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bolognezer"                                  => ["height" => [25, 30], "weight" => [2.5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bordeaux Dog"                                => ["height" => [60, 70], "weight" => [45, 60], "classes" => ["", "", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", ""]],
            "Border Collie"                               => ["height" => [50, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Border Terrier"                              => ["height" => [33, 38], "weight" => [6, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bosanski Ostrodlaki Gonic Barak"             => ["height" => [40, 55], "weight" => [16, 24], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Boston Terrier"                              => ["height" => [30, 45], "weight" => [5, 12], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bouvier des Ardennes"                        => ["height" => [54, 60], "weight" => [23, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bouvier des Flandres"                        => ["height" => [59, 68], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Boxer"                                       => ["height" => [55, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bracco Italiano"                             => ["height" => [55, 67], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Brandlbracke"                                => ["height" => [45, 60], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Braque d'Auvergne"                           => ["height" => [55, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Braque de l'Ariège"                          => ["height" => [60, 70], "weight" => [40, 45], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Braque du Bourbonnais"                       => ["height" => [48, 57], "weight" => [15, 30], "classes" => ["", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Braque Français, type Gascogne"              => ["height" => [55, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Braque Français, type Pyrénées (klein)"      => ["height" => [45, 60], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Braque Saint Germain"                        => ["height" => [50, 65], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Braziliaanse Terrier"                        => ["height" => [30, 40], "weight" => [7, 12], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Briard"                                      => ["height" => [55, 70], "weight" => [30, 35], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Briquet griffon vendéen"                     => ["height" => [45, 55], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Broholmer"                                   => ["height" => [70, 80], "weight" => [50, 60], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", ""]],
            "Bull Terrier"                                => ["height" => [30, 38], "weight" => [16, 22], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bullmastiff"                                 => ["height" => [60, 70], "weight" => [40, 60], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", ""]],
            "Cairn Terrier"                               => ["height" => [25, 35], "weight" => [7, 12], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Canaänhond"                                  => ["height" => [50, 60], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Cane Corso"                                  => ["height" => [60, 70], "weight" => [40, 50], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Cão da Serra da Estrela, korthaar"           => ["height" => [60, 70], "weight" => [35, 55], "classes" => ["", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Cão da Serra da Estrela, langhaar"           => ["height" => [60, 70], "weight" => [35, 55], "classes" => ["", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Cão da Serra de Aires"                       => ["height" => [40, 55], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Cão de Agua Português"                       => ["height" => [45, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Cão de Castro Laboreiro"                     => ["height" => [50, 60], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Cão Fila de São Miguel"                      => ["height" => [45, 60], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Cavalier King Charles Spaniël"               => ["height" => [32, 38], "weight" => [5, 8], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Centraal-Aziatische Ovcharka"                => ["height" => [60, 75], "weight" => [50, 65], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", ""]],
            "Cesky Fousek"                                => ["height" => [60, 65], "weight" => [20, 35], "classes" => ["", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Cesky Terrier"                               => ["height" => [25, 30], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Chart Polski"                                => ["height" => [70, 80], "weight" => [35, 40], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Chesapeake Bay Retriever"                    => ["height" => [55, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Chien d'Artois"                              => ["height" => [50, 60], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Chihuahua, korthaar"                         => ["height" => [15, 20], "weight" => [0.5, 2.5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Chihuahua, langhaar"                         => ["height" => [15, 20], "weight" => [0.5, 2.5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Chinese Naakthond"                           => ["height" => [27, 32], "weight" => [5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Chow Chow"                                   => ["height" => [45, 55], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Cimarrón Uruguayo"                           => ["height" => [50, 60], "weight" => [30, 45], "classes" => ["", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ciobanesc Romanesc Carpatin"                 => ["height" => [60, 75], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ciobanesc Romanesc de Bucovina"              => ["height" => [65, 80], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ciobanesc Romanesc Mioritic"                 => ["height" => [65, 75], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Cirneco dell'Etna"                           => ["height" => [42, 50], "weight" => [8, 12], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Clumber Spaniel"                             => ["height" => [42, 48], "weight" => [30, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Coton de Tuléar"                             => ["height" => [20, 30], "weight" => [3, 6], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Crnogorski Planinski Gonic"                  => ["height" => [45, 55], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Curly Coated Retriever"                      => ["height" => [60, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dalmatische Hond"                            => ["height" => [55, 65], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dandie Dinmont Terrier"                      => ["height" => [20, 28], "weight" => [8, 11], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dashond, korthaar"                           => ["height" => [15, 25], "weight" => [7, 12], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dashond, langhaar"                           => ["height" => [15, 25], "weight" => [7, 12], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dashond, ruwhaar"                            => ["height" => [15, 25], "weight" => [7, 12], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Deens-Zweedse Boerderijhond"                 => ["height" => [30, 40], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Deerhound"                                   => ["height" => [70, 80], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dobermann"                                   => ["height" => [60, 70], "weight" => [30, 45], "classes" => ["", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dogo Canario"                                => ["height" => [55, 65], "weight" => [40, 55], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Drentsche Patrijshond"                       => ["height" => [55, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Drever"                                      => ["height" => [30, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Brak"                                 => ["height" => [40, 55], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Dog, blauw"                           => ["height" => [70, 85], "weight" => [50, 80], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "x", "x", "", "", "", ""]],
            "Duitse Dog, geel/gestroomd"                  => ["height" => [70, 85], "weight" => [50, 80], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "x", "x", "", "", "", ""]],
            "Duitse Dog, zwart/zwart-wit"                 => ["height" => [70, 85], "weight" => [50, 80], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "x", "x", "", "", "", ""]],
            "Duitse Dwergpinscher"                        => ["height" => [25, 30], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Herdershond Langstokhaar"             => ["height" => [55, 65], "weight" => [22, 40], "classes" => ["", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Herdershond Stokhaar"                 => ["height" => [55, 65], "weight" => [22, 40], "classes" => ["", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Jachtterrier"                         => ["height" => [35, 45], "weight" => [7, 12], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Pinscher"                             => ["height" => [45, 50], "weight" => [12, 18], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Staande Hond Draadhaar"               => ["height" => [55, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Staande Hond Korthaar"                => ["height" => [60, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Staande Hond Langhaar"                => ["height" => [60, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Staande Hond Stekelhaar"              => ["height" => [55, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Duitse Wachtelhond"                          => ["height" => [45, 55], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dunker"                                      => ["height" => [45, 55], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergdashond, korthaar"                      => ["height" => [12, 17], "weight" => [5, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergdashond, langhaar"                      => ["height" => [12, 17], "weight" => [5, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergdashond, ruwhaar"                       => ["height" => [12, 17], "weight" => [5, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergkeeshond"                               => ["height" => [15, 25], "weight" => [2, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergpoedel, grijs-abrikoos-rood"            => ["height" => [25, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergpoedel, zwart-wit-bruin"                => ["height" => [25, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergschnauzer, peper en zout"               => ["height" => [30, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergschnauzer, wit"                         => ["height" => [30, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergschnauzer, zwart"                       => ["height" => [30, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Dwergschnauzer, zwart-zilver"                => ["height" => [30, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Engelse Bulldog"                             => ["height" => [35, 45], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Engelse Cocker Spaniel"                      => ["height" => [35, 45], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Engelse Setter"                              => ["height" => [60, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Engelse Springer Spaniel"                    => ["height" => [45, 55], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Engelse Toy Terrier"                         => ["height" => [25, 30], "weight" => [2, 4], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "English Foxhound"                            => ["height" => [55, 65], "weight" => [30, 35], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Entlebucher Sennenhond"                      => ["height" => [40, 50], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Epagneul Bleu de Picardie"                   => ["height" => [55, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Epagneul Breton"                             => ["height" => [45, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Epagneul de Pont-Audemer"                    => ["height" => [50, 60], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Epagneul français"                           => ["height" => [55, 60], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Epagneul Nain Continental, Papillon"         => ["height" => [25, 30], "weight" => [1.5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Epagneul Nain Continental, Phalène"          => ["height" => [25, 30], "weight" => [1.5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Epagneul Picard"                             => ["height" => [55, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Erdélyi Kopo"                                => ["height" => [45, 65], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Eurasier"                                    => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Field Spaniel"                               => ["height" => [40, 50], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Fila Brasileiro"                             => ["height" => [60, 75], "weight" => [40, 65], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "x", "x", "", "", "", "", "", "", ""]],
            "Finse Brak"                                  => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Finse Lappenhond"                            => ["height" => [40, 50], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Finse Spits"                                 => ["height" => [40, 50], "weight" => [5, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Flatcoated Retriever"                        => ["height" => [55, 60], "weight" => [20, 35], "classes" => ["", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Foxterrier Draadhaar"                        => ["height" => [35, 45], "weight" => [7, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Foxterrier Gladhaar"                         => ["height" => [35, 45], "weight" => [7, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Français Blanc et Noir"                      => ["height" => [60, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Français Blanc et Orange"                    => ["height" => [60, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Français Tricolore"                          => ["height" => [60, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Franse Bulldog"                              => ["height" => [32, 38], "weight" => [8, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Galgo Español"                               => ["height" => [60, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Gammel Dansk Hønsehund"                      => ["height" => [50, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Golden Retriever"                            => ["height" => [50, 60], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Gonczy Polski"                               => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Gordon Setter"                               => ["height" => [60, 65], "weight" => [30, 35], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Gos d'Atura Català"                          => ["height" => [45, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grand anglo-français blanc et noir"          => ["height" => [60, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grand anglo-français blanc et orange"        => ["height" => [60, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grand anglo-français tricolore"              => ["height" => [60, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grand Basset griffon vendéen"                => ["height" => [40, 45], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grand bleu de Gascogne"                      => ["height" => [60, 70], "weight" => [40, 50], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Grand gascon saintongeois"                   => ["height" => [60, 70], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grand Griffon vendéen"                       => ["height" => [60, 70], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Greyhound"                                   => ["height" => [65, 75], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Griffon belge"                               => ["height" => [25, 35], "weight" => [3, 6], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Griffon bleu de Gascogne"                    => ["height" => [50, 60], "weight" => [30, 35], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Griffon bruxellois"                          => ["height" => [25, 35], "weight" => [3, 6], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Griffon fauve de Bretagne"                   => ["height" => [45, 60], "weight" => [30, 35], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Griffon Korthals"                            => ["height" => [50, 60], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Griffon nivernais"                           => ["height" => [50, 60], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Groenlandhond"                               => ["height" => [55, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grote Keeshond, bruin-zwart"                 => ["height" => [40, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grote Keeshond, wit"                         => ["height" => [40, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grote Keeshond, wolfsgrijs"                  => ["height" => [40, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grote Münsterlander"                         => ["height" => [60, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grote Poedel, grijs-abrikoos-rood"           => ["height" => [45, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grote Poedel, zwart-wit-bruin"               => ["height" => [45, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Grote Zwitserse Sennenhond"                  => ["height" => [60, 75], "weight" => [40, 50], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Haldenstøvare"                               => ["height" => [50, 60], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hamiltonstövare"                             => ["height" => [50, 60], "weight" => [35, 40], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hannover'scher Schweisshund"                 => ["height" => [40, 60], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Harrier"                                     => ["height" => [45, 55], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Havanezer"                                   => ["height" => [20, 30], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Heidewachtel"                                => ["height" => [50, 55], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hellinikos Ichnilatis"                       => ["height" => [40, 55], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hokkaido"                                    => ["height" => [45, 55], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hollandse Herdershond, korthaar"             => ["height" => [55, 65], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hollandse Herdershond, langhaar"             => ["height" => [55, 65], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hollandse Herdershond, ruwhaar"              => ["height" => [55, 65], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hollandse Smoushond"                         => ["height" => [35, 45], "weight" => [5, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hovawart"                                    => ["height" => [60, 70], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hrvatski Ovcar"                              => ["height" => [40, 50], "weight" => [10, 25], "classes" => ["", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Hygenhund"                                   => ["height" => [45, 55], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ierse rood-witte Setter"                     => ["height" => [55, 65], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ierse Setter"                                => ["height" => [55, 65], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ierse Terrier"                               => ["height" => [45, 50], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ierse Water Spaniel"                         => ["height" => [50, 60], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ierse Wolfshond"                             => ["height" => [70, 80], "weight" => [40, 55], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "IJslandse Hond"                              => ["height" => [40, 50], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Irish Glen of Imaal Terrier"                 => ["height" => [30, 35], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Irish Soft Coated Wheaten Terrier"           => ["height" => [40, 50], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Istarski Kratkodlaki Gonic"                  => ["height" => [45, 55], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Istarski Ostrodlaki Gonic"                   => ["height" => [45, 55], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Italiaans Windhondje"                        => ["height" => [30, 40], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Jack Russell Terrier"                        => ["height" => [25, 30], "weight" => [5, 7], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Jämthund"                                    => ["height" => [50, 65], "weight" => [30, 35], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Japanse Spaniel"                             => ["height" => [20, 30], "weight" => [2.5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Japanse Spits"                               => ["height" => [25, 40], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Japanse Terrier"                             => ["height" => [30, 40], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Jura Laufhund"                               => ["height" => [45, 60], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Jura Niederlaufhund"                         => ["height" => [30, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kai"                                         => ["height" => [45, 55], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kaninchen Dashond, korthaar"                 => ["height" => [10, 15], "weight" => [2.5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kaninchen Dashond, langhaar"                 => ["height" => [10, 15], "weight" => [2.5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kaninchen Dashond, ruwhaar"                  => ["height" => [10, 15], "weight" => [2.5, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Karelische Berenhond"                        => ["height" => [45, 60], "weight" => [30, 35], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kaukasische Ovcharka"                        => ["height" => [65, 75], "weight" => [45, 65], "classes" => ["", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", ""]],
            "Kerry Blue Terrier"                          => ["height" => [45, 50], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "King Charles Spaniël"                        => ["height" => [25, 35], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kishu"                                       => ["height" => [40, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kleine Keeshond, bruin-zwart"                => ["height" => [20, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kleine Keeshond, oranje en anderskleurig"    => ["height" => [20, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kleine Keeshond, wit"                        => ["height" => [20, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Komondor"                                    => ["height" => [70, 80], "weight" => [40, 60], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", ""]],
            "Korea Jindo Dog"                             => ["height" => [45, 55], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kraski Ovcar"                                => ["height" => [50, 60], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kromfohrländer"                              => ["height" => [40, 45], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Kuvasz"                                      => ["height" => [65, 75], "weight" => [30, 55], "classes" => ["", "", "", "", "", "", "x", "x", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Labrador Retriever"                          => ["height" => [55, 60], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Lagotto Romagnolo"                           => ["height" => [40, 50], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Lakeland Terrier"                            => ["height" => [30, 40], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Lancashire Heeler"                           => ["height" => [25, 30], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Landseer ECT"                                => ["height" => [65, 80], "weight" => [50, 70], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", ""]],
            "Laplandse Herdershond"                       => ["height" => [45, 55], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Leeuwhondje"                                 => ["height" => [25, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Leonberger"                                  => ["height" => [65, 80], "weight" => [55, 65], "classes" => ["", "", "", "", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", ""]],
            "Lhasa Apso"                                  => ["height" => [20, 30], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Luzerner Laufhund"                           => ["height" => [45, 60], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Luzerner Niederlaufhund"                     => ["height" => [30, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Magyar Agár"                                 => ["height" => [65, 70], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Maltezer"                                    => ["height" => [20, 25], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Manchester Terrier"                          => ["height" => [35, 45], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Markiesje"                                   => ["height" => [30, 40], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Mastiff"                                     => ["height" => [70, 80], "weight" => [80, 100], "classes" => ["", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x"]],
            "Mastin del Pirineo"                          => ["height" => [70, 80], "weight" => [50, 70], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", ""]],
            "Mastin Español"                              => ["height" => [60, 70], "weight" => [65, 80], "classes" => ["", "", "", "", "", "", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", ""]],
            "Mastino Napoletano"                          => ["height" => [60, 75], "weight" => [60, 80], "classes" => ["", "", "", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", ""]],
            "Mexicaanse Naakthond, medio"                 => ["height" => [35, 45], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Mexicaanse Naakthond, miniatuur"             => ["height" => [25, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Mexicaanse Naakthond, standaard"             => ["height" => [45, 60], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Middenslag Keeshond, bruin-zwart"            => ["height" => [30, 40], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Middenslag Keeshond, oranje en anderkleurig" => ["height" => [30, 40], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Middenslag Keeshond, wit"                    => ["height" => [30, 40], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Middenslag Poedel, grijs-abrikoos-rood"      => ["height" => [35, 45], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Middenslag Poedel, zwart-wit-bruin"          => ["height" => [35, 45], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Middenslag Schnauzer, peper en zout"         => ["height" => [45, 50], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Middenslag Schnauzer, zwart"                 => ["height" => [45, 50], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Miniatuur Bull Terrier"                      => ["height" => [30, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Mopshond"                                    => ["height" => [30, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Mudi"                                        => ["height" => [35, 50], "weight" => [5, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Nederlandse Kooikerhondje"                   => ["height" => [35, 40], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Nederlandse Schapendoes"                     => ["height" => [40, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Newfoundlander"                              => ["height" => [65, 75], "weight" => [50, 70], "classes" => ["", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", ""]],
            "Noorse Buhund"                               => ["height" => [40, 50], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Noorse Elandhond Grijs"                      => ["height" => [45, 55], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Noorse Elandhond Zwart"                      => ["height" => [40, 50], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Noorse Lundehund"                            => ["height" => [30, 35], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Norfolk Terrier"                             => ["height" => [20, 25], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Norrbottenspets"                             => ["height" => [40, 45], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Norwich Terrier"                             => ["height" => [20, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Nova Scotia Duck Tolling Retriever"          => ["height" => [45, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ogar Polski"                                 => ["height" => [55, 65], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Old English Sheepdog"                        => ["height" => [55, 65], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Oostenrijkse Pinscher"                       => ["height" => [35, 50], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Oostsiberische Laika"                        => ["height" => [50, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Otterhound"                                  => ["height" => [60, 70], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Parson Russell Terrier"                      => ["height" => [30, 40], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Pekingees"                                   => ["height" => [25, 35], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Perdigueiro de Burgos"                       => ["height" => [65, 75], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Perdigueiro Português"                       => ["height" => [50, 60], "weight" => [15, 30], "classes" => ["", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Perro de Agua Español"                       => ["height" => [40, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Perro de Pastor Mallorquin"                  => ["height" => [60, 75], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Perro Dogo Mallorquín"                       => ["height" => [50, 60], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Peruaanse Haarloze Hond, groot"              => ["height" => [50, 65], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Peruaanse Haarloze Hond, middenslag"         => ["height" => [40, 50], "weight" => [7, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Peruaanse Haarloze Hond, miniatuur"          => ["height" => [25, 40], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Petit Basset griffon vendéen"                => ["height" => [35, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Petit Bleu de Gascogne"                      => ["height" => [50, 60], "weight" => [30, 35], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Petit Brabançon"                             => ["height" => [25, 35], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Petit gascon saintongeois"                   => ["height" => [55, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Pharaohond"                                  => ["height" => [50, 65], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Picardische Herdershond"                     => ["height" => [55, 65], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podenco Canario"                             => ["height" => [50, 65], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podenco Ibicenco, gladhaar"                  => ["height" => [60, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podenco Ibicenco, ruwhaar"                   => ["height" => [60, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podengo Português, groot, gladhaar"          => ["height" => [55, 70], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podengo Português, groot, ruwhaar"           => ["height" => [55, 70], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podengo Português, klein, gladhaar"          => ["height" => [20, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podengo Português, klein, ruwhaar"           => ["height" => [20, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podengo Português, middenslag, gladhaar"     => ["height" => [40, 55], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Podengo Português, middenslag, ruwhaar"      => ["height" => [40, 55], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Poedelpointer"                               => ["height" => [55, 70], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Pointer"                                     => ["height" => [60, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Poitevin"                                    => ["height" => [60, 75], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Polski Owczarek Nizinny"                     => ["height" => [40, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Porcelaine"                                  => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Posavski Gonic"                              => ["height" => [45, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Puli, anders dan wit"                        => ["height" => [35, 45], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Puli, wit"                                   => ["height" => [35, 45], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Pumi"                                        => ["height" => [35, 45], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Pyreneese Berghond"                          => ["height" => [65, 80], "weight" => [45, 60], "classes" => ["", "", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", ""]],
            "Pyreneese Herdershond à face rase"           => ["height" => [40, 50], "weight" => [7, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Pyreneese Herdershond à poil long"           => ["height" => [40, 50], "weight" => [7, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Rafeiro do Alentejo"                         => ["height" => [65, 75], "weight" => [40, 55], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Rhodesian Ridgeback"                         => ["height" => [60, 70], "weight" => [30, 45], "classes" => ["", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Riesenschnauzer, peper en zout"              => ["height" => [60, 70], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Riesenschnauzer, zwart"                      => ["height" => [60, 70], "weight" => [35, 45], "classes" => ["", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Rottweiler"                                  => ["height" => [55, 70], "weight" => [40, 55], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Russian Toy, korthaar"                       => ["height" => [20, 30], "weight" => [2, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Russian Toy, langhaar"                       => ["height" => [20, 30], "weight" => [2, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Russisch-Europese Laika"                     => ["height" => [50, 60], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Saarlooswolfhond"                            => ["height" => [60, 75], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Sabueso Español"                             => ["height" => [50, 60], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Saluki"                                      => ["height" => [55, 70], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Samojeed"                                    => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Sarplaninac"                                 => ["height" => [65, 80], "weight" => [40, 65], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "x", "x", "", "", "", "", "", "", ""]],
            "Schillerstövare"                             => ["height" => [50, 60], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Schipperke"                                  => ["height" => [20, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Schotse Herdershond Korthaar"                => ["height" => [50, 60], "weight" => [15, 30], "classes" => ["", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Schotse Herdershond Langhaar"                => ["height" => [50, 60], "weight" => [15, 30], "classes" => ["", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Schotse Terrier"                             => ["height" => [30, 40], "weight" => [7, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Schwyzer Laufhund"                           => ["height" => [45, 60], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Schwyzer Niederlaufhund"                     => ["height" => [30, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Sealyham Terrier"                            => ["height" => [25, 35], "weight" => [7, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Segugio Italiano Gladhaar"                   => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Segugio Italiano Ruwhaar"                    => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Shar-Pei"                                    => ["height" => [40, 55], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Shetland Sheepdog"                           => ["height" => [35, 40], "weight" => [5, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Shiba"                                       => ["height" => [35, 45], "weight" => [5, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Shih Tzu"                                    => ["height" => [20, 30], "weight" => [3, 10], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Shikoku"                                     => ["height" => [40, 60], "weight" => [15, 30], "classes" => ["", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Siberian Husky"                              => ["height" => [50, 60], "weight" => [20, 35], "classes" => ["", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Sint Bernard, korthaar"                      => ["height" => [65, 90], "weight" => [65, 85], "classes" => ["", "", "", "", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", ""]],
            "Sint Bernard, langhaar"                      => ["height" => [65, 90], "weight" => [65, 85], "classes" => ["", "", "", "", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", ""]],
            "Skye Terrier"                                => ["height" => [20, 30], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Sloughi"                                     => ["height" => [60, 70], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Slovenský Cuvac"                             => ["height" => [60, 70], "weight" => [40, 50], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Slovenský Hrubosrstý Stavac"                 => ["height" => [55, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Slovenský Kopov"                             => ["height" => [40, 50], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Smålandsstövare"                             => ["height" => [45, 50], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Spinone Italiano"                            => ["height" => [60, 70], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Srpski Gonic"                                => ["height" => [45, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Srpski Trobojni Gonic"                       => ["height" => [45, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Stabijhoun"                                  => ["height" => [50, 55], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Staffordshire Bull Terrier"                  => ["height" => [35, 40], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Steirische ruwharige Brak"                   => ["height" => [45, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Sussex Spaniel"                              => ["height" => [35, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Taiwan Dog"                                  => ["height" => [40, 50], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Tatrahond"                                   => ["height" => [60, 70], "weight" => [35, 70], "classes" => ["", "", "", "", "", "", "", "x", "x", "x", "x", "x", "x", "x", "", "", "", "", "", ""]],
            "Thai Bangkaew Dog"                           => ["height" => [40, 55], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Thai Ridgeback Dog"                          => ["height" => [50, 65], "weight" => [20, 25], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Tibetaanse Mastiff"                          => ["height" => [60, 70], "weight" => [35, 55], "classes" => ["", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Tibetaanse Spaniel"                          => ["height" => [20, 30], "weight" => [3, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Tibetaanse Terrier"                          => ["height" => [35, 40], "weight" => [7, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Tiroler Brak"                                => ["height" => [30, 40], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Tornjak"                                     => ["height" => [60, 70], "weight" => [30, 50], "classes" => ["", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", ""]],
            "Tosa"                                        => ["height" => [55, 65], "weight" => [55, 70], "classes" => ["", "", "", "", "", "", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", ""]],
            "Toypoedel"                                   => ["height" => [25, 35], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Tsjechoslowaakse Wolfhond"                   => ["height" => [60, 70], "weight" => [25, 45], "classes" => ["", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Västgötaspets"                               => ["height" => [30, 35], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Vizsla Draadhaar"                            => ["height" => [55, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Vizsla Korthaar"                             => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Volpino Italiano"                            => ["height" => [25, 35], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Weimaraner, korthaar"                        => ["height" => [55, 70], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Weimaraner, langhaar"                        => ["height" => [55, 70], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Welsh Corgi Cardigan"                        => ["height" => [25, 35], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Welsh Corgi Pembroke"                        => ["height" => [25, 30], "weight" => [10, 15], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Welsh Springer Spaniel"                      => ["height" => [45, 50], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Welsh Terrier"                               => ["height" => [35, 45], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "West Highland White Terrier"                 => ["height" => [25, 30], "weight" => [5, 10], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Westfaalse Dasbrak"                          => ["height" => [30, 40], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Westsiberische Laika"                        => ["height" => [50, 60], "weight" => [20, 30], "classes" => ["", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Wetterhoun"                                  => ["height" => [50, 55], "weight" => [25, 30], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Whippet"                                     => ["height" => [45, 55], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Yorkshire Terrier"                           => ["height" => [20, 25], "weight" => [2, 5], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Zuid-Russische Ovcharka"                     => ["height" => [60, 70], "weight" => [40, 60], "classes" => ["", "", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", ""]],
            "Zwart Russische Terriër"                     => ["height" => [65, 75], "weight" => [35, 55], "classes" => ["", "", "", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", ""]],
            "Zweedse Lappenhond"                          => ["height" => [40, 50], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Zwitserse Witte Herdershond"                 => ["height" => [50, 70], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Amerikaanse bulldog"                         => ["height" => [50, 75], "weight" => [25, 60], "classes" => ["", "", "", "", "", "x", "x", "x", "x", "x", "x", "x", "", "", "", "", "", "", "", ""]],
            "Amerikaanse Eskimohond"                      => ["height" => [40, 50], "weight" => [30, 45], "classes" => ["", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Bandog"                                      => ["height" => [50, 80], "weight" => [35, 60], "classes" => ["", "", "", "", "", "", "", "x", "x", "x", "x", "x", "", "", "", "", "", "", "", ""]],
            "Boerboel"                                    => ["height" => [55, 70], "weight" => [65, 90], "classes" => ["", "", "", "", "", "", "", "", "", "", "", "", "", "x", "x", "x", "x", "x", "", ""]],
            "Boomer"                                      => ["height" => [25, 30], "weight" => [3, 10], "classes" => ["x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Ca de Bou"                                   => ["height" => [50, 60], "weight" => [30, 40], "classes" => ["", "", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Elo"                                         => ["height" => [30, 50], "weight" => [15, 30], "classes" => ["", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "El Perro de PastorGarafiano"                 => ["height" => [55, 65], "weight" => [20, 35], "classes" => ["", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Golden Doodle Groot"                         => ["height" => [45, 70], "weight" => [15, 35], "classes" => ["", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Golden Doodle Klein"                         => ["height" => [30, 50], "weight" => [5, 25], "classes" => ["", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Labradoodle - Miniatuur"                     => ["height" => [35, 45], "weight" => [5, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Labradoodle - Medium"                        => ["height" => [45, 55], "weight" => [10, 25], "classes" => ["", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Labradoodle - standaard"                     => ["height" => [55, 65], "weight" => [20, 35], "classes" => ["", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Lurcher"                                     => ["height" => [60, 70], "weight" => [25, 35], "classes" => ["", "", "", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Old english bulldog"                         => ["height" => [50, 60], "weight" => [25, 40], "classes" => ["", "", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Patterdale terrier"                          => ["height" => [25, 40], "weight" => [10, 20], "classes" => ["", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Pittbull Terrier"                            => ["height" => [40, 55], "weight" => [20, 35], "classes" => ["", "", "", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Plott Hound"                                 => ["height" => [50, 75], "weight" => [15, 35], "classes" => ["", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Rat Terrier (dwerg-)"                        => ["height" => [20, 30], "weight" => [3, 7], "classes" => ["x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Rat Terrier (grote-)"                        => ["height" => [40, 50], "weight" => [7, 15], "classes" => ["", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Rat Terrier (middenslag-)"                   => ["height" => [30, 40], "weight" => [15, 20], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Renascense bulldog"                          => ["height" => [40, 60], "weight" => [25, 45], "classes" => ["", "", "", "", "", "x", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", ""]],
            "Schafpudel"                                  => ["height" => [45, 60], "weight" => [15, 25], "classes" => ["", "", "", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],
            "Shiloh Shepherd"                             => ["height" => [65, 80], "weight" => [35, 70], "classes" => ["", "", "", "", "", "", "", "x", "x", "x", "x", "x", "x", "x", "", "", "", "", "", ""]],
            "Silky Windhound"                             => ["height" => [45, 60], "weight" => [10, 25], "classes" => ["", "", "x", "x", "x", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]],


        ];
    }

    public function getBreedData()
    {
        return $this->getBreedData;
    }

    public function initProviderBreedName()
    {
        $this->getProviderBreedName = [

            "Affenpinscher"                               => ["Affenpinscher", "Affenpinscher", "Affenpinscher", "Affenpinscher"],
            "Afghaanse windhond"                          => ["Afgaanse windhond", "Afgaanse windhond", "Afghaanse windhond", "Afghaanse windhond"],
            "Aïdi"                                        => [],
            "Airedale Terrier"                            => ["Airedale terrier", "Airedale Terrier", "Airedale Terrier", "Airedale terrier"],
            "Akita"                                       => ["Akita Inu", "Great Japanese Dog", "Akita Inu", "Akita", "Great Japanese dog", "Akita Inu", "Great Japanese Dog"],
            "Alaska Malamute"                             => ["Alaska malamute", "Alaska Malamute", "Alaska Malamute", "Alaska Malamute"],
            "Alpenländische Dachsbracke"                  => [],
            "American Akita"                              => ["Akita", "Great Japanese dog", "Akita Inu", "Great Japanese Dog"],
            "American Foxhound"                           => [],
            "American Staffordshire Terrier"              => ["American staffordshire terrier", "American Staffordshire Terrier", "Amerikaanse Stafford", "American Staffordshire Terrier", "American staffordshire terrier"],
            "Amerikaanse Cocker Spaniel"                  => ["Amerikaanse cocker spaniel", "cocker spaniel", "Amerikaanse Cocker Spaniel", "Amerikaanse Cocker Spaniel", "Amerikaanse cocker spaniel"],
            "Amerikaanse Water Spaniel"                   => ["Amerikaanse Water Spaniel", "Amerikaanse Waterspaniel"],
            "Anatolische Herdershond"                     => ["Akbash (Turkse Herder)", "Anatolische herder", "Akbash", "Anatolische Herder", "Akbash (Turkse herder)", "Anatolische herder", "Akbash (Turkse Herder)", "Anatolische herder"],
            "Anglo-Français de petite vénerie"            => ["Anglo francais de petite venerie", "Anglo Francais de Pettie Venerie", "Anglo francais de petite venerie", "Anglo francais de petite venerie"],
            "Appenzeller Sennenhond"                      => ["Appenzeller Sennenhond", "Appenzeller Sennenhond", "Appenzeller Sennenhond", "Appenzeller Sennenhond"],
            "Argentijnse Dog"                             => ["Argentijnse dog", "Argentijnse dog", "Argentijnse dog"],
            "Ariégois"                                    => [],
            "Australian Cattle Dog"                       => ["Australian cattle dog", "Australian Cattle Dog", "Australian cattle dog", "Australian cattle dog"],
            "Australian Kelpie"                           => ["Australian kelpie", "Australian Kelpie", "Australian kelpie", "Australien kelpie"],
            "Australian Shepherd"                         => ["Australian shepherd", "Australian Shepherd", "Australian shepherd", "Australian shepherd"],
            "Australian Silky Terrier"                    => ["Silky Terrier", "Silky Terrier", "Australian Silky Terrier", "Silky terrier"],
            "Australian Stumpy Tail Cattle Dog"           => [],
            "Australian Terrier"                          => ["Australische terrier", "Australische Terrier", "Australische Terrier", "Australische terrier"],
            "Azawakh"                                     => ["Azawakh", "Azawakh", "Azakwakh"],
            "Barbet"                                      => ["Barbet", "Barbet", "Barbet", "Barbet", "Boerboel"],
            "Barsoi"                                      => ["Barsoi", "Barsoi", "Barsoi", "Barsoi"],
            "Basenji"                                     => ["Basenji", "Basenji", "Basenji", "Basenji"],
            "Basset Artésien Normand"                     => ["Basset artesien normand", "Basset", "Basset Artesien Normand", "Basset artesien normand", "Basset artesien normand"],
            "Basset bleu de Gascogne"                     => ["Basset bleu de gascogne", "Basset", "Basset Bleu de Gascogne", "Basset bleu de gascogne", "Basset bleu de gascogne"],
            "Basset fauve de Bretagne"                    => ["Basset fauve de bretagne", "Basset", "Basset Fauve de Bretagne", "Basset Fauve de Bretagne (Basset Fauve)", "Basset fauve de bretagne"],
            "Basset Hound"                                => ["Basset hound", "Basset Hound", "Basset Hound", "Basset hound"],
            "Bayrischer Gebirgsschweisshund"              => ["Beierse bergzweethond (Bayrischer Gebirgsschweisshund)", "Beierse Bergzweethond", "Beierse bergzweethond", "Beierse bergzweethond (Bayrischer Gebirgsschweisshund)"],
            "Beagle"                                      => ["Beagle", "Beagle", "Beagle", "Beagle"],
            "Beagle Harrier"                              => ["Beagle", "Beagle"],
            "Bearded Collie"                              => ["Bearded collie", "Bearded Collie", "Bearded Collie", "Bearded collie"],
            "Beauceron"                                   => ["Beauceron", "Beauceron", "Beauceron", "Beauceron"],
            "Bedlington Terrier"                          => ["Bedlington terrier", "Bedlington Terrier", "Bedlington Terrier", "Bedlington terrier"],
            "Belgische Herdershond, Groenendaeler"        => ["Belgische herder", "Groenendaeler", "Belgische Herder", "Groenendaeler", "Groenendaeler", "Belgische herder", "Groenendaeler"],
            "Belgische Herdershond, Laekense"             => ["Belgische herder", "Laekense herder", "Belgische Herder", "Laekense Herder", "Laekense herder", "Belgische herder", "Laekense herder"],
            "Belgische Herdershond, Mechelse"             => ["Belgische herder", "Mechelse herder", "Belgische Herder", "Mechelse Herder", "Mechelse herder", "Belgische herder", "Mechelse herder"],
            "Belgische Herdershond, Tervuerense"          => ["Belgische herder", "Tervuerense herder", "Belgische Herder", "Tervuerense Herder", "Tervuerense herder", "Belgische herder", "Tervuerense herder"],
            "Bergamasco"                                  => ["Bergamasco", "Bergamasco", "Bergamasco", "Bergamasco"],
            "Berghond van de Maremmen"                    => ["Berghond van de maremmen en de abruzzen", "Berghond v.d. maremmen", "Berghond van de Maremmen en Abruzzen", "Berghond van de maremmen en de abruzzen"],
            "Berner Laufhund"                             => ["Berner Laufhund", "Berner Laufhund", "Berner Laufhund"],
            "Berner Niederlaufhund"                       => ["Berner Laufhund", "Berner Laufhund"],
            "Berner Sennenhond"                           => ["Berner sennenhond", "Berner Sennenhond", "Berner Sennenhond", "Berner Sennenhond"],
            "Bichon frisé"                                => ["Bichon frise", "Bichon Frise", "Bichon Frise", "Bichon frise"],
            "Billy"                                       => [],
            "Black and Tan Coonhound"                     => ["Black and Tan Coonhound"],
            "Bloedhond"                                   => ["Bloedhond", "Sint-hubertushond", "Bloedhond", "Sint-Hubertushond", "Bloedhond", "Sint Hubertushond", "Bloedhond", "Sint-hubertushond"],
            "Bolognezer"                                  => ["Bolognezer", "Bolognezer", "Bolognezer", "Bolognezer"],
            "Bordeaux Dog"                                => ["Bordeaux Dog", "Bordeaux Dog", "Bordeaux Dog"],
            "Border Collie"                               => ["Border collie", "Border Collie", "Border Collie", "Border collie"],
            "Border Terrier"                              => ["Border terrier", "Border Terrier", "Border Terrier", "Border terrier"],
            "Bosanski Ostrodlaki Gonic Barak"             => [],
            "Boston Terrier"                              => ["Boston terrier", "Boston Terrier", "Boston Terrier", "Boston terrier"],
            "Bouvier des Ardennes"                        => ["Bouvier", "Bouvier", "Bouvier", "Bouvier"],
            "Bouvier des Flandres"                        => ["Bouvier", "Bouvier", "Bouvier", "Bouvier"],
            "Boxer"                                       => ["Boxer", "Boxer", "Boxer", "Boxer"],
            "Bracco Italiano"                             => ["Bracco italiano", "Bracco Italiano", "Bracco Italiano", "Bracco italiano"],
            "Brandlbracke"                                => ["Brandl Brak", "Brandl Brak", "Brandl Brak"],
            "Braque d'Auvergne"                           => ["Braque d'auvergne", "Braque d'auvergne", "Braque d auvergne", "Braque d'auvergne"],
            "Braque de l'Ariège"                          => [],
            "Braque du Bourbonnais"                       => ["Braque de bourbonnais", "Braque de Bourbonnais", "Braque de bourbonnais", "Braque de bourbonnais"],
            "Braque Français, type Gascogne"              => [],
            "Braque Français, type Pyrénées (klein)"      => [],
            "Braque Saint Germain"                        => [],
            "Braziliaanse Terrier"                        => [],
            "Briard"                                      => ["Briard", "Briard", "Briard", "Briard"],
            "Briquet griffon vendéen"                     => ["Griffon Vendeen"],
            "Broholmer"                                   => ["Broholmer", "Broholmer", "Broholmer"],
            "Bull Terrier"                                => ["Bull terrier", "Bull Terrier", "Bull Terrier", "Bull terrier"],
            "Bullmastiff"                                 => ["Bullmastiff", "Bullmastiff", "Bullmastiff", "Bullmastiff"],
            "Cairn Terrier"                               => ["Cairn terrier", "Cairn Terrier", "Cairn Terrier", "Cairn terrier"],
            "Canaänhond"                                  => ["Kanaanhond", "Kanaänhond"],
            "Cane Corso"                                  => ["Cane Corso", "Cane Corso", "Cane Corso", "Cane Corso"],
            "Cão da Serra da Estrela, korthaar"           => ["Cao da serra da estrela", "Cao da Serra de Etrela", "Cao da Serra da Estrela", "Cao da serra da estrela"],
            "Cão da Serra da Estrela, langhaar"           => ["Cao da serra da estrela", "Cao da Serra de Etrela", "Cao da Serra da Estrela", "Cao da serra da estrela"],
            "Cão da Serra de Aires"                       => ["Cao da serra de aires", "Cao da Serra de Aires", "Cao da Serra de aires", "Cao da serra de aires"],
            "Cão de Agua Português"                       => ["Portugese waterhond", "Cão de Agua Português", "Portugese Waterhond", "Portugese waterhond (Cao de Agua)", "Portugese waterhond"],
            "Cão de Castro Laboreiro"                     => [],
            "Cão Fila de São Miguel"                      => [],
            "Cavalier King Charles Spaniël"               => ["Cavalier king charles spaniel", "Cavalier King Charles Spaniel", "Cavalier king charles spaniel"],
            "Centraal-Aziatische Ovcharka"                => ["Centraal-Aziatische Owcharka", "Centraal-Aziatische Owcharka", "Centraal-Aziatische Owcharka"],
            "Cesky Fousek"                                => ["Cesky fousek", "Cesky Fousek", "Cesky fousek", "Cesky fousek"],
            "Cesky Terrier"                               => ["Boheemse terrier", "Cesky terrier", "Boheemse Terrier", "Cesky Terrier", "Cesky Terrier", "Boheemse terrier", "Cesky terrier", "Boheemse terrier"],
            "Chart Polski"                                => ["Chartpolski", "Chartpolski", "Chartpolski"],
            "Chesapeake Bay Retriever"                    => ["Chesapeake bay retriever", "Retriever", "Chesapeake Bay Retriever", "Retriever", "Chesapeake Bay Retriever", "Chesapeake bay retriever"],
            "Chien d'Artois"                              => [],
            "Chihuahua, korthaar"                         => ["Chihuahua", "Chihuahua", "Chihuahua", "Chihuahua"],
            "Chihuahua, langhaar"                         => ["Chihuahua", "Chihuahua", "Chihuahua", "Chihuahua"],
            "Chinese Naakthond"                           => ["Chinese naakthond", "Chinese Naakthond", "Chinese Gekuifde Naakthond", "Chinese naakthond"],
            "Chow Chow"                                   => ["Chow-chow", "Chow-Chow", "Chow-Chow", "Chow-chow"],
            "Cimarrón Uruguayo"                           => [],
            "Ciobanesc Romanesc Carpatin"                 => [],
            "Ciobanesc Romanesc de Bucovina"              => [],
            "Ciobanesc Romanesc Mioritic"                 => [],
            "Cirneco dell'Etna"                           => ["Cirneco dell'etna", "Cirneco dell` etna", "Cirneco dell etna", "Cirneco dell'etna"],
            "Clumber Spaniel"                             => ["Clumber spaniel", "Clumber Spaniel", "Clumber Spaniel", "Clumber spaniel"],
            "Coton de Tuléar"                             => ["Coton de tulear", "Coton de Tulear", "Coton de tulear", "Coton de tulear"],
            "Crnogorski Planinski Gonic"                  => [],
            "Curly Coated Retriever"                      => ["Curly coated retriever", "Retriever", "Curly coated retriever", "Retriever", "Curlycoated Retriever", "Curly coated retriever"],
            "Dalmatische Hond"                            => ["Dalmatische hond", "Dalmatische Hond", "Dalmatische Hond", "Dalmatische hond"],
            "Dandie Dinmont Terrier"                      => ["Dandie dinmont terrier", "Dandie Dinmont Terrier", "Dandie Dinmont Terrier", "Dandie dinmont terrier"],
            "Dashond, korthaar"                           => ["Kortharige teckel", "Rode Standaard Teckel", "Teckel", "Kortharige Teckel", "Rode Standaard Teckel", "Teckel (standaard)", "Teckel", "Kortharige teckel"],
            "Dashond, langhaar"                           => ["Langharige teckel", "Rode Standaard Teckel", "Teckel", "Langharige Teckel", "Rode Standaard Teckel", "Teckel (standaard)", "Teckel", "Langharige teckel"],
            "Dashond, ruwhaar"                            => ["Rode Standaard Teckel", "Teckel", "Rode Standaard Teckel", "Teckel (standaard)", "Teckel"],
            "Deens-Zweedse Boerderijhond"                 => [],
            "Deerhound"                                   => ["Deerhound", "Deerhound", "Deerhound", "Deerhound"],
            "Dobermann"                                   => ["Dobermann", "Dobermann", "Dobermann", "Dobermann"],
            "Dogo Canario"                                => ["Dogo-Canario", "Perro de Presa Canario", "Dogo Canario", "Perro de Presa Canario", "Dogo-Canario", "Perro de Presa Canario", "Dogo-Canario", "Perro de Presa Canario"],
            "Drentsche Patrijshond"                       => ["Drentse patrijshond", "Drentse Patrijshond", "Drentse patrijshond", "Drentse patrijshond"],
            "Drever"                                      => [],
            "Duitse Brak"                                 => ["Duitse brak", "Duitse Brak", "Duitse brak", "Duitse brak"],
            "Duitse Dog, blauw"                           => ["Duitse dog", "Deense Dog", "Zwarte Duitse Dog", "Duitse Dog", "Duitse dog", "Deense Dog"],
            "Duitse Dog, geel/gestroomd"                  => ["Duitse dog", "Deense Dog", "Gevlekte duitse dog", "Zwarte Duitse Dog", "Duitse Dog", "Duitse dog", "Deense Dog"],
            "Duitse Dog, zwart/zwart-wit"                 => ["Duitse dog", "Deense Dog", "Zwarte Duitse Dog", "Duitse Dog", "Duitse dog", "Deense Dog"],
            "Duitse Dwergpinscher"                        => ["Dwergpinscher", "Dwergpinscher", "Dwergpinscher", "Dwergpinscher"],
            "Duitse Herdershond Langstokhaar"             => ["Duitse herder", "Oud Duitse Herdershond", "Duitse Herder", "Oud Duitse Herdershond", "Duitse Herdershond", "Oud Duitse Herdershond", "Duitse herder", "Oud Duitse Herdershond"],
            "Duitse Herdershond Stokhaar"                 => ["Duitse herder", "Duitse Herder", "Oud Duitse Herdershond", "Duitse Herdershond", "Duitse herder"],
            "Duitse Jachtterrier"                         => ["Duitse jachthond", "Duitse jachthond"],
            "Duitse Pinscher"                             => ["Duitse pinscher", "Duitse Pinscher", "Duitse Pinscher", "Duitse pinscher"],
            "Duitse Staande Hond Draadhaar"               => ["Duitse staande hond", "Duitse jachthond", "Duitse Jachthond", "Duitse Staande hond", "Duitse staande hond (glad en draadharig)", "Duitse staande hond"],
            "Duitse Staande Hond Korthaar"                => ["Duitse staande hond", "Duitse jachthond", "Duitse Jachthond", "Duitse Staande hond", "Duitse staande hond (glad en draadharig)", "Duitse staande hond"],
            "Duitse Staande Hond Langhaar"                => ["Duitse staande hond", "Duitse jachthond", "Duitse Jachthond", "Duitse Staande hond", "Duitse staande hond (glad en draadharig)", "Duitse staande hond"],
            "Duitse Staande Hond Stekelhaar"              => ["Duitse staande hond", "Duitse jachthond", "Duitse Jachthond", "Duitse Staande hond", "Duitse staande hond (glad en draadharig)", "Duitse staande hond"],
            "Duitse Wachtelhond"                          => [],
            "Dunker"                                      => [],
            "Dwergdashond, korthaar"                      => ["Kortharige dwergteckel", "Kortharige dwergteckel", "Teckel (dwerg)", "Teckel", "Kortharige dwergteckel"],
            "Dwergdashond, langhaar"                      => ["Teckel (dwerg)", "Teckel"],
            "Dwergdashond, ruwhaar"                       => ["Ruwharige dwergteckel", "Ruwharige Dwergteckel", "Teckel (dwerg)", "Teckel", "Ruwharige dwergteckel"],
            "Dwergkeeshond"                               => ["Dwergkees", "Dwergkees", "Keeshond, dwerg", "Keeshond (dwerg, klein)", "Dwergkees"],
            "Dwergpoedel, grijs-abrikoos-rood"            => ["Dwergpoedel", "Grijze dwergpoedel", "Poedel", "Dwergpoedel", "Grijze Dwergpoedel", "Poedel", "Poedel (dwerg, toy)", "Dwergpoedel", "Poedel"],
            "Dwergpoedel, zwart-wit-bruin"                => ["Dwergpoedel", "Poedel", "Dwergpoedel", "Poedel", "Poedel (dwerg, toy)", "Dwergpoedel", "Poedel"],
            "Dwergschnauzer, peper en zout"               => ["Dwergschnauzer", "Schnauzer", "Dwergschnauzer", "Schnauzer", "Schnauzer (dwerg)", "Dwergschnauzer"],
            "Dwergschnauzer, wit"                         => ["Dwergschnauzer", "Schnauzer", "Dwergschnauzer", "Schnauzer", "Schnauzer (dwerg)", "Dwergschnauzer"],
            "Dwergschnauzer, zwart"                       => ["Dwergschnauzer", "Schnauzer", "Dwergschnauzer", "Schnauzer", "Schnauzer (dwerg)", "Dwergschnauzer", "Schnauzer"],
            "Dwergschnauzer, zwart-zilver"                => ["Dwergschnauzer", "Schnauzer", "Dwergschnauzer", "Schnauzer", "Schnauzer (dwerg)", "Dwergschnauzer", "Schnauzer"],
            "Engelse Bulldog"                             => ["Engelse bulldog", "Engelse Bulldog", "Engelse bulldog"],
            "Engelse Cocker Spaniel"                      => ["Cocker spaniel", "Engelse cocker spaniel", "Engelse cocker spaniel", "Engelse Cocker Spaniel", "Engelse cocker spaniel", "Cocker spaniel"],
            "Engelse Setter"                              => ["Engelse setter", "Engelse setter", "Engelse Setter", "Engelse setter"],
            "Engelse Springer Spaniel"                    => ["Engelse springer spaniel", "Springer Spaniel", "Engelse springer spaniel", "Springer Spaniel", "Engelse Springer Spaniel", "Engelse springer spaniel"],
            "Engelse Toy Terrier"                         => ["Engelse Toy Terrier", "Engelse Toy Terrier"],
            "English Foxhound"                            => ["Foxhound (engelse)"],
            "Entlebucher Sennenhond"                      => ["Entlebucher sennenhond", "Entlebucher Sennenhond", "Entlebucher sennenhond", "Entlebucher sennenhond"],
            "Epagneul Bleu de Picardie"                   => ["Epagneul bleu de picardie", "Epagneul Bleu de Picardie", "Epagneul bleu de picardie", "Epagneul bleu de picardie"],
            "Epagneul Breton"                             => ["Epagneul breton", "Brittany", "Epagneul Breton", "Brittany Spaniel", "Epagneul breton", "Epagneul breton"],
            "Epagneul de Pont-Audemer"                    => [],
            "Epagneul français"                           => ["Epagneul francais", "Epagneul Francais", "Epagneul francais", "Epagneul francais"],
            "Epagneul Nain Continental, Papillon"         => ["Epagneul papillon", "Epagneul Papillon", "Vlinderhond", "Epagneul papillon", "Epagneul papillon"],
            "Epagneul Nain Continental, Phalène"          => ["Epagneul phalene", "Epagneul Phalene", "Vlinderhond", "Epagneul phalene", "Epagneul phalene"],
            "Epagneul Picard"                             => [],
            "Erdélyi Kopo"                                => ["Erdelyi kopo", "Erdelyi kopo", "Erdelyi kopo"],
            "Eurasier"                                    => ["Eurasier", "Eurasier", "Eurasier", "Eurasier"],
            "Field Spaniel"                               => ["Field spaniel", "Field Spaniel", "Field Spaniel", "Field spaniel"],
            "Fila Brasileiro"                             => ["Fila brasileiro", "Fila Brasileiro", "Fila brasileiro", "Fila brasileiro"],
            "Finse Brak"                                  => [],
            "Finse Lappenhond"                            => ["Finse lappenhond(Lapinkoira)", "Finse Lappenhond (Lapinkoira)", "Lapinkoira", "Lapphund", "Finse lappenhond (Lapinkoira)", "Finse lappenhond (Lapinkoira)"],
            "Finse Spits"                                 => ["Finse spits", "Finse Spits", "Finse Spits", "Finse spits"],
            "Flatcoated Retriever"                        => ["Flat coated retriever", "Retriever", "Flat Coated Retriever", "Retriever", "Flatcoated Retriever", "Flat coated retriever"],
            "Foxterrier Draadhaar"                        => ["Foxterrier", "Foxterrier", "Foxterrier draadhaar", "Foxterrier (glad en draadharig)", "Foxterrier"],
            "Foxterrier Gladhaar"                         => ["Foxterrier", "Foxterrier", "Foxterrier (glad en draadharig)", "Foxterrier"],
            "Français Blanc et Noir"                      => [],
            "Français Blanc et Orange"                    => [],
            "Français Tricolore"                          => [],
            "Franse Bulldog"                              => ["Franse bulldog", "Franse Bulldog", "Franse bulldog"],
            "Galgo Español"                               => ["Galgo Espanol", "Galgo espanol", "Galgo espanol"],
            "Gammel Dansk Hønsehund"                      => [],
            "Golden Retriever"                            => ["Golden retriever", "Retriever", "Golden Retriever", "Retriever", "Golden Retriever", "Golden retriever"],
            "Gonczy Polski"                               => [],
            "Gordon Setter"                               => ["Gordon setter", "Gorden Setter", "Gordon Setter", "Gordon setter"],
            "Gos d'Atura Català"                          => ["Gos d'Atura", "Gos d` Atura", "Gos d Atura", "Gos d'Atura"],
            "Grand anglo-français blanc et noir"          => [],
            "Grand anglo-français blanc et orange"        => [],
            "Grand anglo-français tricolore"              => [],
            "Grand Basset griffon vendéen"                => ["Basset griffon vendeen", "Grand basset griffon vendeen", "Basset Griffon Vendeen", "Grand basset Griffon Vendeen", "Griffon Vendeen", "Basset Griffon vendeen (Grand)", "Basset griffon vendeen", "Grand basset griffon vendeen"],
            "Grand bleu de Gascogne"                      => ["Grand Bleu de Cascogne"],
            "Grand gascon saintongeois"                   => ["Bloedhond"],
            "Grand Griffon vendéen"                       => ["Griffon Vendeen"],
            "Greyhound"                                   => ["Greyhound", "Greyhound", "Greyhound", "Greyhound"],
            "Griffon belge"                               => ["Griffon belge", "Griffon Belge", "Griffon belge"],
            "Griffon bleu de Gascogne"                    => [],
            "Griffon bruxellois"                          => ["Griffon bruxellois", "Griffon Bruxellois", "Griffon Bruxellois", "Griffon bruxellois"],
            "Griffon fauve de Bretagne"                   => ["Griffon Fauve de Bretagne", "Griffon Fauve de Bretagne"],
            "Griffon Korthals"                            => ["Korthals griffon", "Korthals Griffon", "Griffon Korthals", "Korthals griffon"],
            "Griffon nivernais"                           => [],
            "Groenlandhond"                               => ["Groenlandse hond", "Groendlandse Hond", "Canadese Eskimohond", "Groenlandse Hond"],
            "Grote Keeshond, bruin-zwart"                 => ["Grote keeshond", "Wolfskeeshond", "Grote Keeshond", "Wolfkeeshond", "Keeshond (groot)", "Grote keeshond", "Wolfskeeshond"],
            "Grote Keeshond, wit"                         => ["Grote keeshond", "Wolfskeeshond", "Grote Keeshond", "Wolfkeeshond", "Keeshond (groot)", "Grote keeshond", "Wolfskeeshond"],
            "Grote Keeshond, wolfsgrijs"                  => ["Grote keeshond", "Wolfskeeshond", "Grote Keeshond", "Wolfkeeshond", "Keeshond (groot)", "Grote keeshond", "Wolfskeeshond"],
            "Grote Münsterlander"                         => ["Grote munsterlander", "Grote Munsterlander", "Grote Munsterlander", "Grote munsterlander"],
            "Grote Poedel, grijs-abrikoos-rood"           => ["Grote poedel", "Koningspoedel", "Poedel", "Grote Poedel", "Koningspoedel", "Poedel", "Zwarte Grote Poedel", "Koningspoedel", "Grote poedel", "Koningspoedel"],
            "Grote Poedel, zwart-wit-bruin"               => ["Grote poedel", "Koningspoedel", "Poedel", "Grote Poedel", "Koningspoedel", "Poedel", "Zwarte Grote Poedel", "Koningspoedel", "Grote poedel", "Poedel"],
            "Grote Zwitserse Sennenhond"                  => ["Grote Zwitserse Sennenhond", "Grote Zwitserse Sennenhond", "Grote Zwitserse Sennenhond", "Grote Zwitserse Sennenhond"],
            "Haldenstøvare"                               => [],
            "Hamiltonstövare"                             => ["Hamilton Stövare"],
            "Hannover'scher Schweisshund"                 => [],
            "Harrier"                                     => ["Harrier"],
            "Havanezer"                                   => ["Havanezer", "Havenezer", "Havanezer", "Havanezer"],
            "Heidewachtel"                                => ["Heidewachtel", "Kleine munsterlander", "Heidewachtel", "Kleine Musterlander", "Heidewachtel/Kl munsterlander", "Heidewachtel", "Kleine munsterlander"],
            "Hellinikos Ichnilatis"                       => [],
            "Hokkaido"                                    => [],
            "Hollandse Herdershond, korthaar"             => ["Hollandse herder", "Hollandse Herder", "Hollandse herder", "Hollandse herder"],
            "Hollandse Herdershond, langhaar"             => ["Hollandse herder", "Langharige hollandse herder", "Hollandse Herder", "Langharige Hollandse Herder", "Hollandse herder", "Hollandse herder", "Langharige hollandse herder"],
            "Hollandse Herdershond, ruwhaar"              => ["Hollandse herder", "Ruwharige hollandse herder", "Hollandse Herder", "Ruwharige Hollandse Herder", "Hollandse herder", "Hollandse herder", "Ruwharige hollandse herder"],
            "Hollandse Smoushond"                         => ["Hollandse smoushond", "Smoushond", "Hollandse Smoushond", "Smoushond", "Hollandse smoushond", "Hollandse Smoushond", "Smoushond"],
            "Hovawart"                                    => ["Hovawart", "Hovawart", "Hovawart", "Hovawart"],
            "Hrvatski Ovcar"                              => [],
            "Hygenhund"                                   => [],
            "Ierse rood-witte Setter"                     => ["Ierse Setter", "Ierse setter"],
            "Ierse Setter"                                => ["Ierse setter", "Ierse Setter", "Ierse Setter", "Ierse setter"],
            "Ierse Terrier"                               => ["Ierse terrier", "Ierse Terrier", "Ierse Terrier", "Ierse terrier"],
            "Ierse Water Spaniel"                         => ["Ierse waterspaniel", "Ierse Waterspaniel", "Ierse Waterspaniel", "Ierse waterspaniel"],
            "Ierse Wolfshond"                             => ["Ierse wolfshond", "Ierse Wolfshond", "Ierse Wolfshond", "Ierse wolfshond"],
            "IJslandse Hond"                              => ["Ijslandse hond", "Ijslandse Hond", "IJslandse hond", "Ijslandse hond"],
            "Irish Glen of Imaal Terrier"                 => ["Glen of Imaalterrier", "Ierse glen of iemaal terrier", "Glen of Imaalterrier", "Glen of Imaalterrier", "Irish Glen of Imaal Terrier", "Ierse glen of iemaal terrier", "Glen of Imaalterrier"],
            "Irish Soft Coated Wheaten Terrier"           => ["Irish softcoated wheaten terrier", "Irish Softcoated Weaten Terrier", "Irish Softcoated Wheaten Terrier", "Irish softcoated wheaten terrier"],
            "Istarski Kratkodlaki Gonic"                  => [],
            "Istarski Ostrodlaki Gonic"                   => [],
            "Italiaans Windhondje"                        => ["Italiaans windhondje", "Italiaans Windhondje", "Italiaans Windhondje", "Italiaans windhondje"],
            "Jack Russell Terrier"                        => ["Jack Russel Terrier", "Kortbenige jack russell terrier", "Jack Russell Terrier", "Kortbenige Jack Russell Terrier", "Jack Russell Terrier", "Jack Russel Terrier", "Kortbenige jack russel terrier"],
            "Jämthund"                                    => ["Jamthund", "Jamthund", "Jamthund"],
            "Japanse Spaniel"                             => ["Japanse spaniel", "Japanse Spaniel", "Japanse Spaniel", "Japanse spaniel"],
            "Japanse Spits"                               => ["Japanse spits", "Japanse Spits", "Japanse Spits", "Japanse spits"],
            "Japanse Terrier"                             => [],
            "Jura Laufhund"                               => ["Jura laufhund", "Jura Laufhund", "Jura Laufhund", "Jura laufhund"],
            "Jura Niederlaufhund"                         => ["Jura Laufhund", "Jura laufhund"],
            "Kai"                                         => [],
            "Kaninchen Dashond, korthaar"                 => ["Teckel (kaninchen)", "Teckel"],
            "Kaninchen Dashond, langhaar"                 => ["Teckel (kaninchen)", "Teckel"],
            "Kaninchen Dashond, ruwhaar"                  => ["Teckel (kaninchen)", "Teckel"],
            "Karelische Berenhond"                        => ["Karelische Berenhond", "Karelische Berenhond", "Karelische Berenhond"],
            "Kaukasische Ovcharka"                        => ["Kaukasische herder", "Kaukasische owcharka", "Kaukasische Herder", "Kaukasische Owcharka", "Kaukasische herder", "Kaukasische herder", "Kaukasische owcharka"],
            "Kerry Blue Terrier"                          => ["Kerry blue terrier", "Kerry Blue Terrier", "Kerry Blue Terrier", "Kerry blue terrier"],
            "King Charles Spaniël"                        => ["King charles spaniel", "King Charles Spaniel", "King charles spaniel"],
            "Kishu"                                       => [],
            "Kleine Keeshond, bruin-zwart"                => ["Kleine keeshond", "Kleine keeshond", "Keeshond (dwerg, klein)", "Kleine keeshond"],
            "Kleine Keeshond, oranje en anderskleurig"    => ["Kleine keeshond", "Kleine keeshond", "Keeshond (dwerg, klein)", "Kleine keeshond"],
            "Kleine Keeshond, wit"                        => ["Kleine keeshond", "Kleine keeshond", "Keeshond (dwerg, klein)", "Kleine keeshond"],
            "Komondor"                                    => ["Komondor", "Komondor", "Komondor", "Komondor"],
            "Korea Jindo Dog"                             => [],
            "Kraski Ovcar"                                => ["Kraski Ovcar", "Kraski Ovcar", "Kraski Ovcar", "Kraski Ovcar"],
            "Kromfohrländer"                              => ["Kromfohrlander", "Kromfohrlander", "Kromfohrlander", "Kromfohrlander"],
            "Kuvasz"                                      => ["Kuvasz", "Kuvasz", "Kuvasz", "Kuvasz"],
            "Labrador Retriever"                          => ["Labrador retriever", "Retriever", "Labrador Retriever", "Retriever", "Labrador retriever", "Labrador retriever"],
            "Lagotto Romagnolo"                           => ["Lagotto Romagnolo", "Lagotto Romagnolo", "Lagotto Romagnolo", "Lagotto Romagnolo"],
            "Lakeland Terrier"                            => ["Lakeland terrier", "Lakeland Terrier", "Lakeland Terrier", "Lakeland terrier"],
            "Lancashire Heeler"                           => ["Lancashire Heeler"],
            "Landseer ECT"                                => ["Landseer", "Landseer", "Landseer", "Landseer"],
            "Laplandse Herdershond"                       => ["Laplandse Herdershond", "Lapinporokoira", "Laplandse Herdershond", "Laplandse Herdershond"],
            "Leeuwhondje"                                 => ["Leeuwhondje", "Leeuwhondje", "Leeuwhondje", "Leeuwhondje"],
            "Leonberger"                                  => ["Leonberger", "Leonberger", "Leonberger", "Leonberger"],
            "Lhasa Apso"                                  => ["Lhasa apso", "Ihasa Apso", "Lhaso Apso", "Lhasa apso", "Lhasa apso"],
            "Luzerner Laufhund"                           => ["Luzerner Laufhund", "Luzerner Laufhund", "Luzerner Laufhund"],
            "Luzerner Niederlaufhund"                     => ["Luzerner Laufhund", "Luzerner Laufhund"],
            "Magyar Agár"                                 => [],
            "Maltezer"                                    => ["Maltezer", "Malthezer", "Maltezer", "Maltezer"],
            "Manchester Terrier"                          => ["Manchester terrier", "Manchester Terrier", "Manchester Terrier", "Manchester terrier"],
            "Markiesje"                                   => ["Markiesje", "Markiesje", "Markiesje", "Markiesje"],
            "Mastiff"                                     => ["Mastiff", "Mastiff", "Mastiff", "Mastiff"],
            "Mastin del Pirineo"                          => ["Mastin de Los Pyreneos", "Mastin de Los Pyreneos", "Mastin de Los Pyreneos", "Mastin de Los Pyreneos"],
            "Mastin Español"                              => ["Mastin espanol", "Mastin Espanol", "Mastin espanol", "Mastin espanol"],
            "Mastino Napoletano"                          => ["Mastino napoletano", "Mastino", "Mastino", "Mastino Napoletano", "Mastino Napoletano", "Mastino napoletano", "Mastino "],
            "Mexicaanse Naakthond, medio"                 => ["Mexicaanse naakthond", "Xoloitzcuintle", "Mexicaanse Naakthond", "Xoloitzcuintle", "Mexicaanse naakthond", "Mexicaanse naakthond"],
            "Mexicaanse Naakthond, miniatuur"             => ["Mexicaanse naakthond", "Xoloitzcuintle", "Mexicaanse Naakthond", "Xoloitzcuintle", "Mexicaanse naakthond", "Mexicaanse naakthond"],
            "Mexicaanse Naakthond, standaard"             => ["Mexicaanse naakthond", "Xoloitzcuintle", "Mexicaanse Naakthond", "Xoloitzcuintle", "Mexicaanse naakthond", "Mexicaanse naakthond"],
            "Middenslag Keeshond, bruin-zwart"            => ["Middenslag keeshond", "Keeshond, Middenslag", "Middenslag Keeshond", "Keeshond (middel)", "Middenslag keeshond"],
            "Middenslag Keeshond, oranje en anderkleurig" => ["Middenslag keeshond", "Keeshond, Middenslag", "Middenslag Keeshond", "Keeshond (middel)", "Middenslag keeshond"],
            "Middenslag Keeshond, wit"                    => ["Middenslag keeshond", "Keeshond, Middenslag", "Middenslag Keeshond", "Keeshond (middel)", "Middenslag keeshond"],
            "Middenslag Poedel, grijs-abrikoos-rood"      => ["Middenslag poedel", "Poedel", "Middenslag Poedel", "Poedel", "Poedel (middenslag)", "Middenslag poedel", "Poedel"],
            "Middenslag Poedel, zwart-wit-bruin"          => ["Middenslag poedel", "Poedel", "Middenslag Poedel", "Poedel", "Poedel (middenslag)", "Middenslag poedel", "Poedel"],
            "Middenslag Schnauzer, peper en zout"         => ["Middenslag schnauzer", "Schnauzer", "Middenslag Schnauzer", "Schnauzer", "Schnauzer (middenslag)", "Middenslag schnauzer", "Schnauzer"],
            "Middenslag Schnauzer, zwart"                 => ["Middenslag schnauzer", "Schnauzer", "Middenslag Schnauzer", "Schnauzer", "Schnauzer (middenslag)", "Middenslag schnauzer", "Schnauzer"],
            "Miniatuur Bull Terrier"                      => ["Miniature bull terrier", "Bull Terrier, miniatuur", "Miniature Bull Terrier", "Bull Terrier Miniatuur", "Miniature bull terrier", "Miniature bull terrier"],
            "Mopshond"                                    => ["Mopshond", "Mopshond", "Mopshond", "Mopshond"],
            "Mudi"                                        => ["Mudi (Hongaarse herdershond)", "Mudi (Hongaarse herdershond)", "Mudi (Hongaarse herdershond)"],
            "Nederlandse Kooikerhondje"                   => ["Kooikerhondje", "Kooikerhondje", "Kooikerhondje", "Kooikerhondje"],
            "Nederlandse Schapendoes"                     => ["Schapendoes", "Schapendoes", "Schapendoes", "Schapendoes"],
            "Newfoundlander"                              => ["Newfoundlander", "Newfoundlander", "Newfoundlander", "Newfoundlander"],
            "Noorse Buhund"                               => ["Noorse Buhund", "Noorse Buhund", "Buhund", "Noorse Buhund", "Noorse Buhund"],
            "Noorse Elandhond Grijs"                      => ["Noorse elandhond", "Noorse Elandhond", "Noorse elandhond", "Noorse elandhond"],
            "Noorse Elandhond Zwart"                      => ["Noorse Elandhond", "Noorse elandhond", "Noorse elandhond"],
            "Noorse Lundehund"                            => ["Norsk Lundehund", "Noorse lundehund", "Lundehond", "Norsk Lundehund", "Norsk Lundehund", "Noorse lundehund"],
            "Norfolk Terrier"                             => ["Norfolk terrier", "Norfolk Terrier", "Norfolk Terrier", "Norfolk terrier"],
            "Norrbottenspets"                             => ["Norbottenspets (pohjanpystykorva)", "Norbottenspets (pohjanpystykorva)", "Norbottenspets (pohjanpystykorva)"],
            "Norwich Terrier"                             => ["Norwich terrier", "Norwich Terrier", "Norwich terrier"],
            "Nova Scotia Duck Tolling Retriever"          => ["Nova scotia duck tolling retriever", "Retriever", "Nova Scotia Duck Tolling Retriever", "Retriever", "Nova Scotia Duck Tolling Retriever", "Nova scotia duck tolling retriever"],
            "Ogar Polski"                                 => ["Ogar Polski", "Ogar Polski", "Ogar Polski"],
            "Old English Sheepdog"                        => ["Bobtail", "Old english sheepdog", "Bobtail", "Old English Sheepdog", "Bobtail (Old English Sheepsdog)", "Bobtail", "Old english sheepdog"],
            "Oostenrijkse Pinscher"                       => ["Oostenrijkse pinscher", "Oostenrijkse Pinscher", "Oostenrijkse pinscher", "Oostenrijkse pinscher"],
            "Oostsiberische Laika"                        => ["Siberische Husky"],
            "Otterhound"                                  => ["Otterhound", "Otterhound", "Otterhond", "Otterhound"],
            "Parson Russell Terrier"                      => ["Parson jack russell terrier", "Parson Jack Russel Terrier", "Parson jack russel terrier", "Parson jack russel terrier"],
            "Pekingees"                                   => ["Pekinees", "Pekinees", "Pekinees", "Pekinees"],
            "Perdigueiro de Burgos"                       => [],
            "Perdigueiro Português"                       => ["Perdigueiro Portigeus", "Perdigueiro Portigeus", "Perdiqueiro Portigeus"],
            "Perro de Agua Español"                       => ["Perro de agua espanol", "Perro de Agua Espanol", "Spaanse Waterhond", "Perro de agua espagnol (Spaanse waterhond) ", "Perro de agua espanol"],
            "Perro de Pastor Mallorquin"                  => ["Ca de Bestiar", "Ca de Bestiar", "Ca de Bestiar"],
            "Perro Dogo Mallorquín"                       => [],
            "Peruaanse Haarloze Hond, groot"              => ["Peruaanse behaarde naakthond", "Peruaanse behaarde naakthond"],
            "Peruaanse Haarloze Hond, middenslag"         => ["Peruaanse behaarde naakthond", "Peruaanse behaarde naakthond"],
            "Peruaanse Haarloze Hond, miniatuur"          => ["Peruaanse behaarde naakthond", "Peruaanse behaarde naakthond"],
            "Petit Basset griffon vendéen"                => ["Basset griffon vendeen", "Petit basset griffon vendeen", "Basset Griffon Vendeen", "Griffon Vendeen", "Basset Griffon Vendeen (Petit)", "Petit basset griffon vendeen", "Petit basset griffon vendeen"],
            "Petit Bleu de Gascogne"                      => ["Petit blue de gascogne", "Petit Blue de Gascogne", "Petit bleu de gascogne"],
            "Petit Brabançon"                             => ["Petite brabancon", "Petite Brabancon", "Petite brabancon", "Petite brabancon"],
            "Petit gascon saintongeois"                   => ["Bloedhond"],
            "Pharaohond"                                  => ["Pharaohond", "Pharaohond", "Pharaohond", "Pharaohond"],
            "Picardische Herdershond"                     => ["Picardische herder", "Picardische Herder", "Picardische herder", "Picardische herder"],
            "Podenco Canario"                             => [],
            "Podenco Ibicenco, gladhaar"                  => ["Podenco ibicenco", "Podenco (Ibicenco)", "Podenco Ibicenco", "Podenco ibicenco"],
            "Podenco Ibicenco, ruwhaar"                   => ["Podenco ibicenco", "Podenco (Ibicenco)", "Podenco Ibicenco", "Podenco ibicenco"],
            "Podengo Português, groot, gladhaar"          => ["Podengo (Portugese)"],
            "Podengo Português, groot, ruwhaar"           => ["Podengo (Portugese)"],
            "Podengo Português, klein, gladhaar"          => ["Podengo (Portugese)"],
            "Podengo Português, klein, ruwhaar"           => ["Podengo (Portugese)"],
            "Podengo Português, middenslag, gladhaar"     => ["Podengo (Portugese)"],
            "Podengo Português, middenslag, ruwhaar"      => ["Podengo (Portugese)"],
            "Poedelpointer"                               => ["Poedelpointer", "Pointer", "Poedel (middenslag)", "Pointer", "Poedel"],
            "Pointer"                                     => ["Pointer", "Pointer", "Pointer", "Pointer"],
            "Poitevin"                                    => [],
            "Polski Owczarek Nizinny"                     => ["Polski owczarek nizinny", "Polski Owczarek Nizinny", "Polski Owczarek Nizinny", "Polski owczarek nizinny "],
            "Porcelaine"                                  => ["Porcelaine", "Porcelaine", "Porcelaine", "Porcelaine"],
            "Posavski Gonic"                              => [],
            "Puli, anders dan wit"                        => ["Puli", "Puli", "Puli", "Puli"],
            "Puli, wit"                                   => ["Puli", "Puli", "Puli", "Puli"],
            "Pumi"                                        => ["Pumi", "Pumi", "Pumi", "Pumi"],
            "Pyreneese Berghond"                          => ["Pyreneese berghond", "Pyreneese Berghond", "Pyreneese Berghond", "Pyreneese berghond"],
            "Pyreneese Herdershond à face rase"           => ["Pyreneese herder", "Pyreneese Herder", "Pyreneese Herdershond", "Pyreneese herder"],
            "Pyreneese Herdershond à poil long"           => ["Pyreneese herder", "Pyreneese Herder", "Pyreneese Herdershond", "Pyreneese herder"],
            "Rafeiro do Alentejo"                         => [],
            "Rhodesian Ridgeback"                         => ["Pronkrug", "Rhodesian ridgeback", "Pronkrug", "Rhodesian Ridgeback", "Pronkrug", "Rhodesian Ridgeback", "Rhodesian ridgeback", "Pronkrug"],
            "Riesenschnauzer, peper en zout"              => ["Riesenschnauzer", "Schnauzer", "Riesenschnauzer", "Schnauzer", "Riesenschnauzer", "Riesenschnauzer", "Schnauzer"],
            "Riesenschnauzer, zwart"                      => ["Riesenschnauzer", "Schnauzer", "Riesenschnauzer", "Schnauzer", "Riesenschnauzer", "Riesenschnauzer", "Schnauzer"],
            "Rottweiler"                                  => ["Rottweiler", "Rottweiler", "Rottweiler", "Rottweiler"],
            "Russian Toy, korthaar"                       => [],
            "Russian Toy, langhaar"                       => [],
            "Russisch-Europese Laika"                     => ["Siberische Husky", "West siberische laika"],
            "Saarlooswolfhond"                            => ["Saarloos wolfhond", "Saarloos Wolfhond", "Saarloos wolfhond", "Saarloos wolfhond"],
            "Sabueso Español"                             => ["Sabueso Espagnol", "Sabueso Espagnol", "Sabueso Espagnol"],
            "Saluki"                                      => ["Saluki", "Saluki", "Saluki", "Saluki"],
            "Samojeed"                                    => ["Samojeed", "Samojeed", "Samojeed", "Samojeed"],
            "Sarplaninac"                                 => ["Sarplaninac", "Sarplaninac", "Saplaninac", "Sarplaninac"],
            "Schillerstövare"                             => [],
            "Schipperke"                                  => ["Schipperke", "Schipperke", "Schipperke", "Schipperke"],
            "Schotse Herdershond Korthaar"                => ["Kortharige schotse herder", "Schotse herder", "Kortharige Schotse herder", "Schotse Collie", "Schotse herder", "Schotse Herdershond (kort- en langharig)", "Schotse herder", "Kortharige schotse herder"],
            "Schotse Herdershond Langhaar"                => ["Langharige schotse herder", "Schotse herder", "Langharige Schotse Herder", "Schotse Collie", "Schotse herder", "Schotse Herdershond (kort- en langharig)", "Schotse herder", "Langharige schotse herder"],
            "Schotse Terrier"                             => ["Schotse terrier", "Schotse Terrier", "Schotse Terrier", "Schotse terrier"],
            "Schwyzer Laufhund"                           => ["Schweizer Laufhund", "Schweizer Laufhund", "Schweizer Laufhund"],
            "Schwyzer Niederlaufhund"                     => ["Schweizer Laufhund"],
            "Sealyham Terrier"                            => ["Sealyham terrier", "Sealyham Terrier", "Sealyham Terrier", "Sealyham terrier"],
            "Segugio Italiano Gladhaar"                   => ["Segugio Italiano  Gladhaar", "Segugio Italiano"],
            "Segugio Italiano Ruwhaar"                    => ["Segugio Italiano"],
            "Shar-Pei"                                    => ["Shar pei", "Shar-Pei", "Shar pei"],
            "Shetland Sheepdog"                           => ["Sheltie", "Shetland sheepdog", "Sheltie", "Shetland Sheepdog", "Sheltie/Shetland sheepdog", "Sheltie", "Shetland sheepdog"],
            "Shiba"                                       => ["Shiba inu", "Shiba Inu", "Shiba", "Shiba inu"],
            "Shih Tzu"                                    => ["Shih tsu", "Shih Tsu", "Shih-Tzu", "Shih tzu"],
            "Shikoku"                                     => ["Shiloh Shepherd"],
            "Siberian Husky"                              => ["Siberische husky", "Siberische Husky", "Siberische Husky", "Siberische husky"],
            "Sint Bernard, korthaar"                      => ["Kortharige sint-bernard", "Sint-bernard", "Kortharige Sint-Bernard", "Sint-Bernard", "Sint Bernard", "Kortharige sint-bernard", "Sint-bernard"],
            "Sint Bernard, langhaar"                      => ["Langharige sint-bernard", "Sint-bernard", "Langharige Sint-Bernard", "Sint-Bernard", "Sint Bernard", "Sint-bernard"],
            "Skye Terrier"                                => ["Skye terrier", "Skye Terrier", "Skye Terrier", "Skye terrier"],
            "Sloughi"                                     => ["Sloughi", "Sloughi", "Sloughi", "Sloughi"],
            "Slovenský Cuvac"                             => ["Slovensky Cuvac", "Slovenský Cuvac", "Slovensky Cuvac "],
            "Slovenský Hrubosrstý Stavac"                 => [],
            "Slovenský Kopov"                             => [],
            "Smålandsstövare"                             => [],
            "Spinone Italiano"                            => ["Spinone", "Spinone", "Spinone", "Spinone"],
            "Srpski Gonic"                                => [],
            "Srpski Trobojni Gonic"                       => [],
            "Stabijhoun"                                  => ["Friese stabij", "Stabyhoun", "Friese Stabij", "Stabyhoun", "Friese stabij (Stabyhound)", "Stabyhoun", "Friese stabij"],
            "Staffordshire Bull Terrier"                  => ["Staffordshire bull terrier", "Staffordshire Bull Terrier", "Staffordshire Bull Terrier", "Staffordshire bull terrier"],
            "Steirische ruwharige Brak"                   => [],
            "Sussex Spaniel"                              => ["Sussex spaniel", "Sussex Spaniel", "Sussex Spaniel", "Sussex spaniel"],
            "Taiwan Dog"                                  => [],
            "Tatrahond"                                   => ["Tatra", "Tatra", "Tatra", "Tatra"],
            "Thai Bangkaew Dog"                           => [],
            "Thai Ridgeback Dog"                          => ["Thai ridgeback dog", "Thai ridgeback dog", "Thai ridgeback dog"],
            "Tibetaanse Mastiff"                          => ["Tibetaanse mastiff", "Tibetaanse Mastiff", "Tibetaanse Mastiff", "Tibetaanse mastiff"],
            "Tibetaanse Spaniel"                          => ["Tibetaanse spaniel", "Tibetaanse Spaniel", "Tibetaanse Spaniel", "Tibetaanse spaniel"],
            "Tibetaanse Terrier"                          => ["Tibetaanse terrier", "Tibetaanse Terrier", "Tibetaanse Terrier", "Tibetaanse terrier"],
            "Tiroler Brak"                                => [],
            "Tornjak"                                     => [],
            "Tosa"                                        => ["Tosa inu", "Tosa Inu", "Tosa", "Tosa inu"],
            "Toypoedel"                                   => ["Toypoedel", "Poedel", "Poedel (dwerg, toy)", "Toypoedel", "Poedel"],
            "Tsjechoslowaakse Wolfhond"                   => ["Tjechoslowaakse wolfhond", "Tsjechoslowaakse wolfhond", "Tjechoslowaakse wolfhond"],
            "Västgötaspets"                               => ["Valhund", "Vastgotaspets", "Valhund", "Västgötaspets", "Valhund", "Valhund", "Vastgotaspets"],
            "Vizsla Draadhaar"                            => ["Vizsla", "Vizsla", "Vizsla", "Vizsla"],
            "Vizsla Korthaar"                             => ["Vizsla", "Vizsla", "Vizsla", "Vizsla"],
            "Volpino Italiano"                            => ["Volpino Italiano"],
            "Weimaraner, korthaar"                        => ["Weimaraner", "Weimaraner", "Weimaraner", "Weimaraner"],
            "Weimaraner, langhaar"                        => ["Weimaraner", "Weimaraner", "Weimaraner", "Weimaraner"],
            "Welsh Corgi Cardigan"                        => ["Welsh corgi cardigan", "Welsh Corgi Cardigan", "Welsh Corgi (Cardigan/Pembroke)", "Welsh corgi cardigan"],
            "Welsh Corgi Pembroke"                        => ["Welsh corgi pembroke", "Welsh Corgi Pembroke", "Welsh Corgi (Cardigan/Pembroke)", "Welsh corgi pembroke"],
            "Welsh Springer Spaniel"                      => ["Welsh springer spaniel", "Welsh Springer Spaniel", "Welsh Springer Spaniel", "Springer spaniel", "Welsh springer spaniel"],
            "Welsh Terrier"                               => ["Welsh terrier", "Welsh Terrier", "Welsh Terrier", "Welsh terrier"],
            "West Highland White Terrier"                 => ["West highland white terrier", "West Highland White Terrier", "West Highland WhiteTerrier", "West highland white terrier"],
            "Westfaalse Dasbrak"                          => [],
            "Westsiberische Laika"                        => ["West siberische laika", "West Siberische Laika", "West siberische laika", "Siberische Husky", "West siberische laika"],
            "Wetterhoun"                                  => ["Wetterhoun", "Wetterhoun", "Wetterhoun", "Wetterhoun"],
            "Whippet"                                     => ["Whippet", "Whippet", "Whippet", "Whippet"],
            "Yorkshire Terrier"                           => ["Yorkshire terrier", "Yorkshire Terrier", "Yorkshire Terrier", "Yorkshire terrier"],
            "Zuid-Russische Ovcharka"                     => ["Zuid-russische owcharka", "Zuid Russische Owcharka", "Zuid-russische owcharka"],
            "Zwart Russische Terriër"                     => ["Zwarte russische terrier", "Zwarte Russische Terrier", "Zwart Russische Terrier", "Zwarte russische terrier"],
            "Zweedse Lappenhond"                          => ["Zweedse lappenhond", "Zweedse Lappenhond", "Zweedse lappenhond"],
            "Zwitserse Witte Herdershond"                 => ["White swiss shepherd dog", "Amerikaans-canadese witte herdershond", "Canadese Herdershond", "Amerikaans-canadese witte herdershond", "White swiss shepherd dog", "White swiss shepherd dog", "Amerikaans-canadese witte herdershond"],
            "Amerikaanse bulldog"                         => ["Amerikaanse bulldog", "Amerikaanse bulldog"],
            "Amerikaanse Eskimohond"                      => ["Amerikaanse Eskimohond", "Amerikaanse Eskimohond", "Amerikaanse bulldog", "American Bully "],
            "Bandog"                                      => ["Bandog", "Bandog", "Amerikaanse Eskimohond"],
            "Boerboel"                                    => ["Boerboel", "Boerboel", "Bandog"],
            "Boomer"                                      => ["Boomer", "Boomer", "Boomer", "Boomer"],
            "Ca de Bestiar"                               => ["Ca de Bestiar", "Ca de Bou"],
            "Ca de Bou"                                   => ["Ca de Bou"],
            "Elo"                                         => ["Elo"],
            "El Perro de PastorGarafiano"                 => ["El Perro de PastorGarafiano", "El Perro de Pastor Garafiano", "Golden Doodle Groot"],
            "Golden Doodle Groot"                         => ["Golden Doodle Groot", "Golden Doodle Klein"],
            "Golden Doodle Klein"                         => ["Golden Doodle Klein", "Labradoodle - Miniatuur"],
            "Karabash"                                    => ["Karabash", "Labradoodle - Medium"],
            "Labradoodle - Miniatuur"                     => ["Labradoodle", "Labradoodle", "Labradoodle-Miniatuur", "Labradoodle - standaard"],
            "Labradoodle - Medium"                        => ["Labradoodle", "Labradoodle", "Labradoodle-Medium"],
            "Labradoodle - standaard"                     => ["Labradoodle", "Labradoodle", "Labradoodle-Standaard", "Old english bulldog"],
            "Lurcher"                                     => ["Lurcher", "Patterdale terrier"],
            "Old english bulldog"                         => ["Old english bulldog", "Old English bulldog", "Pittbull Terrier"],
            "Patterdale terrier"                          => ["Patterdale terrier", "Patterdale Terrier", "Patterdale terrier"],
            "Pittbull Terrier"                            => ["Pittbull Terrier", "Pittbull Terrier", "Pittbull Terrier", "Rat Terrier"],
            "Plott Hound"                                 => ["Plott Hound", "Plott Hound", "Rat Terrier"],
            "Rat Terrier (dwerg-)"                        => ["Rat Terrier", "Rat Terrier (dwerg-)", "Rat Terrier"],
            "Rat Terrier (grote-)"                        => ["Rat Terrier", "Rat Terrier (grote-)", "Renascense bulldog"],
            "Rat Terrier (middenslag-)"                   => ["Rat Terrier", "Rat Terrier (middenslag-)"],
            "Renascense bulldog"                          => ["Shiloh Shepherd"],
            "Schafpudel"                                  => ["Schafpudel", "Silky Windhound"],
            "Shiloh Shepherd"                             => ["Shiloh Shepherd"],
            "Silky Windhound"                             => ["Silky Windhound", "Silky Windhound"],
            "Song dog"                                    => ["Song dog"],
            "Toy Fox Terrier"                             => ["Toy Fox Terrier", "Toyfox terrier (American Toy Terrier)"],

        ];

        $newNameList = [];
        foreach ($this->getProviderBreedName as $masterBreedName => $subBreedNames) {
            if (!array_key_exists($masterBreedName, $newNameList))
                $newNameList[$masterBreedName] = [];

            foreach ($subBreedNames as $subBreedName) {
                if (!in_array($subBreedName, $newNameList[$masterBreedName]))
                    $newNameList[$masterBreedName][] = $subBreedName;
            }
        }

        $this->getProviderBreedName = $newNameList;

    }

    public function getProviderBreedNameWithoutDoubles()
    {
        $withoutDoublesList = [];
        foreach ($this->getProviderBreedName as $masterBreedName => $subBreedNames) {
            if (!array_key_exists($masterBreedName, $withoutDoublesList))
                $withoutDoublesList[$masterBreedName][] = $masterBreedName;

            foreach ($subBreedNames as $subBreedName) {

                $pregRewriteRules = ['/-/'                  => '',
                                     '/ë/'                  => 'e',
                                     '/ñ/'                  => 'gn',
                                     "/'/"                  => '',
                                     '/`/'                  => '',
                                     '/(zwarte)/'           => 'zwart',
                                     '/(tsjechoslowaakse)/' => 'tjechoslowaakse',
                                     '/(owcharka)/'         => 'ovcharka',
                                     '/(saplaninac)/'       => 'sarplaninac',

                ];


                $tempWithoutDoublesList = array_map('strtolower', $withoutDoublesList[$masterBreedName]);
                $tempWithoutDoublesList = array_map(function ($name) {

                    $pregRewriteRules = ['/-/'                  => '',
                                         '/ë/'                  => 'e',
                                         '/ñ/'                  => 'gn',
                                         "/'/"                  => '',
                                         '/`/'                  => '',
                                         '/(zwarte)/'           => 'zwart',
                                         '/(tsjechoslowaakse)/' => 'tjechoslowaakse',
                                         '/(owcharka)/'         => 'ovcharka',
                                         '/(saplaninac)/'       => 'sarplaninac',

                    ];

                    $name = ResourceFilterHelper::multiPregReplace($name, $pregRewriteRules);
                    $name = str_replace(' ', '', $name);
                    return $name;
                }, $tempWithoutDoublesList);
                $tempWithoutDoublesList = array_map('trim', $tempWithoutDoublesList);


                $tempSubBreedName = strtolower($subBreedName);
                $tempSubBreedName = ResourceFilterHelper::multiPregReplace($tempSubBreedName, $pregRewriteRules);
                $tempSubBreedName = str_replace(' ', '', $tempSubBreedName);
                $tempSubBreedName = trim($tempSubBreedName);


                if (in_array($tempSubBreedName, $tempWithoutDoublesList))
                    continue;


                $withoutDoublesList[$masterBreedName][] = $subBreedName;
            }
        }


        /////exceptions
        /////////////////
        $pregRewriteRules = ['/(cocker spaniel)/'   => 'Cocker Spaniel',
                             '/(Wolfkeeshond)/'     => 'Wolfskeeshond',
                             '/(Siberische husky)/' => 'Siberische Husky',
                             '/(Shih tsu)/'         => 'Shih Tzu',
        ];

        foreach ($withoutDoublesList as $masterBreedName => $arrSubBreedName) {
            foreach ($arrSubBreedName as $key => $breedName) {
                $arrSubBreedName[$key] = ResourceFilterHelper::multiPregReplace($breedName, $pregRewriteRules);
            }
            $withoutDoublesList[$masterBreedName] = $arrSubBreedName;
        }

        return $withoutDoublesList;
    }


    public function getProviderBreedName()
    {
        return $this->getProviderBreedName;
    }

}

