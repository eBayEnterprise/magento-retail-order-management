<?php
class TrueAction_Eb2cTax_Test_Model_Overrides_Sales_Total_Quote_SubtotalTest extends EcomDev_PHPUnit_Test_Case
{
	public function setUp()
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
        $this->applyTaxes = new ReflectionMethod($this->subtotal, '_applyTaxes');
        $this->applyTaxes->setAccessible(true);
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
	 * @large
	 * @test
	 */
	public function testCollectWithBundle()
	{
		$this->markTestIncomplete('test needs an update');
		$mockObserver = $this->getModelMock('tax/observer', array('quoteCollectTotalsBefore'));
		$mockObserver->expects($this->any())
			->method('quoteCollectTotalsBefore')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'tax/observer', $mockObserver);
		$this->replaceByMock('model', 'tax/observer', $mockObserver);

		$mockProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$mockProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));

		$mockItem = $this->getModelMock('sales/quote_item', array('getQty', 'getCalculationPriceOriginal', 'getStore', 'getId', 'getProduct', 'getHasChildren', 'isChildrenCalculated', 'getChildren'));
		$mockItem->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockItem->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($mockProduct));
		$mockItem->expects($this->any())
			->method('getHasChildren')
			->will($this->returnValue(true));
		$mockItem->expects($this->any())
			->method('getCalculationPriceOriginal')
			->will($this->returnValue(100));
		$mockItem->expects($this->any())
			->method('isChildrenCalculated')
			->will($this->returnValue(true));
		$mockItem->expects($this->any())
			->method('getChildren')
			->will($this->returnValue(array($mockItem)));
		$mockItem->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));
		$mockItem->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1));

		$mockQuoteAddress = $this->getModelMock('sales/quote_address', array('getQuote', 'getAllNonNominalItems', 'getId'));
		$mockQuoteAddress->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockQuoteAddress->expects($this->any())
			->method('getAllNonNominalItems')
			->will($this->returnValue(array($mockItem)));

		$mockQuote = $this->getModelMock('sales/quote', array('getStore', 'getId', 'getItemsCount', 'getBillingAddress', 'getAllVisibleItems'));
		$mockQuote->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockQuote->expects($this->any())
			->method('getItemsCount')
			->will($this->returnValue(1));
		$mockQuote->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue($mockQuoteAddress));
		$mockQuote->expects($this->any())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($mockItem)));
		$mockQuote->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));

		$mockQuoteAddress->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($mockQuote));

		$calc  = Mage::helper('tax')->getCalculator();
		$this->subtotal->collect($mockQuoteAddress);

		$this->assertSame(0, $mockItem->getTaxPercent());
		$this->assertSame(100.38, $mockItem->getPriceInclTax());
		$this->assertSame(100.38, $mockItem->getRowTotalInclTax());
		$this->assertSame(10, $mockItem->getTaxableAmount());
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
		$calc    = Mage::helper('tax')->getCalculator();
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$address = $quote->getShippingAddress();
		$items   = $address->getAllNonNominalItems();
		foreach ($items as $item) {
			$this->applyTaxes->invoke($this->subtotal, $item, $address);
			$exp = $this->expected('1-' . $item->getSku());
			$this->assertSame($exp->getTaxPercent(), $item->getTaxPercent());
			$this->assertSame($exp->getPriceInclTax(), $item->getPriceInclTax());
			$this->assertSame($exp->getRowTotalInclTax(), $item->getRowTotalInclTax());
			$this->assertSame($exp->getTaxableAmount(), $item->getTaxableAmount());
			$this->assertSame($exp->getIsPriceInclTax(), $item->getIsPriceInclTax());
		}
	}

	/**
	 * @test
	 * Test length extended to medium due exceeding 1 second on Jenkins
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @large
	 */
	public function testCollect()
	{
		$calc  = Mage::helper('tax')->getCalculator();
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$address = $quote->getShippingAddress();
		$this->subtotal->collect($address);
	}

	public function testCollectTerminalCases()
	{
		$mockItem = $this->getModelMock('sales/quote_item', array('getCalculationPriceOriginal', 'getQuote', 'getParentId'));
		$mockItem->expects($this->any())
			->method('getParentId')
			->will($this->returnValue(1));
		$mockItem->expects($this->any())
			->method('getQuote')
			->will($this->returnValue(Mage::app()->getStore()));
		$mockItem->expects($this->any())
			->method('getCalculationPriceOriginal')
			->will($this->returnValue(100));

		$mockQuoteAddress = $this->getModelMock('sales/quote_address', array('getQuote', 'getAllNonNominalItems', 'getId'));
		$mockQuoteAddress->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockQuoteAddress->expects($this->any())
			->method('getAllNonNominalItems')
			->will($this->returnValue(array($mockItem)));

		$mockQuote = $this->getModelMock('sales/quote', array('getStore'));
		$mockQuote->expects($this->any())
			->method('getStore')
			->will($this->returnValue(Mage::app()->getStore()));

		$mockQuoteAddress->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($mockQuote));

		$this->subtotal->collect($mockQuoteAddress);
		$mockQuoteAddress->expects($this->any())
			->method('getAllNonNominalItems')
			->will($this->returnValue(array()));
		$this->subtotal->collect($mockQuoteAddress);
	}
}
