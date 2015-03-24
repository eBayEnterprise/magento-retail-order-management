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

class EbayEnterprise_PayPal_Test_Model_Method_ExpressTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var EbayEnterprise_PayPal_Model_Express_Payment_Info */
	protected $_paymentInfo;

	/**
	 * setup necessary mocks
	 */
	public function setUp()
	{
		$this->_paymentInfo = $this->getModelMock(
			'payment/info',
			array('setAdditionalInformation')
		);
	}

	/**
	 * verify:
	 * - That Magento will see our Model as a Payment Method via payment Helper Method getMethodInstance
	 * - If it does, it should return the exact same thing as Mage::getModel() of our Model.
	 */
	public function testIsRegisteredAsPaymentMethod()
	{
		$rawModel = Mage::getModel('ebayenterprise_paypal/method_express');
		$method = Mage::helper('payment')->getMethodInstance(
			'ebayenterprise_paypal_express'
		);
		$this->assertInstanceOf(get_class($rawModel), $method);
	}

	/**
	 * verify:
	 * - given an array with a 'token' key; the token will be stored as additionalinformation in the payment info model.
	 * - the method will work with a varien object as well.
	 *
	 * @dataProvider provideTrueFalse
	 */
	public function testAssignData($isDataObject)
	{
		$data = array(
			'token' => 'thetokenstring',
			'ignored_field' => 'ignored_value',
			'shipping_address' => ['status' => 'right one'],
			'billing_address' => ['status' => 'wrong one'],
		);
		if ($isDataObject) {
			$data = new Varien_Object($data);
		}
		$express = Mage::getModel('ebayenterprise_paypal/method_express');
		$info = Mage::getModel('payment/info');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($express, '_selectorKeys', array('token'));
		$express->setData('info_instance', $info);
		$express->assignData($data);
		$this->assertSame('thetokenstring', $info->getAdditionalInformation('token'));
		$this->assertSame(
			'right one',
			$info->getAdditionalInformation(EbayEnterprise_PayPal_Model_Express_Checkout::PAYMENT_INFO_ADDRESS_STATUS)
		);
	}
}
