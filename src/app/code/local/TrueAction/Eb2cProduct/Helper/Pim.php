<?php
class TrueAction_Eb2cProduct_Helper_Pim
{
	/**
	 * return a cdata node from a given string value.
	 * @param  string                              $attrValue
	 * @param  Mage_Catalog_Model_Entity_Attribute $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return DOMNode|null
	 */
	public function getValueAsDefault(
		$attrValue,
		Mage_Catalog_Model_Entity_Attribute $attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		return $doc->createElement('Value', $attrValue);
	}
	/**
	 * return inner value element contining $attrValue.
	 * @param  string                              $attrValue
	 * @param  Mage_Catalog_Model_Entity_Attribute $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  TrueAction_Dom_Document             $doc
	 * @return DOMNode|null
	 */
	public function getTextAsNode(
		$attrValue,
		Mage_Catalog_Model_Entity_Attribute $attribute,
		Mage_Catalog_Model_Product $product,
		TrueAction_Dom_Document $doc)
	{
		return is_null($attrValue) ? null : $doc->createCDataSection($attrValue);
	}
}
