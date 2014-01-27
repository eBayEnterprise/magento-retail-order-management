<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2cTax_Test_Model_Overrides_CalculationTest extends TrueAction_Eb2cCore_Test_Base
{
	public function setUp()
	{
		parent::setUp();

		$this->mockCheckoutSession();

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('convertPrice'))
			->getMock();
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

		$item = $this->_buildModelMock('sales/quote_item', array(
			'getSku' => $this->returnValue('somesku'),
			'getBaseTaxAmount' => $this->returnValue(23.00),
		));
		$this->item = $item;
	}

	public function buildCalcMock($methods=null)
	{
		return $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods($methods)
			->getMock();
	}

	public function tearDown()
	{
		parent::tearDown();
		Mage::unregister('_singleton/tax/calculation');
	}

	/**
	 * an invalid response should return an empty response_orderitem model.
	 */
	public function testGetItemResponseInvalidResponse()
	{
		$response = $this->_buildModelMock('eb2ctax/response', array(
			'isValid'            => $this->returnValue(false),
			'getResponseForItem' => $this->returnValue(new Varien_Object())
		));

		$calc = $this->buildCalcMock();
		$calc->setTaxResponse($response);
		$item    = $this->getModelMock('sales/quote_item');
		$address = $this->getModelMock('sales/quote_address');
		$fn      = $this->_reflectMethod($calc, '_getItemResponse');
		$val     = $fn->invoke($calc, $item, $address);
		$this->assertInstanceOf('TrueAction_Eb2cTax_Model_Response_Orderitem', $val);
		$this->assertEmpty($val->getTaxQuotes());
		$this->assertEmpty($val->getTaxQuoteDiscounts());
	}

	public function testGetTax()
	{
		$calc = $this->buildCalcMock();
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTax($slug);
		$this->assertSame(6.25, $value);
	}

	public function testGetTaxShipping()
	{
		$calc = $this->buildCalcMock();
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTax($slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE);
		$this->assertSame(0.20, $value);
	}

	public function testGetTaxDuty()
	{
		$calc = $this->buildCalcMock();
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTax($slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::DUTY_TYPE);
		$this->assertSame(8.72, $value);
	}

	public function testGetTaxForAmount()
	{
		$calc = $this->buildCalcMock();
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTaxForAmount(1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE);
		$this->assertSame(0.0625, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE);
		$this->assertSame(0.06875, $value);
	}

	public function testGetTaxForAmountShipping()
	{
		$calc = $this->buildCalcMock();
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTaxForAmount(1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE);
		$this->assertSame(0.0133, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE);
		$this->assertSame(0.01463, $value);
	}

	public function testGetTaxForAmountDuty()
	{
		$calc = $this->buildCalcMock();
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getTaxForAmount(1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::DUTY_TYPE);
		$this->assertSame(8.2362, $value);
		$value = $calc->getTaxForAmount(1.1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::DUTY_TYPE);
		$this->assertSame(8.23882, $value);
	}

	public function testGetTaxForAmountGetTax()
	{
		$calc = $this->buildCalcMock();
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$this->assertSame(
			$calc->getTax($slug),
			$calc->round($calc->getTaxForAmount(99.99, $slug))
		);
	}

	public function testGetDiscountTax()
	{
		$calc = $this->buildCalcMock();
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
		$value = $calc->getDiscountTax($slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE);
		$this->assertSame(0.07, $value);
	}

	public function testGetDiscountTaxForAmount()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getDiscountTaxForAmount(1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE);
		$this->assertSame(0.0625, $value);
		$value = $calc->getDiscountTaxForAmount(1.1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE);
		$this->assertSame(0.06875, $value);
	}

	public function testGetDiscountTaxForAmountShipping()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$value = $calc->getDiscountTaxForAmount(1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE);
		$this->assertSame(0.0133, $value);
		$value = $calc->getDiscountTaxForAmount(1.1, $slug, TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE);
		$this->assertSame(0.01463, $value);
	}

	public function testGetDiscountTaxGetDiscountTaxForAmount()
	{
		$calc = Mage::getSingleton('tax/calculation');
		$calc->setTaxResponse($this->_mockResponseWithAll());
		$slug = new Varien_Object(array('item' => $this->item, 'address' => $this->addressMock));
		$this->assertSame(
			$calc->getDiscountTax($slug),
			$calc->round($calc->getDiscountTaxForAmount(12.24, $slug))
		);
	}

	public function provideGetTaxRequestNoAddress()
	{
		$newRequest = $this->getModelMockBuilder('eb2ctax/request')->disableOriginalConstructor()->getMock();
		$response = $this->getModelMockBuilder('eb2ctax/response')
			->disableOriginalConstructor()
			->setMethods(array('getRequest'))
			->getMock();
		$storedRequest = $this->getModelMockBuilder('eb2ctax/request')->disableOriginalConstructor()->getMock();

		// $storedRequest will only come out of the $response mock. $newRequest will only come
		// from the Mage factory
		$response->expects($this->once())->method('getRequest')->will($this->returnValue($storedRequest));

		return array(
			array($response, $newRequest, $storedRequest),
			array(null, $newRequest, $newRequest),
		);
	}
	/**
	 * Get getting the tax request from a calculation model. When given no address
	 * to build a request from, as is the case in these tests, the method should
	 * either return the response stored on a stored request or a new, empty request
	 * object.
	 * @param  TrueAction_Eb2cTax_Model_Response|null $response  The response object stored on the calc
	 * @param  TrueAction_Eb2cTax_Model_Request $newRequest      The request to be returned by the factory method
	 * @param  TrueAction_Eb2cTax_Model_Request $expectedRequest The request object that should be returned
	 * @test
	 * @dataProvider provideGetTaxRequestNoAddress
	 */
	public function testGetTaxRequestNoAddress($response, $newRequest, $expectedRequest)
	{
		$this->replaceByMock('model', 'eb2ctax/request', $newRequest);

		$calc = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('getTaxResponse'))
			->getMock();
		$calc->expects($this->once())
			->method('getTaxResponse')
			->will($this->returnValue($response));

		$this->assertSame($expectedRequest, $calc->getTaxRequest());
	}
	public function testGetTaxRequestWithAddress()
	{
		$address = Mage::getModel('sales/quote_address');
		$calc = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('unsTaxResponse'))
			->getMock();
		$request = $this->getModelMockBuilder('eb2ctax/request')
			->disableOriginalConstructor()
			->setMethods(array('processAddress'))
			->getMock();
		$this->replaceByMock('model', 'eb2ctax/request', $request);

		$calc->expects($this->once())
			->method('unsTaxResponse')
			->will($this->returnSelf());
		$request->expects($this->once())
			->method('processAddress')
			->with($this->identicalTo($address))
			->will($this->returnSelf());
		$this->assertSame($request, $calc->getTaxRequest($address));
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
	 * @dataProvider provideTrueFalse
	 * @loadExpectation testGetAppliedRates.yaml
	 */
	public function testGetAppliedRates($isAfterDiscounts)
	{
		$response = $this->_mockResponseWithAll();

		$helper = $this->getHelperMockBuilder('eb2ctax/data')
			->disableOriginalConstructor()
			->setMethods(array('__', 'taxDutyAmountRateCode', 'getApplyTaxAfterDiscount'))
			->getMock();
		$helper->expects($this->any())
			->method('__')
			->will($this->returnArgument(0));
		$helper->expects($this->any())
			->method('taxDutyAmountRateCode')
			->will($this->returnValue('eb2c-duty-amount'));
		$helper->expects($this->any())
			->method('getApplyTaxAfterDiscount')
			->will($this->returnValue($isAfterDiscounts));
		$this->replaceByMock('helper', 'eb2ctax', $helper);

		$calc = Mage::getModel('tax/calculation');
		$calc->setTaxResponse($response);

		$itemSelector = new Varien_Object(
			array('item' => $this->item, 'address' => $this->addressMock)
		);

		$appliedRates = $calc->getAppliedRates($itemSelector);
		$this->assertNotEmpty($appliedRates);

		$scenario = $isAfterDiscounts ? 'afterdiscount' : 'beforediscount';

		$testData = array(
			TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE,
			TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE,
			TrueAction_Eb2cTax_Overrides_Model_Calculation::DUTY_TYPE, 3
		);
		foreach ($testData as $type) {
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
			$this->assertSame(
				count($r), count($group['rates']),
				"$expectationKey: applied group does not include the correct number of rates"
			);
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

		$helper = $this->getHelperMockBuilder('eb2ctax/data')
			->disableOriginalConstructor()
			->setMethods(array('__', 'taxDutyAmountRateCode', 'getApplyTaxAfterDiscount'))
			->getMock();
		$helper->expects($this->any())
			->method('__')
			->will($this->returnArgument(0));
		$helper->expects($this->any())
			->method('taxDutyAmountRateCode')
			->will($this->returnValue('eb2c-duty-amount'));
		$helper->expects($this->any())
			->method('getApplyTaxAfterDiscount')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ctax', $helper);

		$calc = Mage::getModel('tax/calculation');
		$calc->setTaxResponse($response);

		$itemSelector = new Varien_Object(
			array('item' => $this->item, 'address' => $this->addressMock)
		);

		$appliedRates = $calc->getAppliedRates($itemSelector);
		$this->assertNotEmpty($appliedRates);

		$scenario = 'duplicate-after';

		foreach (array(TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE, 3) as $type) {
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

	/**
	 * tax percent 6.25, price 99.99 discount 12.24,
	 * shipping 14.95 discount 5, duty 8.21,
	 * @return array
	 */
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
		$shipQuoteA = $this->_buildModelMock('eb2ctax/response_quote', array(
			'getCode'          => $this->returnValue('shipping tax'),
			'getType'          => $this->returnValue(1),
			'getTaxableAmount' => $this->returnValue(14.95),
			'getEffectiveRate' => $this->returnValue(0.0133),
			'getCalculatedTax' => $this->returnValue(0.20),
		));
		$shipQuoteB = $this->_buildModelMock('eb2ctax/response_quote', array(
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
		$taxQuotes    = array($taxQuote, $shipQuoteA, $shipQuoteB, $dutyQuote);
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

	/**
	 * verify a request object is returned when address is not null
	 * @test
	 */
	public function testGetTaxRequest()
	{
		$calc = $this->getModelMockBuilder('tax/calculation')
			->setMethods(array('getTaxResponse', 'unsTaxResponse'))
			->disableOriginalConstructor()
			->getMock();
		$calc->expects($this->once())
			->method('unsTaxResponse')
			->will($this->returnSelf());
		$calc->expects($this->never())
			->method('getTaxResponse');

		$request = $this->getModelMockBuilder('eb2ctax/request')
			->setMethods(array('processAddress'))
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('processAddress')
			->with($this->isInstanceOf('Mage_Sales_Model_Quote_Address'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ctax/request', $request);

		$this->assertSame(
			$request,
			$calc->getTaxRequest(
				$this->getModelMockBuilder('sales/quote_address')
					->disableOriginalConstructor()
					->getMock()
			)
		);
	}
	/**
	 * verify a request object is always returned.
	 * verify existing request/response is discarded when quote is not null
	 * verify same request is returned when quote is null and previous request exists.
	 * @test
	 * @dataProvider provideTrueFalse
	 */
	public function testGetTaxRequestNullAddress($hasResponse)
	{
		$request = $this->getModelMockBuilder('eb2ctax/request')
			->setMethods(array('processAddress'))
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->never())
			->method('processAddress');
		$this->replaceByMock('model', 'eb2ctax/request', $request);

		if ($hasResponse) {
			$oldRequest = $this->getModelMockBuilder('eb2ctax/request')
				->disableOriginalConstructor()
				->getMock();
			$response = $this->getModelMockBuilder('eb2ctax/response')
				->setMethods(array(
					'getRequest',
					'isValid'
				))
				->getMock();
			$response->expects($this->any())
				->method('getRequest')
				->will($this->returnValue($oldRequest));
			$response->expects($this->any())
				->method('isValid')
				->will($this->returnValue(true));
		}

		$calc = $this->getModelMockBuilder('tax/calculation')
			->setMethods(array('getTaxResponse', 'unsTaxResponse'))
			->disableOriginalConstructor()
			->getMock();
		$calc->expects($this->once())
			->method('getTaxResponse')
			->will($this->returnValue($hasResponse ? $response : null));
		$calc->expects($this->never())
			->method('unsTaxResponse');

		$address = $this->getModelMockBuilder('sales/quote_address')
		->disableOriginalConstructor()
		->getMock();
		$result = $calc->getTaxRequest();
		$this->assertSame($hasResponse ? $oldRequest : $request, $result);
	}
	/**
	 * simple data provider that gives the sequence true then false.
	 * @return array
	 */
	public function provideTrueFalse()
	{
		return array(array(true), array(false));
	}
}
