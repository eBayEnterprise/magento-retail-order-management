# Magento as a Product Information Management System

Magento shall be the system of record for product information (except for inventory management). The implementation should consist mostly of filter, dump and transform operations on the Magento flat products table to provide the expected XML output for Sterling Integrator.

## Combined Feed

The outbound product feed is defined by a unified schema with elements similar to those of the inbound ItemMaster, ContentMaster, PriceEvent and iShip feeds.

## Mappings

Attribute codes in Magento shall be mapped using a config.xml definition similar to Magento event observers:

```xml
<eb2cproduct_feed_pim_mappings>
	<mage_attribute_code> <!-- The attribute code for this attribute in Magento -->
		<class>eb2cproduct/pim</class> <!-- Magento object factory string -->
		<type>(disabled|model|helper|singleton)</type> <!-- Add helper to what dispatchEvent handles -->
		<method>takeAction</method> <!-- Any public method -->
		<xml_dest>Explicit/Relative/Path/To/Node</xml_dest> <!-- Subset-of-xpath expression describing how to create the destination node. -->
		<translate>1</translate> <!-- Whether the resultant xml can contain more than one node differentiated by a `xml:lang` attribute or only a single node with no `xml:lang` -->
	</mage_attribute_code>
	...
</eb2cproduct_feed_pim_mappings>
```

The `xml_dest` node takes its syntax from XPath, but must unambiguously describe a single destination node, so essentially can only consist of element and attribute nodenames separated by slashes. Ambiguous `xml_dest` values and `xml_dest` values with leading slashes, starting with `..` or ending with a slash should result in a fatal exception. The leftmost node of the xpath is expected to be one of BaseAttributes, ExtendedAttributes or CustomAttributes, but it is not explicitly forbidden for it to be something else.

If `type` is `disabled`, the attribute should not be sent to Sterling Integrator. This is useful if you want to specify that an attribute should never be sent such that it cannot be overridden.

To send a hard-coded value regardless of the actual attribute value for the product, create a method that returns that value. (Ideally, the method would fetch a hard-coded value from some other reasonable config.xml node, but if we really want to be idealistic we should not send hard-coded values.)

### Built-in methods

An eb2cproduct pim helper should have predefined passthrough methods for the following data types:

- boolean
- string
- integer
- float

### Examples

A simple example mapping a Yes/No attribute with the code of drop_shippable to the IsDropShipped BaseAttribute would look like:

```xml
<drop_shippable>
	<class>eb2cproduct/pim</class>
	<type>helper</type>
	<method>passYesNoToBool</method>
	<xpath>BaseAttributes/IsDropShipped</xpath>
</drop_shippable>
```

## Dependency and Non-Dependency Attributes

_This section to consist of description of attributes that Sterling Integrator depends on, and should not be overwritten. Also, we need to describe how configurable products get mapped to styles._

## Language

In Eb2c terms, some attribute values can vary on language. In Magento terms this means we distribute the different values for these nodes across different stores, if such stores exist. (For these purposes we do not distinguish between a "store" and a "store view".) If the config.xml `translate` element is set to 0, the resultant xml should contain a single node with no `xml:lang` attribute.`

### Where to find language in the feeds

Feeds denote language according to one of two possible structures, either:

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

Thus, if the base path starts with `CustomAttributes`, the generator must produce one `Attribute` node per language. Otherwise, the leaf node can be duplicated per language.

### What to do with language-specific attribute values

Once you have acquired an attribute node with a language, apply the value to all scopes that have that language. For example, assume you have a Magento instance set up the following way:

| Scope               | Language      | Product name  |
| ------------------- | ------------- | ------------- |
| Default             | "en-us"       | Pickle        |
| Website1            | "use default" | "use default" |
| Website1:Storeview1 | "use default" | "use default" |
| Website1:Storeview2 | "fr-ca"       | pétrin        |
| Website2            | "de-de"       | Gurke         |
| Website2:Storeview3 | "it-it"       | sottaceto     |
| Website2:Storeview4 | "en-us"       | Dill Pickle   |
| Website2:Storeview5 | "use website" | Essiggurke    |
| Website2:Storeview6 | "zh-cn"       | "use website" |

And assuming this configuration:

```xml
<eb2cproduct_feed_pim_mappings>
	<name>
		<class>eb2cproduct/pim</class>
		<type>helper</type>
		<method>passString</method>
		<xml_dest>BaseAttributes/Title</xml_dest>
		<translate>1</translate>
	</name>
	...
</eb2cproduct_feed_pim_mappings>
```

The resultant XML would look like:

```xml
<BaseAttributes>
	<Title xml:lang="en-us">Pickle</Title>
	<Title xml:lang="fr-ca">pétrin</Title>
	<Title xml:lang="de-de">Gurke</Title>
	<Title xml:lang="it-it">sottaceto</Title>
	<Title xml:lang="en-us">Dill Pickle</Title>
	<Title xml:lang="zh-cn">Pickle</Title>
	...
</BaseAttributes>
```

If two store views with the same client id, store code and language have different values for an attribute, it is OK to list both of them. In fact, for sake of simplicity it is acceptable to list redundant attribute nodes, like:

```xml
<Foo xml:lang="en-us">Fish</Foo>
<Foo xml:lang="en-us">Fish</Foo>
```

This is harmless and Sterling Integrator will normalize the input.

