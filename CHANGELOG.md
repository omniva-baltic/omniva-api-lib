## [1.3.5]
- added the posibility to specify the required parameter `contentDescription` for international shipments. Usage: `(new Package())->setContentDescription($string)`

## [1.3.4]
- fixed the size of packages for international shipments to be calculated using the formula `longest_edge + 2 x (middle_edge + shortest_edge)`

## [1.3.3]
- added the ability to enable OMX test mode via a constant `_OMNIVA_API_USE_TEST_OMX_`
- added ability to change username via constant `_OMNIVA_API_USERNAME_`
- added ability to change password via constant `_OMNIVA_API_PASSWORD_`
- added ability to change request timeout via constant `_OMNIVA_API_CURL_TIMEOUT_`
- added new delivery channel: POST_BOX
- added new package service: EXPRESS_LETTER

## [1.3.2]
- added `X-Integration-Agent-Id` header to all API calls with value of constant `_OMNIVA_INTEGRATION_AGENT_ID_` if it is defined

## [1.3.1]
- added phone number conversion to international format for Lithuania

## [1.3.0]
- **Breaking change**: changed how Package::isOffloadPostcodeRequired() function works, now it expects to be given Package object, with main service, channel and servicePackage (if applied)
- if offloadPostcode is set on receiver Address object, when getting receiver address for registration it will return array with offloadPostcode format
- enabled LETTER main service, most validations left for Omniva API side
- added additional services for LETTER main service

## [1.2.1]
- changed default date time for PowerBi to be 1990-01-01 00:00:00
- Contact personName, altName and companyName fields now allows double quotes

## [1.2.0]
- created the ability to send statistical data to Omniva PowerBi
- added that after the formatting function, the type of the measurement values is changed to string to avoid the problem when some servers provide a value with many numbers after the decimal point when converting to json
- added COD amount value to be converted to string to avoid value with many decimal numbers issue on some servers
- CallCourierOmxRequest (and CallCourier for backwards compatibility) now accepts timezone for better calculation of pickup datetime, if not set uses server timezone
- altName tag support for sender contact. When setting sender contact onto package it will automaticaly fill altName with personName if altName was not set on sender contact
- preparations for non Baltic states shipments (servicePackageHelper)

## [1.1.0] - Improvements
- adapted to work with the Omniva OMX server

## [1.0.18] - Fixes
- fixed filtering of locations by type
- adapted to work with PHP 5.6
- fixed the barcode show when using TCPDF library version 6.7.4 or newer

## [1.0.17] - Improvements
- added error message when receiving a "401 Unauthorized" error
- added debug to all functions in Request class
- added enableDebug function in Request class
- unified names of all functions in Request class
- deprecated function get_labels() in Request class. Replaced with getLabels().
- deprecated function get_debug_data() in Request class. Replaced with getDebugData().
- when initializing the Request class, all parameters became unnecessary, as the possibility to add them through separate functions was created
- added possibility to register multi-parcels shipments (MPS)

## [1.0.16] - Fixes
- fixed server URl change
- improved server URL management

## [1.0.15] - Improvements
- changed to use local envelope scheme

## [1.0.14] - Fixes
- fixed barcode show in manifest

## [1.0.13] - Improvements
- to services map added QH and QL services
- services CD and CE are marked as having terminals

## [1.0.12] - Improvements
- added additional services map for each shipping service
- added the email adding to sender

## [1.0.11] - Improvements
- centralized terminal services getting
- added barcode image display in manifest
- added order number to manifest
- added the possibility to change the manifest strings
- added the possibility to change the manifest columns width
- fixed error on PHP 8.2

## [1.0.10] - Fixes
- removed PK service from "required offload postcode" services list
- added error message display when error message is written in prompt element

## [1.0.9] - Improvements
- added a ability to debug request
- fixed error message when API credentials is wrong
- fixed error message when API URL is wrong
- added the option to specify how many parcels will be sent when calling the courier
- fixed courier pickup time when it has already started but not yet finished

## [1.0.8] - Fixes
- fixed comment section in Shipment XML request building
- fixed courier pickup time format in requests

## [1.0.7] - Fixes
- fixed syntax of return code showing in customer SMS/email

## [1.0.6.1] - Fixes
- fixed escaping function for special letters
- removed vendor directory

## [1.0.6] - Improvements
- removed ns3: from all responses
- added a escaping of sender and receiver values

## [1.0.5] - Updates
- Added length to measurements
- Update readme, added examples with comments

## [1.0.4] - Improvements
- comment tag moved to correct place
- manifest supports mode 'S' - return as string
- added PK and PP services as terminal service
- removed ns3: string from API response

## [1.0.0] - Initial release
