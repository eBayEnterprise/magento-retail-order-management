<?php
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
		$this->_mockObject->replaceByMockPaypalSetExpressCheckoutModel();
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
		$this->_mockObject->replaceByMockPaypalSetExpressCheckoutModel();

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
	 * @loadFixture loadConfigPaymentsDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallSetExpressCheckoutDisabled()
	{
		$this->_mockObject->replaceByMockPaypalSetExpressCheckoutModel();

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
		$this->_mockObject->replaceByMockPaypalGetExpressCheckoutModel();

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
	 * @loadFixture loadConfigPaymentsDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallGetExpressCheckoutDetailsDisabled()
	{
		$this->_mockObject->replaceByMockPaypalGetExpressCheckoutModel();

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
		$this->_mockObject->replaceByMockPaypalDoExpressCheckoutModel();

		$nvpReflector = new ReflectionObject($this->_nvp);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$quote = $this->_mockObject->buildQuoteMock();
		$quote->expects($this->atLeastOnce())
			->method('getId')
			->will($this->returnValue(1));
		$cartObject = new Mage_Paypal_Model_Cart(array($quote));

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
	 * @loadFixture loadConfigPaymentsDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallDoExpressCheckoutPaymentDisabled()
	{
		$this->_mockObject->replaceByMockPaypalDoExpressCheckoutModel();

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
		$this->_mockObject->replaceByMockPaypalDoAuthorizationModel();

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
	 * @loadFixture loadConfigPaymentsDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallDoAuthorizationDisabled()
	{
		$this->_mockObject->replaceByMockPaypalDoAuthorizationModel();

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
		$this->_mockObject->replaceByMockPaypalDoVoidModel();

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
	 * @loadFixture loadConfigPaymentsDisabled.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testCallDoVoidDisabled()
	{
		$this->_mockObject->replaceByMockPaypalDoVoidModel();

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
	 * Test that when any non-overridden API call tries to get through the call
	 * method while Eb2cPayments is enabled, an exception is thrown.
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testCallExceptions()
	{
		$this->setExpectedException('Mage_Core_Exception', 'Non-EB2C PayPal API call attempted for UnsupportedPayPalMethod');
		Mage::getModel('paypal/api_nvp')->call('UnsupportedPayPalMethod', array());
	}

}
