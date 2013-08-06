<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Overrides_Api_NvpTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_nvp;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_nvp = Mage::getModel('eb2cpaymentoverrides/api_nvp');
	}

	public function buildQuoteMock()
	{
		$paymentMock = $this->getMock(
			'Mage_Sales_Model_Quote_Payment',
			array(
				'getEb2cPaypalToken', 'getEb2cPaypalPayerId', 'getEb2cPaypalTransactionID',
				'setEb2cPaypalToken', 'setEb2cPaypalPayerId', 'setEb2cPaypalTransactionID', 'save'
			)
		);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalToken')
			->will($this->returnValue('EC-5YE59312K56892714')
			);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalPayerId')
			->will($this->returnValue('1234')
			);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalTransactionID')
			->will($this->returnValue('O-5SM75867VD734394E')
			);

		$paymentMock->expects($this->any())
			->method('setEb2cPaypalToken')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('setEb2cPaypalPayerId')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('setEb2cPaypalTransactionID')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getEntityId', 'getAllItems', 'getPayment')
		);
		$quoteMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue(1)
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getName', 'getQty', 'getPrice')
		);
		$itemMock->expects($this->any())
			->method('getName')
			->will($this->returnValue('Product A')
			);
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getPrice')
			->will($this->returnValue(25.00)
			);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('getPayment')
			->will($this->returnValue($paymentMock)
			);

		return $quoteMock;
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
		$getPaypalSetExpressCheckout = $nvpReflector->getMethod('_getPaypalSetExpressCheckout');
		$getPaypalSetExpressCheckout->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Paypal_Set_Express_Checkout',
			$getPaypalSetExpressCheckout->invoke($nvp)
		);

		// get coverage for getCart method
		$getCart = $nvpReflector->getMethod('_getCart');
		$getCart->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Paypal_Model_Cart',
			$getCart->invoke($nvp)
		);

		$setExpressCheckout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$setExpressCheckoutReflector = new ReflectionObject($setExpressCheckout);
		$helper = $setExpressCheckoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($setExpressCheckout, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalSetExpressCheckout = $nvpReflector->getProperty('_paypalSetExpressCheckout');
		$paypalSetExpressCheckout->setAccessible(true);
		$paypalSetExpressCheckout->setValue($this->_nvp, $setExpressCheckout);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));

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
		$setExpressCheckout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$setExpressCheckoutReflector = new ReflectionObject($setExpressCheckout);
		$helper = $setExpressCheckoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($setExpressCheckout, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalSetExpressCheckout = $nvpReflector->getProperty('_paypalSetExpressCheckout');
		$paypalSetExpressCheckout->setAccessible(true);
		$paypalSetExpressCheckout->setValue($this->_nvp, $setExpressCheckout);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
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
		$setExpressCheckout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$setExpressCheckoutReflector = new ReflectionObject($setExpressCheckout);
		$helper = $setExpressCheckoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($setExpressCheckout, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalSetExpressCheckout = $nvpReflector->getProperty('_paypalSetExpressCheckout');
		$paypalSetExpressCheckout->setAccessible(true);
		$paypalSetExpressCheckout->setValue($this->_nvp, $setExpressCheckout);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
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
		// because we are getting the paypal Get express checkout class property in the setup as a reflection some of te code
		// is not being covered, let make sure in this test that the code get covered and that it return the right class instantiation
		$nvp = Mage::getModel('eb2cpaymentoverrides/api_nvp');
		$nvpReflector = new ReflectionObject($nvp);
		$getPaypalGetExpressCheckout = $nvpReflector->getMethod('_getPaypalGetExpressCheckout');
		$getPaypalGetExpressCheckout->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Paypal_Get_Express_Checkout',
			$getPaypalGetExpressCheckout->invoke($nvp)
		);

		$getExpressCheckout = Mage::getModel('eb2cpayment/paypal_get_express_checkout');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$getExpressCheckoutReflector = new ReflectionObject($getExpressCheckout);
		$helper = $getExpressCheckoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($getExpressCheckout, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalGetExpressCheckout = $nvpReflector->getProperty('_paypalGetExpressCheckout');
		$paypalGetExpressCheckout->setAccessible(true);
		$paypalGetExpressCheckout->setValue($this->_nvp, $getExpressCheckout);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));

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
		$getExpressCheckout = Mage::getModel('eb2cpayment/paypal_get_express_checkout');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$getExpressCheckoutReflector = new ReflectionObject($getExpressCheckout);
		$helper = $getExpressCheckoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($getExpressCheckout, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalGetExpressCheckout = $nvpReflector->getProperty('_paypalGetExpressCheckout');
		$paypalGetExpressCheckout->setAccessible(true);
		$paypalGetExpressCheckout->setValue($this->_nvp, $getExpressCheckout);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
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
		// because we are getting the paypal Do express checkout class property in the setup as a reflection some of te code
		// is not being covered, let make sure in this test that the code get covered and that it return the right class instantiation
		$nvp = Mage::getModel('eb2cpaymentoverrides/api_nvp');
		$nvpReflector = new ReflectionObject($nvp);
		$getPaypalDoExpressCheckout = $nvpReflector->getMethod('_getPaypalDoExpressCheckout');
		$getPaypalDoExpressCheckout->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Paypal_Do_Express_Checkout',
			$getPaypalDoExpressCheckout->invoke($nvp)
		);

		$doExpressCheckout = Mage::getModel('eb2cpayment/paypal_do_express_checkout');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$doExpressCheckoutReflector = new ReflectionObject($doExpressCheckout);
		$helper = $doExpressCheckoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($doExpressCheckout, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalDoExpressCheckout = $nvpReflector->getProperty('_paypalDoExpressCheckout');
		$paypalDoExpressCheckout->setAccessible(true);
		$paypalDoExpressCheckout->setValue($this->_nvp, $doExpressCheckout);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));

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
		$doExpressCheckout = Mage::getModel('eb2cpayment/paypal_do_express_checkout');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$doExpressCheckoutReflector = new ReflectionObject($doExpressCheckout);
		$helper = $doExpressCheckoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($doExpressCheckout, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalDoExpressCheckout = $nvpReflector->getProperty('_paypalDoExpressCheckout');
		$paypalDoExpressCheckout->setAccessible(true);
		$paypalDoExpressCheckout->setValue($this->_nvp, $doExpressCheckout);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
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
		// because we are getting the paypal Do Authorization class property in the setup as a reflection some of te code
		// is not being covered, let make sure in this test that the code get covered and that it return the right class instantiation
		$nvp = Mage::getModel('eb2cpaymentoverrides/api_nvp');
		$nvpReflector = new ReflectionObject($nvp);
		$getPaypalDoAuthorization = $nvpReflector->getMethod('_getPaypalDoAuthorization');
		$getPaypalDoAuthorization->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Paypal_Do_Authorization',
			$getPaypalDoAuthorization->invoke($nvp)
		);

		$doAuthorization = Mage::getModel('eb2cpayment/paypal_do_authorization');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$doAuthorizationReflector = new ReflectionObject($doAuthorization);
		$helper = $doAuthorizationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($doAuthorization, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalDoAuthorization = $nvpReflector->getProperty('_paypalDoAuthorization');
		$paypalDoAuthorization->setAccessible(true);
		$paypalDoAuthorization->setValue($this->_nvp, $doAuthorization);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));

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
		$doAuthorization = Mage::getModel('eb2cpayment/paypal_do_authorization');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$doAuthorizationReflector = new ReflectionObject($doAuthorization);
		$helper = $doAuthorizationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($doAuthorization, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalDoAuthorization = $nvpReflector->getProperty('_paypalDoAuthorization');
		$paypalDoAuthorization->setAccessible(true);
		$paypalDoAuthorization->setValue($this->_nvp, $doAuthorization);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
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
		// because we are getting the paypal Do Void class property in the setup as a reflection some of te code
		// is not being covered, let make sure in this test that the code get covered and that it return the right class instantiation
		$nvp = Mage::getModel('eb2cpaymentoverrides/api_nvp');
		$nvpReflector = new ReflectionObject($nvp);
		$getPaypalDoVoid = $nvpReflector->getMethod('_getPaypalDoVoid');
		$getPaypalDoVoid->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Paypal_Do_Void',
			$getPaypalDoVoid->invoke($nvp)
		);

		$doVoid = Mage::getModel('eb2cpayment/paypal_do_void');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$doVoidReflector = new ReflectionObject($doVoid);
		$helper = $doVoidReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($doVoid, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalDoVoid = $nvpReflector->getProperty('_paypalDoVoid');
		$paypalDoVoid->setAccessible(true);
		$paypalDoVoid->setValue($this->_nvp, $doVoid);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));

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
		$doVoid = Mage::getModel('eb2cpayment/paypal_do_void');
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$doVoidReflector = new ReflectionObject($doVoid);
		$helper = $doVoidReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($doVoid, $paymentHelper);

		$nvpReflector = new ReflectionObject($this->_nvp);
		$paypalDoVoid = $nvpReflector->getProperty('_paypalDoVoid');
		$paypalDoVoid->setAccessible(true);
		$paypalDoVoid->setValue($this->_nvp, $doVoid);

		$configObject = new Mage_Paypal_Model_Config();
		$config = $nvpReflector->getProperty('_config');
		$config->setAccessible(true);
		$config->setValue($this->_nvp, $configObject);

		$cartObject = new Mage_Paypal_Model_Cart(array($this->buildQuoteMock()));
		$cart = $nvpReflector->getProperty('_cart');
		$cart->setAccessible(true);
		$cart->setValue($this->_nvp, $cartObject);

		$this->assertNull($this->_nvp->callDoVoid());
	}
}
