<?php
class TrueAction_Eb2cTax_Test_Model_Overrides_Sales_Total_Quote_SubtotalTest extends EcomDev_PHPUnit_Test_Case
{
	protected function setUp()
	{
		parent::setUp();
		$cookieMock = $this->getModelMockBuilder('core/cookie')
			->disableOriginalConstructor() // This one removes session_start and other methods usage
			->setMethods(array('set')) // Enables original methods usage, because by default it overrides all methods
			->getMock();
		$cookieMock->expects($this->any())
			->method('set')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'core/cookie', $cookieMock);

		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);

		$this->subtotal = Mage::getModel('tax/sales_total_quote_subtotal');

		$taxQuote  = $this->getModelMock('eb2ctax/response_quote',
			array('getEffectiveRate', 'getCalculatedTax', 'getTaxableAmount'));
		$taxQuote->expects($this->any())
			->method('getEffectiveRate')
			->will($this->returnValue(1.5));
		$taxQuote->expects($this->any())
			->method('getCalculatedTax')
			->will($this->returnValue(0.38));
		$taxQuote->expects($this->any())
			->method('getTaxableAmount')
			->will($this->returnValue(10));
		$taxQuotes = array($taxQuote);
		$orderItem = $this->getModelMock(
			'eb2ctax/response_orderitem',
			array('getTaxQuotes', 'getMerchandiseAmount')
		);
		$orderItem->expects($this->any())
			->method('getTaxQuotes')
			->will($this->returnValue($taxQuotes));
		$orderItem->expects($this->any())
			->method('getMerchandiseAmount')
			->will($this->returnValue(299.99));
		$orderItems = array($orderItem);
		$response = $this->getModelMock(
			'eb2ctax/response',
			array('getResponseForItem')
		);
		$response->expects($this->any())
			->method('getResponseForItem')
			->will($this->returnValue($orderItem));
		Mage::helper('tax')->getCalculator()
			->setTaxResponse($response);
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @loadExpectation testApplyTaxes.yaml
	 */
	public function testApplyTaxes()
	{
		$this->markTestIncomplete('expectation needs to be updated');

		$applyTaxes = new ReflectionMethod($this->subtotal, '_applyTaxes');
		$applyTaxes->setAccessible(true);

		$calc    = Mage::helper('tax')->getCalculator();
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$address = $quote->getShippingAddress();
		$items   = $address->getAllNonNominalItems();
		foreach ($items as $item) {
			$applyTaxes->invoke($this->subtotal, $item, $address);
			$exp = $this->expected('1-' . $item->getSku());
			$this->assertSame($exp->getTaxPercent(), $item->getTaxPercent());
			$this->assertSame($exp->getPriceInclTax(), $item->getPriceInclTax());
			$this->assertSame($exp->getRowTotalInclTax(), $item->getRowTotalInclTax());
			$this->assertSame($exp->getTaxableAmount(), $item->getTaxableAmount());
			$this->assertSame($exp->getIsPriceInclTax(), $item->getIsPriceInclTax());
		}
	}

	/**
	 * Mainly to prevent the event callback triggered in collect from taking any action
	 * but also serves as an additional assertion that the event is being triggered.
	 */
	protected function _mockCollectEventSubscriber()
	{
		$mockObserver = $this->getModelMock('tax/observer', array('taxEventSendRequest'));
		$mockObserver->expects($this->once())
			->method('taxEventSendRequest')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'tax/observer', $mockObserver);
	}

	/**
	 * Create quote item mocks to use in the collect test
	 * @param  Mock_Mage_Sales_Model_Quote_Item $parentItem         Item parent item
	 * @param  boolean $hasChildren        Does this item have child items
	 * @param  boolean $childrenCalculated Are children of this item calculated
	 * @return Mock_Mage_Sales_Model_Quote_Item
	 */
	protected function _collectItemGenerator(
		$parentItem,$hasChildren,$childrenCalculated
	) {
		$item = $this->getModelMock('sales/quote_item', array(
			'getParentItem',
			'getHasChildren',
			'isChildrenCalculated',
			'getChildren',
		));
		$item->expects($this->any())
			->method('getParentItem')
			->will($this->returnValue($parentItem));
		$item->expects($this->any())
			->method('getHasChildren')
			->will($this->returnValue($hasChildren));
		$item->expects($this->any())
			->method('isChildrenCalculated')
			->will($this->returnValue($childrenCalculated));
		return $item;
	}

	/**
	 * Test the collect method when there are actual items in the address.
	 * @test
	 */
	public function testCollectWithItems()
	{
		$this->_mockCollectEventSubscriber();

		$quote = $this->getModelMock('sales/quote', array('getStore'));
		$quote->expects($this->any())
			->method('getStore')
			->will($this->returnValue(null));

		// set up the items for the address
		$itemParent = $this->_collectItemGenerator(null, true, true);

		$itemChild = $this->_collectItemGenerator($itemParent, false, false);
		$itemChild->expects($this->never())
			->method('getChildren');

		$itemParent->expects($this->any())
			->method('getChildren')
			->will($this->returnValue(array($itemChild)));

		$itemNonCalculatedChild = $this->_collectItemGenerator(null, true, false);
		$itemNonCalculatedChild->expects($this->never())
			->method('getChildren');

		$item = $this->_collectItemGenerator(null, false, false);
		$item->expects($this->never())
			->method('getChildren');

		$items = array($itemParent, $itemChild, $itemNonCalculatedChild, $item);

		$address = $this->getModelMock('sales/quote_address', array(
			'getQuote',
			'setSubtotalInclTax',
			'setBaseSubtotalInclTax',
			'setTotalAmount',
			'setBaseTotalAmount',
			'setRoundingDeltas',
		));
		$address->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		$address->expects($this->once())
			->method('setSubtotalInclTax')
			->will($this->returnSelf());
		$address->expects($this->once())
			->method('setBaseSubtotalInclTax')
			->will($this->returnSelf());
		$address->expects($this->exactly(2))
			->method('setTotalAmount')
			->with(
				$this->logicalOr($this->equalTo('subtotal'), $this->equalTo('tax_subtotal')),
				$this->equalTo(0)
			)
			->will($this->returnSelf());
		$address->expects($this->exactly(2))
			->method('setBaseTotalAmount')
			->with(
				$this->logicalOr($this->equalTo('subtotal'), $this->equalTo('tax_subtotal')),
				$this->equalTo(0)
			)
			->will($this->returnSelf());
		$address->expects($this->once())
			->method('setRoundingDeltas')
			->will($this->returnSelf());

		$subtotal = $this->getModelMock('tax/sales_total_quote_subtotal', array(
			'_getAddressItems',
			'_applyTaxes',
			'_recalculateParent',
			'_addSubtotalAmount',
		));
		$subtotal->expects($this->once())
			->method('_getAddressItems')
			->will($this->returnValue($items));
		$subtotal->expects($this->exactly(3))
			->method('_applyTaxes')
			->will($this->returnSelf());
		$subtotal->expects($this->exactly(1))
			->method('_recalculateParent')
			->will($this->returnSelf());
		$subtotal->expects($this->exactly(3))
			->method('_addSubtotalAmount')
			->will($this->returnSelf());

		$subtotal->collect($address);

		$this->assertEventDispatched('eb2ctax_subtotal_collect_before');
	}

	/**
	 * Test the collect method with an address item with a parent item.
	 * @test
	 */
	public function testCollectItemWithParentItem()
	{
		$this->_mockCollectEventSubscriber();

		$mockQuote = $this->getModelMock('sales/quote', array('getStore'));
		$mockQuote->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));

		$mockItem = $this->getModelMock('sales/quote_item', array(
			'getCalculationPriceOriginal',
			'getQuote',
			'getParentItem'
		));
		$mockItem->expects($this->any())
			->method('getParentItem')
			->will($this->returnValue($this->getModelMock('sales/quote/item')));
		$mockItem->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($mockQuote));
		$mockItem->expects($this->any())
			->method('getCalculationPriceOriginal')
			->will($this->returnValue(100));

		$mockQuoteAddress = $this->getModelMock('sales/quote_address', array(
			'setSubtotalInclTax',
			'setBaseSubtotalInclTax',
			'setTotalAmount',
			'setBaseTotalAmount',
			'getQuote',
			'getAllNonNominalItems',
			'getId',
			'setRoundingDeltas',
		));
		$mockQuoteAddress->expects($this->any())
			->method('setSubtotalInclTax')
			->will($this->returnSelf());
		$mockQuoteAddress->expects($this->any())
			->method('setBaseSubtotalInclTax')
			->will($this->returnSelf());
		$mockQuoteAddress->expects($this->any())
			->method('setTotalAmount')
			->will($this->returnSelf());
		$mockQuoteAddress->expects($this->any())
			->method('setBaseTotalAmount')
			->will($this->returnSelf());
		$mockQuoteAddress->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockQuoteAddress->expects($this->any())
			->method('getAllNonNominalItems')
			->will($this->returnValue(array($mockItem)));
		$mockQuoteAddress->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($mockQuote));
		$mockQuoteAddress->expects($this->once())
			->method('setRoundingDeltas')
			->will($this->returnSelf());
		$this->subtotal->collect($mockQuoteAddress);

		$this->assertEventDispatched('eb2ctax_subtotal_collect_before');
	}

	/**
	 * When there are no items, subtotals should not be collected.
	 * Some of this test may be overly brittle due to PHPUnit limitation in
	 * testing method parameters. Part of the test needs to ensure that setTotalAmount
	 * and setBaseTotalAmount are called twice, once with tax_subtotal as the first argument
	 * and once with subtotal as the first argument. The only way to assert this currnetly
	 * is by specifying at which invocation of any method on the address mock the arguments
	 * are passed. This means that in order for the test to succeed, the methods must be called in
	 * the same order they currently are. Any additional calls to the address mock within the test
	 * are likely to cause a failure.
	 * @test
	 */
	public function testCollectNoItems()
	{
		$this->_mockCollectEventSubscriber();

		$quote = $this->getModelMock('sales/quote', array('getStore'));
		$quote->expects($this->once())
			->method('getStore')
			->will($this->returnValue(null));
		$address = $this->getModelMock('sales/quote_address', array(
			'getQuote',
			'setTotalAmount',
			'setBaseTotalAmount',
			'setSubtotalInclTax',
			'setBaseSubtotalInclTax',
			'getAllNonNominalItems',
			'setRoundingDeltas',
		));
		$address->expects($this->at(0))
			->method('setTotalAmount')
			->with($this->equalTo('tax_subtotal'), $this->equalTo(0))
			->will($this->returnSelf());
		$address->expects($this->at(1))
			->method('setBaseTotalAmount')
			->with($this->equalTo('tax_subtotal'), $this->equalTo(0))
			->will($this->returnSelf());
		$address->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		$address->expects($this->once())
			->method('setSubtotalInclTax')
			->with($this->equalTo(0))
			->will($this->returnSelf());
		$address->expects($this->once())
			->method('setBaseSubtotalInclTax')
			->with($this->equalTo(0))
			->will($this->returnSelf());
		$address->expects($this->at(6))
			->method('setTotalAmount')
			->with($this->equalTo('subtotal'), $this->equalTo(0))
			->will($this->returnSelf());
		$address->expects($this->at(7))
			->method('setBaseTotalAmount')
			->with($this->equalTo('subtotal'), $this->equalTo(0))
			->will($this->returnSelf());
		$address->expects($this->once())
			->method('getAllNonNominalItems')
			->will($this->returnValue(null));

		$this->subtotal->collect($address);

		$this->assertEventDispatched('eb2ctax_subtotal_collect_before');
	}

}
