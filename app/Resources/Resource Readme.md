Resources structure
---------------
##resources.php
Use this file to link for instance car to CarInsurance of rolls

##Method request arguments
- rules: this is to validate through Validator class.
- example: this is shown when doing an OPTIONS request at the service
- filter: this is a filter applied AFTER validation but BEFORE passing to the external webservice. These filter functions need to bedefined in ResourceFilterHelper
- external_list: use this to link an argument to an external list. The input will be automatically validated 
- default: default value, which is uses to set argument if this argument is not filled, and not required

##Service request filters and mapping
Field mapping is used to map a standard name (like 'id') to a webservices. It's applied to both keys and values
Filter mapping is used on only values, to process after return. These filters should be defined in ResourceFilterHelper


##Flow
- update arguments from external list
- validate input
- filter params if filter is set for this argument
- set default for params if this param is not set, not required, and default is defined
- check in cache if this request has been made before and has non-expired record, not in debug mode, if yes return value
- set params
- execute function 
- process the results: replace keys and values based on resource field mapping
- process the results: filter values based on resource filter mapping

##debug
When in debug mode ($params['debug'] = 'true'), cache is skipped, and service outputs also non defined fields.

##store
When store ($params['store'] = 'true'), cache is skipped and products stored in prod database according to storeModel and storeMapping.


##strict standards
Use this $this->strictStandardFields = false in your client if you want to avoid filtering on standard fields (useful for policies);

##internal requests to other clients
Example:
$res           = $this->internalRequest('car','licenseplate', [ ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE]]);
            