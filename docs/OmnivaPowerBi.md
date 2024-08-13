# Omniva PowerBI API

OmnivaPowerBi class is responsible for sending given data to Omniva PowerBI. It is not responsible for said data collection. 

## Usage

Usage is simple, initialize OmnivaPowerBi with user API key and password (**Note: this could change, as currently it is no known how authorization will work**).

And pass collected data:
- `setPluginVersion(string version)` - Plugin version
- `setPlatform(string platform)` - eCommerce platform name and version
- `setSenderName(string name)` - Sender name, from module settings
- `setSenderCountry(string country)` - Sender country code, from module settings
- `setDateTimeStamp(string datetime)` - Date time stamp (0000-00-00 00:00:00 format) from which data was collected
- `setOrderCountCourier(int count)` - Order count with courier option
- `setOrderCountTerminal(int count)` - Order count with terminal option
- `setPrice(string country, string courierPrice, string terminalPrice)` - Set price for courier and terminal in a given country. If country not given will be set as Default instead of country code

Example:
```php
use Mijora\Omniva\PowerBi\OmnivaPowerBi;

$opb = (new OmnivaPowerBi('0123456', 'secret'))
    ->setPluginVersion('1.2.2')
    ->setPlatform('Opencart v2.0.0')
    ->setSenderName('Testas UAB')
    ->setSenderCountry('LT')
    ->setDateTimeStamp(OmnivaPowerBi::DEFAULT_TIMESTAMP)
    ->setOrderCountCourier(10)
    ->setOrderCountTerminal(666)
    ->setPrice('LT', '5', '0:2.5 ; 100:1')
    ->setPrice('LV', null, null)
    ->setPrice('EE', -1, 3.5)
    ->setPrice('FI', 10, 15.25)
;

$result = $opb->send();

```

this code will generate and send this body
```json
{
    "pluginVersion": "1.2.2",
    "eCommPlatform": "Opencart v2.0.0",
    "omnivaApiKey": "0123456",
    "senderName": "Testas UAB",
    "senderCountryCode": "LT",
    "ordersCount": {
        "courier": 10,
        "terminal": 666
    },
    "ordersCountSince": "0000-00-00 00:00:00",
    "setPricing": {
        "LT": {
            "country": "LT",
            "courier": "5",
            "terminal": "0:2.5 ; 100:1"
        },
        "LV": {
            "country": "LV",
            "courier": "",
            "terminal": ""
        },
        "EE": {
            "country": "EE",
            "courier": "-1",
            "terminal": "3.5"
        },
        "FI": {
            "country": "FI",
            "courier": "10",
            "terminal": "15.25"
        }
    },
    "sendingTimestamp": "2024-08-13 11:04:25"
}
```

Result from send() is either true or false depending on if data was sent or not (currently always returns false as there is no enpoint given).