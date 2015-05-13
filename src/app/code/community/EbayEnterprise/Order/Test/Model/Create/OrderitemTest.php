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

class EbayEnterprise_Order_Test_Model_Create_OrderitemTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const PAYLOAD_CLASS =
		'\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem';
	const PRICEGROUP_PAYLOAD_CLASS =
		'\eBayEnterprise\RetailOrderManagement\Payload\Order\IPriceGroup';

	protected $_chargeType = EbayEnterprise_Order_Model_Create::SHIPPING_CHARGE_TYPE_FLATRATE;
	/** @var Mage_Sales_Model_Order */
	protected $_itemStub;
	/** @var Mage_Sales_Model_Order_Item */
	protected $_orderStub;
	/** @var IOrderItem */
	protected $_payload;
	/** @var IPriceGroup */
	protected $_priceGroupStub;
	/** @var Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection */
	protected $_optionValueCollectionStub;

	public function setUp()
	{
		parent::setUp();
		// replace the session for the logger context
		$this->_replaceSession('core/session');
		$this->_itemStub = $this->getModelMock('sales/order_item', ['getId', 'getOrder']);
		$this->_orderStub = $this->getModelMock('sales/order', ['load', 'save']);
		$this->_orderStub->setData(['shipping_method' => 'someshipping method']);
		$this->_addressStub = $this->getModelMock('sales/order_address', ['getId']);

		$this->_itemStub->expects($this->any())
			->method('getOrder')
			->will($this->returnValue($this->_orderStub));

		$this->_payload = Mage::helper('eb2ccore')
			->getSdkApi('orders', 'create')
			->getRequestBody()
			->getOrderItems()
			->getEmptyOrderItem();

		$this->_itemStub->addData([
			'name' => 'itemstub',
			'sku' => 'thesku',
			'store_id' => 1,
			'product_options' => serialize([
				'info_buyRequest' => [
					'super_attribute' => [
						92 => '15', // fake color
						3 => '2',   // fake size
					]
				]
			])
		]);
		$this->_optionValueCollectionStub = $this->getResourceModelMock(
			'eav/entity_attribute_option_collection',
			['load']
		);
	}

	/**
	 * provide the method to run along with the expected values
	 * @return array
	 */
	public function provideForSizeColorInfo()
	{
		return [
			['_getItemColorInfo', 'Black', '2'],
			['_getItemSizeInfo', null, null],
		];
	}

	/**
	 * verify
	 * - the localized and default values are returned
	 * - if the option does not exist, null is returned
	 *   for both default and localized values
	 *
	 * @param  string $method
	 * @param  string $localizedValue
	 * @param  string $defaultValue
	 * @dataProvider provideForSizeColorInfo
	 */
	public function testGetItemColorAndSizeInfo($method, $localizedValue, $defaultValue)
	{
		$this->replaceByMock(
			'resource_model',
			'eav/entity_attribute_option_collection',
			$this->_optionValueCollectionStub
		);
		$this->_optionValueCollectionStub->addItem(
			Mage::getModel('eav/entity_attribute_option', [
				'attribute_code' => 'color',
				'option_id' => 15,
				'value' => 'Black',
				'default_value' => '2',
			])
		);
		$handler = Mage::getModel('ebayenterprise_order/create_orderitem');
		$this->assertSame(
			[$localizedValue, $defaultValue],
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$handler, $method, [$this->_itemStub]
			)
		);
	}

	/**
	 * Remainder amounts should not be added to merchandise
	 * pricing payloads.
	 */
	public function testBuildOrderItemsRemainder()
	{
		$this->_itemStub->addData([
			'qty_ordered' => 2,
			'row_total' => 14,
			'price' => 7,
			'discount_amount' => 4,
		]);
		$handler = $this->getModelMock(
			'ebayenterprise_order/create_orderitem',
			['_loadOrderItemOptions', '_isShippingPriceGroupRequired']
		);
		$handler->expects($this->any())
			->method('_loadOrderItemOptions')
			->will($this->returnValue($this->_optionValueCollectionStub));
		$handler->expects($this->any())
			->method('_isShippingPriceGroupRequired')
			->will($this->returnValue(false));
		$handler->buildOrderItem($this->_payload, $this->_itemStub, $this->_orderStub, $this->_addressStub, 1, $this->_chargeType);

		$this->assertSame('thesku', $this->_payload->getItemId());
		$this->assertSame(null, $this->_payload->getMerchandisePricing()->getRemainder());
	}

	/**
	 * verify
	 * - the localized value is not set if there is no default value
	 */
	public function testBuildOrderItemsMissingOptionDefault()
	{
		$handler = $this->getModelMock(
			'ebayenterprise_order/create_orderitem',
			['_loadOrderItemOptions', '_prepareMerchandisePricing']
		);
		$handler->expects($this->any())
			->method('_loadOrderItemOptions')
			->will($this->returnValue($this->_optionValueCollectionStub));
		$handler->expects($this->any())
			->method('_prepareMerchandisePricing')
			->will($this->returnSelf());
		// add fake color option value
		$this->_optionValueCollectionStub->addItem(
			Mage::getModel('eav/entity_attribute_option', [
			'attribute_code' => 'color',
			'option_id' => 15,
			'value' => 'Black',
			'default_value' => null,
			])
		);
		$handler->buildOrderItem($this->_payload, $this->_itemStub, $this->_orderStub, $this->_addressStub, 2, 'FLATRATE');
		$this->assertNull($this->_payload->getColor());
		$this->assertNull($this->_payload->getColorId());
	}

	public function provideShippingPriceGroupInclusionCases()
	{
		return [
			['FLATRATE', 1],
			['NOTFLATRATE', 1],
			['NOTFLATRATE', 2],
			];
	}

	/**
	 * verify
	 * - shipping price group is included when it should be
	 * @dataProvider provideShippingPriceGroupInclusionCases
	 */
	public function testBuildOrderItemsWithShippingPriceGroup($chargeType, $lineNumber)
	{
		$handler = $this->getModelMock(
			'ebayenterprise_order/create_orderitem',
			['_loadOrderItemOptions', '_prepareMerchandisePricing', '_prepareShippingPriceGroup']
		);
		$handler->expects($this->any())
			->method('_loadOrderItemOptions')
			->will($this->returnValue($this->_optionValueCollectionStub));
		$handler->expects($this->any())
			->method('_prepareMerchandisePricing')
			->will($this->returnSelf());
		$handler->expects($this->once())
			->method('_prepareShippingPriceGroup')
			->will($this->returnSelf());
		// add fake color option value
		$this->_optionValueCollectionStub->addItem(
			Mage::getModel('eav/entity_attribute_option', [
			'attribute_code' => 'color',
			'option_id' => 15,
			'value' => 'Black',
			'default_value' => null,
			])
		);
		$handler->buildOrderItem(
			$this->_payload,
			$this->_itemStub,
			$this->_orderStub,
			$this->_addressStub,
			$lineNumber,
			$chargeType
		);
	}

	/**
	 * verify
	 * - shipping discounts are applied to the shipping pricegroup
	 */
	public function testBuildOrderItemsWithShippingDiscounts()
	{
		$chargeType = 'whatever';
		$lineNumber = 1;
		$this->_addressStub->setEbayEnterpriseOrderDiscountData(['1,2,3,4' => ['id' => '1,2,3,4']]);
		$handler = $this->getModelMock(
			'ebayenterprise_order/create_orderitem',
			['_loadOrderItemOptions', '_prepareMerchandisePricing']
		);
		$handler->expects($this->any())
			->method('_loadOrderItemOptions')
			->will($this->returnValue($this->_optionValueCollectionStub));
		$handler->expects($this->any())
			->method('_prepareMerchandisePricing')
			->will($this->returnSelf());
		$handler->buildOrderItem(
			$this->_payload,
			$this->_itemStub,
			$this->_orderStub,
			$this->_addressStub,
			$lineNumber,
			$chargeType
		);
		$pg = $this->_payload->getShippingPricing();
		$this->assertNotNull($pg);
		$this->assertCount(1, $pg->getDiscounts());
	}
	/**
	 * verify
	 * - discounts are applied to the merchandises pricegroup
	 */
	public function testBuildOrderItemsWithItemDiscounts()
	{
		$chargeType = 'whatever';
		$lineNumber = 1;
		$this->_itemStub->setEbayEnterpriseOrderDiscountData(['1' => ['id' => '1']]);
		$handler = $this->getModelMock(
			'ebayenterprise_order/create_orderitem',
			['_loadOrderItemOptions', '_prepareShippingPriceGroup']
		);
		$handler->expects($this->any())
			->method('_loadOrderItemOptions')
			->will($this->returnValue($this->_optionValueCollectionStub));
		$handler->expects($this->any())
			->method('_prepareShippingPriceGroup')
			->will($this->returnSelf());
		$handler->buildOrderItem(
			$this->_payload,
			$this->_itemStub,
			$this->_orderStub,
			$this->_addressStub,
			$lineNumber,
			$chargeType
		);
		// merchandise price group should always exist
		$pg = $this->_payload->getMerchandisePricing();
		$this->assertCount(1, $pg->getDiscounts());
	}
}
