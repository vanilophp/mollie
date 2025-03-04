# Mollie for Vanilo Changelog

## Unreleased
#### 2025-XX-YY

- Upgrade to Vanilo 5
- Dropped PHP 8.2 support
- Added Laravel 12 support

## 2.3.1
#### 2024-08-27

- Fixed the possible sending of invalid locale to Mollie at order creation

## 2.3.0
#### 2024-06-06

- Changed minimum Vanilo version to v4.1
- Changed minimum Mollie PHP SDK version to v2.54
- Added the (experimental) `TransactionHandler` class
- Added retry payment feature (via the TransactionHandler class)
- Added the `view` option to the payment request's html snippet method

## 2.2.0
#### 2024-06-03

- Changed the payment request to return the mollie order id as payable remote id instead of the transaction id

## 2.1.2
#### 2024-05-29

- Fixed price-related errors at Mollie order creation

## 2.1.1
#### 2024-05-29

- Fix possible error at order creation by not sending the phone number to Mollie when it's not present

## 2.1.0
#### 2024-05-07

- Added the propaganistas/laravel-phone package dependency
- Added automatic conversion of billpayer phone numbers to E.164 format when crafting the create mollie order request

## 2.0.0
#### 2024-04-25

- Added Vanilo 4 Compatibility
- Added Laravel 11 support
- Changed minimum Laravel requirement to v10.43
- Dropped Vanilo 3, Laravel 9 and PHP 8.1 support

## 1.1.0
#### 2023-12-18

- Added Laravel 10 Support
- Added PHP 8.3 Support

## 1.0.0
#### 2023-03-07

- Initial release
- Based on the Mollie order API
- Supports PHP 8.1+ & Laravel 9.2 - 9.52
