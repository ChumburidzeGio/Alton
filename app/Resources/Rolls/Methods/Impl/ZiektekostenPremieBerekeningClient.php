<?php
/**
 * ZiektekostenPremieBerekeningClient (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 26-sep-2010
 * Time: 23:25:24
 */

require_once(ROOT_PATH . '/inc/soap/abstract_soap_request.php');


class ZiektekostenPremieBerekeningClient extends abstract_soap_request {

    public function __construct() {
        parent::__construct('KS301807', get_class($this));
        $this->logger = Logger::getLogger(__CLASS__);
        $this->logger->debug('Constructor ');
    }

    public function deletePremieobjectenPOProductselectie() {
        $link = $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie;
        if ($link) {
            $dom = dom_import_simplexml($link);
            $dom->parentNode->removeChild($dom);
        }
    }
//Bestpassende
    public function setHoofdverzekerdeVerplicht($par) {
        $arr = $this->xml->Functie->Parameters->Hoofdverzekerde->attributes();
        $arr['verplicht'] = $par;
    }

    public function deleteNieuwepolisProductfilters() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Productfilters);
        //unset($this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekitemfilter);
    }

    public function deleteGezinKinderen() {
        unset($this->xml->Functie->Parameters->Gezin->Kinderen);
    }

    public function deleteDefKind() {
        unset($this->xml->Functie->Parameters->Gezin->Kinderen->Kind);
    }

    public function voegKindToe($val) {
        $this->logger->debug('voeg kind toe ' . $val);
        $kind = $this->xml->Functie->Parameters->Gezin->Kinderen->addChild('Kind');
        $kind->addChild('Geboortedatum', $val);
    }

    public function setToepassingMinimaliseerresultset($val) {
        $this->logger->debug('setToepassingMinimaliseerresultset ' . $val);
        $toepassing = $this->xml->Functie->Parameters->Nieuwepolis->Toepassing;
        if (!$toepassing) {
            $toepassing = $this->xml->Functie->Parameters->Nieuwepolis->addChild('Toepassing');
        }
        $toepassing->addChild('Minimaliseerresultset',$val);
    }
    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setHoofdverzekerdeGeboortedatum($par) {
        $this->xml->Functie->Parameters->Hoofdverzekerde->Geboortedatum = $par;
    }

    public function setHoofdverzekerdeOrganisaties($par) {
        $this->xml->Functie->Parameters->Hoofdverzekerde->Organisaties = $par;
    }

    public function setMedeverzekerdeGeboortedatum($par) {
        $this->xml->Functie->Parameters->Medeverzekerde->Geboortedatum = $par;
    }

    public function setGezinKinderenKindGeboortedatum($par) {
        $this->xml->Functie->Parameters->Gezin->Kinderen->Kind->Geboortedatum = $par;
    }

    public function setHuidigepolisProductid($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Productid = $par;
    }

    public function setHuidigepolisAanvullingenAanvullingid($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Aanvullingen->Aanvullingid = $par;
    }

    public function setNieuwepolisIngangsdatum($par) {
        $this->xml->Functie->Parameters->Nieuwepolis->Ingangsdatum = $par;
    }

    public function setNieuwepolisPostcode($par) {
        $this->xml->Functie->Parameters->Nieuwepolis->Postcode = $par;
    }

    public function setNieuwepolisProductfiltersRubriekfilterId($par) {
        $this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekfilter->Id = $par;
    }

    public function setNieuwepolisProductfiltersRubriekfilterGrensschaal10000($par) {
        $this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekfilter->Grensschaal10000 = $par;
    }

    public function setNieuwepolisProductfiltersRubriekitemfilterId($par) {
        $this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekitemfilter->Id = $par;
    }

    public function setNieuwepolisProductfiltersRubriekitemfilterGrensschaal1000($par) {
        $this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekitemfilter->Grensschaal1000 = $par;
    }

    public function setProductselectieProductId($par) {
        $this->xml->Functie->Parameters->Productselectie->Product->Id = $par;
    }

    public function setPremieobjectenPremieobjectBetalingstermijn($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Betalingstermijn = $par;
    }

    public function setPremieobjectenPremieobjectRekentermijn($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Rekentermijn = $par;
    }

    public function setPremieobjectenPremieobjectGewensteigenrisico($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Gewensteigenrisico = $par;
    }

    public function setPremieobjectenPremieobjectPoliskostenincl($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Poliskostenincl = $par;
    }

    public function setPremieobjectenPremieobjectPremies($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Premies = $par;
    }

    public function setPremieobjectenPremieobjectPO_ProductselectiePO_ProductId($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product->Id = $par;
    }

    public function deleteHoofdverzekerdeGeboortedatum() {
        unset($this->xml->Functie->Parameters->Hoofdverzekerde->Geboortedatum);
    }

    public function deleteHoofdverzekerdeOrganisaties() {
        unset($this->xml->Functie->Parameters->Hoofdverzekerde->Organisaties);
    }

    public function deleteMedeverzekerdeGeboortedatum() {
        unset($this->xml->Functie->Parameters->Medeverzekerde->Geboortedatum);
    }

    public function deleteGezinKinderenKindGeboortedatum() {
        unset($this->xml->Functie->Parameters->Gezin->Kinderen->Kind->Geboortedatum);
    }

    public function deleteHuidigepolisProductid() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Productid);
    }

    public function deleteHuidigepolisAanvullingenAanvullingid() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Aanvullingen->Aanvullingid);
    }

    public function deleteNieuwepolisIngangsdatum() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Ingangsdatum);
    }

    public function deleteNieuwepolisPostcode() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Postcode);
    }

    public function deleteNieuwepolisProductfiltersRubriekfilterId() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekfilter->Id);
    }

    public function deleteNieuwepolisProductfiltersRubriekfilterGrensschaal10000() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekfilter->Grensschaal10000);
    }

    public function deleteNieuwepolisProductfiltersRubriekitemfilterId() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekitemfilter->Id);
    }

    public function deleteNieuwepolisProductfiltersRubriekitemfilterGrensschaal1000() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Productfilters->Rubriekitemfilter->Grensschaal1000);
    }

    public function deleteProductselectieProductId() {
        unset($this->xml->Functie->Parameters->Productselectie->Product->Id);
    }

    public function deletePremieobjectenPremieobjectBetalingstermijn() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Betalingstermijn);
    }

    public function deletePremieobjectenPremieobjectRekentermijn() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Rekentermijn);
    }

    public function deletePremieobjectenPremieobjectGewensteigenrisico() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Gewensteigenrisico);
    }

    public function deletePremieobjectenPremieobjectPoliskostenincl() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Poliskostenincl);
    }

    public function deletePremieobjectenPremieobjectPremies() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Premies);
    }

    public function deletePremieobjectenPremieobjectPO_ProductselectiePO_ProductId() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product->Id);
    }

}
