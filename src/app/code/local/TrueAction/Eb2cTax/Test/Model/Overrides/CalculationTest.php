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

	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->response);
		$value = $calc->getTaxForItem($this->item, $this->addressMock);
		$this->assertSame(10.98, $value);
	}

	/**
	 * @test
	 */
	public function testGetTaxForItemAmount()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->response);
		$value = $calc->getTaxForItemAmount(1, $this->item, $this->addressMock, true);
		$this->assertSame(0.51, $value);
		$value = $calc->getTaxForItemAmount(1.1, $this->item, $this->addressMock, false);
		$this->assertSame(0.561, $value);
	}

	/**
	 * @test
	 */
	public function testSessionStore()
	{
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
		$calc = Mage::getModel('tax/calculation');
		$this->orderItem->expects($this->any())
			->method('getMerchandiseAmount')
			->will($this->returnValue(50));
		$calc->setTaxResponse($this->response);
		$amount = $calc->getTaxableForItem($this->item, $this->addressMock);
		$this->assertSame(15, $amount);
	}

	/**
	 * @test
	 */
	public function testGetTaxableForItem2()
	{
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
