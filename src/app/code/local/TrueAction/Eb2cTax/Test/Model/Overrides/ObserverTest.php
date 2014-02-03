<?php
class TrueAction_Eb2cTax_Test_Model_Overrides_ObserverTest extends TrueAction_Eb2cCore_Test_Base
{
	public $className = 'TrueAction_Eb2cTax_Overrides_Model_Observer';

	public function _mockEventObserver($address)
	{
		$quote = $this->getModelMock('sales/quote', array());
		$event = $this->getMock('Varien_Event', array('getQuote', 'getAddress'));

		$event->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		$event->expects($this->any())
			->method('getAddress')
			->will($this->returnValue($address));
		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));
		return $observer;
	}

	protected function _mockRequest($isValid)
	{
		$request = $this->getModelMockBuilder('eb2ctax/request')
			->disableOriginalConstructor()
			->setMethods(array('isValid'))
			->getMock();
		$request->expects($this->any())
			->method('isValid')
			->will($this->returnValue($isValid));
		return $request;
	}

	protected function _mockResponse($isValid)
	{
		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->setMethods(array('isValid'))
			->getMock();
		$response->expects($this->any())
			->method('isValid')
			->will($this->returnValue($isValid));
		return $response;
	}
	/**
	 * Test adding of address tax data to the order.
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testAddAddressTaxToOrder($addressTaxes, $orderTaxes)
	{
		$addressMock = $this->getModelMock('sales/order_address', array('getAppliedTaxes'));
		$addressMock->expects($this->any())
			->method('getAppliedTaxes')
			->will($this->returnValue($addressTaxes));

		$expected = $this->expected(
			'set-%s-%s',
			$addressTaxes ? implode(',', $addressTaxes) : '',
			$orderTaxes ? implode(',', $orderTaxes) : ''
		);
		$orderMock = $this->getModelMock(
			'sales/order',
			array('getAppliedTaxes', 'setAppliedTaxes', 'setConvertingFromQuote')
		);
		$orderMock->expects($this->any())
			->method('getAppliedTaxes')
			->will($this->returnValue($orderTaxes));
		if ($addressTaxes) {
			$orderMock->expects($this->once())
				->method('setAppliedTaxes')
				->with($this->identicalTo($expected->getTaxes()))
				->will($this->returnSelf());
			$orderMock->expects($this->once())
				->method('setConvertingFromQuote')
				->with($this->isTrue())
				->will($this->returnSelf());
		} else {
			$orderMock->expects($this->never())
				->method('setAppliedTaxes');
			$orderMock->expects($this->never())
				->method('setConvertingFromQuote');
		}
		$event = $this->getMock('Varien_Event', array('getAddress', 'getOrder'));
		$event->expects($this->any())->method('getAddress')->will($this->returnValue($addressMock));
		$event->expects($this->any())->method('getOrder')->will($this->returnValue($orderMock));
		$eventObserver = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$eventObserver->expects($this->any())->method('getEvent')->will($this->returnValue($event));

		$observer = Mage::getModel('tax/observer');
		$observer->salesEventConvertQuoteAddressToOrder($eventObserver);
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
		$quoteItemA = $this->getModelMock('sales/quote_item', array(
			'getId',
		));
		$quoteItemA->expects($this->any())
			->method('getId')
			->will($this->returnValue($quoteItemIds[0]));
		$quoteItemB = $this->getModelMock('sales/quote_item', array(
			'getId',
		));
		$quoteItemB->expects($this->any())
			->method('getId')
			->will($this->returnValue($quoteItemIds[1]));
		return array($quoteItemA, $quoteItemB);
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
		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->setMethods(array('getResponseForItem', 'storeResponseData'))
			->getMock();
		$itemResponseMap = array(
			array($quoteItems[0], $address, null),
			array($quoteItems[1], $address, $responseItem),
		);
		$response->expects($this->any())
			->method('getResponseForItem')
			->will($this->returnValueMap($itemResponseMap));
		$response->expects($this->once())
			->method('storeResponseData')
			->will($this->returnSelf());
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
		$calc = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('getTaxResponse'))
			->getMock();
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
		$response->hasThisBeenReplacedByMyMock = 'yes this has been';
		$calculator = $this->_orderSaveCalculatorMock($response);
		$helper = $this->_orderSaveHelperMock($calculator);
		$this->replaceByMock('helper', 'tax', $helper);
		$order = $this->_orderSaveOrderMock($quote);
		$observer = $this->_orderSaveObserverMock($order);

		Mage::getSingleton('tax/observer')->salesEventOrderAfterSave($observer);
	}
	/**
	 * Test cleaning up any session flags related to tax. As all of this is handled
	 * by the helper, basically just means calling the helper method.
	 * @test
	 */
	public function testCleanupTaxRequestFlags()
	{
		$helper = $this->getHelperMock('eb2ctax/data', array('cleanupSessionFlags'));
		$this->replaceByMock('helper', 'eb2ctax', $helper);
		$helper->expects($this->once())->method('cleanupSessionFlags')->will($this->returnSelf());
		$observer = Mage::getModel('tax/observer');
		$this->assertSame($observer, $observer->cleanupTaxRequestFlags());
	}
	/**
	 * verify calculation trigger is set and response is attached to the calculator
	 * @test
	 */
	public function testHandleResponse()
	{
		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->setMethods(array('isValid'))
			->getMock();
		$response->expects($this->once())
			->method('isValid')
			->will($this->returnValue(true));

		$calculator = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('setTaxResponse'))
			->getMock();
		$calculator->expects($this->once())
			->method('setTaxResponse')
			->with($this->identicalTo($response))
			->will($this->returnSelf());

		$helper = $this->getHelperMock('tax/data');
		$helper->expects($this->any())
			->method('getCalculator')
			->will($this->returnValue($calculator));
		$this->replaceByMock('helper', 'tax', $helper);

		$observer = Mage::getModel('tax/observer');
		$this->assertSame(
			$observer,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$observer,
				'_handleResponse',
				array($response)
			)
		);
	}
	/**
	 * verify failTaxRequest is called with invalid response and no calculation is triggered.
	 * @test
	 */
	public function testHandleResponseInvalidResponse()
	{
		$response = $this->_mockResponse(false);

		$calculator = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('setCalculationTrigger'))
			->getMock();
		$calculator->expects($this->never())
			->method('setCalculationTrigger');

		$helper = $this->getHelperMock('tax/data', array('getCalculator'));
		$helper->expects($this->any())
			->method('getCalculator')
			->will($this->returnValue($calculator));
		$this->replaceByMock('helper', 'tax', $helper);

		$eb2cTaxHelper = $this->getHelperMock('eb2ctax/data', array('failTaxRequest'));
		$eb2cTaxHelper->expects($this->once())
			->method('failTaxRequest')
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'eb2ctax', $eb2cTaxHelper);

		$observer = Mage::getModel('tax/observer');
		$this->assertSame(
			$observer,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$observer,
				'_handleResponse',
				array($response)
			)
		);
		// clean up the prepared data
		unset($this->_testHandleResponses);
	}
	/**
	 * verify the response is passed to _handleResponse
	 * @test
	 */
	public function testDoRequest()
	{
		$request = $this->_mockRequest(true);
		$response = $this->_mockResponse(true);
		$eb2cTaxHelper = $this->getHelperMock(
			'eb2ctax/data',
			array('sendRequest', 'failTaxRequest')
		);
		$this->replaceByMock('helper', 'eb2ctax', $eb2cTaxHelper);

		$eb2cTaxHelper->expects($this->once())
			->method('sendRequest')
			->with($this->identicalTo($request))
			->will($this->returnValue($response));
		$eb2cTaxHelper->expects($this->never())
			->method('failTaxRequest');

		$observer = $this->getModelMock('tax/observer', array('_handleResponse'));
		$observer->expects($this->once())
			->method('_handleResponse')
			->with($this->identicalTo($response))
			->will($this->returnSelf());
		$this->assertSame(
			$observer,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$observer,
				'_doRequest',
				array($request)
			)
		);
	}
	/**
	 * verify exception will prevent the call to _handleResponse
	 * verify exception will trigger a call to failTaxRequest
	 * @test
	 */
	public function testDoRequestException()
	{
		$request = $this->_mockRequest(true);
		$response = $this->_mockResponse(true);
		$eb2cTaxHelper = $this->getHelperMock(
			'eb2ctax/data',
			array('sendRequest', 'failTaxRequest')
		);
		$this->replaceByMock('helper', 'eb2ctax', $eb2cTaxHelper);

		$eb2cTaxHelper->expects($this->once())
			->method('sendRequest')
			->with($this->identicalTo($request))
			->will($this->throwException(new Mage_Core_Exception('some error')));
		$eb2cTaxHelper->expects($this->once())
			->method('failTaxRequest')
			->will($this->returnSelf());

		$observer = $this->getModelMock('tax/observer', array('_handleResponse'));
		$observer->expects($this->never())
			->method('_handleResponse');
		$this->assertSame(
			$observer,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$observer,
				'_doRequest',
				array($request)
			)
		);
	}
	/**
	 * verify valid request is processed as normal.
	 * @test
	 */
	public function testFetchTaxUpdate()
	{
		$request = $this->_mockRequest(true);

		$address = Mage::getModel('sales/quote_address');

		$calc = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('getTaxRequest'))
			->getMock();
		$calc->expects($this->once())
			->method('getTaxRequest')
			->with($this->identicalTo($address))
			->will($this->returnValue($request));

		$taxHelper = $this->getHelperMockBuilder('tax/data')
			->disableOriginalConstructor()
			->setMethods(array('getCalculator'))
			->getMock();
		$taxHelper->expects($this->once())
			->method('getCalculator')
			->will($this->returnValue($calc));
		$this->replaceByMock('helper', 'tax', $taxHelper);

		$eb2cTaxHelper = $this->getHelperMock(
			'eb2ctax/data',
			array('failTaxRequest')
		);
		$eb2cTaxHelper->expects($this->never())
			->method('failTaxRequest');
		$this->replaceByMock('helper', 'eb2ctax', $eb2cTaxHelper);

		$observer = $this->getModelMock('tax/observer', array('_doRequest'));
		$observer->expects($this->once())
			->method('_doRequest')
			->with($this->identicalTo($request))
			->will($this->returnSelf());

		$this->assertSame(
			$observer,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$observer,
				'_fetchTaxUpdate',
				array($address)
			)
		);
	}
	/**
	 * verify invalid request triggers a call to failTaxRequest.
	 * @test
	 */
	public function testFetchTaxUpdateInvalidRequest()
	{
		$request = $this->_mockRequest(false);
		$address = Mage::getModel('sales/quote_address');

		$calc = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('getTaxRequest'))
			->getMock();
		$calc->expects($this->once())
			->method('getTaxRequest')
			->with($this->identicalTo($address))
			->will($this->returnValue($request));

		$taxHelper = $this->getHelperMockBuilder('tax/data')
			->disableOriginalConstructor()
			->setMethods(array('getCalculator'))
			->getMock();
		$taxHelper->expects($this->once())
			->method('getCalculator')
			->will($this->returnValue($calc));
		$this->replaceByMock('helper', 'tax', $taxHelper);

		$eb2cTaxHelper = $this->getHelperMock(
			'eb2ctax/data',
			array('failTaxRequest')
		);
		$eb2cTaxHelper->expects($this->once())
			->method('failTaxRequest')
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'eb2ctax', $eb2cTaxHelper);

		$observer = $this->getModelMock('tax/observer', array('_doRequest'));
		$observer->expects($this->never())
			->method('_doRequest');

		$this->assertSame(
			$observer,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$observer,
				'_fetchTaxUpdate',
				array($address)
			)
		);
	}
	/**
	 * verify updated tax information will be fetched if an updated is needed
	 * @test
	 */
	public function testTaxEventSubtotalCollectBefore()
	{
		$address = Mage::getModel('sales/quote_address');

		$eb2cTaxHelper = $this->getHelperMock(
			'eb2ctax/data',
			array('isRequestForAddressRequired')
		);
		$eb2cTaxHelper->expects($this->once())
			->method('isRequestForAddressRequired')
			->with($this->identicalTo($address))
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ctax', $eb2cTaxHelper);

		$eventObserver = $this->_mockEventObserver($address);

		$observer = $this->getModelMock('tax/observer', array('_fetchTaxUpdate'));
		$observer->expects($this->once())
			->method('_fetchTaxUpdate')
			->will($this->returnSelf());
		$this->assertSame($observer, $observer->taxEventSubtotalCollectBefore($eventObserver));
	}
	/**
	 * verify updated tax information will not be fetched when no update is needed.
	 * NOTE: no need to test for the case where address is null since a null address
	 *       is handled by the underlying call to
	 *       TrueAction_Eb2cTax_Overrides_Model_Calculation::getTaxRequest
	 * @test
	 */
	public function testTaxEventSubtotalCollectBeforeNoUpdate()
	{
		$address = Mage::getModel('sales/quote_address');

		$eb2cTaxHelper = $this->getHelperMock(
			'eb2ctax/data',
			array('isRequestForAddressRequired')
		);
		$eb2cTaxHelper->expects($this->once())
			->method('isRequestForAddressRequired')
			->with($this->identicalTo($address))
			->will($this->returnValue(false));
		$this->replaceByMock('helper', 'eb2ctax', $eb2cTaxHelper);

		$eventObserver = $this->_mockEventObserver($address);

		$observer = $this->getModelMock('tax/observer', array('_fetchTaxUpdate'));
		$observer->expects($this->never())
			->method('_fetchTaxUpdate');
		$this->assertSame($observer, $observer->taxEventSubtotalCollectBefore($eventObserver));
	}
}
