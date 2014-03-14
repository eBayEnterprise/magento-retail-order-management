<?php
/**
 * Functions to help import EB2C attributes into Magento product attributes.
 *
 * Each function is passed a DOMNodeList of nodes matching the configured xpath expression and the product object currently being processed.
 *
 * @example: public function prototypicalMapFunction(DOMNodeList $nodes, Mage_Catalog_Model_Product $product);
 *
 * <code>
 * // Return the mapped type_id if the product doesn't already have one.
 * // Otherwise return the product's existing value.
 * public function getTypeIdIfNew(DOMNodeList $nodes, Mage_Catalog_Model_Product $product) {
 *   return $product->getTypeId() ?: $nodes->item(0)->nodeValue;
 * }
 * </code>
 */
class TrueAction_Eb2cProduct_Helper_Map extends Mage_Core_Helper_Abstract
{
	const TYPE_GIFTCARD = 'giftcard';
	/**
	 * Map ownerDocuments to DomXPath objects to avoid recreating them.
	 *
	 * @var SplObjectStorage
	 */
	protected $_splStorageDocMap = null;
	/**
	 * Keep from having to reinstantiate this collection when doing the product imports.
	 *
	 * @var Mage_Catalog_Model_Category_Collection
	 */
	protected $_categoryCollection = null;
	/**
	 * extract the first element of a dom node list and return a string value
	 * @param DOMNodeList $nodes
	 * @return string
	 */
	public function extractStringValue(DOMNodeList $nodes)
	{
		return ($nodes->length)? $nodes->item(0)->nodeValue : null;
	}
	/**
	 * extract the first element of a dom node list and return a boolean
	 * value of the extract string
	 * @param DOMNodeList $nodes
	 * @return bool
	 */
	public function extractBoolValue(DOMNodeList $nodes)
	{
		return Mage::helper('eb2cproduct')->parseBool(($nodes->length)? $nodes->item(0)->nodeValue : null);
	}
	/**
	 * extract the first element of a dom node list and return the string value cast as integer value
	 * @param DOMNodeList $nodes
	 * @return int
	 */
	public function extractIntValue(DOMNodeList $nodes)
	{
		return ($nodes->length)? (int) $nodes->item(0)->nodeValue : 0;
	}
	/**
	 * extract the first element of a dom node list and return the string value cast as float value
	 * @param DOMNodeList $nodes
	 * @return int
	 */
	public function extractFloatValue(DOMNodeList $nodes)
	{
		return ($nodes->length)? (float) $nodes->item(0)->nodeValue : 0;
	}
	/**
	 * check if the node list has item and if the first item node value equal to 'active' to return
	 * the status for enable otherwise status for disable
	 * @param DOMNodeList $nodes
	 * @return string
	 */
	public function extractStatusValue(DOMNodeList $nodes)
	{
		return ($nodes->length && strtolower($nodes->item(0)->nodeValue) === 'active')?
			Mage_Catalog_Model_Product_Status::STATUS_ENABLED:
			Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
	}
	/**
	 * if the node list has node value is not 'always' or 'regular' a magento value
	 * that's not visible oherwise return a magento visibility both
	 * @param DOMNodeList $nodes
	 * @return string
	 */
	public function extractVisibilityValue(DOMNodeList $nodes)
	{
		$catalogClass = Mage::helper('eb2ccore')->extractNodeVal($nodes);
		return (strtolower($catalogClass) === 'regular' || strtolower($catalogClass) === 'always') ?
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
	 * if there's no item in the DOMNodeList return the default simple product type constant value
	 * @param DOMNodeList $node
	 * @param Mage_Catalog_Model_Product $product
	 * @return string
	 */
	public function extractProductTypeValue(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		$value = strtolower(Mage::helper('eb2ccore')->extractNodeVal($nodes));
		$type = ($this->_isValidProductType($value))? $value : Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
		$product->setTypeId($type)
			->setTypeInstance(Mage_Catalog_Model_Product_Type::factory($product, true), true);
		return $type;
	}

	/**
	 * check if a given string is a valid product type value
	 * @param string $value the value that must match one of magento product type
	 * @return bool true the value match magento product type otherwise false
	 */
	protected function _isValidProductType($value)
	{
		return in_array($value, array(
			self::TYPE_GIFTCARD,
			Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
			Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
			Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
		));
	}

	/**
	 * This should produce a serialized array of product links to be handled by
	 * the product cleaner. Arrays should consist of
	 * @param  DOMNodeList                $node    DOM nodes extracted from the feed
	 * @return string Serialized array
	 */
	public function extractProductLinks(DOMNodeList $nodes)
	{
		$links = array();
		foreach ($nodes as $linkNode) {
			$attrs = $linkNode->attributes;
			try {
				$linkType = $this->_convertToMagentoLinkType($attrs->getNamedItem('link_type')->nodeValue);
			} catch (Mage_Core_Exception $e) {
				// If the link_type in the feed dosn't match a known link type, do not
				// include it and move on to the next link.
				continue;
			}
			$links[] = array(
				'link_type' => $linkType,
				'operation_type' => $attrs->getNamedItem('operation_type')->nodeValue,
				'link_to_unique_id' => trim($linkNode->nodeValue),
			);
		}
		return serialize($links);
	}
	/**
	 * Convert the EB2C link types to link types Magento knows about through a
	 * mapping in the product config.xml.
	 * @param  string $linkType
	 * @return string
	 * @throws Mage_Core_Exception If the link type is not mapped to a Magento link type
	 */
	protected function _convertToMagentoLinkType($linkType)
	{
		return Mage::helper('eb2cproduct')->getConfigModel()->getConfig(strtolower("link_types_$linkType"));
	}

	/**
	 * extract the value in the nodelist and then passed it to helper
	 * function to ensure the value has the right info
	 * @param DOMNodeList $nodes
	 * @return string
	 */
	public function extractSkuValue(DOMNodeList $nodes)
	{
		$helper = Mage::helper('eb2ccore');
		return $helper->normalizeSku(
			$helper->extractNodeVal($nodes),
			Mage::helper('eb2cproduct')->getConfigModel()->catalogId
		);
	}
	/**
	 * extract the the title value from the DOMNodelist object if value not empty
	 * simply append the store id and return the vlaue. if the value is empty
	 * append the a know string string with the sku and the store id and return
	 * @param DOMNodeList $nodes
	 * @param Mage_Catalog_Model_Product $product
	 * @return string
	 */
	public function extractUrlKeyValue(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		$urlKey = Mage::helper('eb2ccore')->extractNodeVal($nodes);
		return ($urlKey !== '')?
			$urlKey . '-' . $product->getStoreId() :
			'Invalid Product: ' . $product->getSku() . '-' . $product->getStoreId();
	}
	/**
	 * Always return false.
	 * This is useful for clearing a value to have it fallback to a higher scope.
	 */
	public function extractFalse()
	{
		return false;
	}
	/**
	 * given a gift card type return the gift card constant mapped to it
	 * @param string $type the gift card type in this set (virtual, physical or combined)
	 * @see Enterprise_GiftCard_Model_Giftcard
	 * @return int|null
	 */
	protected function _getGiftCardType($type)
	{
		switch ($type) {
			case self::GIFTCARD_PHYSICAL:
				return Enterprise_GiftCard_Model_Giftcard::TYPE_PHYSICAL;
			case self::GIFTCARD_COMBINED:
				return Enterprise_GiftCard_Model_Giftcard::TYPE_COMBINED;
			default:
				return Enterprise_GiftCard_Model_Giftcard::TYPE_VIRTUAL;
		}
	}
	/**
	 * extract the giftcard tender code from the DOMNOdeList object get its map value
	 * from the config and then return the actual constant to the know magento gift card sets
	 * @param DOMNodeList $nodes
	 * @return int|null integer value a valid tender type was extracted null tender type is not configure
	 */
	public function extractGiftcardTenderValue(DOMNodeList $nodes)
	{
		$value = Mage::helper('eb2ccore')->extractNodeVal($nodes);
		$mapData = Mage::helper('eb2ccore/feed')->getConfigData(TrueAction_Eb2cCore_Helper_Feed::GIFTCARD_TENDER_CONFIG_PATH);
		return isset($mapData[$value])? $this->_getGiftCardType($mapData[$value]) : null;
	}
}

