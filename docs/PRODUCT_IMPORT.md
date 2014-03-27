# Import Processing Details

## Import Products into Different Magento Websites

The combination of incoming catalog_id, gsi_client_id and gsi_store_id are mapped to Magento Websites.

Product import first gathers all the websites for the Magento Instance, and then for each Magento Website,
extracts relevant information from each of the feed files according to these rules:

* The ‘catalog_id’, if present, must match the Magento-webite’s catalog_id. All Magento-websites for an instance use the same catalog_id, this effectively filters out and items that have a catalog_id, and that catalog_id does not match the Magento-website.
* The ‘client_id’, if present, must match the Magento-webite’s client_id.
* The ’store_id’, if present, must match the Magento-webite’s store_id.

It is important to note that the absence of an an attribute in the incoming feed effectively acts as a wildcard.

Consider a Magento installation with 2 websites, configured with the same client_id but different store_ids.
An incoming feed that specifies only the client_id will be assigned to **both** websites.

An incoming feed node specifying **none** of the these attributes will be assigned to **all** websites.

## XML Configuration

The mappings themselves shall all be defined in config.xml in a manner similar to how Magento config already defines event observers:

```xml
<eb2cproduct_feed_attribute_mappings>
	<mage_attribute_code> <!-- The attribute code for this attribute in Magento -->
		<class>eb2cproduct/map</class> <!-- Magento object factory string -->
		<type>(disabled|model|helper|singleton)</type> <!-- Add helper to what dispatchEvent handles -->
		<method>takeAction</method> <!-- Any public method -->
		<xpath>Relative/Path/To/Node</xpath> <!-- Relative to the current item or content node -->
	</mage_attribute_code>
	...
</eb2cproduct_feed_attribute_mappings>
```

### Built-in methods

An eb2cproduct map helper should have predefined passthrough methods for the following data types:

- boolean
- string
- integer
- float

### Examples

A simple example mapping IsDropShipped from BaseAttributes to the is_drop_shipped Yes/No attribute would look like:

```xml
<is_drop_shipped>
	<class>eb2cproduct/map</class>
	<type>helper</type>
	<method>passBool</method>
	<xpath>BaseAttributes/IsDropShipped</xpath>
</is_drop_shipped>
```

XPath has a lot of power for finding nodes and even transforming them itself, so much of the work can be done in the xpath expression. For example, a standard custom attribute might look like this:

```xml
<my_custom_attribute>
	<class>eb2cproduct/map</class>
	<type>helper</type>
	<method>passString</method>
	<xpath>CustomAttributes/Attribute/[@name="my_custom_attribute"]/Value</xpath>
</my_custom_attribute>
```

## Dependency and Non-Dependency Attributes

Mappings for attributes that Magento and/or EB2C depend on must be defined in the config.xml for eb2cproduct itself, so they cannot be overridden. Mappings for other attributes should be unspecified or specified in app/etc/productimport.xml.sample so that they can be customized by SIs. If the feed processor encounters an attribute in a feed that has no Magento config mapping, it should silently proceed without error. The following are dependency attributes:

### Directly Affecting the Behavior of EB2C

- is_clean
- item_type
- sku
- style_id
- tax_code
- unresolved_product_links

### Required by Magento Out-of-the-Box

- name
- description
- short_description
- sku
- price
- weight
- status
- visibility
- tax_class_id
- price_view
- allow_open_amount
- type_id

## Language

In Eb2c terms, some attribute values can vary on language. In Magento terms this means we distribute the different values for these nodes across different stores, if such stores exist. (For these purposes we do not distinguish between a "store" and a "store view".)

### Where to find language in the feeds

Eb2c feeds denote language according to one of two possible structures, either:

```xml
<CustomAttributes>
	<Attribute name="some_attribute" xml:lang="bcp-47-language-code">
		<Value>The localized value</Value>
	</Attribute>
</CustomAttribute>
```

or

