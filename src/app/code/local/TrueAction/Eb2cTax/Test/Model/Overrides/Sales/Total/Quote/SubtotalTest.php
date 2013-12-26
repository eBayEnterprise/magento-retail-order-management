<?php
class TrueAction_Eb2cTax_Test_Model_Overrides_Sales_Total_Quote_SubtotalTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public static $currencyConversionRate = 1.0;
	public function cbConvertPrice($amount)
	{
		return $amount * self::$currencyConversionRate;
	}

	/**
	 * Test the application of taxes to an item.
	 * @large
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testApplyTaxes($qty, $price, $subtotal, $basePrice, $baseSubtotal)
	{
		$address = $this->getModelMock('sales/quote_address');
		$store = $this->buildModelMock('core/store', array('convertPrice'));
		$store->expects($this->any())
			->method('convertPrice')
			->will($this->returnCallback(array($this, 'cbConvertPrice')));
		$quote = $this->getModelMock('sales/quote', array('getStore'));
		$quote->expects($this->any())
			->method('getStore')
			->will($this->returnValue($store));

		$helper = $this->getHelperMock('tax/data', array('convertToBaseCurrency'));
		$helper->expects($this->any())
			->method('convertToBaseCurrency')
			->with($this->anything(), $this->identicalTo($store))
			->will($this->returnCallback(
				function ($val, $store)
				{ return $val * 2; }
			));

		$item = $this->getModelMock('sales/quote_item', array(
			'getQuote',
			'getTotalQty',
			'getBaseCalculationPriceOriginal',
			'getBaseRowTotal',
			'setTaxPercent',
			'hasCustomPrice',
			'getOriginalPrice',
			'setCustomPrice',
			'setBaseCustomPrice',
			'setPrice',
			'setBasePrice',
			'setRowTotal',
			'setBaseRowTotal',
			'setPriceInclTax',
			'setBasePriceInclTax',
			'setRowTotalInclTax',
			'setBaseRowTotalInclTax',
			'setTaxableAmount',
			'setBaseTaxableAmount',
			'setIsPriceInclTax',
			'setDiscountCalculationPrice',
			'setBaseDiscountCalculationPrice',
		));

		// make sure all of the float values from the dataProvider are actually floats
		$price = (float) $price;
		$subtotal = (float) $subtotal;
		$basePrice = (float) $basePrice;
		$baseSubtotal = (float) $baseSubtotal;

		// pull the expectations for this set of inputs
		$e = $this->expected('%s-%.2f-%.2f-%.2f-%.2f', $qty, $price, $subtotal, $basePrice, $baseSubtotal);

		$rate = 0;

		// make sure all of the expectation values are actually floats
		$tax = (float) $e->getTax();
		$taxPrice = (float) $e->getTaxPrice();
		$taxSubtotal = (float) $e->getTaxSubtotal();
		$taxable = (float) $e->getTaxable();

		$baseTax = (float) $e->getBaseTax();
		$baseTaxPrice = (float) $e->getBaseTaxPrice();
		$baseRowTax = (float) $e->getBaseRowTax();
		$baseTaxSubtotal = (float) $e->getBaseTaxSubtotal();
		$baseTaxable = (float) $e->getBaseTaxable();

		$item->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));
		// item getters, should mainly return the inputs to this test, tax calculations
		// will start with these numbers
		$item->expects($this->once())
			->method('getTotalQty')
			->will($this->returnValue($qty));
		$item->expects($this->once())
			->method('getBaseCalculationPriceOriginal')
			->will($this->returnValue($basePrice));
		$item->expects($this->once())
			->method('getBaseRowTotal')
			->will($this->returnValue($subtotal));
		$item->expects($this->once())
			->method('hasCustomPrice')
			->will($this->returnValue(true));
		$item->expects($this->once())
			->method('getOriginalPrice')
			->will($this->returnValue(null));

		// this may not be very useful as the item isn't the authority on the tax rate
		$item->expects($this->once())
			->method('setTaxPercent')
			->with($this->identicalTo($rate))
			->will($this->returnSelf());

		//////////////////////////////////////////////////////////////////////////
		// The following assertions ensure the calculations are done properly //
		//////////////////////////////////////////////////////////////////////////

		// these will only be hit when the item has a custom price, which this item does
		$item->expects($this->once())
			->method('setCustomPrice')
			->with($this->identicalTo($price))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setBaseCustomPrice')
			->with($this->identicalTo($basePrice))
			->will($this->returnSelf());

		$item->expects($this->once())
			->method('setPrice')
			->with($this->equalTo($price))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setBasePrice')
			->with($this->identicalTo($basePrice))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setRowTotal')
			->with($this->identicalTo($subtotal))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setBaseRowTotal')
			->with($this->identicalTo($baseSubtotal))
			->will($this->returnSelf());

		// the following should all get set according to the tax calculations
		$item->expects($this->once())
			->method('setPriceInclTax')
			->with($this->identicalTo($taxPrice))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setBasePriceInclTax')
			->with($this->identicalTo($baseTaxPrice))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setRowTotalInclTax')
			->with($this->identicalTo($taxSubtotal))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setBaseRowTotalInclTax')
			->with($this->identicalTo($baseTaxSubtotal))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setTaxableAmount')
			->with($this->identicalTo($taxable))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setBaseTaxableAmount')
			->with($this->identicalTo($baseTaxable))
			->will($this->returnSelf());

		// this will always be false
		$item->expects($this->once())
			->method('setIsPriceInclTax')
			->with($this->identicalTo(false))
			->will($this->returnSelf());

		// should only be hit when the discountTax config is true, will be in this case
		$item->expects($this->once())
			->method('setDiscountCalculationPrice')
			->with($this->identicalTo($taxPrice))
			->will($this->returnSelf());
		$item->expects($this->once())
			->method('setBaseDiscountCalculationPrice')
			->with($this->identicalTo($baseTaxPrice))
			->will($this->returnSelf());

		// the calculation model returns the amount of tax to apply to each item
		$calculator = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('getTax', 'round', 'getTaxForAmount'))
			->getMock();
		$calculator->expects($this->any())
			->method('getTax')
			->with(
				$this->logicalAnd(
					$this->attribute($this->arrayHasKey('item'), '_data'),
					$this->attribute($this->contains($item), '_data'),
					$this->attribute($this->arrayHasKey('address'), '_data'),
					$this->attribute($this->contains($address), '_data'),
					$this->isInstanceOf('Varien_Object')
				)
			)
			->will($this->returnValue($baseRowTax));
		$calculator->expects($this->any())
			->method('getTaxForAmount')
			->with(
				$this->identicalTo($basePrice),
				$this->logicalAnd(
					$this->attribute($this->arrayHasKey('item'), '_data'),
					$this->attribute($this->contains($item), '_data'),
					$this->attribute($this->arrayHasKey('address'), '_data'),
					$this->attribute($this->contains($address), '_data'),
					$this->isInstanceOf('Varien_Object')
				)
			)
			->will($this->returnValue($baseTax));
		$calculator->expects($this->any())
			->method('round')
			->will($this->returnArgument(0));

		// mock out the config used within the _applyTaxes method
		// have the discountTax method return true to hit the inside of the conditional
		// @todo - should probably have a separate test where this is false to make sure
		// only hit methods according to the store configuration
		$config = $this->getModelMock('tax/config', array('discountTax'));
		$config->expects($this->any())
			->method('discountTax')
			->will($this->returnValue(true));

		// mock the subtotal model (that we are testing) to prevent the constructor
		// from funning and hitting the session
		$subtotal = $this->getModelMockBuilder('tax/sales_total_quote_subtotal')
			->disableOriginalConstructor()
			->setMethods(array(
				'_getAddressItems',
				'_setAddress',
				'_setAmount',
				'_setBaseAmount',
			))
			->getMock();
		// inject the mocks into the subtotal model properties
		$this->_reflectProperty($subtotal, '_calculator')->setValue($subtotal, $calculator);
		$this->_reflectProperty($subtotal, '_config')->setValue($subtotal, $config);
		$this->_reflectProperty($subtotal, '_store')->setValue($subtotal, $store);
		$this->_reflectProperty($subtotal, '_helper')->setValue($subtotal, $helper);
		$applyTaxes = $this->_reflectMethod($subtotal, '_applyTaxes');

		// make sure the method returns itself for chainability
		$this->assertSame(
			$subtotal,
			$applyTaxes->invoke($subtotal, $item, $address)
		);
	}

	/**
	 * Create quote item mocks to use in the collect test
	 * @param  Mock_Mage_Sales_Model_Quote_Item $parentItem         Item parent item
	 * @param  boolean $hasChildren        Does this item have child items
	 * @param  boolean $childrenCalculated Are children of this item calculated
	 * @return Mock_Mage_Sales_Model_Quote_Item
	 */
	protected function _collectItemGenerator(
		$parentItem, $hasChildren, $childrenCalculated
	)
	{
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
	public function testCollectWithoutTrigger()
	{
		$quote = $this->getModelMock('sales/quote', array('getStore'));
		$quote->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));

		$item = $this->_collectItemGenerator(null, false, false);
		$item->expects($this->never())
			->method('getChildren');

		$items = array();

		$address = $this->getModelMock('sales/quote_address', array(
			'getQuote',
		));
		$address->expects($this->once())
			->method('getQuote');

		$subtotal = $this->getModelMockBuilder('tax/sales_total_quote_subtotal')
			->disableOriginalConstructor()
			->setMethods(array(
				'_getAddressItems',
				'_setAddress',
				'_setAmount',
				'_setBaseAmount',
			))
			->getMock();
		$subtotal->expects($this->never())
			->method('_getAddressItems');
		// mock the functions used by the grandparent::collect method.
		$subtotal->expects($this->never())
			->method('_setAddress');
		$subtotal->expects($this->never())
			->method('_setAmount');
		$subtotal->expects($this->never())
			->method('_setBaseAmount');

		$calc = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('hasCalculationTrigger'))
			->getMock();
		$calc->expects($this->once())
			->method('hasCalculationTrigger')
			->will($this->returnValue(false));

		$this->mockObserver();

		$this->_reflectProperty($subtotal, '_calculator')
			->setValue($subtotal, $calc);

		$subtotal->collect($address);
	}

	/**
	 * Test the collect method when there are actual items in the address.
	 * @test
	 */
	public function testCollectWithItems()
	{
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
		// grandparent::collect will call this with param $subtotal->_code and 0
		// when constructor is disabled (as in this case) $subtotal->_code will be null
		// subtotal model will call it with subtotal and 0
		$address->expects($this->exactly(2))
			->method('setTotalAmount')
			->with(
				$this->logicalOr($this->equalTo('subtotal'), $this->equalTo(null)),
				$this->equalTo(0)
			)
			->will($this->returnSelf());
		// grandparent::collect will call this with param $subtotal->_code and 0
		// when constructor is disabled (as in this case) $subtotal->_code will be null
		// subtotal model will call it with subtotal and 0
		$address->expects($this->exactly(2))
			->method('setBaseTotalAmount')
			->with(
				$this->logicalOr($this->equalTo('subtotal'), $this->equalTo(null)),
				$this->equalTo(0)
			)
			->will($this->returnSelf());
		$address->expects($this->once())
			->method('setRoundingDeltas')
			->will($this->returnSelf());

		$subtotal = $this->getModelMockBuilder('tax/sales_total_quote_subtotal')
			->disableOriginalConstructor()
			->setMethods(array(
				'_getAddressItems',
				'_applyTaxes',
				'_recalculateParent',
				'_addSubtotalAmount',
			))
			->getMock();
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

		$calc = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('hasCalculationTrigger'))
			->getMock();
		$calc->expects($this->once())
			->method('hasCalculationTrigger')
			->will($this->returnValue(true));

		$this->_reflectProperty($subtotal, '_calculator')
			->setValue($subtotal, $calc);

		$this->mockObserver();

		$subtotal->collect($address);
	}

	/**
	 * Test the collect method with an address item with a parent item.
	 * @test
	 */
	public function testCollectItemWithParentItem()
	{
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

		$subtotal = $this->buildModelMock('tax/sales_total_quote_subtotal');

		$calc = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('hasCalculationTrigger'))
			->getMock();
		$calc->expects($this->once())
			->method('hasCalculationTrigger')
			->will($this->returnValue(true));

		$this->_reflectProperty($subtotal, '_calculator')
			->setValue($subtotal, $calc);

		$this->mockObserver();

		$subtotal->collect($mockQuoteAddress);
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
		$store = $this->buildModelMock('core/store');
		$quote = $this->buildModelMock('sales/quote', array('getStore'));
		$quote->expects($this->once())
			->method('getStore')
			->will($this->returnValue($store));
		$address = $this->getModelMock('sales/quote_address', array(
			'getQuote',
			'setTotalAmount',
			'setBaseTotalAmount',
			'setSubtotalInclTax',
			'setBaseSubtotalInclTax',
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
		$address->expects($this->once())
			->method('setTotalAmount')
			->will($this->returnSelf());
		$address->expects($this->once())
			->method('setBaseTotalAmount')
			->will($this->returnSelf());
		$address->expects($this->never())
			->method('setRoundingDeltas');

		$calc = $this->buildModelMock('tax/calculation', array('hasCalculationTrigger'));
		$calc->expects($this->once())
			->method('hasCalculationTrigger')
			->will($this->returnValue(true));

		$subtotal = $this->buildModelMock('tax/sales_total_quote_subtotal', array(
			'_getAddressItems',
			'_addSubtotalAmount',
			'_setAddress',
			'_setAmount',
			'_setBaseAmount'
		));
		$subtotal->expects($this->once())
			->method('_getAddressItems')
			->with($address)
			->will($this->returnValue(array()));
		$subtotal->expects($this->never())
			->method('_addSubtotalAmount');

		$this->_reflectProperty($subtotal, '_calculator')
			->setValue($subtotal, $calc);

		$this->mockObserver();

		$subtotal->collect($address);
	}

	/**
	 * verify the subtotal module triggers the eb2ctax_subtotal_collect_before event.
	 * @test
	 */
	public function testCollectSendRequestEvent()
	{
		$observer = $this->getModelMock('tax/observer', array('taxEventSubtotalCollectBefore'));
		$this->replaceByMock('model', 'tax/observer', $observer);

		$store = $this->buildModelMock('core/store');
		$quote = $this->buildModelMock('sales/quote', array('getStore'));
		$quote->expects($this->once())
			->method('getStore')
			->will($this->returnValue($store));
		$address = $this->getModelMock('sales/quote_address', array(
			'getQuote',
			'setTotalAmount',
			'setBaseTotalAmount',
			'setSubtotalInclTax',
			'setBaseSubtotalInclTax',
		));
		$address->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quote));

		$subtotal = $this->buildModelMock('tax/sales_total_quote_subtotal', array(
			'_getAddressItems',
			'_setAddress',
			'_setAmount',
			'_setBaseAmount',
		));

		$calc = $this->buildModelMock('tax/calculation', array('hasCalculationTrigger'));
		$calc->expects($this->once())
			->method('hasCalculationTrigger')
			->will($this->returnValue(true));

		$this->_reflectProperty($subtotal, '_calculator')
			->setValue($subtotal, $calc);

		$this->mockObserver();

		$subtotal->collect($address);
		$this->assertEventDispatched('eb2ctax_subtotal_collect_before');
	}

	/**
	 * mock the observer object to catch the event dispatched at the start of collect.
	 * @return TrueAction_Eb2cTax_Overrides_Model_Observer
	 */
	public function mockObserver()
	{
		$observer = $this->getModelMock('tax/observer', array('taxEventSubtotalCollectBefore'));
		$this->replaceByMock('model', 'tax/observer', $observer);
		return $observer;
	}
}
