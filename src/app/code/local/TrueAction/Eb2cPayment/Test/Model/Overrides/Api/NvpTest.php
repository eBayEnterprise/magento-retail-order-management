<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Overrides_Api_NvpTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_nvp;
	protected $_mockObject;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_nvp = Mage::getModel('eb2cpaymentoverrides/api_nvp');
		$this->_mockObject = new TrueAction_Eb2cPayment_Test_Mock_Model_Overrides_Paypal_Api_Nvp();
		$this->_mockObject->replaceByMockCheckoutSessionModel();
	}

	/**
	 * testing callSetExpressCheckout method
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallSetExpressCheckout()
	{
		// because we are setting the paypal set express checkout class property in the setup as a reflection some of te code
		// is not being covered, let make sure in this test that the code get covered and that it return the right class instantiation
		$nvp = Mage::getModel('eb2cpaymentoverrides/api_nvp');
		$nvpReflector = new ReflectionObject($nvp);

		// get coverage for getCart method
		$getCart = $nvpReflector->getMethod('_getCart');
		$getCart->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Paypal_Model_Cart',
			$getCart->invoke($nvp)
		);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));

		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		// adding profiles
		$item = new Varien_Object();
		$item->setScheduleDescription('Unit Test Contents');
		$this->_nvp->addRecurringPaymentProfiles(array($item));

		// set shipping options
		$this->_nvp->setShippingOptions(array($this->_nvp));
		$this->_nvp->setAmount(10.00);

		$this->assertNull($this->_nvp->callSetExpressCheckout());
	}

	/**
	 * testing callSetExpressCheckout method - with address defined
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallSetExpressCheckoutWithAddress()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));
		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		// adding profiles
		$item = new Varien_Object();
		$item->setScheduleDescription('Unit Test Contents');
		$this->_nvp->addRecurringPaymentProfiles(array($item));

		$this->_nvp->setAddress(new Varien_Object());

		$this->assertNull($this->_nvp->callSetExpressCheckout());
	}

	/**
	 * testing callSetExpressCheckout method - when eb2c PayPalSetExpressCheckout is disabled
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfigWithPaypalSetExpressCheckoutDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallSetExpressCheckoutDisabled()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);
		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));
		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		// adding profiles
		$item = new Varien_Object();
		$item->setScheduleDescription('Unit Test Contents');
		$this->_nvp->addRecurringPaymentProfiles(array($item));

		$this->assertNull($this->_nvp->callSetExpressCheckout());
	}

	/**
	 * testing callGetExpressCheckoutDetails method
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallGetExpressCheckoutDetails()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));

		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->assertNull($this->_nvp->callGetExpressCheckoutDetails());
	}

	/**
	 * testing callGetExpressCheckoutDetails method - when eb2c PayPalGetExpressCheckout is disabled
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfigWithPaypalGetExpressCheckoutDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallGetExpressCheckoutDetailsDisabled()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));
		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->assertNull($this->_nvp->callGetExpressCheckoutDetails());
	}

	/**
	 * testing callDoExpressCheckoutPayment method
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallDoExpressCheckoutPayment()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));

		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->_nvp->setAddress(new Varien_Object());

		$this->assertNull($this->_nvp->callDoExpressCheckoutPayment());
	}

	/**
	 * testing callDoExpressCheckoutPayment method - when eb2c PayPalDoExpressCheckout is disabled
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfigWithPaypalDoExpressCheckoutDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallDoExpressCheckoutPaymentDisabled()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));
		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->assertNull($this->_nvp->callDoExpressCheckoutPayment());
	}

	/**
	 * testing callDoAuthorization method
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallDoAuthorization()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));

		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Api_Nvp',
			$this->_nvp->callDoAuthorization()
		);
	}

	/**
	 * testing callDoAuthorization method - when eb2c PayPalDoAuthorization is disabled
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfigWithPaypalDoAuthorizationDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallDoAuthorizationDisabled()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));
		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Model_Api_Nvp',
			$this->_nvp->callDoAuthorization()
		);
	}

	/**
	 * testing callDoVoid method
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallDoVoid()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));

		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->assertNull($this->_nvp->callDoVoid());
	}

	/**
	 * testing callDoVoid method - when eb2c PayPalDoVoid is disabled
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfigWithPaypalDoVoidDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallDoVoidDisabled()
	{
		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->_mockObject->buildQuoteMock()));
		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->assertNull($this->_nvp->callDoVoid());
	}
}