```xml
<BaseOrExtendedAttributes>
	<SomeAttribute xml:lang="bcp-47-language-code">The localized value</SomeAttribute>
</BaseOrExtendedAttributes>
```

Thus the processor must search for `xml:lang` in either the leaf node, or, if the nodeName is "Value", its parent node. If an attribute node exists without an `xml:lang` attribute, that value should be set as the default value for the product attribute. (In other words, the value should be set for the product attribute at the default scope level.)

### What to do with language-specific attribute values

Once you have acquired an attribute node with a language, apply the value to all stores that have that language. For example, assume you have a Magento instance set up the following way:

| Scope               | Language      | Product name  |
| ------------------- | ------------- | ------------- |
| Default             | "en-us"       | Pickle        |
| Website1            | "use default" |               |
| Website1:Storeview1 | "use default" | Gherkin       |
| Website1:Storeview2 | "fr-ca"       | pétrin        |
| Website2            | "de-de"       |               |
| Website2:Storeview3 | "it-it"       | sottaceto     |
| Website2:Storeview4 | "en-us"       | Pickle        |
| Website2:Storeview5 | "use website" | Essiggurke    |
| Website2:Storeview6 | "zh-cn"       | "use default" |

And assuming this configuration:

```xml
<eb2cproduct_feed_attribute_mappings>
	<name>
		<class>eb2cproduct/map</class>
		<type>helper</type>
		<method>passString</method>
		<xpath>BaseAttributes/Title</xpath>
	</name>
	...
</eb2cproduct_feed_attribute_mappings>
```

With the following feed fragment to import (in the context of the "pickle" product, for sake of simplicity):

```xml
<BaseAttributes>
	…
	<Title xml:lang="en-us">Dill Pickle</Title>
	<Title xml:lang="he-il">דיל פיקל</Title>
	<Title xml:lang="de-de">Dillgurke</Title>
	<Title xml:lang="zh-cn">泡菜</Title>
	…
</BaseAttributes>
```

The processor would put a message in the Error Confirmation feed that there are no configured stores with "he-il", and the rest of the data import would have this result:

| Scope               | Language      | Product name  |
| ------------------- | ------------- | ------------- |
| Default             | "en-us"       | Dill Pickle   |
| Website1            | "use default" |               |
| Website1:Storeview1 | "use default" | "use default" |
| Website1:Storeview2 | "fr-ca"       | pétrin        |
| Website2            | "de-de"       |               |
| Website2:Storeview3 | "it-it"       | sottaceto     |
| Website2:Storeview4 | "en-us"       | "use default" |
| Website2:Storeview5 | "use website" | Dillgurke     |
| Website2:Storeview6 | "zh-cn"       | 泡菜           |

#### Precedence:

A language specific value always trumps a value with no language if that language applies. If your default store has "pt-br" and your XML looks like this:

```xml
<Title>Bowl</Title>
<Title xml:lang="pt-br">tigela</Title>
```

then the value "tigela" should be applied to the default scope.

#### Scope notes:

1. If the language of a store view is the same as the default language, and the value of an attribute is changes at that upper scope, then any attribute value already set at the inner scope should be removed, and it should be set to fall back as well. This is what happened to Storeview1 and Storeview5.
2. The processor can only apply values at the lowest scope of the attribute itself. Thus, if "name" were a global attribute, only the "en-us" value would be applicable because the language at the default scope is "en-us".
	1. If the feed tries to apply multiple languages to a global attribute, the processor should put a message in the error confirmation file.
	2. If the feed tries to apply multiple languages to a website-level attribute, the processor should only put a message in the error confirmation file if there are languages in the feed that do not correspond to any configuration at the website level, even if a store view with that language exists.

## Complex Dependency Attributes

The purpose of the approach described in this document is to unify how we import product data from the feeds, so thus far this document has been as generic as possible. However, some attributes have critical meaning and should be called out explicitly.

