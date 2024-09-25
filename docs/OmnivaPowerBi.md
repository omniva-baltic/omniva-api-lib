# Omniva PowerBI API

OmnivaPowerBi class is responsible for sending given data to Omniva PowerBI. It is not responsible for said data collection. 

## Usage

Usage is simple, initialize OmnivaPowerBi with user API key (**Note: this could change, as currently it is no known how authorization will work**).

To send data to test endpoint pass second param `TRUE`:
```php
$opb = new OmnivaPowerBi('0123456', true);
```

And pass collected data:
- `setPluginVersion(string version)` - Plugin version
- `setPlatform(string platform)` - eCommerce platform name and version
- `setSenderName(string name)` - Sender name, from module settings
- `setSenderCountry(string country)` - Sender country code, from module settings
- `setDateTimeStamp(string datetime)` - Date time stamp (0000-00-00 00:00:00 format) from which data was collected
- `setOrderCountCourier(int count)` - Order count with courier option
- `setOrderCountTerminal(int count)` - Order count with terminal option
- `setCourierPrice(string country, string minPrice, string maxPrice)` - Set price for courier in a given country. If country not given will be set as Default instead of country code. Max price not necessary if the method has no ranges
- `setTerminalPrice(string country, string minPrice, string maxPrice)` - Set price for terminal in a given country. If country not given will be set as Default instead of country code. Max price not necessary if the method has no ranges

Example:
```php
use Mijora\Omniva\PowerBi\OmnivaPowerBi;

$opb = (new OmnivaPowerBi('0123456'))
    ->setPluginVersion('1.2.2')
    ->setPlatform('Opencart v2.0.0')
    ->setSenderName('Testas UAB')
    ->setSenderCountry('LT')
    ->setDateTimeStamp(OmnivaPowerBi::DEFAULT_TIMESTAMP)
    ->setOrderCountCourier(10)
    ->setOrderCountTerminal(666)
    ->setCourierPrice('LT', 5)
    ->setTerminalPrice('LT', 1, 2.5)
    ->setCourierPrice('LV', null) //For LV set only Courier, but price is not set
    ->setCourierPrice('EE', -1)
    ->setTerminalPrice('EE', 3.5)
    ->setCourierPrice('FI', 10)
    ->setTerminalPrice('FI', 15.25)
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
            "courier": {
                "min": "5",
                "max": "5"
            },
            "terminal": {
                "min": "1",
                "max": "2.5"
            }
        },
        "LV": {
            "country": "LV",
            "courier": {
                "min": "",
                "max": ""
            },
            "terminal": null
        },
        "EE": {
            "country": "EE",
            "courier": {
                "min": "-1",
                "max": "-1"
            },
            "terminal": {
                "min": "3.5",
                "max": "3.5"
            }
        },
        "FI": {
            "country": "EE",
            "courier": {
                "min": "10",
                "max": "10"
            },
            "terminal": {
                "min": "15.25",
                "max": "15.25"
            }
        }
    },
    "sendingTimestamp": "2024-08-13 11:04:25"
}
```

Result from send() is either true or false depending on if data was sent or not (currently always returns false as there is no enpoint given).