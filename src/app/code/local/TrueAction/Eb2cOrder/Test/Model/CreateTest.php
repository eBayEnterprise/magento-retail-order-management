<?php
/**
 * Test Suite for the Order_Create
 */
abstract class TrueAction_Eb2cOrder_Test_Abstract extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Mocks a Sales Order
	 */
	protected function getMockSalesOrder()
	{
		return $this->_getFullMocker(
			'sales/order',
			array(
				'getAllItems'				=> array($this->_getMockSalesOrderItem()),
				'getAllPayments'			=> array($this->_getMockSalesOrderPayment()),
				'getBillingAddress'			=> $this->_getMockSalesOrderAddress(),
				'getCreatedAt'				=> '2013-08-09',
				'getCustomerDob'			=> '1890-10-02',
				'getCustomerEmail'			=> 'groucho@westwideweb.com',
				'getCustomerFirstname'		=> 'Hugo',
				'getCustomerGender'			=> 'M',
				'getCustomerId'				=> '77',
				'getCustomerLastname'		=> 'Hackenbush',
				'getCustomerMiddlename'		=> 'Z.',
				'getCustomerPrefix'			=> 'Dr.',
				'getCustomerSuffix'			=> 'MD',
				'getCustomerTaxvat'			=> '--',
				'getEb2cHostName'			=> 'mwest.mage-tandev.net',
				'getEb2cIpAddress'			=> '208.247.73.130',
				'getEb2cJavascriptData'		=> 'TF1;015;;;;;;;;;;;;;;;;;;;;;;Mozilla;Netscape;5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20Safari/536.30.1;20030107;undefined;true;;true;MacIntel;undefined;Mozilla/5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20Safari/536.30.1;en-us;iso-8859-1;;undefined;undefined;undefined;undefined;true;true;1376075038705;-5;June%207%2C%202005%209%3A33%3A44%20PM%20EDT;1920;1080;;11.8;7.7.1;;;;2;300;240;August%209%2C%202013%203%3A03%3A58%20PM%20EDT;24;1920;1054;0;22;;;;;;Shockwave%20Flash%7CShockwave%20Flash%2011.8%20r800;;;;QuickTime%20Plug-in%207.7.1%7CThe%20QuickTime%20Plugin%20allows%20you%20to%20view%20a%20wide%20variety%20of%20multimedia%20content%20in%20web%20pages.%20For%20more%20information%2C%20visit%20the%20%3CA%20HREF%3Dhttp%3A//www.apple.com/quicktime%3EQuickTime%3C/A%3E%20Web%20site.;;;;;Silverlight%20Plug-In%7C5.1.20125.0;;;;18;',
				'getEb2cReferer'			=> 'https://www.google.com/',
				'getEb2cSessionId'			=> '5nqm2sczfncsggzdqylueb2h',
				'getEb2cUserAgent'			=> 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95 Safari/537.36',
				'getEntityId'				=> '711',
				'getGrandTotal'				=> '1776',
				'getGrandTotal'				=> '1776',
				'getId'						=> '666',
				'getIncrementId'			=> '8675309',
				'getOrderCurrencyCode'		=> 'USD',
				'getShippingAddress'		=> $this->_getMockSalesOrderAddress(),
			)
		);
	}

	/**
	 * Mocks the Mage_Sales_Model_Order_Address
	 *
	 */
	private function _getMockSalesOrderAddress()
	{
		return $this->_getFullMocker(
			'sales/order_address',
			array (
				'getCity'		=> 'Williamstown',
				'getCountryId'	=> 'US',
				'getFirstname' 	=> 'Rufus',
				'getLastname' 	=> 'Firefly',
				'getMiddlename'	=> 'T.',
				'getPostalCode'	=> '90210',
				'getPrefix'		=> 'Prof.',
				'getRegion'		=> 'NJ',
				'getStreet'		=> array('1313 Mockingbird Ln', 'Suite 13'),
				'getSuffix'		=> '5th Earl of Shroudshire',
				'getTelephone'	=> '800-666-1313',
			)
		);
	}

	/**
	 * Mocks the Mage_Sales_Model_Order_Item
	 *
	 */
	private function _getMockSalesOrderItem()
	{
		return $this->_getFullMocker(
			'sales/order_item',
			array (
				'getId'							=> '48',
				'getDiscountAmount'				=> '0',
				'getEb2cDeliveryWindowFrom'		=> '2013-08-09',
				'getEb2cDeliveryWindowTo'		=> '2013-08-13',
				'getEb2cMessageType'			=> 'MessageType',
				'getEb2cReservationId'			=> '0123456789',
				'getEb2cShippingWindowFrom'		=> '2013-08-09',
				'getEb2cShippingWindowTo'		=> '2013-08-13',
				'getName'						=> 'An Item Name',
				'getPrice'						=> '1776',
				'getQtyOrdered'					=> '1',
				'getSku'						=> 'SKU123456',
				'getTaxAmount'					=> '0',
				'getTaxPercent'					=> '0',
			)
		);
	}

	/** 
 	 * Let us mock a Mage_Sales_Model_Order_Payment
	 *
	 */
	private function _getMockSalesOrderPayment()
	{
		return $this->_getFullMocker(
			'sales/order_payment',
			array (
				'getAmountAuthorized'	=> '1776',
				'getCcApproval' 		=> 'APP123456',
				'getCcAvsStatus' 		=> 'Z',
				'getCcCidStatus' 		=> 'Y',
				'getCcExpMonth'			=> '12',
				'getCcExpYear' 			=> '2015',
				'getCcStatus' 			=> true,
				'getMethod' 			=> 'eb2cfakepay',
			)
		);
	}

	/**
	 * Returns a mocked object
	 * @param a Magento Class Alias
	 * @param array of key / value pairs; key is the method name, value is value returned by that method
	 *
	 * @return mocked-object
	 */
	private function _getFullMocker($classAlias, $mockedMethodSet)
	{
		$justMethodNames = array();
		foreach( $mockedMethodSet as $method => $returnValue ) {
			$justMethodNames[] = $method;
		}

		$mock = $this->getModelMockBuilder($classAlias)
			->disableOriginalConstructor()
			->setMethods($justMethodNames)
			->getMock();

		reset($mockedMethodSet);
		foreach($mockedMethodSet as $method => $returnValue ) {
			$mock->expects($this->any())
				->method($method)
				->will($this->returnValue($returnValue));
		}
		return $mock;
	}
}

