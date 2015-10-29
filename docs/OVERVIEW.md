![ebay logo](static/logo-vert.png)

**Magento Retail Order Management Extension**
# Overview

The intended audience for this guide is Magento merchants, business users and system integrators.

## Contents
- [Introduction](#introduction)
- [Catalog Management](#catalog-management)
- [Cart and Checkout](#cart-and-checkout)
- [Order Management](#order-management)

## Introduction

The [Retail Order Management](http://www.ebayenterprise.com/order-management/retail-order-management-0) solution is a unique set of integrated omnichannel-enabling capabilities, services and infrastructure. The solution provides tight orchestration across the entire technology value chain, spanning several key areas:

- Distributed Order Management
- Omnichannel Inventory Management 
- Store-based Fulfillment (Extension Support Coming Soon)
- Dropship Management
- Reporting
- Customer Service Tools
- Payment Processing
- Fraud Protection & Risk Management
- Taxes, Duties & Fees

The Magento Retail Order Management Extension is designed as a framework that streamlines data transmission and allows for fast track integration of Magento Enterprise Edition with eBay Enterprise's Retail Order Management.

### Data Flows

Data flows between Magento and Retail Order Management in three forms:

1. Real-time web services, called during the shopping experience
1. Batch feeds, transferred via SFTP and processed on a cron schedule
1. Near-real-time events, consumed via message queues

## Catalog Management

The Magento Retail Order Management Extension uses batch feeds to manage and synchronize catalog data between Magento and the Retail Order Management's Product and Fulfillment Hub

### Inventory Management 

The extension will import an Available to Promise (ATP) feed from the Fulfillment Hub to be used by Magento to indicate whether a product should be in-stock or out-of-stock for use on category, search or product pages. 

A real-time inventory service call will be performed when adding the product to cart. Inventory data from the ATP feed is used instead of the inventory service on category, search and product pages to maintain site performance and the ability to cache the catalog pages.

### International Taxes, Duties and Fee Ratings

The extension will import a feed from the Product Hub that provides duty and rate information that is required for shipping outside of the United States. 

### Image Meta Data Export

The extension will export image meta data from Magento to the Product Hub for potential use by other systems such as marketplaces or e-mail service providers.

### Product Information

The Magento Retail Order Management Extension provides two different workflows for managing product information. The extension can support direct management of product information from within Magento or import product information into Magento from a 3rd party Product Information Management system.

When managing product information directly in Magento, the extension will export the following feeds from Magento to the Product Hub:

- Item Master
- Pricing
- Content

Conversely, when managing product information using a 3rd party system, that system will be expected to send those same three feeds to the Product Hub, enabling the extension to import those feeds into Magento.

The extension will create product attributes in Magento as needed for either importing or exporting product data.

## Cart and Checkout

The following is the data flow of a typical shopping experience using Magento with the ROM Extension installed:

1. Product information, including in-stock/out-of-stock availability will be managed on the category, search and product as described in the previous [Catalog Management](#catalog-management) section.
1. As products are carted, the inventory service is called to check inventory quantity, decrementing quantity from the quote if insufficient quantity is returned or removing the product if it is completely out-of-stock.
1. If the customer chooses to pay using a gift card, the payment service is called to check the gift card balance and apply that balance to the quote.
1. As the customer submits their address, the address validation service is called and depending on the response, the address is accepted, normalized or rejected. If rejected, the customer may be prompted with potential address matches. 
	- If the same address is used for both shipping and billing, then that single address is validated as a shipping address.
	- If different addresses are used for shipping and billing, then only the shipping address is validated. 
	- Addresses entered via the "My Account" address book will also be validated.
1. Once an address has been validated and applied to the quote, the tax service is called to provide estimated taxes, duties and fees for the quote.
	- At this time the shipping origin is unknown, so the tax request will use the "Tax Admin Origin" supplied in the extension's admin configuration.
1. As the customer submits their shipping method, the inventory service is called to provide the inventory details, which includes the shipping origin as well as the estimated delivery date.
1. Immediately after the inventory details is received, the tax service is called once more to provide more accurate tax, duties and fees details for the quote based on the actual shipping origin and including shipping taxes if applicable.
1. As the customer reviews and submits their order, the inventory service is called to allocate inventory for the order.
1. After the inventory is allocated, the payment service is called to redeem any gift cards applied to the order.
1. The payment service may be called again to authorize any credit card or PayPal transactions applied to the order.
1. After payment redemption and/or authorization, the order service is called to create and submit the order.

Orders using third-party payment methods will be submitted as "prepaid" orders. The Magento system integrator should ensure these payments are captured, as the Retail Order Management payment service will not.

The Magento Retail Order Management Extension will automatically void or reverse any allocations or payments, if necessary, when a failure occurs during order submission.

The Magento Retail Order Management Extension will gracefully handle any transient issues with Retail Order Management services. If a non-essential service like inventory or tax is unavailable or provokes a timeout, the service call will be skipped and the customer will be able to proceed through checkout. If an order is submitted without a successful allocation or applied taxes, the order service will retry the inventory, tax and payment calls before releasing the order for fulfillment. If the order service itself is unavailable, the extension will accept the order, save it locally and attempt to resubmit it at a later time.

The only services that will prevent a customer from proceeding when unavailable are the payment services for PayPal and gift cards.

## Order Management

When using the Magento Retail Order Management Extension, the Retail Order Management platform will now serve as the Order Management System (OMS) and be the sole system responsible for the state of the order.

| Important |
|:----------|
| Once an order is submitted, changes to an order from Magento will not be applied to that order. Likewise, Magento may not receive all order updates from the Retail Order Management order service. Additionally, other Magento extensions depending upon order state or status may not behave as expected. |

### Order History

When a customer checks their order status via the "Orders and Returns" or "My Orders" section of their "My Account" profile, the order service is called to provide up-to-date order history and details, including order status and shipment tracking numbers. All of the customer's orders, even those that did not originate with Magento, are displayed. This information is not saved back into the Magento sales database.

### Order Events

The Retail Order Management order service may make order events available for the extension to consume via a message queue. On consumption, the extension will fire corresponding Magento events with the message of that event as a payload. The extension may trigger Magento actions like creating a shipment or credit memo when applicable.

It should be noted that the extension will not guarantee capture of all updates or changes to an order. Depending on the desired outcome, the Magento system integrator may need to create custom code to listen for these events and react as desired.

### Transactional Emails

The Retail Order Management notification service is pre-integrated with eBay Enterprise Email to send transactional emails. If the extension is configured to use eBay Enterprise Email as the email handler, then the following emails will be suppressed from Magento:

- New Order Confirmation
- New Order
- Order Comment
- New Shipment
- Shipment Comment
- New Invoice
- Invoice Comment
- New Credit Memo
- Credit Memo Comment

If a specific implementation requires a third-party email provider, then the Magento system integrator will be required to ensure the order events trigger the proper third party emails.

- - -
Copyright © 2014 eBay Enterprise, Inc.
