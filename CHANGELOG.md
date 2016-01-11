# Change Log
All notable changes to this project will be documented in this file.

## [1.6.??] - ??
### Fixed
- Applied gift cards carry over to later admin orders

## [1.6.35] - 2015-12-21
### Added
- Debug logging to inform when to make/not make inventory details calls

## [1.6.34] - 2015-12-17
### Fixed
- Product feeds imported for every store view
- Product website id not set when importing into a multi-website Magento instance

## [1.6.33] - 2015-12-09
### Fixed
- Maintain Order of Attributes in Feed Import

## [1.6.32] - 2015-12-07
### Fixed
- Full region name from PayPal not mapped properly for shipping address

## [1.6.31] - 2015-12-04
### Fixed
- Duplicate gift card messages in cart

## [1.6.30] - 2015-12-03
### Fixed
- Order level gift messages should be applicable to ShipGroups

## [1.6.29] - 2015-12-01
### Reverted 1.6.28 incorrect change

## [1.6.28] - 2015-12-01
### Changed
- Map BaseAttributes/ItemDescription in feeds to product short_description attribute

## [1.6.27] - 2015-12-01
### Fixed
- Properly update in-stock status in inventory feed processing

## [1.6.26] - 2015-11-24
### Fixed
- Region code used in order create may be full state name

## [1.6.25] - 2015-11-20
### Fixed
- Multiple Orders in Order Resubmit Corrupts Payloads

## [1.6.24] - 2015-11-17
### Fixed
- Credit card expiration data unavailable from order details

## [1.6.23] - 2015-11-17
### Fixed
- Out of stock product added to the Admin order is causing error

## [1.6.22] - 2015-11-16
### Fixed
- Subtract backorder demand from available-to-promise inventory on import

## [1.6.21] - 2015-11-09
### Added/Removed
- Test address validation module in circle-ci
- Remove gift registry from address validation

## [1.6.20] - 2015-11-09
### Fixed
- Error confirmations may be malformed

## [1.6.19] - 2015-11-03
### Fixed
- Exception during order lookup test

## [1.6.18] - 2015-11-03
### Fixed
- Exception during order lookup does not display error message for the customer

## [1.6.17] - 2015-11-02
### Fixed
- Unable to perform PayPal transaction after changing zip on cart

## [1.6.16] - 2015-10-30
### Fixed
- Gift card PHP Floating Point Error causes card redeem to fail in checkout

## [1.6.15] - 2015-10-30
### Fixed
- PayPal order review step does not refresh totals when shipping is updated

## [1.6.14] - 2015-10-29
### Fixed
- PayPal Orders do not collect Fraud data

## [1.6.13] - 2015-10-29
### Fixed
- Phone number and email missing in the OCR for PayPal orders

## [1.6.12] - 2015-10-29
### Fixed
- Prevent empty shipping method nodes form being added to OCR
- Tax Class is not displayed in the OCR for Configurable Products

## [1.6.11] - 2015-10-27
### Added
- Add before send request payload event for ROM Credit Card Auth

## [1.6.10] - 2015-10-26
### Fixed
- 404 when trying to change config scope

## [1.6.9] - 2015-10-26
### Fixed
- Added Missing Property Declaration to Giftcard Memo
- Handle Order Info without Billing Address
- Consistent logging of exceptions thrown by the SDK

## [1.6.8] - 2015-10-22
### Fixed
- Remove current gift card from session when displayed

## [1.6.7] - 2015-10-22
### Fixed
- Gift card module stores excessive data in the session
- Fix broken unit tests

## [1.6.6] - 2015-10-13
### Changed
- Allow easier customization of the PayPal order review page

### Fixed
- OCR Promotion Discount Amount Calculation Error for Order with multiple discounts

## [1.6.5] - 2015-10-12
### Fixed
- OCR Promotion Discount Amount Calculation Error for Order with multiple discounts

## [1.6.4] - 2015-10-10
### Fixed
- Remove SDK helper to prevent circular DI

