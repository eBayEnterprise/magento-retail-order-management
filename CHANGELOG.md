# Change Log
All notable changes to this project will be documented in this file.

## [1.5.0-beta-5] -  2015-06-04
### Changed
- Installation and Configuration Guide

### Fixed
- Order create request sends duplicate skus for configurable products
- Order create request includes a shipping amount for all items

## [1.5.0-beta-4] -  2015-05-21
### Removed
- Vestigial event observer from eb2cOrder

### Fixed
- Order create merchandise amount remainder calculation wrong and unnecessary

## [1.5.0-beta-3] -  2015-05-07
### Fixed
- Store language codes are not being properly validated in the admin

## [1.5.0-beta-2] -  2015-04-24
### Fixed
- Invalid tax response does not cause Order Create Request tax header to be set properly
- Error when `ItemDesc` value includes quotation marks

## [1.5.0-beta-1] -  2015-04-09
### Fixed
- PayPal module is not receiving the address status from the api
- ProductImageExport generates new files even when no images/products have changed
- Logger Context Helper Undefined Index Error

### Changed
- Handle unacknowledged feeds with a CRIT log instead of resending the feed file

## [1.5.0-alpha-6] -  2015-03-26
### Added
- Configuration mapping for customer gender
- Exposed events to allow modification or addition of information to the Order Create request

### Changed
- Order create has been refactored to use the [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)
- Refactor modules to use events to inject data into the order create request

### Removed
- Order custom attribute mappings


