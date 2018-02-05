<?php
/**
 * User: Roeland Werring
 * Date: 06/04/16
 * Time: 16:01
 * 
 */
namespace App\Resources\Nedvol\Methods;

use Config;
use App\Resources\Nearshoring\AbstractNedvolRequest;
use DOMDocument;
use SimpleXMLElement;

class CarInsurance extends AbstractNedvolRequest {
    const contractDocumentXmlns = 'http://www.komparu.com';


    /** @var DOMDocument $domtree */
    private $domtree;

    /** @var SimpleXMLElement $xml */
    private $xml;

    public function __construct() {
        $this->wsdl = ((app()->configure('resource_nedvol')) ? '' : config('resource_nedvol.settings.wsdl'));
        $this->method = 'AddRelatieWithPolis';
    }

    public function setParams(Array $params)
    {
        $this->initXml();

        //Beide roepen de functie “AddRelatieWithPolis” aan met een parameter “key”, die “independer” moet zijn, en een parameter “data” die de XML moet bevatten.

        $this->params = $params;
    }

    private function initXml()
    {


        /* create a dom document with encoding utf8 */
        $domtree = new DOMDocument('1.0', 'UTF-8');

        /* create the root element of the xml tree */
        $xmlRoot = $domtree->createElement("xml");
        /* append it to the document created */

        $xmlRoot = $domtree->appendChild($xmlRoot);

        //<Contractdocument xmlns="http://www.independer.nl/autoverzekering/2014/09/17">

        //create root
        $contractdocument = $domtree->createElement('Contractdocument');
        $contractdocument = $xmlRoot->appendChild($contractdocument);

        //set attribugte
        $contractdocumentXmlns = $domtree->createAttribute('xmlns');
        $contractdocumentXmlns->value = self::contractDocumentXmlns;

        $contractdocument->appendChild($contractdocumentXmlns);

        $al = $domtree->createElement('AL');
        $contractdocument->appendChild($al);
        $alFunctie = $domtree->createElement('AL_FUNCTIE','01');
        $alDatacat = $domtree->createElement('AL_DATACAT','23C');
        $al->appendChild($alFunctie);
        $al->appendChild($alDatacat);

        $xg = $domtree->createElement('XG');
        $contractdocument->appendChild($xg);

        $xgStatus = $domtree->createElement('XG_STATUS','18');
        $xgStatust = $domtree->createElement('XG_STATUST');
        $xg->appendChild($xgStatus);
        $xg->appendChild($xgStatust);


        $xm = $domtree->createElement('XM');
        $xg->appendChild($xm);
        $xmStattxt = $domtree->createElement('XM_STATTXT','Verwerkt door Komparu');
        $xm->appendChild($xmStattxt);
//        <AL>
//          <AL_FUNCTIE>01</AL_FUNCTIE>
//          <AL_DATACAT>23C</AL_DATACAT>
//        </AL>

//        <XG>
//          <XG_STATUS>18</XG_STATUS>
//          <XG_STATUST />
//          <XM>
//              <XM_STATTXT>Verwerkt door Email2XML-Independer</XM_STATTXT>
//          </XM>
//        </XG>




        echo $domtree->saveXML();
        die();

    }

}
