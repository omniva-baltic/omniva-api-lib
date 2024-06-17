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
