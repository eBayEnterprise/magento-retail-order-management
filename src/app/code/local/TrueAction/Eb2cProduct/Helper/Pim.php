<?php
class TrueAction_Eb2cProduct_Helper_Pim
{
	const DEFAULT_OPERATION_TYPE = 'Add';
	/**
	 * return a cdata node from a given string value.
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getValueAsDefault(
		$attrValue,
		$attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		return $doc->createElement('Value', $attrValue);
	}
	/**
	 * return inner value element contining $attrValue.
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTextAsNode(
		$attrValue,
		$attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		return is_null($attrValue) ? null : $doc->createCDataSection($attrValue);
	}
	/**
	 * return a DOMAttr object containing the client id value
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return DOMAttr
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getGsiClientId(
		$attrValue,
		$attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		$domAttribute = $this->_getDomAttr($doc, $attribute);
		$domAttribute->value = Mage::helper('eb2ccore/feed')->getClientId();
		return $domAttribute;
	}

	/**
	 * return DOMAttr object containing the default operation type value
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return DOMAttr
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getOperationType(
		$attrValue,
		$attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		$domAttribute = $this->_getDomAttr($doc, $attribute);
		$domAttribute->value = self::DEFAULT_OPERATION_TYPE;
		return $domAttribute;
	}

	/**
	 * return a DOMAttr object containing catalog id value
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return DOMAttr
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getCatalogId(
		$attrValue,
		$attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		$domAttribute = $this->_getDomAttr($doc, $attribute);
		$domAttribute->value = Mage::helper('eb2ccore/feed')->getCatalogId();
		return $domAttribute;
	}

	/**
	 * return a DOMAttr object containing store id value
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return DOMAttr
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getStoreId(
		$attrValue,
		$attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		$domAttribute = $this->_getDomAttr($doc, $attribute);
		$domAttribute->value = Mage::helper('eb2ccore/feed')->getStoreId();
		return $domAttribute;
	}

	/**
	 * given a DOMDocument and attribute name normalize the attribe create a DONAttr
	 * @param DOMDocument $doc
	 * @param string $nodeAttribute
	 * @return DOMAttr
	 */
	protected function _getDomAttr(DOMDocument $doc, $nodeAttribute)
	{
		return $doc->createAttribute(implode('_', array_filter(explode('_', $nodeAttribute))));
	}
}
