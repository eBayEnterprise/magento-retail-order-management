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
	 * @param  string $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @param  string $key
	 * @return TrueAction_Eb2cProduct_Model_Pim_Attribute
	 */
	public function getPimAttribute(
		$attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc, $key)
	{

		$resolvedArgs = $this->_resolveMappedCallback(
			$this->_getAttributeMapping($attribute, $key),
			$attribute,
			$product,
			$doc
		);

		return $resolvedArgs ? Mage::getModel('eb2cproduct/pim_attribute', $resolvedArgs) : null;
	}
	/**
	 * Get the attribute mapping, either from the configured mappings or using
	 * a generated default mapping for the attribute.
	 * @param  string $attribute
	 * @param  string $key
	 * @return array
	 */
	protected function _getAttributeMapping($attribute, $key)
	{
		return isset($this->_attributeMappings[$key]['mappings'][$attribute]) ?
			$this->_attributeMappings[$key]['mappings'][$attribute] :
			array();
	}

	protected function _resolveMappedCallback(
		array $callbackMapping=array(),
		$attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		// type "disabled" callbacks should always just return null
		if (empty($callbackMapping) || $callbackMapping['type'] === 'disabled') {
			return null;
		}
		$callbackMapping['parameters'] = array(
			$product->getDataUsingMethod($attribute),
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
