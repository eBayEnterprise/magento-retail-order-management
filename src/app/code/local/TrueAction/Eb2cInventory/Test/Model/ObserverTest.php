<?php
class TrueAction_Eb2cInventory_Test_Model_ObserverTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify the observer is configured to listen for necessary events.
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testObserverConfiguration($area, $eventName, $method)
	{
		EcomDev_PHPUnit_Test_Case_Config::assertEventObserverDefined(
			$area,
			$eventName,
			'TrueAction_Eb2cInventory_Model_Observer',
			$method
		);
	}
	/**
	 * Mock a Varien_Event_Observer with a Varien_Event with a 'getQuote' method
	 * that will return the given quote
	 * @param  Mage_Sales_Model_Quote $quote Quote the Varien_Event contains
	 * @return Mock_Varien_Event_Observer Mocked Varien_Event_Observer wrapping a mocked Varien_Event wrapping the quote object
	 */
	protected function _mockObserverWithQuote(Mage_Sales_Model_Quote $quote)
	{
		$event = $this->getMock('Varien_Event', array('getQuote'));
		$observer = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$observer
			->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($event));
		$event
			->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		return $observer;
	}
	public function providerCheckInventoryQuantity()
	{
		return array(
			//    updateQty updateDetails
			array(true,     true,),
			array(false,    true,),
			array(false,    false,),
		);
	}
	/**
	 * When a quote's items quantities have changed, inventory quantities and details should
	 * be updated. Test provider should run through all necessary scenarios, providing the expected
	 * changes to the quote, a PHPUnit_Framework_MockObject_Matcher_InvokedCount for how many times
	 * the update quantity method should be called, a PHPUnit_Framework_MockObject_Matcher_InvokedCount
	 * for the number of times details should be updated, and whether or not the check should result
	 * in the add to/update cart process should be blocked.
	 * @param boolean $isQtyUpdated     Should inventory quantity be checked
	 * @param boolean $isDetailsUpdated Should inventory details be updated
	 * @test
	 * @dataProvider providerCheckInventoryQuantity
	 */
	public function testCheckInventory($isQtyUpdated, $isDetailsUpdated)
	{
		$quote = $this->getModelMock('sales/quote');
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('isQuantityUpdateRequired', 'isDetailsUpdateRequired'))
			->getMock();
		$helper = $this->getHelperMock(
			'eb2cinventory/quote',
			array('rollbackAllocation')
		);
		$observer = $this->getModelMock(
			'eb2cinventory/observer',
			array('_initInventoryCheck', '_updateQuantity', '_updateDetails')
		);

		$this->replaceByMock('helper', 'eb2cinventory/quote', $helper);
		$this->replaceByMock('model', 'eb2ccore/session', $session);

		$session
			->expects($this->any())
			->method('isQuantityUpdateRequired')
			->will($this->returnValue($isQtyUpdated));
		$session
			->expects($this->any())
			->method('isDetailsUpdateRequired')
			->will($this->returnValue($isDetailsUpdated));

		// if quantity or details are being checked/updated, rollback any existing allocations
		// as something has changed so the allocation would no longer be any good
		if ($isQtyUpdated || $isDetailsUpdated) {
			$helper
				->expects($this->once())
				->method('rollbackAllocation')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
		} else {
			$helper
				->expects($this->never())
				->method('rollbackAllocation');
		}

		if ($isQtyUpdated) {
			$observer
				->expects($this->once())
				->method('_updateQuantity')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
		} else {
			$observer
				->expects($this->never())
				->method('_updateQuantity');
		}
		if ($isDetailsUpdated) {
			$observer
				->expects($this->once())
				->method('_updateDetails')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
		} else {
			$observer
				->expects($this->never())
				->method('_updateDetails');
		}

		$this->assertSame($observer, $observer->checkInventory($this->_mockObserverWithQuote($quote)));
	}
	/**
	 * Data provider for the testUpdateQuantity test. Provides the expected response
	 * from the inventory service - simulate a non-empty and an empty response.
	 * @return array Args array containing a string to simulate a non-empty or empty response.
	 */
	public function providerUpdateResponse()
	{
		return array(array('<MockResponse/>'), array(''));
	}
	/**
	 * Test updating a quote with a quantity request. Method should make the request and update the
	 * quote via the observer's _makeRequestAndUpdate method, passing along the request object,
	 * quote and changes in the quote. If the service call returned a usable response, the session
	 * data for quote quantities should be updated.
	 * @param  string $response Response from the inventory quantity service.
	 * @test
	 * @dataProvider providerUpdateResponse
	 */
	public function testUpdateQuantity($response)
	{
		$quote = $this->getModelMock('sales/quote');
		$qtyRequest = $this->getModelMock('eb2cinventory/quantity');
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('updateQuoteInventory', 'resetQuantityUpdateRequired'))
			->getMock();
		$observer = $this->getModelMock('eb2cinventory/observer', array('_makeRequestAndUpdate'));

		$this->replaceByMock('model', 'eb2cinventory/quantity', $qtyRequest);
		$this->replaceByMock('model', 'eb2ccore/session', $session);

		$observer
			->expects($this->once())
			->method('_makeRequestAndUpdate')
			->with($this->identicalTo($qtyRequest), $this->identicalTo($quote))
			->will($this->returnValue($response));

		// when the service response is not empty, update session data
		if ($response) {
			$session
				->expects($this->once())
				->method('updateQuoteInventory')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
			$session
				->expects($this->once())
				->method('resetQuantityUpdateRequired')
				->will($this->returnSelf());
		} else {
			$session->expects($this->never())->method('updateQuoteInventory');
			$session->expects($this->never())->method('resetQuantityUpdateRequired');
		}

		$method = $this->_reflectMethod($observer, '_updateQuantity');
		$this->assertSame($observer, $method->invoke($observer, $quote));
	}
	/**
	 * Test updating a quote with a inventory details request. Method should make the request and update
	 * the quote via the observer's _makeRequestAndUpdate method, passing along the request object,
	 * quote and changes to the quote. If the service returns a usable response, the session data
	 * for quantities and details should be updated.
	 * @param  string $response Response from inventory service
	 * @test
	 * @dataProvider providerUpdateResponse
	 */
	public function testUpdateDetails($response)
	{
		$quote = $this->getModelMock('sales/quote');
		$dtsRequest = $this->getModelMock('eb2cinventory/details');
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('updateQuoteInventory', 'resetDetailsUpdateRequired'))
			->getMock();
		$observer = $this->getModelMock('eb2cinventory/observer', array('_makeRequestAndUpdate'));

		$this->replaceByMock('model', 'eb2cinventory/details', $dtsRequest);
		$this->replaceByMock('model', 'eb2ccore/session', $session);

		$observer
			->expects($this->once())
			->method('_makeRequestAndUpdate')
			->with($this->identicalTo($dtsRequest), $this->identicalTo($quote))
			->will($this->returnValue($response));

		// when the service response is not empty, update session data
		if ($response) {
			$session
				->expects($this->once())
				->method('updateQuoteInventory')
				->with($this->identicalTo($quote))
				->will($this->returnSelf());
			$session
				->expects($this->once())
				->method('resetDetailsUpdateRequired')
				->will($this->returnSelf());
		} else {
			$session->expects($this->never())->method('updateQuoteInventory');
			$session->expects($this->never())->method('resetDetailsUpdateRequired');
		}

		$method = $this->_reflectMethod($observer, '_updateDetails');
		$this->assertSame($observer, $method->invoke($observer, $quote));
	}
	public function providerMakeRequestAndUpdate()
	{
		return array(
			array(new TrueAction_Eb2cInventory_Exception_Cart, null),
			array(new TrueAction_Eb2cInventory_Exception_Cart_Interrupt, null),
			array(null, '<MockResponse/>'),
		);
	}
	/**
	 * Test the abstract method for making an inventory service request and updating
	 * the quote with the results. Method should handle catching inventory exceptions
	 * thrown while making the request, this may result in flagging the observer to
	 * interrupt the cart add/update.
	 * @param  TrueAction_Eb2cInventory_Exception_Cart|TrueAction_Eb2cInventory_Exception_Cart_Interrupt|null $exception Exception to throw from makeRequestForQuote or null of no exception
	 * @param  string|null $response Response expected from the inventory service
	 * @test
	 * @dataProvider providerMakeRequestAndUpdate
	 */
	public function testMakeRequestAndUpdate($exception, $response)
	{
		$observer = Mage::getModel('eb2cinventory/observer');
		$quote = $this->getModelMock('sales/quote');
		$quoteDiff = array('skus' => array('45-123' => 4), 'shipping' => array(array('method' => 'flatrate')));
		$request = $this->getModelMock(
			'eb2cinventory/request_abstract',
			array('makeRequestForQuote', 'updateQuoteWithResponse', '_buildRequestMessage')
		);
		$request
			->expects($this->once())
			->method('makeRequestForQuote')
			->with($this->identicalTo($quote))
			->will(is_null($exception) ? $this->returnValue($response) : $this->throwException($exception));
		$request
			->expects($this->once())
			->method('updateQuoteWithResponse')
			->with($this->identicalTo($quote), $this->identicalTo($response));

		$method = $this->_reflectMethod($observer, '_makeRequestAndUpdate');
		$this->assertSame($response , $method->invoke($observer, $request, $quote, $quoteDiff));
	}
	/**
	 * Data provider for testing allocation methods.
	 * @return array Args array containing Varien_Event_Observer passed to the allocation observer method
	 */
	public function providerProcessEb2cAllocation()
	{
		$addressMock = $this->getModelMock(
			'sales/quote_address',
			array('getShippingMethod', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode', 'getAllItems')
		);
		$addressMock->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('USPS: 3 Day Select')
			);
		$addressMock->expects($this->any())
			->method('getStreet')
			->will($this->returnValue(array('1938 Some Street'))
			);
		$addressMock->expects($this->any())
			->method('getCity')
			->will($this->returnValue('King of Prussia')
			);
		$addressMock->expects($this->any())
			->method('getRegion')
			->will($this->returnValue('Pennsylvania')
			);
		$addressMock->expects($this->any())
			->method('getCountryId')
			->will($this->returnValue('US')
			);
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue('19726')
			);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->getMock();

		$itemMock = $this->getModelMock(
			'sales/quote_item',
			array('getQty', 'getId', 'getSku', 'save', 'getProduct')
		);
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock));

		$addressMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);

		$quoteMock = $this->getModelMock(
			'sales/quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'getAllAddresses')
		);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($addressMock)
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($addressMock))
			);
		$eventMock = $this->getMock(
			'Varien_Event',
			array('getQuote')
		);
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$observerMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing processing allocation observer
	 *
	 * @test
	 * @dataProvider providerProcessEb2cAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessEb2cAllocation($observer)
	{
		$allocationMock = $this->getModelMockBuilder('eb2cinventory/allocation')
			->disableOriginalConstructor()
			->setMethods(array('requiresAllocation', 'processAllocation', 'filterInventoriedItems', 'allocateQuoteItems'))
			->getMock();
		$allocationMock->expects($this->any())
			->method('requiresAllocation')
			->will($this->returnValue(true));
		$allocationMock->expects($this->any())
			->method('processAllocation')
			->will($this->returnValue(array()));
		$allocationMock->expects($this->any())
			->method('filterInventoriedItems')
			->will($this->returnValue(true));
		$allocationMock->expects($this->any())
			->method('allocateQuoteItems')
			->will($this->returnValue('<foo></foo>'));

		$this->replaceByMock('model', 'eb2cinventory/allocation', $allocationMock);

		$this->assertNull(
			Mage::getModel('eb2cinventory/observer')->processEb2cAllocation($observer)
		);
	}

	/**
	 * What happens when a quantity can not be allocated for a quote?
	 *
	 * @test
	 * @expectedException TrueAction_Eb2cInventory_Model_Allocation_Exception
	 * @dataProvider providerProcessEb2cAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessEb2cAllocationError($observer)
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('addError')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);

		$allocationMock = $this->getModelMockBuilder('eb2cinventory/allocation')
			->disableOriginalConstructor()
			->setMethods(array('requiresAllocation', 'processAllocation', 'allocateQuoteItems', 'filterInventoriedItems'))
			->getMock();
		$allocationMock->expects($this->any())
			->method('requiresAllocation')
			->will($this->returnValue(true));
		$allocationMock->expects($this->any())
			->method('processAllocation')
			->will($this->returnValue(array(array('Sorry, item "2610" out of stock.'))));
		$allocationMock->expects($this->any())
			->method('allocateQuoteItems')
			->will($this->returnValue('<foo></foo>'));
		$allocationMock->expects($this->any())
			->method('filterInventoriedItems')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cinventory/allocation', $allocationMock);

		$this->assertNull(
			Mage::getModel('eb2cinventory/observer')->processEb2cAllocation($observer)
		);
	}

	/**
	 * When a quote does not have any items that have managed stock, no allocation
	 * request should be made.
	 * @param  Varien_Event_Observer $observer
	 * @test
	 * @dataProvider providerProcessEb2cAllocation
	 */
	public function testNoAllocationRequestWhenNotRequired($observer)
	{
		$allocationMock = $this->getModelMock('eb2cinventory/allocation', array('requiresAllocation', 'allocateQuoteItems'));
		// make requiresAllocation fail
		$allocationMock->expects($this->once())
			->method('requiresAllocation')
			->with($this->identicalTo($observer->getEvent()->getQuote()))
			->will($this->returnValue(false));
		// ensure allocateQuoteItems is not called
		$allocationMock->expects($this->never())
			->method('allocateQuoteItems');
		$this->replaceByMock('model', 'eb2cinventory/allocation', $allocationMock);
		Mage::getModel('eb2cinventory/observer')->processEb2cAllocation($observer);
	}

}
