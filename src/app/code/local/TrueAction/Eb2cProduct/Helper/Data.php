<?php
class TrueAction_Eb2cProduct_Helper_Data extends Mage_Core_Helper_Abstract
{
	private $_types;
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
	 * clear magento cache and rebuild inventory status.
	 *
	 * @return TrueAction_Eb2cProduct_Helper_Data
	 */
	public function clean()
	{
		Mage::log(sprintf('[ %s ] Disabled during testing; manual reindex required', __METHOD__), Zend_Log::WARN);
		return;
		Mage::log(sprintf('[ %s ] Start rebuilding stock data for all products.', __CLASS__), Zend_Log::DEBUG);
		try {
			// STOCK STATUS
			Mage::getSingleton('cataloginventory/stock_status')->rebuild();
		} catch (Exception $e) {
			Mage::log(sprintf('[ %s ] %s', __CLASS__, $e->getMessage()), Zend_Log::WARN);
		}
		Mage::log(sprintf('[ %s ] Done rebuilding stock data for all products.', __CLASS__), Zend_Log::DEBUG);
		return $this;
	}

	/**
	 * @return bool true if the eav config has at least one instance of the given attribute.
	 * @param string $attr
	 */
	public function hasEavAttr($at)
	{
		return 0 < (int) Mage::getSingleton('eav/config')
			->getAttribute(Mage_Catalog_Model_Product::ENTITY, $at)
			->getId();
	}

	/**
	 * @return bool true if Magento knows about the product type.
	 * @param string $type
	 */
	public function hasProdType($type)
	{
		if (!$this->_types) {
			$this->_types = array_keys(Mage_Catalog_Model_Product_Type::getTypes());
		}
		return in_array($type, $this->_types);
	}
}
