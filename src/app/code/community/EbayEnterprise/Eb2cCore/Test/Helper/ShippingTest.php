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

class EbayEnterprise_Eb2cCore_Test_Helper_ShippingTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var EbayEnterprise_Eb2cCore_Helper_Shipping */
	protected $_shippingHelper;

	public function setUp()
	{
		$config = $this->buildCoreConfigRegistry([
			'shippingMethodMap' => [
				'carrier1_methodA' => 'SDK_METHODA',
				'carrier2_methodB' => 'SDK_METHODB'
			],
		]);
		$carriers = [
			'carrier1' => $this->_mockCarrier('StarMail'),
			'carrier2' => $this->_mockCarrier('SpaceFallOne')
		];
		$this->shippingConfig = $this->getModelMockBuilder('shipping/config')
			->disableOriginalConstructor()
			->setMethods(['getActiveCarriers'])
			->getMock();
		$this->shippingConfig->expects($this->any())
			->method('getActiveCarriers')
			->will($this->returnValue($carriers));
		$this->_shippingHelper = $this->getHelperMockBuilder('eb2ccore/shipping')
			->setMethods(['_getShippingConfig'])
			->setConstructorArgs([['config' => $config]])
			->getMock();
		$this->_shippingHelper->expects($this->once())
			->method('_getShippingConfig')
			->will($this->returnValue($this->shippingConfig));
	}

	/**
	 * verify the first shipping method code found is returned
	 *
	 */
	public function testGetUsableMethod()
	{
		$this->assertSame(
			'carrier1_methodA',
			$this->_shippingHelper->getUsableMethod(Mage::getModel('sales/quote_address'))
		);
	}

	/**
	 * verify if the address has a shipping method, return the address' shipping method
	 *
	 */
	public function testGetUsableMethodFromAddress()
	{
		$this->assertSame(
			'carrier2_methodB',
			$this->_shippingHelper->getUsableMethod(
				Mage::getModel('sales/quote_address', ['shipping_method' => 'carrier2_methodB'])
			)
		);
	}

	/**
	 * verify
	 * - returns a string for the carrier and shipping method
	 */
	public function testGetMethodTitle()
	{
		$this->assertSame(
			'SpaceFallOne - ludicrously speedy',
			$this->_shippingHelper->getMethodTitle('carrier2_methodB')
		);
	}

	/**
	 * mock up a carrier mdoel
	 *
	 * @param string
	 * @param array $methods shipping method array
	 * @return Mage_Shipping_Model_Carrier_Abstract
	 */
	protected function _mockCarrier($title, $methods = null)
	{
		$methods = $methods ?: ['methodA' => 'warp speed', 'methodB' => 'ludicrously speedy'];
		$carrierStub = $this->getMockBuilder('Mage_Shipping_Model_Carrier_Abstract')
			->disableOriginalConstructor()
			->setMethods(['getAllowedMethods', 'getConfigData', 'setStore'])
			->getMockForAbstractClass();
		$carrierStub->expects($this->once())
			->method('getAllowedMethods')
			->will($this->returnValue($methods));
		$carrierStub->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo('title'))
			->will($this->returnValue($title));
		$carrierStub->expects($this->once())
			->method('setStore')
			->with($this->anything())
			->will($this->returnSelf());
		return $carrierStub;
	}
}