class TrueAction_Eb2cOrder_Test_Model_CreateTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	private $_creator;

	/**
	 * Setup gets a TrueAction_Eb2cOrder_Model_Create, and mocks the send method
	 */
	public function setUp()
	{
		$this->_creator = $this->getMock('TrueAction_Eb2cOrder_Model_Create', array('sendRequest'));
		$this->_creator->expects($this->any())
			->method('sendRequest')
			->will($this->returnValue(true));
		/*
		$x = $this->getMockSalesOrder();
		foreach( get_class_methods($x) as $method ) {
			if( !strncmp($method, 'get', 3) ) {
				$foo = $x->$method();
				echo "$method yields ";
				echo $foo;
				echo "\n";
			}
		}
		 */
	}

	/**
	 * @test
	 * Test factory method returns proper class
	 */
	public function testFactoryMethod()
	{
		$testFactoryCreator = Mage::getModel('eb2corder/create');
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Create', $testFactoryCreator );
	}

	/**
	 * Create the Order
	 * @test
	 * @large
	 * @loadFixture testOrderCreateScenarios.yaml
	 */
	public function testOrderCreate()
	{
		$creator = Mage::getModel('eb2corder/create');
		$creator->buildRequest($this->getMockSalesOrder());
		$status = $creator->sendRequest();
		$this->assertSame($status, true);
	}

	/**
	 * @test
	 * TODO: Heck knows how this will be fully implemented but at some point under some set of circumstances
	 *	we will have 'finally failed' to create an eb2c order
	 */
	public function testFinallyFailed()
	{
		$orderCreateClass = get_class(Mage::getModel('eb2corder/create'));
		$privateFinallyFailedMethod = new ReflectionMethod($orderCreateClass,'_finallyFailed');
		$privateFinallyFailedMethod->setAccessible(true);
		$privateFinallyFailedMethod->invoke(new $orderCreateClass);
		$this->assertEventDispatched('eb2c_order_create_fail');
	}


	/**
	 * @test
	 * @large
	 * @loadFixture testOrderCreateScenarios.yaml
	 */
	public function testObserverCreate()
	{
		// Now mock up the event
		$mockEvent = $this->getModelMockBuilder('varien/event')
				->disableOriginalConstructor()
				->setMethods(
					array(
						'getOrder',
					)
				)
				->getMock();

		// Make the event return the mock quote:
		$mockEvent->expects($this->any())
				->method('getOrder')
				->will($this->returnValue($this->getMockSalesOrder()));

		// Now make the fake observer arg return the fake event ... confusingly, this arg is called "an event observer"
		//	thus an event observer is called with an event observer
		$mockEventObserverArgThingy = $this->getModelMockBuilder('varien/event_observer')
				->disableOriginalConstructor()
				->setMethods(
					array(
						'getEvent',
					)
				)
				->getMock();

		// Finally set up event observer to return our fakey event
		$mockEventObserverArgThingy->expects($this->any())
				->method('getEvent')
				->will($this->returnValue($mockEvent));

		// TODO: This should be a Mage::dispatchEvent based on config.xml, see also Eb2cFraud where it's done better.
		// still, it covers the code 'good enough' for now.
		$this->_creator->observerCreate($mockEventObserverArgThingy);
	}
}