<dl>
	<dt><code>./@gsi_client_id</code><dt><dd>This product should only be imported into websites having an <code>eb2ccore_general_client_id</code> configuration matching the value of this attribute. If no such website exists, this entire product should be skipped and a message should be added to the error confirmation feed.</dd>
	<dt><code>./@catalog_id</code></dt><dd>If the <code>eb2ccore_general_catalog_id</code> configured for the Magento instance is different from the value of this xml attribute, the entire product should be skipped and a message should be added to the error confirmation feed.</dd>
	<dt><code>./@gsi_store_id</code><dt><dd>This product should only be imported into websites having an <code>eb2ccore_general_store_id</code> configuration matching the value of this attribute. If no such website exists, this entire product should be skipped and a message should be added to the error confirmation feed. (This attribute does not appear in Item Master and should have no effect if it is not found.)</dd>
	<dt><code>(./ClientItemId|./ItemId/ClientItemId|./UniqueID)</dt><dd>Map to sku. If it does not already begin with the <code>catalog_id</code> and a dash, prepend it. (Do this with a mapping.)</dd>
	<dt><code>./BaseAttributes/CatalogClass</code></dt><dd>If the value is "regular" or "always", set <code>visibility=Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH</code>. Otherwise, set <code>visibility=Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE</code>.</dd>
	<dt><code>./BaseAttributes/ItemDescription</code></dt><dd>If the product is being created for the first time, <code>ItemDescription</code> should be the required <code>name</code> if no more appropriate attribute exists.</dd>
	<dt><code>./BaseAttributes/ItemStatus[text()="active"]</code></dt><dd>Set the product status. If "active", <code>status=Mage_Catalog_Model_Product_Status::STATUS_ENABLED</code>. Otherwise, <code>status=Mage_Catalog_Model_Product_Status::STATUS_DISABLED</code>.</dd>
	<dt><code>./BaseAttributes/TaxCode</code></dt><dd>Order Create requires a <code>tax_code</code> attribute, which should have been added to Magento when the extension installed. The mapping itself is string pass through.</dd>
	<dt><code>./ExtendedAttributes/LongDescription</code></dt><dd>String pass through to <code>description</code>.</dd>
	<dt><code>./ExtendedAttributes/ShortDescription</code></dt><dd>String pass through to <code>short_description</code>.</dd>
	<dt><code>./ExtendedAttributes/GiftWrap</code></dt><dd>Boolean pass through to <code>gift_wrapping_available</code>.</dd>
	<dt><code>./ExtendedAttributes/AllowGiftMessage</code></dt><dd>Boolean pass through to <code>gift_message_available</code>.</dd>
	<dt><code>./ExtendedAttributes/BackOrderable</code></dt><dd><code>Mage::getModel('cataloginventory/stock_item')->loadByProduct($productObject)->backorders</code></dd>
	<dt><code>./ExtendedAttributes/ItemDimension/Shipping/Mass/Weight</code></dt><dd>Float pass through to <code>weight</code></dd>
	<dt><code>./ExtendedAttributes/ColorAttributes</code></dt><dd>Colors are described by a <code>Code</code> element and one or more language-specific <code>Description</code> nodes. Eb2c ColorAttributes map to color options in Magento. The <code>Code</code> value should be set as the default and the language-specific values should map to a store view-specific field.</dd>
	<dt><code>./ExtendedAttributes/SizeAttributes</code></dt><dd>Sizes are described by a <code>Value</code> element and one or more language-specific <code>Description</code> nodes. Eb2c SizeAttributes map to color options in Magento. The <code>Value</code> value should be set as the default and the language-specific descriptions should map to a store view-specific field.</dd>
	<dt><code>./ExtendedAttributes/Price</code></dt><dd>Float pass through to <code>price</code></dd>
	<dt><code>./Event/Price</code></dt><dd>If <code>./Event/EventNumber</code> exists and has a non-whitespace value, <code>./Event/Price</code> is a float pass through to <code>special_price</code>. If <code>EventNumber</code> is empty, <code>./Event/Price</code> should set the <code>price</code> proper.</dd>
	<dt><code>./Event/StartDate</code></dt><dd>Date pass through to <code>special_from_date</code></dd>
	<dt><code>./Event/EndDate</code></dt><dd>Date pass through to <code>special_to_date</code></dd>
	<dt><code>./ExtendedAttributes/Style/StyleID[text()!=../../../ItemId/ClientItemId/text()]</code></dt><dd>If the <code>styleID</code> is the same as the <code>ClientItemId</code> or <code>UniqueID</code>, then this product is a configurable product. If not, then this product is one of the options for a configurable product with a <code>sku</code> described by the <code>styleID</code> here.</dd>