## [1.6.3] - 2015-10-09
### Changed
- Provide Means to Get All Taxes, Duties and Fees

## [1.6.2] - 2015-10-06
### Fixed
- Fix version of Retail Order Management SDK

## [1.6.1] - 2015-10-06
### Fixed
- PayPal does not void auths when quote validation prevents an order from being created

## [1.6.0] - 2015-10-03
### Added
- Send orders with address line 2 "TEST_AUTOCANCEL" as test orders
- Multi-address shipping now supports PayPal payments.
- Support for ordering bundle products
- Product import for size attributes
- Support for bundle and group product types for inventory allocation operations
- Support for estimated delivery date for backorderable products that are in stock
- Support importing custom product attributes without explicit import map
- Support sending zero-padding Customer Id in OCR and registered customer Account Pages in order to import customers from other web-stores
- Support for bundle and group product types for inventory details and quantity operations
- Support for client-side encryption for multishipping orders when eBay Enterprise Credit Card payment method is used
- Product attributes, import maps and export maps for Catalog Class and Item Status
- Display of Estimated Delivery Date in onepage and multishipping checkout
- Support for multishipping checkout to generate a single order create request
- Support for viewing order details and shipments with multiple shipping addresses in one order
- Support for ROM tax, duty and fee service when using multishipping checkout
- Support for ROM web order cancel and reason
- Documentation to extend the order create request
- Magento Enterprise Edition 1.14.2 support

### Changed
- Implement Fulfilling Virtual Gift Cards
- Implement Address Validation in Gift Registry
- Improve feed save time by stubbing indexer
- Dispatch Event when Feed Processing Complete
- Default Event/Filename for iShip Feed
- Make the color/size attribute mapping public, so custom mappings can be written for attribute options.
- The gift card module no longer needs manual configuration of bin ranges. Any existent bin range configurations will be ignored.
- `ExtendedAttributes/AllowGiftMessage` mapped to Allow Gift Message (Gift Options) instead of Allow Message (Gift Card Information)
- Inventory Allocation operations now use the ROM SDK
- Installation and Configuration Guide
- Product Import Guide
- Inventory details and quantity operations now use the ROM SDK
- Visibility import map uses a custom attribute instead of CatalogClass

### Fixed
- Invalid discount amount may be inserted into order create request
- PHP short tags cause JS error in checkout
- Send Amount Authorized to Order Service
- Sending Empty Error Confirmation Feed to ROM
- Reverted Enable toggling test mode for gift card and credit card payments
- Display Order Creation Date on Order Info
- Prevent Unnecessary Inventory Detail call to ROM because of unmanaged stock item
- Send Theme-Appropriate Shipping Description
- Put Drop Ship Supplier Name in Correct Field
- Do not set stock levels on unmanaged stock
- Properly Handle Zero Subtotal Checkout with Stored Value Cards
- Avoid Re-auth Due to Tax Recalculation
- Send Correct Region Information in Tax Requests
- Missing dashboard rep id in order create request for admin created orders
- Error Confirmation and Ack Feeds Reverse Source and Destination in Headers
- EDD Data not being sent in ROM Order Create Request Payload
- Support MPERF-7650 Patched Magento and Unpatched Magento
- Tax Code for merchandise prices missing from tax request
- EDD not showing during order review step in checkout when used with custom theme
- Giftcard order crash customer order detail and allocation error for out of stock backorderable item.
- MainDivision not showing for address validation suggested address in checkout.
- "Invalid argument" warning on order submit
- Display bundle item in customer order detail page in a single line instead of many lines.
- Supply the correct estimated delivery date for configurable products
- Supply the correct tracking number in order details
- Fix CSR Login Redirecting to 404 Page
- Support for multiple shipping address orders
- Fix errors in Address Validation for Foreign Addresses
- Removed dependency on Enterprise GiftCardAccount
- Inventory Service Failure Blocks Checkout
- Error handling shipping information for virtual products with credit card payments
- BIN is not found in range when multiple ranges exist for the same tender type
- Local inventory is checked instead of the ROM inventory service
- Missing order information on order detail
- Order Create Request does not include a product's color or size
- Registered user checkout success page does not link to the rom order detail correctly
- Log masked gift card requests/responses
- "What's PayPal" popup is not populated
- Estimated Delivery Date is not sent in the OCR if there's no template configured
- Credit Card Auth Response Log Message in `/Request/` Log Key
- Estimated Delivery Date does not display for both configurable and gift-card physical products in checkout review page
- Fatal Error found when cancelling an order that was not originally created within the current webstore
- Fatal error during checkout with discount
- Order Create Request order items are missing ShippingMethod
- Email and Last Name Fields are case sensitive on Orders and Returns Screen
- PayPal SetExpress totals do not add up
- Address validation fatal error when receiving response code "P"
- Shipping discounts are missing from tax and order create requests
- Log messages may contain sensitive data
- Unable to search by Zip in guest order lookup
- Visiting order cancel URL directly results in unhandled error
- Order create request sends duplicate skus for configurable products
- Order create request includes a shipping amount for all items
- Order create merchandise amount remainder calculation wrong and unnecessary
- Store language codes are not being properly validated in the admin
- Invalid tax response does not cause Order Create Request tax header to be set properly
- Error when `ItemDesc` value includes quotation marks
- PayPal module is not receiving the address status from the api
- ProductImageExport generates new files even when no images/products have changed
- Logger Context Helper Undefined Index Error

