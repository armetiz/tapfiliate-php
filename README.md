# Tapfiliate for PHP

## Included features

1. Create conversion : http://docs.tapfiliate.apiary.io/#reference/conversions/conversions-collection/create-a-conversion

## Example

```php
$key   = "APP_KEY"; // Generated from : https://tapfiliate.com/user/api-access/

$tapfiliate  = new Tapfiliate($key);
$tapfiliate->createConversion([
    "external_id" => "john.doe@acme.com",
]);
```

## Testing

Not implemented yet.

## License

This library is under the MIT license. [See the complete license](https://github.com/armetiz/tapfiliate-php/blob/master/LICENSE).

## Credits

Author - [Thomas Tourlourat](http://www.wozbe.com)