## [1.5.0-alpha-5] -  2015-03-12
### Changed
- Log statements now utilize [eBay Enterprise Magento Logger](https://github.com/eBayEnterprise/magento-log) 2.0
- Log statements now contain meta information


## [1.5.0-alpha-4] -  2015-02-26
### Fixed
- Guest order lookup gives "Order not found" error even when an order is found
- PayPal Express Checkout fails when using a gift card that covers the order subtotal, but not the eventual order grand total
- Order details page is not displaying the correct item quantity
- Order details page is not displaying promotional discounts
- Blank page upon submitting an order using PayPal payment method

### Changed
- Use the Retail Order Management SDK for address validation service requests

### Removed
- Last vestiges of admin suppression

## [1.5.0-alpha-3] - 2015-02-12
### Fixed
- Sprintf-format specifiers not replaced in log messages
- Order details page was not displaying country, state or postal code
- No error message when a product with no inventory is removed from cart

### Changed
 - Change "Invalid" to "Incomplete" for products not yet fully imported

## [1.5.0-alpha-2] - 2015-01-29
### Fixed
- Unnecessary TDF requests when PayPal Express Checkout used from cart or product page
- No inventory check when adding an item to the cart that was just ordered
- Magento admin can place $0 orders regardless of configured payment types
- Unable to start PayPal Express Checkout
- Test API (Web Services) fails if Address Validation->Maximum Suggestions is not configured

### Changed
- Undo prepending catalog id to the custom product attribute `style_id` when importing products

## [1.5.0-alpha-1] - 2015-01-15
### Added
- InStore module to create and import in-store pick-up, ship-to-store and ship-from-store product attribute
- Add in-store pick-up, ship-to-store  and ship-from-store attributes to product export feed

## [1.4.0-rc-1] - 2015-01-15
### Added
- Order Price Adjustment event
- Order Gift Certificate event
- Log warning if API call to allocate inventory fails

### Changed
- Use version "~1.0" of eBayEnterprise/RetailOrderManagement-SDK

### Removed
- Unnecessary default configuration values from module etc/config.xml files

### Fixed
- Multiple error messages in the shopping cart when inventory is out-of-stock
- Gift Card from previous session applied when current Gift Card fails
- File Transfer fails from cron, while SFTP test button succeeds
- Number formats should not be locale-specific
- Address validation shows suggestions after valid address is saved
- Unnecessary override of Cart.php in Eb2cInventory
- Checkout fails when multiple credit card payment methods are enabled
- The import product collection is being saved when there are no changes
- Cron scheduling samples for ProductExport and ProductImageExport modules

## [1.4.0-alpha-10] - 2015-01-05
### Added
- PayPal configuration for Image URLs
- PayPal admin configuration to "Transfer Line Items"
- Order Accepted event
- Order Cancelled event
- Order Confirmed event
- Order Credit Issued event
- Order Return in Transit event

### Removed
- Order Status mapping between ROM Order Statuses and Magento Order Status table
- ROM Order Status importation into the Magento Order Status table

### Fixed
- Orders using gift cards are submitted with PrepaidCreditCard payment node that fails in OMS

## [1.4.0-alpha-9] - 2014-12-18
### Added
- Order Credit Issued event
- Order Shipped event
- Order Backorder event

### Changed
- Order history and order details no longer require the order to exist locally in Magento
- PayPal refactored as a payment method

## [1.4.0-alpha-8] - 2014-12-04
### Added
- Order Rejected event
- AMQP Support
- Shell script to reset the export cutoff date

### Changed
- Active support for Magento Enterprise Edition 1.14.1
- Core resource changed from `eb2cproduct` to `ebayenterprise_catalog`
- Documentation significantly updated

### Removed
- Event handler to explicitly reindex products after feed importation

### Fixed
- Shipping method not appearing on transactional e-mails

## [1.4.0-alpha-7] - 2014-11-20
### Added
- Credit card client-side encryption
- New credit card tenders may be added via configuration

## [1.4.0-alpha-6] - 2014-11-06
### Added
- Customers may pay with multiple gift cards on a single order

### Changed
- Gift cards refactored

### Fixed
- Gift card payment information is not submitted when combined with additional payment methods
- Out-of-range gift card is applied to cart when that BIN already exists as a gift card in Magento
- Gift card with positive balance is not updated when BIN already exists as a gift card in Magento with zero balance
- If the gift card module is not configured, it should block gift card redemption
- Money Order is not available when payments are turned off

## [1.4.0-alpha-5] - 2014-10-23
### Changed
- Product refactored into Catalog, Product Import and Product Export
- Credit card refactored as a payment method

### Removed
- Payment Bridge support

## Fixed
- OrderCreateRequest(s) are missing ReservationId element and corresponding Reservation Allocation value

## [1.4.0-alpha-4] - 2014-10-09
### Changed
- Inventory attribute setup to a data setup script
- PHP support from 5.3 to 5.4
- XSDs and export headers from NGP1.0.0 to NGP1.1.0
- Address Validation refactor
- Product attribute creation from configuration to setup scripts

### Fixed
- PayPal AmountAuthorized is 0
- Correct rollback allocation message
- Address admin config using wrong config path
- Purchasing a Virtual Gift Card produces an error
- Only a single price feed is exported for more than one channel/store
- 41st Parameter JavaScriptData is empty using PayPal Express Checkout
- TaxClass is not submitted in TDF Request
- PayPal requests should send the OrderId, not QuoteId
- Sum of multiple gift wrap charges are not being submitted correctly
- `EbayEnterprise_Eb2cProduct_Model_Feed` does not set `_log` property
- Address Validation Suggestions dependent upon Mage_GiftMessage
- PayPal PAYMENTACTION is set using base Magento configuration
- Do not log index rebuilds
- PayPal Express is not working with shopping cart price rules
- PaymentRequestId is inconsistent between service calls
- TDF service is not called when using PayPal Express
- Missing merchandise amount in OrderCreateRequest
- Do not throw exception for getPalDetails non-ROM API call
- PayPal authorization response code is not set
- Send corrected PayPal UniqueId in OrderCreateRequest
- GiftCardTenderCode and MaxGCAmount fields are not exported for gift card products
- `\EbayEnterprise_Eb2cCore_Helper_Feed::getMessageDate` can return non-object
- PayPalPayerInfo is not sent in the Context node when using PayPal as the payment method
- OrderCreate PaymentSessionId should use the OrderId
- Estimated Delivery Data MessageType should be "DeliveryDate" not "None"
- Eb2cFraud JS Collector Breaks Checkout Submit
- OrderCreate should submit empty tags in the HTTPAcceptData node instead of the string "null"

## [1.4.0-alpha-3] - 2014-08-19
### Added
- Gift card balance check from "My Account"

### Changed
- Order retry to look for orders with status of "unsubmitted" instead of orders with a state of "new"
- Admin configuration labels and help text

### Fixed
- Product export is failing XSD validation
- Stored Value Cards have USD hard coded currency code
- Gracefully handle "New" orders with no `eb2c_order_create_request_attribute`

## [1.4.0-alpha-2] - 2014-07-31
### Removed
- Order status feed vestiges

### Fixed
- Removed Order Status code still being called as observer

## [1.4.0-alpha-1] - 2014-07-17
### Fixed
- QuantityRequest seems to duplicate line items of parent/child product
- TaxCode was not populated for the Configurable (Parent) item
- Order history and order detail pulling from local, not eb2c
- Unable to checkout with PayPal when Gift Wrapping in Order
- Gift card PIN is not submitted with the order
- Product import not importing color descriptions

[1.5.0-beta-5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-beta-4...1.5.0-beta-5
[1.5.0-beta-4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-beta-3...1.5.0-beta-4
[1.5.0-beta-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-beta-2...1.5.0-beta-3
[1.5.0-beta-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-beta-1...1.5.0-beta-2
[1.5.0-beta-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-6...1.5.0-beta-1
[1.5.0-alpha-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-5...1.5.0-alpha-6
[1.5.0-alpha-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-5...1.5.0-alpha-6
[1.5.0-alpha-5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-4...1.5.0-alpha-5
[1.5.0-alpha-4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-3...1.5.0-alpha-4
[1.5.0-alpha-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-2...1.5.0-alpha-3
[1.5.0-alpha-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-1...1.5.0-alpha-2
[1.5.0-alpha-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-rc-1...1.5.0-alpha-1
[1.4.0-rc-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-10...1.4.0-rc-1
[1.4.0-alpha-10]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-9...1.4.0-alpha-10
[1.4.0-alpha-9]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-8...1.4.0-alpha-9
[1.4.0-alpha-8]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-7...1.4.0-alpha-8
[1.4.0-alpha-7]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-6...1.4.0-alpha-7
[1.4.0-alpha-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-5...1.4.0-alpha-6
[1.4.0-alpha-5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-4...1.4.0-alpha-5
[1.4.0-alpha-4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-3...1.4.0-alpha-4
[1.4.0-alpha-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-2...1.4.0-alpha-3
[1.4.0-alpha-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-1...1.4.0-alpha-2
[1.4.0-alpha-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.0...1.4.0-alpha-1