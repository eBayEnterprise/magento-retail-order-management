<?php
/**
 * Test Suite for the Order_Create
 */
abstract class TrueAction_Eb2cOrder_Test_Abstract extends EcomDev_PHPUnit_Test_Case
{
	private function _getFullMocker($classAlias, $mockedMethodSet)
	{
		$justMethodNames = array();
		foreach( $mockedMethodSet as $method => $returnValue ) {
			$justMethodNames[] = $method;
		}

		$mock = $this->_getMock($classAlias, $justMethodNames);
		reset($mockedMethodSet);
		foreach($mockedMethodSet as $method => $returnValue ) {
			$this->_setMethod($mock, $method, $returnValue);
		}
		return $mock;
	}

	/**
	 * A wrapper to getModelMockBuilder to make mocks the way we really want them.
	 */
	private function _getMock($classAlias, array $mockedMethodNames)
	{
		return $this->getModelMockBuilder($classAlias)
			->disableOriginalConstructor()
			->setMethods($mockedMethodNames)
			->getMock();
	}

	/**
	 * A setter for methods and their return values
	 */
	private function _setMethod( $mock, $method, $returnValue )
	{
		$mock
			->expects($this->any())
			->method($method)
			->will($this->returnValue($returnValue));
	}

	// $this->_config = $this->_helper->getConfig();
	// $this->buildRequest($event->getEvent()->getOrder()->getIncrementId()); Tested by observer
	//$consts = $this->_helper->getConstHelper();
	//$uri = $this->_helper->getOperationUri($consts::CREATE_OPERATION);
	//if( $this->_helper->getConfig()->developerMode )
	/*
	$uri = $this->_helper->getConfig()->developerCreateUri;
	$response = $this->_helper->getApiModel()
		->setTimeout($this->_helper->getConfig()->serviceOrderTimeout)
	$this->_domResponse = $this->_helper->getDomDocument();
	$status = $this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue;
	Mage::throwException('Send Web Service Request Failed: ' . $e->getMessage());
	Mage::app()->getStore($this->_o->getStoreId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . // TROUBLE
	 */

	protected function getMockSalesOrder()
	{
		$mockOrderMethodNames = array(
			'getId',
			'getIncrementId',
			'getCreatedAt',
			'getOrderCurrencyCode',
			'getEntityId',
			'getGrandTotal',
			'getCustomerId',
			'getCustomerPrefix',
			'getCustomerLastname',
			'getCustomerSuffix',
			'getCustomerMiddlename',
			'getCustomerFirstname',
			'getCustomerGender',
			'getCustomerDob',
			'getCustomerEmail',
			'getCustomerTaxvat',
			'getBillingAddress',
			'getShippingAddress',
			'getGrandTotal',
			'getEb2cHostName',
			'getEb2cIpAddress',
			'getEb2cSessionId',
			'getEb2cUserAgent',
			'getEb2cJavascriptData',
			'getEb2cReferer',
		);

		$this->_order
			->expects($this->any())
			->method('getId')
			->will($this->returnValue('666'));

		$this->_order
			->expects($this->any())
			->method('getIncrementId')
			->will($this->returnValue('8675309'));

		$this->_order
			->expects($this->any())
			->method('getCreatedAt')
			->will($this->returnValue('2013-08-09'));

		$this->_order
			->expects($this->any())
			->method('getOrderCurrencyCode')
			->will($this->returnValue('USD'));

		$this->_order
			->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue('711'));

		$this->_order
			->expects($this->any())
			->method('getGrandTotal')
			->will($this->returnValue('1776'));

		$this->_order
			->expects($this->any())
			->method('getCustomerId')
			->will($this->returnValue('77'));

		$this->_order
			->expects($this->any())
			->method('getCustomerPrefix')
			->will($this->returnValue('Dr.'));

		$this->_order
			->expects($this->any())
			->method('getCustomerLastname')
			->will($this->returnValue('Mangrove'));

		$this->_order
			->expects($this->any())
			->method('getCustomerSuffix')
			->will($this->returnValue('Sr'));

		$this->_order
			->expects($this->any())
			->method('getCustomerMiddlename')
			->will($this->returnValue('Warbler'));

		$this->_order
			->expects($this->any())
			->method('getCustomerFirstname')
			->will($this->returnValue('Throat'));

		$this->_order
			->expects($this->any())
			->method('getCustomerGender')
			->will($this->returnValue('M'));

		$this->_order
			->expects($this->any())
			->method('getCustomerDob')
			->will($this->returnValue('1977-08-16'));

		$this->_order
			->expects($this->any())
			->method('getCustomerEmail')
			->will($this->returnValue('westm@trueaction.com'));

