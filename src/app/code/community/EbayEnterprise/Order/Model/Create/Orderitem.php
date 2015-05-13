<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IPriceGroup;

/**
 * Builds out order items for order create request
 */
class EbayEnterprise_Order_Model_Create_Orderitem
{
	/** @var EbayEnterprise_Eb2cCore_Helper_Discount */
	protected $_discountHelper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;

	/**
	 * inject dependencies on construction
	 * @param array
	 */
	public function __construct(array $args=[])
	{
		list($this->_discountHelper, $this->_coreHelper) =
			$this->_checkTypes(
				$this->_nullCoalesce('discount_helper', $args, Mage::helper('eb2ccore/discount')),
				$this->_nullCoalesce('core_helper', $args, Mage::helper('eb2ccore'))
			);
	}

	/**
	 * ensure correct types are being injected
	 * @param  EbayEnterprise_Eb2cCore_Helper_Discount
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Eb2cCore_Helper_Discount $discountHelper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
	) {
		return [$discountHelper, $coreHelper];
	}

	/**
	 * return $ar[$key] if it exists otherwise return $default
	 * @param  string
	 * @param  array
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce($key, array $ar, $default)
	{
		return isset($ar[$key]) ? $ar[$key] : $default;
	}

	/**
	 * build out the order item payload
	 * @param  IOrderItem
	 * @param  Mage_Sales_Model_Order_Item
	 * @param  Mage_Sales_Model_Order
	 * @param  Mage_Sales_Model_Order_Address
	 * @param  int
	 * @param  string
	 * @return IOrderItem
	 */
	public function buildOrderItem(
		IOrderItem $payload,
		Mage_Sales_Model_Order_Item $item,
		Mage_Sales_Model_Order $order,
		Mage_Sales_Model_Order_Address $address,
		$lineNumber,
		$shippingChargeType
	) {
		$merch = $payload->getMerchandisePricing();
		$this->_prepareMerchandisePricing($item, $merch);
		$romShippingMethod = $this->_coreHelper->lookupShipMethod($order->getShippingMethod());
		if ($this->_isShippingPriceGroupRequired($shippingChargeType, $lineNumber)) {
			$this->_prepareShippingPriceGroup($address, $payload);
		}
		list($itemSize, $itemSizeId) = $this->_getItemSizeInfo($item);
		list($itemColor, $itemColorId) = $this->_getItemColorInfo($item);
		$payload
			->setLineNumber($lineNumber)
			->setItemId($item->getSku())
			->setQuantity($item->getQtyOrdered())
			->setDescription($item->getName())
			->setMerchandisePricing($merch)
			->setColor($itemColor)
			->setColorId($itemColorId)
			->setSize($itemSize)
			->setSizeId($itemSizeId)
			->setDepartment($item->getProduct()->getDepartment())
			->setShippingMethod($romShippingMethod)
			->setShippingMethodDisplayText($order->getShippingDescription())
			->setVendorId($item->getProduct()->getDropShipSupplierNumber())
			->setVendorId($item->getProduct()->getDropShipSupplierName())
			// this is set here as a default; it is expected that the ISPU/STS module
			// will update this value through the order item event
			->setFulfillmentChannel($payload::FULFILLMENT_CHANNEL_SHIP_TO_HOME);
		return $payload;
	}

	/**
	 * fillout the merchandise price group payload for the order item
	 * @param  Mage_Sales_Model_Order_Item
	 * @param  IPriceGroup
	 * @return self
	 */
	protected function _prepareMerchandisePricing(Mage_Sales_Model_Order_Item $item, IPriceGroup $merch)
	{
		$merch
			->setAmount($item->getRowTotal())
			->setUnitPrice($item->getPrice());
		$this->_discountHelper->transferDiscounts($item, $merch);
		return $this;
	}

	/**
	 * fillout the shipping price group payload for the order item
	 * @param  Mage_Sales_Model_Order_Address
	 * @param  IOrderItem
	 * @return self
	 */
	protected function _prepareShippingPriceGroup(Mage_Sales_Model_Order_Address $address, IOrderItem $payload)
	{
		$shippingPriceGroup = $payload->getEmptyPriceGroup();
		$shippingPriceGroup->setAmount((float) $address->getOrder()->getShippingAmount());
		$this->_discountHelper->transferDiscounts($address, $shippingPriceGroup);
		$payload->setShippingPricing($shippingPriceGroup);
		return $this;
	}

	/**
	 * load option data for $item
	 *
	 * @param  Mage_Sales_Model_Order_Item
	 * @return Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection
	 */
	protected function _loadOrderItemOptions(Mage_Sales_Model_Order_Item $item)
	{
		$buyRequest = $item->getProductOptionByCode('info_BuyRequest');
		$attrs = isset($buyRequest['super_attributes']) ? $buyRequest['super_attributes'] : [];
		$attrTable = ['attribute_table' => Mage::getSingleton('core/resource')->getTableName('eav/attribute')];
		$options = Mage::getResourceModel('eav/entity_attribute_option_collection');
		// join with the attribute table to get the attribute code.
		$options->getSelect()->join(
			$attrTable,
			'main_table.attribute_id=attribute_table.attribute_id',
			['attribute_code']
		);
		$options->setStoreFilter($item->getStoreId());
		$options->addFieldToFilter('main_table.attribute_id', ['in' => array_keys($attrs)]);
		$options->addFieldToFilter('main_table.option_id', ['in' => array_values($attrs)]);
		return $options;
	}

	/**
	 * get the selected option's default and localized values of the item's
	 * attribute
	 * @param  string
	 * @param  Mage_Sales_Model_Order_Item
	 * @return array
	 */
	protected function _getOptionInfo($attributeCode, Mage_Sales_Model_Order_Item $item)
	{
		$options = $this->_loadOrderItemOptions($item);
		$option = $options->getItemByColumnValue('attribute_code', $attributeCode);
		if (!$option || ($option->getValue() && !$option->getDefaultValue())) {
			return [null, null];
		}
		return [$option->getValue(), $option->getDefaultValue()];
	}

	/**
	 * get the selected default and localized value for the color attribute
	 * @param  Mage_Sales_Model_Order_Item
	 * @return array
	 */
	protected function _getItemColorInfo(Mage_Sales_Model_Order_Item $item)
	{
		// use the code to get the right data.
		return $this->_getOptionInfo('color', $item);
	}

	/**
	 * get the selected default and localized value for the size attribute
	 * @param  Mage_Sales_Model_Order_Item
	 * @return array
	 */
	protected function _getItemSizeInfo(Mage_Sales_Model_Order_Item $item)
	{
		return $this->_getOptionInfo('size', $item);
	}

	/**
	 * return true if the order item needs to have the shipping price group included
	 * @param  string
	 * @param  int
	 * @return boolean
	 */
	protected function _isShippingPriceGroupRequired($chargeType, $lineNumber)
	{
		return $chargeType !== EbayEnterprise_Order_Model_Create::SHIPPING_CHARGE_TYPE_FLATRATE ||
			$lineNumber === 1;
	}
}
