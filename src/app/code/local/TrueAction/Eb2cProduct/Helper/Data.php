<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Get Product config instantiated object.
	 *
	 * @return TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getModel('eb2cproduct/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
	}

	/**
	 * @return true if a feed model's eav config has at least one instance of the given attribute.
	 * @param TrueAction_Eb2cCore_Model_Feed_Interface $feed
	 * @param string $attr
	 */
	public function hasEavAttr(TrueAction_Eb2cCore_Model_Feed_Interface $feed, $attr)
	{
		return 0 < (int) $feed->getEavConfig()->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attr)->getId();
	}

	/**
	 * clear magento cache and rebuild inventory status.
	 *
	 * @return void
	 */
	public function clean()
	{
		Mage::log(sprintf('[ %s ] Start rebuilding stock data for all products.', __CLASS__), Zend_Log::DEBUG);
		try {
			// STOCK STATUS
			Mage::getSingleton('cataloginventory/stock_status')->rebuild();
		} catch (Exception $e) {
			Mage::log($e->getMessage(), Zend_Log::WARN);
		}
		Mage::log(sprintf('[ %s ] Done rebuilding stock data for all products.', __CLASS__), Zend_Log::DEBUG);

		return $this;
	}
}