</dl>

### Required Custom Attributes

The following custom attributes are not part of any EB2C schema, but must be set for Magento to properly handle a feed. All of these custom attributes should be processed before importing any generic attributes, as they can affect what attributes can be imported.

<dl>
	<dt>ConfigurableAttributes</dt><dd>A comma-separated list of attributes on which the product can be configured. Required for any product having a <code>ProductType</code> of _Configurable_</dd>
	<dt>AttributeSet</dt><dd>Used along with the <code>operation_type</code> of the <code>AttributeSet</code> custom attribute to update the attribute set for the product.</dd>
	<dt>ProductType</dt><dd>The Magento <code>type</code> of the product. Possible values include <code>Bundle</code>, <code>Configurable</code>, <code>Downloadable</code>, <code>Gift Card</code>, <code>Grouped</code>, <code>Simple</code> and <code>Virtual</code>, but only <code>Configurable</code>, <code>Downloadable</code>, <code>Simple</code> and <code>Virtual</code> are supported</dd>
</dl>

## How to Import Giftcard Product Types

The Giftcard Specific Mapping exists in app/etc/exchangeplatform.xml.sample file:


One mapping is eb2c GiftcardTenderCode to Magento Giftcard Type:
```xml
<config>
	<default>
		<eb2ccore>
			<feed>
				<gift_card_tender_code>
					<SD>virtual</SD>
					<SP>physical</SP>
					<ST>combined</ST>
					<SV>virtual</SV>
					<SX>combined</SX>
				</gift_card_tender_code>
			</feed>
		</eb2ccore>
	</default>
</config>
```

Second mapping is the mapping of Magento product attribute to the eb2c feed xpath and callback configuration:
```xml
<config>
	<default>
		<eb2cproduct>
			<feed_attribute_mappings>
				<giftcard_type>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractGiftcardTenderValue</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</giftcard_type>
				<is_redeemable>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractIsRedeemable</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</is_redeemable>
				<use_config_is_redeemable>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractIsRedeemable</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</use_config_is_redeemable>
				<lifetime>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractLifetime</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</lifetime>
				<allow_message>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractBoolValue</method>
					<xpath>ExtendedAttributes/AllowGiftMessage</xpath>
				</allow_message>
				<email_template>
					<class>eb2cproduct/map_giftcard</class>
					<type>helper</type>
					<method>extractEmailTemplate</method>
					<xpath>ExtendedAttributes/GiftCardTenderCode</xpath>
				</email_template>
			</feed_attribute_mappings>
		</eb2cproduct>
	</default>
</config>
```

The ItemMaster Feed Need The Following Nodes In Order To Import Giftcard:
```xml
<ItemMaster>
	<Item>
		<ExtendedAttributes>
			<AllowGiftMessage>true</AllowGiftMessage> <!--This can be optional and the possible values are (true, false)-->
			<GiftCardTenderCode>SP</GiftCardTenderCode><!--This is required and can be any value that's in the map for tender code type and Magento giftcard type-->
		</ExtendedAttributes>
		<CustomAttributes>
			<Attribute name="ProductType">
				<Value>giftcard</Value> <!-- This value 'giftcard' is required if this item is giftcard-->
			</Attribute>
		</CustomAttributes>
	</Item>
</ItemMaster>
```
