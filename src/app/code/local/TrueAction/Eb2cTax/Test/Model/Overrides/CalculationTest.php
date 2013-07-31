<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2cTax_Test_Model_Overrides_CalculationTest extends TrueAction_Eb2cTax_Test_Base
{
	public function setUp()
	{
		$this->_setupBaseUrl();

		$this->addressMock = $this->getModelMock('sales/quote_address');
		$this->addressMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$taxQuote = $this->_mockTaxQuote(0.5, 0.38, 'PENNSYLVANIA-Seller And Use Tax', 10);
		$taxQuote2 = $this->_mockTaxQuote(0.01, 10.60, 'PENNSYLVANIA-Random Tax', 5);
		$taxQuotes = array($taxQuote, $taxQuote2);

		$quoteDiscountMock   = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
			'getRateKey' => $this->returnValue('14_vc_virtual1'),
			'getEffectiveRate' => $this->returnValue(.2),
			'getCalculatedTax' => $this->returnValue(0.38),
			'getTaxableAmount' => $this->returnValue(10),
		));

		$methods = array('getTaxQuotes', 'getMerchandiseAmount');
		$this->orderItem = $this->getModelMock('eb2ctax/response_orderitem', $methods);
		$this->orderItem->expects($this->any())
			->method('getTaxQuotes')
			->will($this->returnValue($taxQuotes));
		$orderItems = array($this->orderItem);

		$response = $this->getModelMock('eb2ctax/response', array('getResponseForItem'));
		$response->expects($this->any())
			->method('getResponseForItem')
			->will($this->returnValue($this->orderItem));
		$this->response = $response;

		$item = $this->_buildModelMock('sales/quote_item', array(
			'getSku' => $this->returnValue('somesku'),
			'getBaseTaxAmount' => $this->returnValue(23.00),
		));
		$this->item = $item;
	}


	/**
	 * an invalid response should return null
	 */
	public function testGetItemResponseInvalidResponse()
	{
		$response = $this->_buildModelMock('eb2ctax/response', array(
			'isValid'            => $this->returnValue(false),
			'getResponseForItem' => $this->returnValue(new Varien_Object())
		));

		$calc    = Mage::getModel('tax/calculation');
		$calc->setTaxResponse($response);
		$item    = $this->getModelMock('sales/quote_item');
		$address = $this->getModelMock('sales/quote_address');
		$fn      = $this->_reflectMethod($calc, '_getItemResponse');
		$val     = $fn->invoke($calc, $item, $address);
		$this->assertNull($val);
	}

	public function testGetTax()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTax($slug);
		$this->assertSame(6.25, $value);
	}

	public function testGetTaxShipping()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTax($slug, 'shipping');
		$this->assertSame(0.20, $value);
	}

	public function testGetTaxDuty()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTax($slug, 'duty');
		$this->assertSame(8.72, $value);
	}

	public function testGetTaxForAmount()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTaxForAmount(1, $slug, 'merchandise', true);
		$this->assertSame(0.06, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, 'merchandise', true);
		$this->assertSame(0.07, $value);
		$value = $calc->getTaxForAmount(1, $slug, 'merchandise', false);
		$this->assertSame(0.0625, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, 'merchandise', false);
		$this->assertSame(0.06875, $value);
	}

	public function testGetTaxForAmountShipping()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTaxForAmount(1, $slug, 'shipping', true);
		$this->assertSame(0.01, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, 'shipping', true);
		$this->assertSame(0.01, $value);
		$value = $calc->getTaxForAmount(1, $slug, 'shipping', false);
		$this->assertSame(0.0133, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, 'shipping', false);
		$this->assertSame(0.01463, $value);
	}

	public function testGetTaxForAmountDuty()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTaxForAmount(1, $slug, 'duty', true);
		$this->assertSame(8.24, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, 'duty', true);
		$this->assertSame(8.24, $value);
		$value = $calc->getTaxForAmount(1, $slug, 'duty', false);
		$this->assertSame(8.2362, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, 'duty', false);
		$this->assertSame(8.23882, $value);
	}

	public function testGetTaxForAmountGetTax()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value1 = $calc->getTax($slug);
		$value2 = $calc->getTaxForAmount(99.99, $slug);
		$this->assertSame($value1, $value2);
	}

	public function testGetDiscountTax()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getDiscountTax($slug);
		$this->assertSame(0.77, $value);
	}

	public function testGetDiscountTaxShipping()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getDiscountTax($slug, 'shipping');
		$this->assertSame(0.07, $value);
	}

	public function testGetDiscountTaxForAmount()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getDiscountTaxForAmount(1, $slug, 'merchandise', true);
		$this->assertSame(0.06, $value);
		$value = $calc->getDiscountTaxForAmount(1.1, $slug, 'merchandise', true);
		$this->assertSame(0.07, $value);
		$value = $calc->getDiscountTaxForAmount(1, $slug, 'merchandise', false);
		$this->assertSame(0.0625, $value);
		$value = $calc->getDiscountTaxForAmount(1.1, $slug, 'merchandise', false);
		$this->assertSame(0.06875, $value);
	}

	public function testGetDiscountTaxForAmountShipping()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getDiscountTaxForAmount(1, $slug, 'shipping', true);
		$this->assertSame(0.01, $value);
		$value = $calc->getDiscountTaxForAmount(1.1, $slug, 'shipping', true);
		$this->assertSame(0.01, $value);
		$value = $calc->getDiscountTaxForAmount(1, $slug, 'shipping', false);
		$this->assertSame(0.0133, $value);
		$value = $calc->getDiscountTaxForAmount(1.1, $slug, 'shipping', false);
		$this->assertSame(0.01463, $value);
	}

	public function testGetDiscountTaxGetDiscountTaxForAmount()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value1 = $calc->getDiscountTax($slug);
		$value2 = $calc->getDiscountTaxForAmount(12.24, $slug);
		$this->assertSame($value1, $value2);
	}

	/**
	 * @test
	 */
	public function testSessionStore()
	{
		$this->_reflectProperty($this->response, '_isValid')->setValue($this->response, true);
		$calc = Mage::getModel('tax/calculation');
		$calc->setTaxResponse($this->response);
		$calc2 = Mage::getModel('tax/calculation');
		$this->assertNotNull($calc2->getTaxResponse());
		$this->assertSame($calc->getTaxResponse(), $calc2->getTaxResponse());
	}

	/**
	 * @test
	 */
	public function testGetTaxRequest()
	{
		$calc = Mage::getModel('tax/calculation');
		$request = $calc->getTaxRequest();
		$this->assertNotNull($request);
	}

	/**
	 * @test
	 */
	public function testGetTaxableForItem()
	{
		$this->_reflectProperty($this->response, '_isValid')->setValue($this->response, true);
		$calc = Mage::getModel('tax/calculation');
		$this->orderItem->expects($this->any())
			->method('getMerchandiseAmount')
			->will($this->returnValue(50));
		$calc->setTaxResponse($this->response);
		$amount = $calc->getTaxableForItem($this->item, $this->addressMock);
		$this->assertSame(15, $amount);
	}

	/**
	 * @testl
	 */
	public function testGetTaxableForItem2()
	{
		$this->_reflectProperty($this->response, '_isValid')->setValue($this->response, true);
		$calc = Mage::getModel('tax/calculation');
		$this->orderItem->expects($this->any())
			->method('getMerchandiseAmount')
			->will($this->returnValue(7));
		$calc->setTaxResponse($this->response);
		$amount = $calc->getTaxableForItem($this->item, $this->addressMock);
		$this->assertSame(7, $amount);
	}

	/**
	 * @test
	 * @loadExpectation
	 */
	public function testGetAppliedRates()
	{
		$this->_reflectProperty($this->response, '_isValid')->setValue($this->response, true);
		$calc = Mage::getModel('tax/calculation');
		$calc->setTaxResponse($this->response);
		$itemSelector = new Varien_Object(
			array('item' => $this->item, 'address' => $this->addressMock)
		);
		$a = $calc->getAppliedRates($itemSelector);
		$this->assertNotEmpty($a);
		$i = 0;
		foreach ($a as $group) {
			$e = $this->expected('0-' . $i);
			$this->assertNotEmpty($group);
			$this->assertSame($e->getId(), $group['id']);
			$this->assertArrayHasKey('percent', $group);
			$this->assertSame((float)$e->getPercent(), $group['percent']);
			$this->assertArrayHasKey('rates', $group);
			$this->assertNotEmpty($group['rates']);
			$this->assertSame(1, count($group['rates']));
			$rate = $group['rates'][0];
			$this->assertSame($e->getCode(), $rate['code']);
			$this->assertSame($e->getCode(), $rate['title']);
			$this->assertSame((float)$e->getAmount(), $rate['amount']);
			$this->assertSame((float)$e->getBaseAmount(), $rate['base_amount']);
			++$i;
		}
	}

	protected function _mockTaxQuote($percent, $tax, $rateKey = '', $taxable = 0)
	{
		$taxQuoteMethods = array('getCode', 'getEffectiveRate', 'getCalculatedTax', 'getTaxableAmount');
		$taxQuote  = $this->getModelMock('eb2ctax/response_quote', $taxQuoteMethods);
		$taxQuote->expects($this->any())
			->method('getEffectiveRate')
			->will($this->returnValue($percent));
		$taxQuote->expects($this->any())
			->method('getCalculatedTax')
			->will($this->returnValue($tax));
		$taxQuote->expects($this->any())
			->method('getTaxableAmount')
			->will($this->returnValue($taxable));
		$taxQuote->expects($this->any())
			->method('getCode')
			->will($this->returnValue($rateKey));
		return $taxQuote;
	}

	protected function _mockOrderItem($lineNumber = 1, $xml = null)
	{
		$xml = $xml ? $xml : TrueAction_Eb2cTax_Overrides_Model_Calculation::$orderItemXml;
		$doc = new TrueAction_Dom_Document();
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($xml);
		$itemResponse = Mage::getModel('eb2ctax/response_orderitem', array('node'=>$doc->documentElement));
		$itemResponse->setLineNumber($lineNumber);
	}

	// tax %6.25, price 99.99 discount 12.24, shipping 14.95 discount 5, duty 8.21,
	protected function _mockResponseWithAll()
	{
		$taxQuote = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('state tax foo'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(99.99),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(6.25),
		));
		$discountQuote = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
			'getCode'          => $this->returnValue('state tax foo'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(12.24),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(0.77),
		));
		$shipQuote = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('shipping tax'),
			'getType'          => $this->returnValue(1),
			'getTaxableAmount' => $this->returnValue(14.95),
			'getEffectiveRate' => $this->returnValue(0.0133),
			'getCalculatedTax' => $this->returnValue(0.20),
		));
		$shipDiscountQuote = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
			'getCode'          => $this->returnValue('shipping tax'),
			'getType'          => $this->returnValue(1),
			'getTaxableAmount' => $this->returnValue(5),
			'getEffectiveRate' => $this->returnValue(0.0133),
			'getCalculatedTax' => $this->returnValue(0.07),
		));
		$dutyQuote = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('duty tax'),
			'getType'          => $this->returnValue(2),
			'getTaxableAmount' => $this->returnValue(8.21),
			'getEffectiveRate' => $this->returnValue(0.0262),
			'getCalculatedTax' => $this->returnValue(0.51),
		));
		$taxQuotes    = array($taxQuote, $shipQuote, $dutyQuote);
		$taxDiscounts = array($discountQuote, $shipDiscountQuote);
		$orderItem    = Mage::getModel('eb2ctax/response_orderitem');
		$orderItem->setData(array('duty_amount' => 8.21));
		$this->_reflectProperty($orderItem, '_taxQuotes')->setValue($orderItem, $taxQuotes);
		$this->_reflectProperty($orderItem, '_taxQuoteDiscounts')->setValue($orderItem, $taxDiscounts);
		$response = $this->_buildModelMock('eb2ctax/response', array(
			'getResponseForItem' => $this->returnValue($orderItem),
			'isValid'            => $this->returnValue(true),
		));
		return $response;
	}

	protected function _mockResponseWithMultiShipRates()
	{
		$taxQuote = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('state tax foo'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(99.99),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(6.25),
		));
		$discountQuote = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
			'getCode'          => $this->returnValue('state tax foo'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(12.24),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(0.77),
		));
		$shipQuote = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('shipping tax'),
			'getType'          => $this->returnValue(1),
			'getTaxableAmount' => $this->returnValue(14.95),
			'getEffectiveRate' => $this->returnValue(0.0133),
			'getCalculatedTax' => $this->returnValue(0.20),
		));
		$shipQuote2 = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('shipping tax2'),
			'getType'          => $this->returnValue(1),
			'getTaxableAmount' => $this->returnValue(14.95),
			'getEffectiveRate' => $this->returnValue(0.0083),
			'getCalculatedTax' => $this->returnValue(0.01),
		));
		$shipDiscountQuote = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
			'getCode'          => $this->returnValue('shipping tax'),
			'getType'          => $this->returnValue(1),
			'getTaxableAmount' => $this->returnValue(5),
			'getEffectiveRate' => $this->returnValue(0.0133),
			'getCalculatedTax' => $this->returnValue(0.07),
		));
		$dutyQuote = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('duty tax'),
			'getType'          => $this->returnValue(2),
			'getTaxableAmount' => $this->returnValue(8.21),
			'getEffectiveRate' => $this->returnValue(0.0262),
			'getCalculatedTax' => $this->returnValue(0.51),
		));
		$taxQuotes    = array($taxQuote, $shipQuote, $shipQuote2, $dutyQuote);
		$taxDiscounts = array($discountQuote, $shipDiscountQuote);
		$orderItem    = Mage::getModel('eb2ctax/response_orderitem');
		$orderItem->setData(array('duty_amount' => 8.21));
		$this->_reflectProperty($orderItem, '_taxQuotes')->setValue($orderItem, $taxQuotes);
		$this->_reflectProperty($orderItem, '_taxQuoteDiscounts')->setValue($orderItem, $taxDiscounts);
		$response = $this->_buildModelMock('eb2ctax/response', array(
			'getResponseForItem' => $this->returnValue($orderItem),
			'isValid'            => $this->returnValue(true),
		));
		return $response;
	}

	public static $orderItemXml = '<?xml version="1.0" encoding="UTF-8"?>
			  <OrderItem lineNumber="1" xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
				<ItemId><![CDATA[classic-jeans]]></ItemId>
				<ItemDesc><![CDATA[Classic Jean]]></ItemDesc>
				<HTSCode/>
				<Quantity><![CDATA[1]]></Quantity>
				<Pricing>
				  <Merchandise>
					<Amount><![CDATA[99.9900]]></Amount>
					<TaxData>
					  <TaxClass>89000</TaxClass>
					  <Taxes>
						<Tax taxType="SELLER_USE" taxability="TAXABLE">
						  <Situs>DESTINATION</Situs>
						  <Jurisdiction jurisdictionId="31152" jurisdictionLevel="STATE">PENNSYLVANIA</Jurisdiction>
						  <Imposition impositionType="General Sales and Use Tax">Sales and Use Tax</Imposition>
						  <EffectiveRate>0.06</EffectiveRate>
						  <TaxableAmount>99.99</TaxableAmount>
						  <CalculatedTax>6.0</CalculatedTax>
						</Tax>
						<Tax taxType="SELLER_USE" taxability="TAXABLE">
						  <Situs>DESTINATION</Situs>
						  <Jurisdiction jurisdictionId="31152" jurisdictionLevel="STATE">PENNSYLVANIA</Jurisdiction>
						  <Imposition impositionType="Random Tax">Random Tax</Imposition>
						  <EffectiveRate>0.02</EffectiveRate>
						  <TaxableAmount>99.99</TaxableAmount>
						  <CalculatedTax>2.0</CalculatedTax>
						</Tax>
					  </Taxes>
					</TaxData>
					<PromotionalDiscounts>
					  <Discount calculateDuty="false" id="334">
						<Amount>20.00</Amount>
						<Taxes>
						  <Tax taxType="SELLER_USE" taxability="TAXABLE">
							<Situs>DESTINATION</Situs>
							<Jurisdiction jurisdictionId="31152" jurisdictionLevel="STATE">PENNSYLVANIA</Jurisdiction>
							<Imposition impositionType="General Sales and Use Tax">Sales and Use Tax</Imposition>
							<EffectiveRate>0.06</EffectiveRate>
							<TaxableAmount>20.0</TaxableAmount>
							<CalculatedTax>1.2</CalculatedTax>
						  </Tax>
						  <Tax taxType="SELLER_USE" taxability="TAXABLE">
							<Situs>DESTINATION</Situs>
							<Jurisdiction jurisdictionId="31152" jurisdictionLevel="STATE">PENNSYLVANIA</Jurisdiction>
							<Imposition impositionType="Random Tax">Random Tax</Imposition>
							<EffectiveRate>0.02</EffectiveRate>
							<TaxableAmount>99.99</TaxableAmount>
							<CalculatedTax>2.0</CalculatedTax>
						  </Tax>
						</Taxes>
					  </Discount>
					</PromotionalDiscounts>
				  </Merchandise>
				</Pricing>
			  </OrderItem>';
}



