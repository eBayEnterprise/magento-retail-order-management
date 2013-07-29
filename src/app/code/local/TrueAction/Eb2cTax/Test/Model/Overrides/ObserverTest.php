<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cTax_Test_Model_Overrides_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
	public $className = 'TrueAction_Eb2cTax_Overrides_Model_Observer';
	public $quoteItem = null;
	public $observer  = null;

	public function setUp()
	{
		parent::setUp();
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$response = $this->getModelMock('eb2ctax/response');
		$this->responseMock = $response;
		$helper = $this->getHelperMock('tax/data', array('sendRequest'));
		$this->replaceByMock('helper', 'tax', $helper);
		$helper->expects($this->any())
			->method('sendRequest')
			->will($this->returnValue($response));
		$quoteItem = $this->getMock('Varien_Object');
		$this->quoteItem = $quoteItem;
		$listeners = array(
			'salesEventItemAdded',
			'cartEventProductUpdated',
			'salesEventItemRemoved',
			'salesEventItemQtyUpdated',
			'quoteCollectTotalsBefore'
		);
		$this->observerMock = $this->getMock(
			'TrueAction_Eb2cTax_Overrides_Model_Observer',
			$listeners
		);
		$this->replaceByMock('model', 'tax/observer', $this->observerMock);
		$this->observer = new TrueAction_Eb2cTax_Overrides_Model_Observer();
		$this->fetchTaxDutyInfo = new ReflectionMethod($this->observer, '_fetchTaxDutyInfo');
		$this->fetchTaxDutyInfo->setAccessible(true);
	}

	public function getMockQuote()
	{
		$quoteAMock = $this->getMock('Mage_Sales_Model_Quote', array('collectTotals', 'save', 'deleteItem'));
		$quoteAMock->expects($this->any())
			->method('collectTotals')
			->will($this->returnValue(1)
			);
		$quoteAMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteAMock->expects($this->any())
			->method('deleteItem')
			->will($this->returnValue(1)
			);

		$itemMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQty', 'getProductId', 'getSku', 'getQuote'));
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getProductId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteAMock)
			);

		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getItem', 'getAllAddresses', 'getQuote'));
		$quoteMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock)
			);
		$quoteMock->expects($this->any())
			->method('getItem')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($quoteMock))
			);
		return $quoteMock;
	}

	public function providerSalesEventItemAdded()
	{
		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($this->getMockQuote()));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Tesing salesEventItemAdded observer method
	 *
	 * @test
	 * @dataProvider providerSalesEventItemAdded
	 */
	public function testSalesEventItemAdded($observer)
	{
		$this->assertNull(
			$this->observer->salesEventItemAdded($observer)
		);
	}

	public function providerCartEventProductUpdated()
	{
		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($this->getMockQuote()));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Tesing cartEventProductUpdated observer method
	 *
	 * @test
	 * @dataProvider providerCartEventProductUpdated
	 */
	public function testCartEventProductUpdated($observer)
	{
		$this->assertNull(
			$this->observer->cartEventProductUpdated($observer)
		);
	}

	public function providerSalesEventItemRemoved()
	{
		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($this->getMockQuote()));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Tesing salesEventItemRemoved observer method
	 *
	 * @test
	 * @dataProvider providerSalesEventItemRemoved
	 */
	public function testSalesEventItemRemoved($observer)
	{
		$this->assertNull(
			$this->observer->salesEventItemRemoved($observer)
		);
	}

	public function providerSalesEventItemQtyUpdated()
	{
		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($this->getMockQuote()));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Tesing salesEventItemQtyUpdated observer method
	 *
	 * @test
	 * @dataProvider providerSalesEventItemQtyUpdated
	 */
	public function testSalesEventItemQtyUpdated($observer)
	{
		$this->assertNull(
			$this->observer->salesEventItemQtyUpdated($observer)
		);
	}

	public function providerSalesEventItemQtyUpdatedWithoutQuoteItem()
	{
		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('getItem'));
		$quoteMock->expects($this->any())
			->method('getItem')
			->will($this->returnValue($quoteMock)
			);

		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($quoteMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Tesing salesEventItemQtyUpdated observer method - without quote item
	 *
	 * @test
	 * @dataProvider providerSalesEventItemQtyUpdatedWithoutQuoteItem
	 */
	public function testSalesEventItemQtyUpdatedWithoutQuoteItem($observer)
	{
		$this->assertNull(
			$this->observer->salesEventItemQtyUpdated($observer)
		);
	}

	public function providerQuoteCollectTotalsBefore()
	{
		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($this->getMockQuote()));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Tesing quoteCollectTotalsBefore observer method
	 *
	 * @test
	 * @dataProvider providerQuoteCollectTotalsBefore
	 */
	public function testQuoteCollectTotalsBefore($observer)
	{
		$this->assertNotNull(
			$this->observer->quoteCollectTotalsBefore($observer)
		);
	}

	public function providerQuoteCollectTotalsBeforeWithInvalidQuoteObject()
	{
		$quoteMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQuote', 'getAllAddresses'));
		$quoteMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock)
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($quoteMock))
			);

		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($quoteMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Tesing quoteCollectTotalsBefore observer method - invalid quote class
	 *
	 * @test
	 * @dataProvider providerQuoteCollectTotalsBeforeWithInvalidQuoteObject
	 */
	public function testQuoteCollectTotalsBeforeWithInvalidQuoteObject($observer)
	{
		$this->assertNotNull(
			$this->observer->quoteCollectTotalsBefore($observer)
		);
	}

	public function providerFetchTaxDutyInfo()
	{
		return array(
			array($this->getMockQuote())
		);
	}

	/**
	 * Tesing _fetchTaxDutyInfo observer method
	 *
	 * @test
	 * @dataProvider providerFetchTaxDutyInfo
	 */
	public function testFetchTaxDutyInfo($quote)
	{
		$responseMock = $this->getModelMock('eb2ctax/response', array());
		$requestMock = $this->getModelMock('eb2ctax/request', array('isValid', 'getQuoteCurrencyCode'));
		$requestMock->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$requestMock->expects($this->any())
			->method('getQuoteCurrencyCode')
			->will($this->returnValue('USD'));

		$calculatorMock = $this->getModelMock('tax/calculation', array('getTaxRequest', 'setTaxResponse'));
		$calculatorMock->expects($this->any())
			->method('getTaxRequest')
			->will($this->returnValue($requestMock));
		$calculatorMock->expects($this->any())
			->method('setTaxResponse')
			->will($this->returnValue(true));

		$taxMock = $this->getMock('TrueAction_Eb2cTax_Overrides_Helper_Data', array('getCalculator', 'sendRequest'));
		$taxMock->expects($this->any())
			->method('getCalculator')
			->will($this->returnValue($calculatorMock));
		$taxMock->expects($this->any())
			->method('sendRequest')
			->will($this->returnValue($responseMock));

		$observerReflector = new ReflectionObject($this->observer);
		$taxProperty = $observerReflector->getProperty('_tax');
		$taxProperty->setAccessible(true);
		$taxProperty->setValue($this->observer, $taxMock);

		$fetchTaxDutyInfoMethod = $observerReflector->getMethod('_fetchTaxDutyInfo');
		$fetchTaxDutyInfoMethod->setAccessible(true);

		$this->assertNull(
			$fetchTaxDutyInfoMethod->invoke($this->observer, $quote)
		);
	}

	/**
	 * Tesing _fetchTaxDutyInfo observer method - With exception thrown.
	 *
	 * @test
	 * @dataProvider providerFetchTaxDutyInfo
	 */
	public function testFetchTaxDutyInfoWithExceptionThrown($quote)
	{
		$responseMock = $this->getModelMock('eb2ctax/response', array());
		$requestMock = $this->getModelMock('eb2ctax/request', array('isValid', 'getQuoteCurrencyCode'));
		$requestMock->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$requestMock->expects($this->any())
			->method('getQuoteCurrencyCode')
			->will($this->returnValue('USD'));

		$calculatorMock = $this->getModelMock('tax/calculation', array('getTaxRequest', 'setTaxResponse'));
		$calculatorMock->expects($this->any())
			->method('getTaxRequest')
			->will($this->returnValue($requestMock));
		$calculatorMock->expects($this->any())
			->method('setTaxResponse')
			->will($this->returnValue(true));

		$taxMock = $this->getMock('TrueAction_Eb2cTax_Overrides_Helper_Data', array('getCalculator', 'sendRequest'));
		$taxMock->expects($this->any())
			->method('getCalculator')
			->will($this->returnValue($calculatorMock));
		$taxMock->expects($this->any())
			->method('sendRequest')
			->will($this->throwException(new Exception('Unit Test Exception thrown')));

		$observerReflector = new ReflectionObject($this->observer);
		$taxProperty = $observerReflector->getProperty('_tax');
		$taxProperty->setAccessible(true);
		$taxProperty->setValue($this->observer, $taxMock);

		$fetchTaxDutyInfoMethod = $observerReflector->getMethod('_fetchTaxDutyInfo');
		$fetchTaxDutyInfoMethod->setAccessible(true);

		$this->assertNull(
			$fetchTaxDutyInfoMethod->invoke($this->observer, $quote)
		);
	}

	public function providerFetchTaxDutyInfoEmptyQuote()
	{
		$quotMock = $this->getModelMock('sales/quote', array('getId'));
		$quotMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(null));
		return array(
			array($quotMock)
		);
	}

	public function providerAddTaxPercentToProductCollection()
	{
		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($this->getMockQuote()));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Tesing addTaxPercentToProductCollection observer method
	 *
	 * @test
	 * @dataProvider providerAddTaxPercentToProductCollection
	 */
	public function testAddTaxPercentToProductCollection($observer)
	{
		$this->assertNotNull(
			$this->observer->addTaxPercentToProductCollection($observer)
		);
	}

	public function providerSalesRuleEventItemProcessed()
	{
		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getId')
		);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$observerMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent', 'getItem')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnSelf());
		$observerMock->expects($this->any())
			->method('getItem')
			->will($this->returnValue($itemMock));

		$itemFakeMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getId')
		);
		$itemFakeMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$observerFakeMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent', 'getItem')
		);
		$observerFakeMock->expects($this->any())
			->method('getEvent')
			->will($this->returnSelf());
		$observerFakeMock->expects($this->any())
			->method('getItem')
			->will($this->returnValue($itemFakeMock));

		return array(
			array($observerMock),
			array($observerFakeMock)
		);
	}

	/**
	 * Tesing salesRuleEventItemProcessed observer method
	 *
	 * @test
	 * @dataProvider providerSalesRuleEventItemProcessed
	 */
	public function testSalesRuleEventItemProcessed($observer)
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cTax_Overrides_Model_Observer',
			$this->observer->salesRuleEventItemProcessed($observer)
		);
	}
}
