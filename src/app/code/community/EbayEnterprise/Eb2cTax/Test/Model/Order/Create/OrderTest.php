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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateRequest;

class EbayEnterprise_Eb2cTax_Test_Model_Order_Create_OrderTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var Mage_Sales_Model_Order_Address */
	protected $_address;
	/** @var \eBayEnterprise\RetailOrderManagement\Api\HttpApi */
	protected $_sdk;
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroup */
	protected $_payload;
	/** @var int */
	protected $_quoteAddressId = 1;

	public function setUp()
	{
		parent::setUp();
		// replace the session for the logger context
		$this->_replaceSession('core/session');
		$this->_taxResponse = $this->getModelMock('eb2ctax/response', ['_construct', 'isValid', 'getResponseItemsByQuoteAddressId']);
		$this->_calculator = Mage::getModel('tax/calculation');
		$this->_calculator->setTaxResponse($this->_taxResponse);
		$this->_sdk = Mage::helper('eb2ccore')->getSdkApi('orders', 'create');
		$this->_payload = $this->_sdk->getRequestBody();
		$this->_address = Mage::getModel('sales/order_address', ['quote_address_id' => $this->_quoteAddressId]);
		$collection = new Varien_Data_Collection();
		$collection->addItem($this->_address);
		$this->_order = $this->getModelMock('sales/order', ['getAddressesCollection']);
		$this->_order->expects($this->any())
			->method('getAddressesCollection')
			->will($this->returnValue($collection));
	}

	/**
	 * The tax header error flag is not set if there are no errors
	 */
	public function testLoadingTaxData()
	{
		$orderItem1 = Mage::getModel('eb2ctax/response_orderitem');
		$taxQuotes = [Mage::getModel('eb2ctax/response_quote')];
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem1, '_taxQuotes', $taxQuotes);

		$orderItem2 = Mage::getModel('eb2ctax/response_orderitem');
		$taxQuotes = [Mage::getModel('eb2ctax/response_quote')];
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem2, '_taxQuotes', $taxQuotes);

		$this->_taxResponse->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$this->_taxResponse->expects($this->any())
			->method('getResponseItemsByQuoteAddressId')
			->with($this->identicalTo($this->_quoteAddressId))
			->will($this->returnValue(['sku1' => $orderItem1, 'sku2' => $orderItem2]));

		$handler = Mage::getModel('eb2ctax/order_create_order', ['calculator' => $this->_calculator]);
		$handler->setTaxHeaderErrorFlag($this->_payload, $this->_order);
		$this->assertNull($this->_payload->getTaxHasErrors());
	}

	/**
	 * the tax header error flag is true if there are errors
	 */
	public function testLoadingTaxDataWithItemError()
	{
		$taxQuotes = [
			Mage::getModel('eb2ctax/response_quote', ['code' => 'CalculationError']),
		];
		$orderItem1 = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem1, '_taxQuotes', $taxQuotes);

		$this->_taxResponse->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$this->_taxResponse->expects($this->any())
			->method('getResponseItemsByQuoteAddressId')
			->with($this->identicalTo($this->_quoteAddressId))
			->will($this->returnValue(['sku1' => $orderItem1]));

		$handler = Mage::getModel('eb2ctax/order_create_order', ['calculator' => $this->_calculator]);
		$handler->setTaxHeaderErrorFlag($this->_payload, $this->_order);
		$this->assertTrue($this->_payload->getTaxHasErrors());
	}

	/**
	 * the tax header error flag is true if there are errors
	 */
	public function testLoadingTaxDataWithResponseError()
	{
		$taxQuotes = [Mage::getModel('eb2ctax/response_quote')];
		$orderItem1 = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem1, '_taxQuotes', $taxQuotes);

		$this->_taxResponse->expects($this->any())
			->method('isValid')
			->will($this->returnValue(false));
		$this->_taxResponse->expects($this->any())
			->method('getResponseItemsByQuoteAddressId')
			->with($this->identicalTo($this->_quoteAddressId))
			->will($this->returnValue(['sku1' => $orderItem1]));

		$handler = Mage::getModel('eb2ctax/order_create_order', ['calculator' => $this->_calculator]);
		$handler->setTaxHeaderErrorFlag($this->_payload, $this->_order);
		$this->assertTrue($this->_payload->getTaxHasErrors());
	}
}
