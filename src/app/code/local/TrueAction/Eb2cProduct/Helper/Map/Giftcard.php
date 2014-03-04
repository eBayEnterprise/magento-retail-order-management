<?php
class TrueAction_Eb2cProduct_Helper_Map_Giftcard extends Mage_Core_Helper_Abstract
{
	const GIFTCARD_VIRTUAL = 'virtual';
	const GIFTCARD_PHYSICAL = 'physical';
	const GIFTCARD_COMBINED = 'combined';

	/**
	 * @var array holding eb2c giftcard tender type map to Magento giftcard type
	 */
	protected $_giftcardMap = array();

	/**
	 * @see self::$_giftcardMap
	 * cache the giftcard configuration map to this class property
	 * if it the property is an empty array and return the property array
	 * @return array
	 */
	protected function _getGiftCardMap()
	{
		if (empty($this->_giftcardMap)) {
			$this->_giftcardMap = Mage::helper('eb2ccore/feed')
				->getConfigData(TrueAction_Eb2cCore_Helper_Feed::GIFTCARD_TENDER_CONFIG_PATH);
		}
		return $this->_giftcardMap;
	}

	/**
	 * given a gift card type return the gift card constant mapped to it
	 * @param string $type the gift card type in this set (virtual, physical, and combined)
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
	 * extract the giftcard tender code from the DOMNOdeList object
	 * then get a configuration array of eb2c key tender type map to Magento giftcard type
	 * check if the extracted giftcard tender code is a key the array of configuration of tender type map
	 * then proceed to add lifetime, is redeemable, email template flag to true in the given product object
	 * and then return the value from get giftcard type call
	 * if the key is not found in the map simply return null
	 * @param DOMNodeList $nodes
	 * @param Mage_Catalog_Model_Product $product
	 * @return int|null integer value a valid tender type was extracted null tender type is not configure
	 */
	public function extractGiftcardTenderValue(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		$value = Mage::helper('eb2ccore')->extractNodeVal($nodes);
		$mapData = $this->_getGiftCardMap();

		if (isset($mapData[$value])) {
			$product->addData(array(
				'use_config_lifetime' => true,
				'use_config_is_redeemable' => true,
				'use_config_email_template' => true
			));
			return $this->_getGiftCardType($mapData[$value]);
		}
		return null;
	}

	/**
	 * check if the extracted giftcard tender type is not null and the use configuration is
	 * redeemable return true to get the redeemable flag value from the configuration for the particular store-view
	 * if any of the condition is not met simply return the product is redeemable value
	 * @param DOMNodeList $nodes
	 * @param Mage_Catalog_Model_Product $product
	 * @return bool
	 */
	public function extractIsRedeemable(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		return (!is_null($this->extractGiftcardTenderValue($nodes, $product)) && $product->getUseConfigIsRedeemable())?
			Mage::helper('eb2ccore')->getStoreConfigFlag(Enterprise_GiftCard_Model_Giftcard::XML_PATH_IS_REDEEMABLE, $product->getStoreId()) :
			(bool) $product->getIsRedeemable();
	}

	/**
	 * check if the extracted giftcard tender type code is not null and the given product
	 * use configuration lifetime attribute value if true to return the store configuration value for the
	 * giftcard lifetime otherwise return the product lifetime value
	 * @param DOMNodeList $nodes
	 * @param Mage_Catalog_Model_Product $product
	 * @return mixed
	 */
	public function extractLifetime(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		return (!is_null($this->extractGiftcardTenderValue($nodes, $product)) && $product->getUseConfigLifetime())?
			(int) Mage::helper('eb2ccore')->getStoreConfig(Enterprise_GiftCard_Model_Giftcard::XML_PATH_LIFETIME, $product->getStoreId()) :
			(int) $product->getLifetime();
	}

	/**
	 * check if the giftcard tender type code is not null and the given product
	 * use  configuration email template is true to return the store configuration value for the
	 * email template configuration path otherwise return the value in the product object
	 * email template attribute
	 * @param DOMNodeList $nodes
	 * @param Mage_Catalog_Model_Product $product
	 * @return int|null if the extract gift tender value is not null zero other otherwise null
	 */
	public function extractEmailTemplate(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		return (!is_null($this->extractGiftcardTenderValue($nodes, $product)) && $product->getUseConfigEmailTemplate())?
			Mage::helper('eb2ccore')->getStoreConfig(Enterprise_GiftCard_Model_Giftcard::XML_PATH_EMAIL_TEMPLATE, $product->getStoreId()) :
			$product->getEmailTemplate();
	}
}
