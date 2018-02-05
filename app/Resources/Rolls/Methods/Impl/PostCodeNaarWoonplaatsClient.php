<?php
/**
 * (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 6-sep-2010
 * Time: 15:42:05
  */

require_once(ROOT_PATH . '/inc/soap/abstract_soap_request.php');


class PostCodeNaarWoonplaatsClient extends abstract_soap_request {
    public function __construct() {
        parent::__construct('KS300000',get_class($this));
        $this->logger = Logger::getLogger(__CLASS__);
        $this->logger->debug('Constructor ');
    }

/**
 * Auto generated functions from XML file 1.0
 *(C) 2010 Vergelijken.net
 */

    public function setPostcode($postcode) {
      $this->xml->Functie->Parameters->Postcode = $postcode;
    }
    
}
