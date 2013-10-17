<?php
class TrueAction_Eb2cProduct_Model_Feed_Iship
	extends TrueAction_Eb2cProduct_Model_Feed_Item
{
	public function __construct()
	{
		parent::__construct();
		$this->_extractors[] = Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
			array('hts_codes' => 'HTSCodes/HTSCode'),
			array(
				// The mfn_duty_rate attributes.
				'mfn_duty_rate' => './@mfn_duty_rate',
				// The destination_country attributes
				'destination_country' => './@destination_country',
				// The restricted attributes
				'restricted' => './@restricted', // (bool)
				// The HTSCode node value
				'hts_code' => '.',
			)
		));

		$this->_baseXpath = '/iShip/Item';
		$this->_feedLocalPath = $this->_config->iShipFeedLocalPath;
		$this->_feedRemotePath = $this->_config->iShipFeedRemotePath;
		$this->_feedFilePattern = $this->_config->iShipFeedFilePattern;
		$this->_feedEventType = $this->_config->iShipFeedEventType;
	}

	/**
	 * mapped the correct visibility data from eb2c feed with magento's visibility expected values
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the CatalogClass to determine the proper Magento visibility value
	 *
	 * @return string, the correct visibility value
	 */
	protected function _getVisibilityData(Varien_Object $dataObject)
	{
		// nosale should map to not visible individually.
		$visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;

		// Both regular and always should map to catalog/search.
		// Assume there can be a custom Visibility field. As always, the last node wins.
		$catalogClass = strtoupper(trim($dataObject->getBaseAttributes()->getCatalogClass()));
		if ($catalogClass === 'REGULAR' || $catalogClass === 'ALWAYS') {
			$visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
		}

		return $visibility;
	}

	/**
	 * disabled the product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return self
	 */
	protected function _disabledItem(Varien_Object $dataObject)
	{
		if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
			// we have a valid item, let's check if this product already exists in Magento
			$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));

			if ($this->getProduct()->getId()) {
				try {
					$productObject = $this->getProduct();
					$productObject->addData(
						array(
							'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, // mark product not visible
							'status' => 0, // disbled product
						)
					)->save(); // saving the product
				} catch (Mage_Core_Exception $e) {
					Mage::logException($e);
				}
			} else {
				// this item doesn't exists in magento let simply log it
				Mage::log(
					sprintf('[ %s ] iShip Feed Delete Operation for SKU (%d), does not exists in Magento', __CLASS__, $dataObject->getItemId()->getClientItemId()),
					Zend_Log::WARN
				);
			}
		}

		return $this;
	}

	/**
	 * adding eb2c specific attributes to a product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add eb2c specific attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set attributes data to
	 *
	 * @return self
	 */
	protected function _addEb2cSpecificAttributeToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$newAttributeData = $this->_getEb2cSpecificAttributeData( $dataObject);
		// we have valid eb2c specific attribute data let's add it and save it to the product object
		if (!empty($newAttributeData)) {
			try{
				$productObject->addData($newAttributeData)->save();
			} catch (Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while adding eb2c specific attributes to product for iShip Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}
		}

		return $this;
	}

	/**
	 * adding custom attributes to a product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add custom attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set custom data to
	 *
	 * @return self
	 */
	protected function _addCustomAttributeToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$prodHlpr = Mage::helper('eb2cproduct');
		$customData = array();
		$customAttributes = $dataObject->getCustomAttributes()->getAttributes();
		if (!empty($customAttributes)) {
			foreach ($customAttributes as $attribute) {
				$attributeCode = $this->_underscore($attribute['name']);
				if ($prodHlpr->hasEavAttr($attributeCode) && strtoupper(trim($attribute['name'])) !== 'CONFIGURABLEATTRIBUTES') {
					// setting custom attributes
					if (strtoupper(trim($attribute['operationType'])) === 'DELETE') {
						// setting custom attributes to null on operation type 'delete'
						$customData[$attributeCode] = null;
					} else {
						// setting custom value whenever the operation type is 'add', or 'change'
						$customData[$attributeCode] = $attribute['value'];
					}
				}
			}
		}

		// we have valid custom data let's add it and save it to the product object
		if (!empty($customData)) {
			try{
				$productObject->addData($customData)->save();
			} catch (Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while adding custom attributes to product for iShip Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}
		}

		return $this;
	}
}
