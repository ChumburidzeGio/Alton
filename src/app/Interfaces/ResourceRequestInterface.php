<?php
/**
 * User: Roeland Werring
 * Date: 17/02/15
 * Time: 12:28
 *
 */

namespace App\Interfaces;

use Komparu\Input\Contract\Validator;

interface ResourceRequestInterface
{
    /**
     * @return Array list of arguments
     */
    public function arguments(Validator $validator);


    /**
     * Execute the function
     * @return mixed
     */
    public function executeFunction();

    /**
     * Set the params
     */
    public function setParams( Array $params );

    /**
     * Get results of request
     * @return mixed
     */
    public function getResult();


    /**
     * Defines wheter this resource requests could be linked to a document
     * @return mixed
     */
    public function isDocumentRequest();

    /**
     * Defines wheter this resource requests should be used to populate the products
     * @return mixed
     */
    public function isPopulateRequest();

    public function isFunnelRequest();

    /**
     * Return an array of the usable output fields of this request
     * @return mixed
     */
    public function outputFields();

    /**
     * Returns actual producttype
     * @return string
     */
    public function getRequestType();

}