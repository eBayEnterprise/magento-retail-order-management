<?php

class TrueAction_Eb2cProduct_Model_Pim_Attribute_Factory
{
	// Config path to PIM Attribute Mappings
	const CONFIG_PATH_ATTRIBUTE_MAPPINGS = 'eb2cproduct/feed_pim_mapping';
	// Config path to the default PIM attribute mapping
	const CONFIG_PATH_DEFAULT_MAPPING = 'eb2cproduct/default_pim_mapping';
	/**
	 * Mapping of attribute codes to specific handling for the attirbute
	 * @var array
	 */
	protected $_attributeMappings = array();
	/**
	 * Default config mapping to use for any product attribute that does not
	 * have an explicit mapping in the config.xml
	 * @var array
	 */
	protected $_defaultMapping = array();
	/**
	 * Load the attribute mappins from config.xml and store them.
	 */
	public function __construct()
	{
		$coreHelper = Mage::helper('eb2ccore/feed');
		$this->_attributeMappings = $coreHelper
			->getConfigData(self::CONFIG_PATH_ATTRIBUTE_MAPPINGS);
		$this->_defaultMapping = $coreHelper
			->getConfigData(self::CONFIG_PATH_DEFAULT_MAPPING);
	}
	/**
	 * Get a new PIM Attribute model for the given product attribute and product
	 * @param  Mage_Catalog_Model_Entity_Attribute $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return TrueAction_Eb2cProduct_Model_Pim_Attribute
	 */
	public function getPimAttribute(
		Mage_Catalog_Model_Entity_Attribute $attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		$resolvedArgs = $this->_resolveMappedCallback(
			$this->_getAttributeMapping($attribute),
			$attribute,
			$product,
			$doc
		);
		return $resolvedArgs ? Mage::getModel('eb2cproduct/pim_attribute', $resolvedArgs) : null;
	}
	/**
	 * Get the attribute mapping, either from the configured mappings or using
	 * a generated default mapping for the attribute.
	 * @param  Mage_Catalog_Model_Entity_Attribute $attribute
	 * @return array
	 */
	protected function _getAttributeMapping(Mage_Catalog_Model_Entity_Attribute $attribute)
	{
		$attributeCode = $attribute->getAttributeCode();
		return isset($this->_attributeMappings[$attributeCode]) ?
			$this->_attributeMappings[$attributeCode] :
			$this->_getDefaultMapping($attributeCode);
	}
	/**
	 * Get a default mapping based on the configured defualt mapping. This method
	 * assumes the default configured 'xml_dest' is a format string expecting
	 * a single parameter of the attribute code.
	 * @param  string $attributeCode
	 * @return array
	 */
	protected function _getDefaultMapping($attributeCode)
	{
		$mapping = $this->_defaultMapping;
		$mapping['xml_dest'] = sprintf($mapping['xml_dest'], $attributeCode);
		return $mapping;
	}

	protected function _resolveMappedCallback(
		array $callbackMapping=array(),
		Mage_Catalog_Model_Entity_Attribute $attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		// type "disabled" callbacks should always just return null
		if (empty($callbackMapping) || $callbackMapping['type'] === 'disabled') {
			return null;
		}
		$callbackMapping['parameters'] = array(
			$product->getDataUsingMethod($attribute->getAttributeCode()),
			$attribute,
			$product,
			$doc
		);
		return $this->_createPimAttributeArgs(
			$callbackMapping,
			Mage::helper('eb2ccore/feed')->invokeCallback($callbackMapping),
			$product
		);
	}
	/**
	 * Create the argument array to be passed to the constructor when creating
	 * a new PIM Attribute model.
	 * @param  array                      $callbackMapping
	 * @param  DOMNode                    $pimAttributeValue
	 * @param  Mage_Catalog_Model_Product $product
	 * @return array
	 */
	protected function _createPimAttributeArgs(
		array $callbackMapping,
		DOMNode $pimAttributeValue=null,
		Mage_Catalog_Model_Product $product)
	{
		return array(
			'destination_xpath' => $callbackMapping['xml_dest'],
			'sku' => $product->getSku(),
			'language' => $callbackMapping['translate'] ?
				$product->getPimLanguageCode() :
				null,
			'value' => $pimAttributeValue,
		);
	}
}
