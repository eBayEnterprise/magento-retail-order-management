<?php
class TrueAction_Eb2cProduct_Helper_Map extends Mage_Core_Helper_Abstract
{
	/**
	 * extract the first element of a dom node list and return a string value
	 * @param DOMNodeList $node
	 * @return string
	 */
	public function extractStringValue(DOMNodeList $node)
	{
		return ($node->length)? $node->item(0)->nodeValue : null;
	}

	/**
	 * extract the first element of a dom node list and return a boolean
	 * value of the extract string
	 * @param DOMNodeList $node
	 * @return bool
	 */
	public function extractBoolValue(DOMNodeList $node)
	{
		return Mage::helper('eb2cproduct')->parseBool(($node->length)? $node->item(0)->nodeValue : null);
	}

	/**
	 * extract the first element of a dom node list and return the string value cast as integer value
	 * @param DOMNodeList $node
	 * @return int
	 */
	public function extractIntValue(DOMNodeList $node)
	{
		return ($node->length)? (int) $node->item(0)->nodeValue : 0;
	}

	/**
	 * extract the first element of a dom node list and return the string value cast as float value
	 * @param DOMNodeList $node
	 * @return int
	 */
	public function extractFloatValue(DOMNodeList $node)
	{
		return ($node->length)? (float) $node->item(0)->nodeValue : 0;
	}

	/**
	 * check if the node list has item and if the first item node value equal to 'active' to return
	 * the status for enable otherwise status for disable
	 * @param DOMNodeList $node
	 * @return string
	 */
	public function extractStatusValue(DOMNodeList $node)
	{
		return ($node->length && strtolower($node->item(0)->nodeValue) === 'active')?
			Mage_Catalog_Model_Product_Status::STATUS_ENABLED:
			Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
	}

	/**
	 * if the node list has node value is not 'always' or 'regular' a magento value
	 * that's not visible oherwise return a magento visibility both
	 * @param DOMNodeList $node
	 * @return string
	 */
	public function extractVisibilityValue(DOMNodeList $node)
	{
		return ($node->length && (strtolower($node->item(0)->nodeValue) === 'regular' || strtolower($node->item(0)->nodeValue) === 'always'))?
			Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH:
			Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
	}

	/**
	 * it return the pass in value parameter
	 * it's a callback to return static value set in the config
	 * @param mixed $value
	 * @return mixed
	 */
	public function passThrough($value)
	{
		return $value;
	}

	/**
	 * extract the first element of a dom node list make sure it is lower case
	 * if there's no item in the DONNodeList return the default simple product type constant value
	 * @param DOMNodeList $node
	 * @return string
	 */
	public function extractProductTypeValue(DOMNodeList $node)
	{
		return ($node->length)? strtolower($node->item(0)->nodeValue) : Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
	}
}
