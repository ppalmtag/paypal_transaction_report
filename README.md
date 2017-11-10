# PayPal Transaction Report Import

A simple class to read a PayPal Tranaction report into a class

## Getting Started

Install via Composer.

## Example

```php
$excel_export = new \PayPalReport\PayPalTransactionReport();

$paypal_report->readReport($csv_data);

foreach ($paypal_report->getData() as $row) {
// to display the rows
}
```

## Built With

* [PHP](http://php.net) - Programming language used

## Author

**Philipp Palmtag** - *Initial work* - [ppalmtag](https://github.com/ppalmtag)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details