### Removed
- Vestigial event observer from eb2cOrder

## [1.5.0] - 2015-07-02
### Added
- Configuration mapping for customer gender
- Exposed events to allow modification or addition of information to the Order Create request
- InStore module to create and import in-store pick-up, ship-to-store and ship-from-store product attribute
- Add in-store pick-up, ship-to-store  and ship-from-store attributes to product export feed

### Changed
- Handle unacknowledged feeds with a CRIT log instead of resending the feed file
- Order create has been refactored to use the [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)
- Refactor modules to use events to inject data into the order create request
- Log statements now utilize [eBay Enterprise Magento Logger](https://github.com/eBayEnterprise/magento-log) 2.0
- Log statements now contain meta information
- Use the Retail Order Management SDK for address validation service requests
- Change "Invalid" to "Incomplete" for products not yet fully imported
- Undo prepending catalog id to the custom product attribute `style_id` when importing products

### Removed
- Order custom attribute mappings
- Last vestiges of admin suppression

### Fixed
- Guest order lookup gives "Order not found" error even when an order is found
- PayPal Express Checkout fails when using a gift card that covers the order subtotal, but not the eventual order grand total
- Order details page is not displaying the correct item quantity
- Order details page is not displaying promotional discounts
- Blank page upon submitting an order using PayPal payment method
- Sprintf-format specifiers not replaced in log messages
- Order details page was not displaying country, state or postal code
- No error message when a product with no inventory is removed from cart
- Unnecessary TDF requests when PayPal Express Checkout used from cart or product page
- No inventory check when adding an item to the cart that was just ordered
- Magento admin can place $0 orders regardless of configured payment types
- Unable to start PayPal Express Checkout
- Test API (Web Services) fails if Address Validation->Maximum Suggestions is not configured

## [1.4.0-rc-1] - 2015-01-15
### Added
- Order Price Adjustment event
- Order Gift Certificate event
- Log warning if API call to allocate inventory fails
- PayPal configuration for Image URLs
- PayPal admin configuration to "Transfer Line Items"
- Order Accepted event
- Order Cancelled event
- Order Confirmed event
- Order Credit Issued event
- Order Return in Transit event
- Order Credit Issued event
- Order Shipped event
- Order Backorder event
- Order Rejected event
- AMQP Support
- Shell script to reset the export cutoff date
- Credit card client-side encryption
- New credit card tenders may be added via configuration
- Customers may pay with multiple gift cards on a single order
- Gift card balance check from "My Account"

### Changed
- Use version "~1.0" of eBayEnterprise/RetailOrderManagement-SDK
- Order history and order details no longer require the order to exist locally in Magento
- PayPal refactored as a payment method
- Active support for Magento Enterprise Edition 1.14.1
- Core resource changed from `eb2cproduct` to `ebayenterprise_catalog`
- Documentation significantly updated
- Gift cards refactored
- Product refactored into Catalog, Product Import and Product Export
- Credit card refactored as a payment method
- Inventory attribute setup to a data setup script
- PHP support from 5.3 to 5.4
- XSDs and export headers from NGP1.0.0 to NGP1.1.0
- Address Validation refactor
- Product attribute creation from configuration to setup scripts
- Order retry to look for orders with status of "unsubmitted" instead of orders with a state of "new"
- Admin configuration labels and help text
- Out of stock and limited stock handling match Magento handling
- Order summary has been refactored to use the [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)

### Removed
- Unnecessary default configuration values from module etc/config.xml files
- Order Status mapping between ROM Order Statuses and Magento Order Status table
- ROM Order Status importation into the Magento Order Status table
- Event handler to explicitly reindex products after feed importation
- Payment Bridge support
- Order status feed vestiges
- Removed Order Status code still being called as observer

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
- Orders using gift cards are submitted with PrepaidCreditCard payment node that fails in OMS
- Shipping method not appearing on transactional e-mails
- Gift card payment information is not submitted when combined with additional payment methods
- Out-of-range gift card is applied to cart when that BIN already exists as a gift card in Magento
- Gift card with positive balance is not updated when BIN already exists as a gift card in Magento with zero balance
- If the gift card module is not configured, it should block gift card redemption
- Money Order is not available when payments are turned off
- OrderCreateRequest(s) are missing ReservationId element and corresponding Reservation Allocation value
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
- Product export is failing XSD validation
- Stored Value Cards have USD hard coded currency code
- Gracefully handle "New" orders with no `eb2c_order_create_request_attribute`
- QuantityRequest seems to duplicate line items of parent/child product
- TaxCode was not populated for the Configurable (Parent) item
- Order history and order detail pulling from local, not eb2c
- Unable to checkout with PayPal when Gift Wrapping in Order
- Gift card PIN is not submitted with the order
- Product import not importing color descriptions

[1.6.35]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.34...1.6.35
[1.6.34]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.33...1.6.34
[1.6.33]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.32...1.6.33
[1.6.32]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.31...1.6.32
[1.6.31]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.30...1.6.31
[1.6.30]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.29...1.6.30
[1.6.29]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.28...1.6.29
[1.6.28]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.27...1.6.28
[1.6.27]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.26...1.6.27
[1.6.26]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.25...1.6.26
[1.6.25]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.24...1.6.25
[1.6.24]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.23...1.6.24
[1.6.23]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.22...1.6.23
[1.6.22]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.21...1.6.22
[1.6.21]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.20...1.6.21
[1.6.20]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.19...1.6.20
[1.6.19]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.18...1.6.19
[1.6.18]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.17...1.6.18
[1.6.17]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.16...1.6.17
[1.6.16]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.15...1.6.16
[1.6.15]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.14...1.6.15
[1.6.14]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.13...1.6.14
[1.6.13]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.12...1.6.13
[1.6.12]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.11...1.6.12
[1.6.11]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.10...1.6.11
[1.6.10]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.9...1.6.10
[1.6.9]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.8...1.6.9
[1.6.8]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.7...1.6.8
[1.6.7]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.6...1.6.7
[1.6.6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.5...1.6.6
[1.6.5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.4...1.6.5
[1.6.4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.3...1.6.4
[1.6.3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.2...1.6.3
[1.6.2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.1...1.6.2
[1.6.1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-rc-1...1.5.0
[1.4.0-rc-1]: https://github.com/eBayEnterprise/magento-retail-order-management/releases/tag/1.4.0-rc-1
