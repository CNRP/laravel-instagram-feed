# Invoice Generator

## Introduction
This package uses Spatie PDF to generate invoices. It simplifies the process of creating and managing invoices within a Laravel application.

## Installation

To install the package, use Composer:

```bash
composer require cnrp/invoice-package
```

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --provider="CNRP\InvoicePackage\InvoiceServiceProvider" --tag=invoice
```

## Configuration

The configuration file is located at `config/invoice.php`. It contains the following settings:

```php
<?php

return [
    'node_binary' => env('NODE_BINARY', 'node'), // Path to the Node.js executable
    'default_theme'=> 'css/invoice-clean.php',   // Path to the default invoice theme
];
```

### Node Binary Path

The `node_binary` setting specifies the path to the Node.js executable on your machine. This is necessary for the package to function correctly.

- **Windows Example**:
  ```php
  NODE_BINARY="C:\\Program Files\\nodejs\\node.exe"
  ```

- **Linux Example**:
  ```php
  NODE_BINARY="/usr/local/bin/node"
  ```

Ensure that the path is correctly set in your `.env` file.

## Usage

After installation and configuration, you can use the package to generate invoices.

### Basic Example

```php
use CNRP\InvoicePackage\Invoice;

// Add here whatever is nessescary, remove variables that are not required in your invoice
$invoice = new Invoice(
    [
        'logo' => 'https://jordheyeshair.co.uk/images/graphics/logo.webp',
        'title' => 'INVOICE',
        'invoice_number' => 'INV-0001',
        'date' => '19/06/2024',
        'from' => [
            'name' => 'Jord Heyes Hair',
            'phone' => '0987654321',
            'email' => 'email@company.com',
            'address_line_1' => '1234 Street Name',
            'address_line_2' => 'Suite 567',
            'address_line_3' => 'City, State, ZIP',
        ],
        'to' => [
            'name' => 'Jane Doe',
            'phone' => '0987654321',
            'email' => 'customer@domain.com',
            'address_line_1' => '5678 Client St',
            'address_line_2' => 'Apt 910',
            'address_line_3' => 'Client City, State, ZIP',
        ],
        'description' => 'Invoice for services rendered',
        'payment_terms' => 'Payment is due within 30 days.',
        'payment_details' => 'Account Number/Sort Code: 12345678 / 12-34-56',
        'footer' => 'See you soon!',
        'is_paid' => true,
    ],
    // Items can be added as an array here.
    [
        ['name' => 'Service 1', 'quantity' => 2, 'price' => 50],
        ['name' => 'Product A', 'description' => 'High quality product', 'quantity' => 1, 'price' => 30],
        ['name' => 'Consultation', 'price' => 100]
    ]
);

// Alternatively to add items you can call upon the addItem method on the Invoice
$invoice->addItem([
  'name' => 'Booking Deposit',
  'Move Date' => '2024-07-19',
  'price' => 150,
]);


// Generate the PDF & Download
return $invoice->generateAndDownloadPdf();

```
This example demonstrates how to create a new invoice with client details, items, and a total amount, then save the generated PDF to a specified path.
The colmns in the table are determined by the items you add, the only required value in an item is 'price'. 

### Theme

By default, it uses the `css/invoice-clean.php` theme.

If you would like to change this, edit the published view at `resources/views/vendor/invoice/default.blade.php` and change:

```blade
@vite(['resources/css/invoice-clean.css'])
```
Or update the file at resources/css/invoice-clean.css.



## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
