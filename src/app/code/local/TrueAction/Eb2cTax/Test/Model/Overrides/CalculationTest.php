<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2cTax_Test_Model_Overrides_CalculationTest extends TrueAction_Eb2cCore_Test_Base
{
	public function setUp()
	{
		$this->_setupBaseUrl();

		$storeMock = $this->getModelMock('core/store', array('convertPrice'));
		// store covertPrice method will double the amount, ensures that all of the base amounts are converted
		// to the disable amounts properly
		$callBack = function ($val) {
			return $val * 2;
		};
		$storeMock->expects($this->any())
			->method('convertPrice')
			->will($this->returnCallback($callBack));

		$this->quoteMock = $this->getModelMock('sales/quote', array('getStore'));
		$this->quoteMock->expects($this->any())
			->method('getStore')
			->will($this->returnValue($storeMock));
		$this->addressMock = $this->getModelMock('sales/quote_address', array('getId', 'getQuote'));
		$this->addressMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$this->addressMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($this->quoteMock));

		$taxQuote = $this->_mockTaxQuote(0.5, 0.38, 'PENNSYLVANIA-Seller And Use Tax', 10);
		$taxQuote2 = $this->_mockTaxQuote(0.01, 10.60, 'PENNSYLVANIA-Random Tax', 5);
		$taxQuotes = array($taxQuote, $taxQuote2);

		$quoteDiscountMock = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
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

		$calc = Mage::getModel('tax/calculation');
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
	 * verify new invalid request is returned when quote is null and no previous request exists.
	 * verify new request is returned when quote is not null
	 * verify existing request/response is discarded when quote is not null
	 * verify same request is returned when qutoe is null and previous request exists.
	 * @test
	 */
	public function testGetTaxRequest()
	{
		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->getMock();
		$calc = Mage::getModel('tax/calculation');
		$request = $calc->getTaxRequest();
		$this->assertNotNull($request);
		$this->assertFalse($request->isValid());

		$otherReq = $calc->getTaxRequest();
		$this->assertNotSame($request, $otherReq);

		$quoteRequest = $calc->getTaxRequest($quote);
		$this->assertNotSame($otherReq, $quoteRequest);

		$otherQuoteRequest = $calc->getTaxRequest($quote);
		$this->assertNotSame($quoteRequest, $otherQuoteRequest);

		$response = $this->getModelMock('eb2ctax/response', array('getRequest'));
		$response->expects($this->any())
			->method('getRequest')
			->will($this->returnValue($request));
		$calc->setData('tax_response', $response);
		$storedRequest = $calc->getTaxRequest();
		$this->assertSame($request, $storedRequest);

		$quoteRequest = $calc->getTaxRequest($quote);
		$this->assertNotSame($storedRequest, $quoteRequest);
		$request = $calc->getTaxRequest();
		$this->assertNotSame($storedRequest, $request);
	}

	protected function _mockConfigRegistry($configValues)
	{
		$configRegistry = $this->getModelMock('eb2ccore/config_registry', array('__get', 'setStore'));
		$configRegistry->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($configValues));
		$configRegistry->expects($this->any())
			->method('setStore')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistry);
		return $configRegistry;
	}

	/**
	 * @test
	 * @dataProvider dataProvider
	 * @loadExpectation testGetAppliedRates.yaml
	 */
	public function testGetAppliedRates($isAfterDiscounts)
	{
		$response = $this->_mockResponseWithAll();

		Mage::unregister('_helper/tax');
		$this->_mockConfigRegistry(array(
			array('taxApplyAfterDiscount', $isAfterDiscounts),
			array('taxDutyRateCode', 'eb2c-duty-amount'),
		));

		$calc = Mage::getModel('tax/calculation');
		$calc->setTaxResponse($response);

		$itemSelector = new Varien_Object(
			array('item' => $this->item, 'address' => $this->addressMock)
		);

		$appliedRates = $calc->getAppliedRates($itemSelector);
		$this->assertNotEmpty($appliedRates);

		$scenario = $isAfterDiscounts ? 'afterdiscount' : 'beforediscount';

		foreach (array('merchandise', 'shipping', 'duty', 'dutyamount') as $type) {
			$expectationKey = "{$scenario}-{$type}";
			$e = $this->expected($expectationKey);
			$this->assertArrayHasKey(
				$e->getId(),
				$appliedRates,
				"$expectationKey: rates does not contain a key for id"
			);
			$group = $appliedRates[$e->getId()];
			$this->assertNotEmpty($group, "$expectationKey: applied groups is empty");
			$this->assertSame($e->getId(), $group['id'], "$expectationKey: applied group id mismatch");
			$this->assertArrayHasKey('percent', $group, "$expectationKey: applied group missing 'percent' key");
			$this->assertSame(
				is_null($e->getPercent()) ? null : (float) $e->getPercent(),
				$group['percent'],
				"$expectationKey: applied groups percent mismatch"
			);
			$this->assertArrayHasKey('rates', $group, "$expectationKey: applied group missing rates");
			$this->assertNotEmpty($group['rates'], "$expectationKey: applied group rates is empty");
			$r = $e->getRates();
			$this->assertSame(count($r), count($group['rates']), "$expectationKey: applied group does not include the correct number of rates");
			foreach ($group['rates'] as $idx => $rate) {
				$this->assertSame($r[$idx]['code'], $rate['code'], "$expectationKey: applied group rate code mismatch");
				$this->assertSame($r[$idx]['code'], $rate['title'], "$expectationKey: applied group rate title mismatch");
				$this->assertSame((float) $r[$idx]['amount'], $rate['amount'], "$expectationKey: applied group rate amount mismatch");
				$this->assertSame((float) $r[$idx]['base_amount'], $rate['base_amount'], "$expectationKey: applied group rate base amount mismatch");
			}
		}
	}

	/**
	 * @atest
	 * @loadExpectation testGetAppliedRates.yaml
	 */
	public function testGetAppliedRatesDuplicateRatesExtraDiscountRates()
	{
		$response = $this->_mockResponseWithDuplicates();

		Mage::unregister('_helper/tax');

		$configRegistry = $this->getModelMock('eb2ccore/config_registry', array('__get', 'setStore'));
		$configRegistry->expects($this->any())
			->method('__get')
			->will($this->returnValueMap(array(
				array('taxApplyAfterDiscount', true),
				array('taxDutyRateCode', 'eb2c-duty-amount'),
			)));
		$configRegistry->expects($this->any())
			->method('setStore')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistry);

		$calc = Mage::getModel('tax/calculation');
		$calc->setTaxResponse($response);

		$itemSelector = new Varien_Object(
			array('item' => $this->item, 'address' => $this->addressMock)
		);

		$appliedRates = $calc->getAppliedRates($itemSelector);
		$this->assertNotEmpty($appliedRates);

		$scenario = 'duplicate-after';

		foreach (array('merchandise', 'dutyamount') as $type) {
			$expectationKey = "{$scenario}-{$type}";
			$e = $this->expected($expectationKey);
			$this->assertArrayHasKey(
				$e->getId(),
				$appliedRates,
				"$expectationKey: rates does not contain a key for id"
			);
			$group = $appliedRates[$e->getId()];
			$this->assertNotEmpty($group, "$expectationKey: applied groups is empty");
			$this->assertSame($e->getId(), $group['id'], "$expectationKey: applied group id mismatch");
			$this->assertArrayHasKey('percent', $group, "$expectationKey: applied group missing 'percent' key");
			$this->assertSame(
				is_null($e->getPercent()) ? null : (float) $e->getPercent(),
				$group['percent'],
				"$expectationKey: applied groups percent mismatch"
			);
			$this->assertArrayHasKey('rates', $group, "$expectationKey: applied group missing rates");
			$this->assertNotEmpty($group['rates'], "$expectationKey: applied group rates is empty");
			$r = $e->getRates();
			$this->assertSame(count($r), count($group['rates']), "$expectationKey: applied group does not include the correct number of rates");
			foreach ($group['rates'] as $idx => $rate) {
				$this->assertSame($r[$idx]['code'], $rate['code'], "$expectationKey: applied group rate code mismatch");
				$this->assertSame($r[$idx]['code'], $rate['title'], "$expectationKey: applied group rate title mismatch");
				$this->assertSame((float) $r[$idx]['amount'], $rate['amount'], "$expectationKey: applied group rate amount mismatch");
				$this->assertSame((float) $r[$idx]['base_amount'], $rate['base_amount'], "$expectationKey: applied group rate base amount mismatch");
			}
		}

		// One of the tax quote discounts should not have matched any of the tax quote
		// codes. The discount should not have been included in the applied rates.
		$this->assertArrayNotHasKey('additional tax-0.0625', $appliedRates);

	}

	protected function _mockTaxQuote($percent, $tax, $rateKey='', $taxable=0)
	{
		$taxQuoteMethods = array('getCode', 'getEffectiveRate', 'getCalculatedTax', 'getTaxableAmount');
		$taxQuote = $this->getModelMock('eb2ctax/response_quote', $taxQuoteMethods);
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

	protected function _mockOrderItem($lineNumber=1, $xml=null)
	{
		$xml = $xml ? $xml : TrueAction_Eb2cTax_Overrides_Model_Calculation::$orderItemXml;
		$doc = new TrueAction_Dom_Document();
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($xml);
		$itemResponse = Mage::getModel('eb2ctax/response_orderitem', array('node' => $doc->documentElement));
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

	protected function _mockResponseWithDuplicates()
	{
		$taxQuoteOne = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('state tax foo'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(99.99),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(6.25),
		));
		$discountQuoteOne = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
			'getCode'          => $this->returnValue('state tax foo'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(12.24),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(0.77),
		));
		$taxQuoteTwo = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('state tax foo'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(99.99),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(6.25),
		));
		$discountQuoteTwo = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
			'getCode'          => $this->returnValue('state tax foo'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(12.24),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(0.77),
		));
		$unknownDiscountQuote = $this->_buildModelMock('eb2ctax/response_quote_discount', array(
			'getCode'          => $this->returnValue('additional tax'),
			'getType'          => $this->returnValue(0),
			'getTaxableAmount' => $this->returnValue(12.24),
			'getEffectiveRate' => $this->returnValue(0.0625),
			'getCalculatedTax' => $this->returnValue(0.77),
		));
		$dutyQuote = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('duty tax'),
			'getType'          => $this->returnValue(2),
			'getTaxableAmount' => $this->returnValue(8.21),
			'getEffectiveRate' => $this->returnValue(0.0262),
			'getCalculatedTax' => $this->returnValue(0.51),
		));
		$doubleDutyQuote = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('double duty tax'),
			'getType'          => $this->returnValue(2),
			'getTaxableAmount' => $this->returnValue(8.21),
			'getEffectiveRate' => $this->returnValue(0.00),
			'getCalculatedTax' => $this->returnValue(0.00),
		));

		$taxQuotes    = array($taxQuoteOne, $taxQuoteTwo, $dutyQuote, $doubleDutyQuote);
		$taxDiscounts = array($discountQuoteOne, $discountQuoteTwo, $unknownDiscountQuote);
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
