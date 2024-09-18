## ServicePackageHelper
Helps to decide what service package code should is available for given country and items combo.

```php
$countryIsoCode = 'SK'; // Must be 2 letter ISO code
$items = [
    new PackageItem(
        30, // weight, Kg
        0.5, // length, m
        0.5, // width, m
        0.6 // height, m
    ),
];
$availableCodes = ServicePackageHelper::getAvailablePackages($countryIsoCode, $items);
```

`getAvailablePackages` checks each item separately for limitations and returns code list that all items can fit.

example result
```
["PREMIUM","STANDARD"]
```

returned codes matches those from `ServicePackage` constants:
```php
const CODE_ECONOMY = 'ECONOMY';
const CODE_STANDARD = 'STANDARD';
const CODE_PREMIUM = 'PREMIUM';
```