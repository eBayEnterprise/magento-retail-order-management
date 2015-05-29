![eBay Enterprise](static/logo-vert.png)

**Magento Retail Order Management Extension**
# Installation and Configuration Guide

The intended audience for this guide is Magento system integrators. You should review the [Magento Retail Order Management Extension Overview](OVERVIEW.md) before attempting to install and configure the extension.

Knowledge of Magento installation and configuration, [PHP Composer](https://getcomposer.org/), Magento Admin Configuration, Magento XML Configuration and Magento localization CSV files is assumed in this document.

## Contents

1. [Requirements](#requirements)
1. [Installation](#installation)
1. [Admin Configuration](#admin-configuration)
1. [Local XML Configuration](#local-xml-configuration)
1. [Catalog Workflow Configuration](#catalog-workflow-configuration)
1. [Optional Configuration](#optional-configuration)

## Requirements

### System Requirements

- Magento Enterprise Edition 1.14.1 ([system requirements](http://magento.com/resources/system-requirements))
- PHP XSL extension
- PHP OpenSSL extension
- [Magento Composer Installer](https://github.com/Cotya/magento-composer-installer)

### Internationalization

Support for high-order unicode characters (such as Japanese and Arabic) can be achieved by installing the [Multibyte String Extension](http://www.php.net/manual/en/book.mbstring.php) and using its [Function Overloading Feature](http://www.php.net/manual/en/mbstring.overload.php). The Magento Retail Order Management Extension does not call `mb_string` functions directly.

### Remote Address Headers

The Magento Retail Order Management Extension's Fraud Protection & Risk Management module requires the client IP address. Compliant browsers send this in the `REMOTE_ADDR` header, so if the client is connected directly to the web server you do not need to do anything. However, in a load balancer/reverse proxy setup you may need to set `remote_addr_headers` in local.xml to get the right value from the reverse proxy. This is also documented in `app/etc/local.xml.additional`, included with Magento.

```xml
<config>
    <global>
        <remote_addr_headers>
            <header1>HTTP_X_REAL_IP</header1>
            <header2>HTTP_X_FORWARDED_FOR</header2>
        </remote_addr_headers>
    </global>
</config>
```

The above will cause Magento to look first in the specified HTTP request headers before falling back to `REMOTE_ADDR`.

### Required Information

The following information will be provided to you by eBay Enterprise:

- Catalog Id
- Client Id
- Store Id(s)
- Store Language Code(s)
- Client Order Id
- Client Customer Id
- Web Service Credentials
	- API Hostname
	- API Key
- Feed Credentials
	- SFTP User Name
	- Remote Host
	- Remote Port
- AMQP Credentials (Optional, required to consumer Order Events)
- Client-side Encryption Key (Optional, required to qualify as SAQ A-EP merchant for PCI DSS Compliance)
- Shipping Codes
- Gift Card Number Ranges (Optional, required to accept gift cards)
- 41st Parameter Collection JavaScript

You will need to provide an SFTP public key to eBay Enterprise to access the Product and Fulfillment Hub.

## Installation
### Step 0: Apply Address Validation Patch

Apply the [Address Validation Patch](../deploy/address.validation-1.14.2.0.patch) to your Magento installation. This patch allows the `customer_address_validation_after` event to halt checkout and provide extendable functionality if the customer's address was not validated.

### Step 1: Configure composer.json

Add the [Firegento Composer Repository](http://packages.firegento.com/) to your `composer.json` file so composer can find the packages it needs to install.

```sh
composer config repositories.firegento composer http://packages.firegento.com
```

### Step 2: Install the Magento Composer Installer

The [Magento Composer Installer](https://github.com/Cotya/magento-composer-installer) empowers composer to deploy packages into a Magento installation. When the package asks for your Magento root directory, you can enter a simple dot (`.`) to mean the current directory.

```sh
composer require magento-hackathon/magento-composer-installer=~2.1
```

(You may safely ignore any suggestion to add http://packages.magento.com as another repository.)

### Step 3: Install the Extension and Its Dependencies

Run the following command:

```sh
composer require 'ebayenterprise/magento-retail-order-management=~{major}.{minor}'
```

This command will install the extension, and symlink the extension files into the appropriate locations of the Magento directory tree.

| Important |
|:----------|
| The Magento Composer Installer deploys files as symlinks by default. Therefore you must enable symlinks in the Magento Admin at **System > Configuration > ADVANCED > Developer > Template Settings > Allow Symlinks > Yes** for the extension to be deployed effectively. |

### Step 4: Install 41st Parameter JavaScript

You will be provided with a set of 41st Parameter JavaScript files required to collect data for Fraud Protection & Risk Management. Please install those files in the [`js/ebayenterprise_eb2cfraud/` directory](../src/js/ebayenterprise_eb2cfraud/).

You can confirm the files have been installed in the Magento Admin at **System > Configuration > eBay Enterprise > Retail Order Management > Fraud > Fraud Files Installed**.

### Updating

You can update the extension and its dependencies by running:

```sh
composer update
```

This command will perform the update according to the version restrictions given in the composer file. For example, if in the previous example we used `"~1.4"`, then `compose update` will fetch the most recent version of the extension in the 1.4.x line. You can change the version restrictions using the `composer require` command.

## Admin Configuration

Credentials and extension options should be configured in the Magento Admin at **System > Configuration > eBay Enterprise > Retail Order Management**.

### Payment Methods

The Magento Retail Order Management Extension provides eBay Enterprise payment methods that should be configured at **System > Configuration > Sales > Payment Methods**. These payment methods include:

- **eBay Enterprise Credit Card**
- **eBay Enterprise PayPal**

### Logging


The Magento Retail Order Management Extension depends on the [Magento Logging Extension by eBay Enterprise](https://github.com/eBayEnterprise/magento-log) to provide important additional logging features. Please refer to the [documentation for that extension](https://github.com/eBayEnterprise/magento-log#ebay-enterprise-mage-logger).

## Local XML Configuration

The Magento Retail Order Management Extension requires additional configuration via local XML.

The extension includes a sample configuration file—[`app/etc/rom.xml.sample`](../src/app/etc/rom.xml.sample)—that includes detailed documentation and example configuration options. You can use this file as a starting point for your implementation by renaming this file to `rom.xml`. Carefully review all options to ensure they match your specific implementation.

## Catalog Workflow Configuration

The Magento Retail Order Management Extension provides two different workflows for managing product information. The extension can support direct management of product information from within Magento or import product information into Magento from a 3rd party Product Information Management system.

| Important |
|:----------|
| The Magento Retail Order Management Extension is not intended, nor was it tested, for both catalog workflows to be implemented at the same time. Please implement one and only one catalog workflow as described below. |

The catalog workflows are intended to import product information only. Regardless of which catalog workflow is implemented, the following should be configured in Magento prior to importing or exporting products:

- Categories
- Product Attributes
- Product Attribute Sets

### Product Information Management via Magento

To configure the extension to export product information from Magento to Product Hub:

#### Enable the ProductExport module

Add an enabler file to `app/etc/modules` to activate the ProductExport module. For example, create a file like `app/etc/modules/Z_EbayEnterprise_Enabler.xml` with contents like

```xml
<config>
    <modules>
        <EbayEnterprise_ProductExport>
            <active>true</active>
        </EbayEnterprise_ProductExport>
    </modules>
</config>
```

#### Configure Product Export via XML

The extension includes a sample product export configuration file—[`app/etc/productexport.xml.sample`](../src/app/etc/productexport.xml.sample)—that includes detailed documentation and example configuration options. Use this file as a starting point for your implementation by renaming this file to `productexport.xml`. Carefully review all options to ensure they match your specific implementation.

### Product Information Management via a 3rd Party System

To configure the extension to import product information from an external Product Information Management system via Product Hub:

#### Enable the ProductImport module

Add an enabler file to `app/etc/modules` to activate the ProductImport module. For example, create a file like `app/etc/modules/Z_EbayEnterprise_Enabler.xml` with contents like

```xml
<config>
    <modules>
        <EbayEnterprise_ProductImport>
            <active>true</active>
        </EbayEnterprise_ProductImport>
    </modules>
</config>
```

#### Configure Product Import via XML

The extension includes a sample product import configuration file—[`app/etc/productimport.xml.sample`](../src/app/etc/productimport.xml.sample)—that includes detailed documentation and example configuration options. Use this file as a starting point for your implementation by renaming this file to `productimport.xml`. Carefully review all options to ensure they match your specific implementation.

## Optional Configuration

### Custom User Messages

At times, the Magento Retail Order Management Extension will display success or failure messages to the user. All messages are passed through Magento's translation functions before output for display. To change any of these messages, simply use a translation CSV file for the required language. The default message content can be found at [`app/locale/en_US/`](../src/app/locale/en_US/)

### Combinations of Modules and Capabilities

The Magento Retail Order Management Extension is an interrelated set of modules, but some modules may be disabled independently.

You can disable the following modules:

1. Customer Service Tools
1. Address Validation
1. Gift Wrap and Messaging
1. Credit Card
1. Gift Card
1. PayPal

- - -
Copyright © 2014 eBay Enterprise, Inc.