		$this->_order
			->expects($this->any())
			->method('getCustomerTaxvat')
			->will($this->returnValue('1'));

		$this->_order
			->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue(true));

		$this->_order
			->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue(true));

		$this->_order
			->expects($this->any())
			->method('getGrandTotal')
			->will($this->returnValue('1776'));

		$this->_order
			->expects($this->any())
			->method('getEb2cHostName')
			->will($this->returnValue('mwest-VirtualBox'));

		$this->_order
			->expects($this->any())
			->method('getEb2cIpAddress')
			->will($this->returnValue('208.247.73.130'));

		$this->_order
			->expects($this->any())
			->method('getEb2cSessionId')
			->will($this->returnValue('5nqm2sczfncsggzdqylueb2h'));

		$this->_order
			->expects($this->any())
			->method('getEb2cUserAgent')
			->will($this->returnValue('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95 Safari/537.36'));

		$this->_order
			->expects($this->any())
			->method('getEb2cJavascriptData')
			->will($this->returnValue('TF1;015;;;;;;;;;;;;;;;;;;;;;;Mozilla;Netscape;5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20Safari/536.30.1;20030107;undefined;true;;true;MacIntel;undefined;Mozilla/5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20Safari/536.30.1;en-us;iso-8859-1;;undefined;undefined;undefined;undefined;true;true;1376075038705;-5;June%207%2C%202005%209%3A33%3A44%20PM%20EDT;1920;1080;;11.8;7.7.1;;;;2;300;240;August%209%2C%202013%203%3A03%3A58%20PM%20EDT;24;1920;1054;0;22;;;;;;Shockwave%20Flash%7CShockwave%20Flash%2011.8%20r800;;;;QuickTime%20Plug-in%207.7.1%7CThe%20QuickTime%20Plugin%20allows%20you%20to%20view%20a%20wide%20variety%20of%20multimedia%20content%20in%20web%20pages.%20For%20more%20information%2C%20visit%20the%20%3CA%20HREF%3Dhttp%3A//www.apple.com/quicktime%3EQuickTime%3C/A%3E%20Web%20site.;;;;;Silverlight%20Plug-In%7C5.1.20125.0;;;;18;'));

		$this->_order
			->expects($this->any())
			->method('getEb2cReferer')
			->will($this->returnValue('https://www.google.com/'));

		/*
		These need to return arrays:
		getAllItems() // Mage_Sales_Order_Item mock needed
		getAllPayments()
		 */
	}

	/** 
 	 * Let us mock a Mage_Sales_Model_Order_Payment
	 *
	 */
	protected function getMockSalesOrderPayment()
	{
		$mockPaymentMethodNames = array(
			'getMethod',
			'getCcStatus',
			'getCcApproval',
			'getCcCidStatus',
			'getCcAvsStatus',
			'getAmountAuthorized',
			'getCcExpYear',
			'getCcExpMonth',
		);

		$this->_payment
			->expects($this->any())
			->method('getMethod')
			->will($this->returnValue('eb2c-faker'));

		$this->_payment
			->expects($this->any())
			->method('getCcStatus')
			->will($this->returnValue('true'));

		$this->_payment
			->expects($this->any())
			->method('getCcApproval')
			->will($this->returnValue('APP123456'));

		$this->_payment
			->expects($this->any())
			->method('getCcCidStatus')
			->will($this->returnValue('Y'));

		$this->_payment
			->expects($this->any())
			->method('getCcAvsStatus')
			->will($this->returnValue('X'));

		$this->_payment
			->expects($this->any())
			->method('getAmountAuthorized')
			->will($this->returnValue('1776'));

		$this->_payment
			->expects($this->any())
			->method('getCcExpYear')
			->will($this->returnValue('07'));

		$this->_payment
			->expects($this->any())
			->method('getCcExpMonth')
			->will($this->returnValue('23'));
	}

	protected function getMockSalesOrderItem()
	{
		$mockItemMethods = array (
			'getId',
			'getDiscountAmount',
			'getEb2cDeliveryWindowFrom',
			'getEb2cDeliveryWindowTo',
			'getEb2cMessageType',
			'getEb2cReservationId',
			'getEb2cShippingWindowFrom',
			'getEb2cShippingWindowTo',
			'getName',
			'getPrice',
			'getQtyOrdered',
			'getSku',
			'getTaxAmount',
			'getTaxPercent',
		);

		$this->_item
			->expects($this->any())
			->method('getId')
			->will($this->returnValue('FreshPrince'));

		$this->_item
			->expects($this->any())
			->method('getDiscountAmount')
			->will($this->returnValue('0'));

		$this->_item
			->expects($this->any())
			->method('getEb2cDeliveryWindowFrom')
			->will($this->returnValue('2013-08-09'));

		$this->_item
			->expects($this->any())
			->method('getEb2cDeliveryWindowTo')
			->will($this->returnValue('2013-08-13'));

		$this->_item
			->expects($this->any())
			->method('getEb2cMessageType')
			->will($this->returnValue('Whatever The Message Type is'));

		$this->_item
			->expects($this->any())
			->method('getEb2cReservationId')
			->will($this->returnValue('Eb2C Reservation Id'));

		$this->_item
			->expects($this->any())
			->method('getEb2cShippingWindowFrom')
			->will($this->returnValue('2013-08-11'));

		$this->_item
			->expects($this->any())
			->method('getEb2cShippingWindowTo')
			->will($this->returnValue('2013-08-14'));

		$this->_item
			->expects($this->any())
			->method('getName')
			->will($this->returnValue('Some Item Name'));

		$this->_item
			->expects($this->any())
			->method('getPrice')
			->will($this->returnValue('1776'));

		$this->_item
			->expects($this->any())
			->method('getQtyOrdered')
			->will($this->returnValue('1'));

		$this->_item
			->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKUZappa'));

		$this->_item
			->expects($this->any())
			->method('getTaxAmount')
			->will($this->returnValue('0'));

		$this->_item
			->expects($this->any())
			->method('getTaxPercent')
			->will($this->returnValue('0'));
	}

	protected function getMockSalesOrderAddress()
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
	}

	/**
	 * @test
	 * Test factory method returns proper class
	 */
	public function testFactoryMethod()
	{
		$testFactoryCreator = Mage::getModel('eb2corder/create');
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Create', $testFactoryCreator );

		/*
		$x = $this->getMockSalesOrderAddress();
		foreach( get_class_methods($x) as $method ) {
			if( !strncmp($method, 'get', 3) ) {
				$foo = $x->$method();
				echo "$method yields ";
				if( is_array($foo) ) {
					print_r($foo);
				}
				else {
					echo $foo;
				}
				echo "\n";
			}
		}
		 */
	}


	/**
	 * @test
	 * @large
	 * @loadFixture testOrderCreateScenarios.yaml
	 * Get a collection; try creating order for last one
	 */
	public function testOrderCreateFromCollection()
	{
		$status = null;
		$testId = Mage::getModel('sales/order')->getCollection()->getLastItem()->getIncrementId();
		$this->_creator->buildRequest($testId);
		$status = $this->_creator->sendRequest();
		$this->assertSame($status, true);
	}

	/**
	 * @test
	 * @loadFixture testOrderCreateScenarios.yaml
	 * One known order is create by increment Id value
	 */
	public function testOrderCreateOneOff()
	{
		$status = null;
		$incrementId = '100000002';
		$this->_creator->buildRequest($incrementId);
		$status = $this->_creator->sendRequest();
		$this->assertSame($status, true);
	}

	/**
	 * @test
	 * @expectedException Mage_Core_Exception
	 * @todo: Is that the best exception we can have? "not found" is even better.
	 * Don't want to find this order, handle exception correctly.
	 */
	public function testOrderNotFound()
	{
		$status = null;
		$incrementId = 'NO_CHANCE';
		$this->_creator->buildRequest($incrementId);
	}

	/**
	 * @test
	 * @loadFixture
	 * @expectedException Mage_Core_Exception
	 * @todo: Is that the best exception we can have? "some kind of http error" is even better, I think
	 * This fixture was setup to fail with a syntactically correct URL that couldn't really answer us in any sensible way.
	 */
	public function testWithEb2cPaymentsEnabled()
	{
		$status = null;

		$this->_creator = Mage::getModel('eb2corder/create');
		$incrementId = '100000003';
		$this->_creator->buildRequest($incrementId);
		$status = $this->_creator->sendRequest();
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
	 * @loadFixture testOrderCreateScenarios.yaml
	 */
	public function testObserverCreate()
	{
		$dummyOrder = Mage::getModel('sales/order')->getCollection()->getLastItem();
		$this->_creator = Mage::getModel('eb2corder/create');

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
				->will($this->returnValue($dummyOrder));

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

		// TODO: This should be a Mage::dispatchEvent based on config.xml, sell also Eb2cFraud where it's done better.
		// still, it covers the code 'good enough' for now.
		$this->_creator->observerCreate($mockEventObserverArgThingy);
	}


	private function _makeMockOrder()
	{
		// Now mock up the event
		$this->_mockOrder = $this->getModelMockBuilder('varien/event')
				->disableOriginalConstructor()
				->setMethods(
					array(
						'getOrder',
					)
				)
				->getMock();
	}
}
