<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cTax_Test_Model_Overrides_ObserverTest extends TrueAction_Eb2cTax_Test_Base
{
	public $className = 'TrueAction_Eb2cTax_Overrides_Model_Observer';
	public $quoteItem = null;
	public $observer  = null;

	public function setUp()
	{
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
		$quoteAddressAMock = $this->getMock('Mage_Sales_Model_Quote_Address', array());
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
			->will($this->returnValue(array($quoteAddressAMock))
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
	 * Testing salesEventItemAdded observer method
	 *
	 * @test
	 * @dataProvider providerSalesEventItemAdded
	 */
	public function testSalesEventItemAdded($observer)
	{
		$this->_setupBaseUrl();
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
	 * Testing cartEventProductUpdated observer method
	 *
	 * @test
	 * @dataProvider providerCartEventProductUpdated
	 */
	public function testCartEventProductUpdated($observer)
	{
		$this->_setupBaseUrl();
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
	 * Testing salesEventItemRemoved observer method
	 *
	 * @test
	 * @dataProvider providerSalesEventItemRemoved
	 */
	public function testSalesEventItemRemoved($observer)
	{
		$this->_setupBaseUrl();
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
	 * Testing salesEventItemQtyUpdated observer method
	 *
	 * @test
	 * @dataProvider providerSalesEventItemQtyUpdated
	 */
	public function testSalesEventItemQtyUpdated($observer)
	{
		$this->_setupBaseUrl();
		$this->assertNull(
			$this->observer->salesEventItemQtyUpdated($observer)
		);
	}

	public function providerSalesEventItemQtyUpdatedWithoutQuoteItem()
	{
		$this->_setupBaseUrl();
		$quoteMock = $this->getMock('Mage_Sales_Model_Quote');
		$eventMock = $this->getMock('Varien_Event', array('getItem'));
		$eventMock->expects($this->any())
			->method('getItem')
			->will($this->returnValue($quoteMock)
			);

		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Testing salesEventItemQtyUpdated observer method - without quote item
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
	 * Testing quoteCollectTotalsBefore observer method
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
		$quoteAddressMock = $this->getMock('Mage_Sales_Model_Quote_Address', array());
		$quoteMock = $this->getMock('Mage_Sales_Model_Quote_Item', array('getQuote', 'getAllAddresses'));
		$quoteMock->expects($this->any())
			->method('getQuote')
			->will($this->returnSelf()
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($quoteAddressMock))
			);
		$eventMock = $this->getMock('Varien_Event', array('getQuote'));
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$observerMock = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * Testing quoteCollectTotalsBefore observer method - invalid quote class
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
	 * Testing _fetchTaxDutyInfo observer method
	 *
	 * @test
	 * @dataProvider providerFetchTaxDutyInfo
	 */
	public function testFetchTaxDutyInfo($quote)
	{
		$this->_setupBaseUrl();
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
	 * Testing _fetchTaxDutyInfo observer method - With exception thrown.
	 *
	 * @test
	 * @dataProvider providerFetchTaxDutyInfo
	 */
	public function testFetchTaxDutyInfoWithExceptionThrown($quote)
	{
		$this->_setupBaseUrl();
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
	 * Testing addTaxPercentToProductCollection observer method
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

	/**
	 * Test adding of address tax data to the order.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testAddAddressTaxToOrder($addressTaxes, $orderTaxes)
	{
		$address = $this->getModelMock('customer/address', array('getAppliedTaxes'));
		$addressAppliedTax = !is_null($addressTaxes) ? explode(',', $addressTaxes) : $addressTaxes;

		$address->expects($this->once())
			->method('getAppliedTaxes')
			->will($this->returnValue($addressAppliedTax));

		$order = $this->getModelMock('sales/order', array(
			'getAppliedTaxes', 'setAppliedTaxes', 'setConvertingFromQuote'
		));
		$orderAppliedTax = !is_null($orderTaxes) ? explode(',', $orderTaxes) : $orderTaxes;

		$times = is_null($addressAppliedTax) ? $this->never() : (is_null($orderAppliedTax) ? $this->once() : $this->exactly(2));

		$order->expects($times)
			->method('getAppliedTaxes')
			->will($this->returnValue($orderAppliedTax));

		$expectedTax = $this->expected('set-%s-%s', $addressTaxes, $orderTaxes)->getTaxes();
		$tax = is_null($expectedTax) ? $expectedTax : explode(',', $expectedTax);

		if (!is_null($addressTaxes)) {
			$order->expects($this->once())
				->method('setAppliedTaxes')
				->with($this->equalTo($tax))
				->will($this->returnSelf());
			$order->expects($this->once())
				->method('setConvertingFromQuote')
				->with($this->equalTo(true))
				->will($this->returnSelf());
		} else {
			$order->expects($this->never())
				->method('setAppliedTaxes');
			$order->expects($this->never())
				->method('setConvertingFromQuote');
		}

		// mock out the observer
		$event = $this->getMock('Varien_Event', array('getAddress', 'getOrder'));
		$event->expects($this->any())
			->method('getAddress')
			->will($this->returnValue($address));
		$event->expects($this->any())
			->method('getOrder')
			->will($this->returnValue($order));
		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));

		Mage::getSingleton('tax/observer')->salesEventConvertQuoteAddressToOrder($observer);
	}

	/**
	 * Following few methods are all related to testing the salesEventOrderAfterSave method
	 * Any mock objects that we don't really care about get mocked out in separate methods.
	 * Mock objects that are expected to have something happen to them, are mocked out
	 * in the actual test.
	 * This hopefully helps to delineate what matters to the test and what doesn't.
	 */

	/**
	 * Generate quote items for use in the testSalesEventOrderAfterSave test
	 * @return Mock_Mage_Sales_Model_Quote_Item[]
	 */
	protected function _orderSaveQuoteItemsMock($quoteItemIds)
	{
		$quoteItem1 = $this->getModelMock('sales/quote_item', array(
			'getId',
		));
		$quoteItem1->expects($this->any())
			->method('getId')
			->will($this->returnValue($quoteItemIds[0]));
		$quoteItem2 = $this->getModelMock('sales/quote_item', array(
			'getId',
		));
		$quoteItem2->expects($this->any())
			->method('getId')
			->will($this->returnValue($quoteItemIds[1]));
		return array($quoteItem1, $quoteItem2);
	}

	/**
	 * Generate the address mock for the testSalesEventOrderAfterSave test
	 * @param  Mock_Mage_Sales_Model_Quote_Item[] $quoteItems quote items the address has
	 * @return Mock_Mage_Sales_Model_Quote_Address
	 */
	protected function _orderSaveAddressMock($quoteItems, $addressId)
	{
		$address = $this->getModelMock('sales/quote_address', array(
			'getAllItems',
			'getId',
		));
		$address->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue($quoteItems));
		$address->expects($this->any())
			->method('getId')
			->will($this->returnValue($addressId));
		return $address;
	}

	/**
	 * Generate the quote mock for use in the testSalesEventOrderAfterSave test
	 * @param  Mock_Mage_Sales_Model_Quote_Address $address Mocked address object
	 * @return Mock_Mage_Sales_Model_Quote
	 */
	protected function _orderSaveQuoteMock($address)
	{
		$quote = $this->getModelMock('sales/quote', array(
			'getTaxesForItems',
			'getAllAddresses',
		));
		$quote->expects($this->any())
			->method('getTaxesForItems')
			->will($this->returnValue(null));
		$quote->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($address)));
		return $quote;
	}

	/**
	 * Generate the reponse item mock for the testSalesEventOrderAfterSave test
	 * @param  Mock_TrueAction_Eb2cTax_Model_Response_Quote $taxQuote
	 * @param  Mock_TrueAction_Eb2cTax_Model_Response_Quote_Discount $taxQuoteDiscount
	 * @param  Mock_Mage_Sales_Model_Quote_Item[] $quoteItems
	 * @param  Mock_Mage_Sales_Model_QuoteAddress $address
	 * @return Mock_TrueAction_Eb2cTax_Model_Response_OrderItem
	 */
	protected function _orderSaveResponseItemMock($taxQuote, $taxQuoteDiscount)
	{
		$responseItem = $this->getModelMock('eb2ctax/response_orderitem', array(
			'getTaxQuotes',
			'getTaxQuoteDiscounts',
		));
		$responseItem->expects($this->any())
			->method('getTaxQuotes')
			->will($this->returnValue(array($taxQuote)));
		$responseItem->expects($this->any())
			->method('getTaxQuoteDiscounts')
			->will($this->returnValue(array($taxQuoteDiscount)));
		return $responseItem;
	}

	protected function _orderSaveResponseMock($responseItem, $quoteItems, $address)
	{
		$response = $this->getModelMock('eb2ctax/response', array('getResponseForItem'));
		$itemResponseMap = array(
			array($quoteItems[0], $address, null),
			array($quoteItems[1], $address, $responseItem),
		);
		$response->expects($this->any())
			->method('getResponseForItem')
			->will($this->returnValueMap($itemResponseMap));
		return $response;
	}

	/**
	 * Build out a mock order for the testSalesEventOrderAfterSave test
	 * @param  Mock_Mage_Sales_Model_Quote $quote
	 * @return Mock_Mage_Sales_Model_Order
	 */
	protected function _orderSaveOrderMock($quote)
	{
		$order = $this->getModelMock('sales/order', array(
			'getConvertingFromQuote',
			'getAppliedTaxIsSaved',
			'getQuote',
			'getAppliedTaxes',
			'getId',
			'getItemByQuoteItemId',
			'setAppliedTaxIsSaved',
		));
		$order->expects($this->any())
			->method('getConvertingFromQuote')
			->will($this->returnValue(true));
		$order->expects($this->any())
			->method('getAppliedTaxIsSaved')
			->will($this->returnValue(false));
		$order->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		$order->expects($this->any())
			->method('getAppliedTaxes')
			->will($this->returnValue(array()));
		$order->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$order->expects($this->any())
			->method('getItemByQuoteItemId')
			->will($this->returnValue(null));
		$order->expects($this->any())
			->method('setAppliedTaxIsSaved')
			->with($this->equalTo(true))
			->will($this->returnSelf());
		return $order;
	}

	/**
	 * Mock out the observer and event objects to pass to the observer method
	 * @param  Mock_Mage_Sales_Model_Order $order Order object to return from the event object
	 * @return Mock_Varien_Event_Obeserver
	 */
	protected function _orderSaveObserverMock($order)
	{
		$event = $this->getMock('Varien_Event', array('getOrder'));
		$event->expects($this->any())
			->method('getOrder')
			->will($this->returnValue($order));
		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));
		return $observer;
	}

	/**
	 * Replce the checkout/session with a mock.
	 * @param  Mock_TrueAction_Eb2cTax_Model_Response $response The response object stored in the session.
	 * @return Mock_Mage_Checkout_Model_Session
	 */
	protected function _orderSaveSetResponseInSession($response)
	{
		$checkout = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('hasEb2cTaxResponse', 'getEb2cTaxResponse'))
			->getMock();
		$checkout->expects($this->any())
			->method('hasEb2cTaxResponse')
			->will($this->returnValue(true));
		$checkout->expects($this->any())
			->method('getEb2cTaxResponse')
			->will($this->returnValue($response));
		$this->replaceByMock('singleton', 'checkout/session', $checkout);
		return $checkout;
	}

	/**
	 * Mock out a tax calculation model which will return the given eb2c tax response.
	 * @param  Mock_TrueAction_Eb2cTax_Model_Response $response Mock response object for the calculation model to return
	 * @return Mock_TrueAction_Eb2cTax_Overrides_Model_Tax_Calculation
	 */
	protected function _orderSaveCalculatorMock($response)
	{
		$calc = $this->getModelMock('tax/calculation', array('getTaxResponse'));
		$calc->expects($this->any())
			->method('getTaxResponse')
			->will($this->returnValue($response));
		return $calc;
	}

	/**
	 * Mock out a tax helper which will return the given calculation model
	 * @param  Mock_TrueAction_Eb2cTax_Overrides_Model_Calculation $calculator Mock calculation model
	 * @return Mock_TrueAction_Eb2cTax_Overrides_Helper_Data
	 */
	protected function _orderSaveHelperMock($calculator)
	{
		$helper = $this->getHelperMock('tax/data', array('getCalculator'));
		$helper->expects($this->any())
			->method('getCalculator')
			->will($this->returnValue($calculator));
		return $helper;
	}

	/**
	 * Test the observer that gets triggered when an order is saved.
	 */
	public function testSalesEventOrderAfterSave()
	{
		// remove any dirty tax helpers Magento already has sitting around
		Mage::unregister('_helper/tax');
		$quoteItemIds = array(1,2);
		$addressId = 2;
		$quoteItems = $this->_orderSaveQuoteItemsMock($quoteItemIds);
		$address = $this->_orderSaveAddressMock($quoteItems, $addressId);
		$quote = $this->_orderSaveQuoteMock($address);

		// need to ensure the tax quote has the quote item id and quote address id
		// set to it and gets saved
		$taxQuote = $this->getModelMock('eb2ctax/response_quote', array(
			'setQuoteItemId',
			'setQuoteAddressId',
			'save',
		));
		$taxQuote->expects($this->once())
			->method('setQuoteItemId')
			->with($this->equalTo($quoteItemIds[1]))
			->will($this->returnSelf());
		$taxQuote->expects($this->once())
			->method('setQuoteAddressId')
			->with($this->equalTo($addressId))
			->will($this->returnSelf());
		$taxQuote->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		// need to ensure the tax quote discount has the quote item id
		// and quote address id set to it and gets saved
		$taxQuoteDiscount = $this->getModelMock('eb2ctax/response_quote', array(
			'setQuoteItemId',
			'setQuoteAddressId',
			'save',
		));
		$taxQuoteDiscount->expects($this->once())
			->method('setQuoteItemId')
			->with($this->equalTo($quoteItemIds[1]))
			->will($this->returnSelf());
		$taxQuoteDiscount->expects($this->once())
			->method('setQuoteAddressId')
			->with($this->equalTo($addressId))
			->will($this->returnSelf());
		$taxQuoteDiscount->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$responseItem = $this->_orderSaveResponseItemMock($taxQuote, $taxQuoteDiscount);
		$response = $this->_orderSaveResponseMock($responseItem, $quoteItems, $address);
		$response->hasThisBeenReplacedByMyMock = "yes this has been";
		$calculator = $this->_orderSaveCalculatorMock($response);
		$helper = $this->_orderSaveHelperMock($calculator);
		$this->replaceByMock('helper', 'tax', $helper);
		$order = $this->_orderSaveOrderMock($quote);
		$observer = $this->_orderSaveObserverMock($order);

		Mage::getSingleton('tax/observer')->salesEventOrderAfterSave($observer);

		Mage::unregister('_helper/tax');
	}

}
