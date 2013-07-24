<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2cTax_Test_Model_RequestTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @var Mage_Sales_Model_Quote (mock)
	 */
	public $quote          = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	public $shipAddress    = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	public $billAddress    = null;

	/**
	 * @var ReflectionProperty(TrueAction_Eb2cTax_Model_Request::_xml)
	 */
	public $doc            = null;

	/**
	 * @var ReflectionClass(TrueAction_Eb2cTax_Model_Request)
	 */
	public static $cls     = null;

	/**
	 * path to the xsd file to validate against.
	 * @var string
	 */
	public static $xsdFile = '';

	public $tdRequest      = null;
	public $destinations   = null;
	public $shipGroups     = null;

	public static function setUpBeforeClass()
	{
		self::$xsdFile = dirname(__FILE__) .
			'/RequestTest/fixtures/TaxDutyFee-QuoteRequest-1.0.xsd';
		self::$cls = new ReflectionClass(
			'TrueAction_Eb2cTax_Model_Request'
		);
	}

	public function setUp()
	{
		parent::setUp();
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
	}

	public function quoteWithVirtualProducts()
	{
		$mockQuoteAddress = $this->getModelMock('sales/quote_address', array('getId', 'getEmail'));
		$mockQuoteAddress->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockQuoteAddress->expects($this->any())
			->method('getEmail')
			->will($this->returnValue('test@test.com'));

		$mockProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$mockProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));

		$mockItem = $this->getModelMock('sales/quote_item', array('getId', 'getProduct', 'getHasChildren', 'isChildrenCalculated', 'getChildren'));
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
			->method('isChildrenCalculated')
			->will($this->returnValue(true));
		$mockItem->expects($this->any())
			->method('getChildren')
			->will($this->returnValue(array($mockItem)));

		$mockQuote = $this->getModelMock('sales/quote', array('getId', 'getItemsCount', 'getBillingAddress', 'getAllVisibleItems'));
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

		return $mockQuote;
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testIsValid()
	{
		$req = Mage::getModel('eb2ctax/request', array('quote' => $this->quoteWithVirtualProducts()));
		$this->assertTrue($req->isValid());
		$req->invalidate();
		$this->assertFalse($req->isValid());
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testValidateWithXsd()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testGetSkus()
	{
		// REMINDER: According to mphang this is useless now. Leaving for code review.
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$result = $request->getSkus();
		// the skus in the test are being converted
		// to numbers
		$this->assertEquals(array(1111, 1112, 1113), $result);
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testGetItemDataBySku()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$itemData = $request->getItemDataBySku('1111');
		$this->assertNotNull($itemData);
		$itemData = $request->getItemDataBySku(1111);
		$this->assertNotNull($itemData);
		$itemData = $request->getItemDataBySku('notfound');
		$this->assertNull($itemData);
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testCheckAddresses()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		// passing in a quote with no changes should not invalidate the request
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		// passing in an unusable quote should not invalidate the request
		$request->checkAddresses(Mage::getModel('sales/quote'));
		$this->assertTrue($request->isValid());
		$request->checkAddresses(null);
		$this->assertTrue($request->isValid());
		// changing address information should invalidate the request
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBillingVirtual.yaml
	 */
	public function testCheckAddressesVirtual()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(4);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture multiShipNotSameAsBilling.yaml
	 */
	public function testCheckAddressMultishipping()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(2);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture multiShipNotSameAsBilling.yaml
	 */
	public function testMultishipping()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(2);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$x = new DOMXPath($doc);
		$x->registerNamespace('a', $doc->documentElement->namespaceURI);
		// there should be 3 mailing address nodes;
		// 1 for the billing address; 2 for the shipping addresses
		$this->assertSame(3, $x->query('//a:Destinations/a:MailingAddress')->length);
		$this->assertSame(3, $x->query('//a:Destinations/*')->length);
		// ensure the billing information references a destination
		$billingRef = $x->evaluate('string(//a:BillingInformation/@ref)');
		$el = $doc->getElementById($billingRef);
		$this->assertSame(
			$el,
			$x->query("//a:Destinations/a:MailingAddress[@id='$billingRef']")->item(0)
		);
		// there should be only 2 shipgroups 1 for each
		// shipping address.
		$ls = $x->query('//a:ShipGroup');
		$this->assertSame(2, $ls->length);
		// make sure each shipgroup references a mailingaddress node.
		foreach ($ls as $sg) {
			$destRef = $x->evaluate('string(/a:DestinationTarget/@ref)');
			$el = $doc->getElementById($destRef);
			$this->assertSame(
				$el,
				$x->query("//a:Destinations/a:MailingAddress[@id='$destRef']")->item(0)
			);
		}
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testVirtualPhysicalMix()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();

		$requestXpath = new DOMXPath($doc);
		$requestXpath->registerNamespace('a', $doc->documentElement->namespaceURI);
		$hasVirtualDestination = false;
		$shipGroups = $requestXpath->query('//a:Shipping/a:ShipGroups/a:ShipGroup');
		foreach ($shipGroups as $group) {
			$id = $group->getAttribute('id');
			$destinationTarget = $requestXpath->query('//a:Shipping/a:ShipGroups/a:ShipGroup[@id="' . $id . '"]/a:DestinationTarget');
			$refAttribute = $destinationTarget->item(0)->getAttribute('ref');
			if ($refAttribute && sizeof(explode('_', $refAttribute)) === 3) {
				$hasVirtualDestination = true;
			}
		}

		// Test when no virtual destination is set because our fixtures doesn't have any virtual product
		$this->assertFalse($hasVirtualDestination);

		// Let condition our quote to have virtual products
		$quote   = $this->quoteWithVirtualProducts();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();

		$requestXpath = new DOMXPath($doc);
		$requestXpath->registerNamespace('a', $doc->documentElement->namespaceURI);
		$hasVirtualDestination = false;
		$shipGroups = $requestXpath->query('//a:Shipping/a:ShipGroups/a:ShipGroup');
		foreach ($shipGroups as $group) {
			$id = $group->getAttribute('id');
			$destinationTarget = $requestXpath->query('//a:Shipping/a:ShipGroups/a:ShipGroup[@id="' . $id . '"]/a:DestinationTarget');
			$refAttribute = $destinationTarget->item(0)->getAttribute('ref');
			if ($refAttribute && sizeof(explode('_', $refAttribute)) === 3) {
				$hasVirtualDestination = true;
			}
		}

		// Test when virtual destination is set
		$this->assertTrue($hasVirtualDestination);
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBilling.yaml
	 */
	public function testCheckItemQty()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(3);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$items = $quote->getAllVisibleItems();
		$item = $items[0];
		$request->checkItemQty($item);
		$this->assertTrue($request->isValid());
		$item->setData('qty', 5);
		$request->checkItemQty($item);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBillingNullSku.yaml
	 */
	public function testWithNoSku()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$items = $quote->getAllVisibleItems();
		$doc = $request->getDocument();
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBillingLongSku.yaml
	 */
	public function testCheckSkuWithLongSku()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$this->assertNotNull($doc->documentElement);
		$x = new DOMXPath($doc);
		$x->registerNamespace('a', $doc->documentElement->namespaceURI);
		// the sku should be truncated at 20 characters so the original sku
		// should not be found.
		$ls = $x->query('//a:OrderItem/a:ItemId[.="123456789012345678901"]');
		$this->assertSame(0, $ls->length);

		$ls = $x->query('//a:OrderItem/a:ItemId[.="12345678901234567890"]');

		// the sku should be truncated at 20 characters
		$this->assertSame(3, $ls->length);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testCheckDiscounts()
	{
		$this->markTestIncomplete('there is an error in checkdiscounts');
		$address = $this->getModelMock('sales/quote_address', array('getId'));
		$address->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$quote = $this->getModelMock('sales/quote', array('getId', 'getItemsCount', 'getBillingAddress', 'getAllVisibleItems'));
		$quote->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$quote->expects($this->any())
			->method('getItemsCount')
			->will($this->returnValue(1));
		$quote->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue($address));

		$request = Mage::getModel('eb2ctax/request');
		$request->setQuote($quote)
			->setBillingAddress($address);
		$this->assertTrue($request->isValid());
		$request->checkDiscounts(array(1));
		$this->assertFalse($request->isValid());

		$appliedIds = self::$cls->getProperty('_appliedDiscountIds');
		$appliedIds->setAccessible(true);

		$request = Mage::getModel('eb2ctax/request');
		$request->setQuote($quote)
			->setBillingAddress($address);
		$this->assertTrue($request->isValid());
		$appliedIds->setValue($request, array('1'));
		$request->checkDiscounts(array(1));
		$this->assertTrue($request->isValid());
	}


	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBilling.yaml
	 */
	public function testAddToDestination()
	{
		$fn = new ReflectionMethod('TrueAction_Eb2cTax_Model_Request', '_addToDestination');
		$fn->setAccessible(true);
		$d = new ReflectionProperty('TrueAction_Eb2cTax_Model_Request', '_destinations');
		$d->setAccessible(true);
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(3);
		$items = $quote->getAllVisibleItems();
		$request = Mage::getModel('eb2ctax/request');
		$request->setQuote($quote);
		$fn->invoke($request, $items[0], $quote->getBillingAddress());
		$destinations = $d->getValue($request);
		$this->assertTrue(isset($destinations[$quote->getBillingAddress()->getId()]));
		$fn->invoke($request, $items[0], $quote->getBillingAddress(), true);
		$destinations = $d->getValue($request);
		$virtualId = $quote->getBillingAddress()->getId() .
			'_' . $quote->getBillingAddress()->getEmail();
		$this->assertTrue(isset($destinations[$virtualId]));
	}

	public function testBuildDiscountNode()
	{
		$request = self::$cls->newInstance();
		$fn = self::$cls->getMethod('_buildDiscountNode');
		$fn->setAccessible(true);
		$doc  = $request->getDocument();
		$node = $doc->createElement('root', null, 'http:/www.example.com/foo'); // parent node
		$doc->appendChild($node);
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);
		$discount = array(
			'merchandise_discount_code'      => 'somediscount',
			'merchandise_discount_calc_duty' => 0,
			'merchandise_discount_amount'    => 10.0,
			'shipping_discount_code'         => 'somediscount2',
			'shipping_discount_calc_duty'    => 1,
			'shipping_discount_amount'       => 5.0,
		);
		$fn->invoke($request, $node, $discount);
		$this->assertSame('somediscount', $xpath->evaluate('string(./a:Discount/@id)', $node));
		$this->assertSame('0', $xpath->evaluate('string(./a:Discount/@calculateDuty)', $node));
		$this->assertSame('10', $xpath->evaluate('string(./a:Discount/a:Amount)', $node));

		$node = $doc->createElement('root', null, 'http:/www.example.com/foo'); // parent node
		$doc->appendChild($node);
		$fn->invoke($request, $node, $discount, false);
		$this->assertSame('somediscount2', $xpath->evaluate('string(./a:Discount/@id)', $node));
		$this->assertSame('1', $xpath->evaluate('string(./a:Discount/@calculateDuty)', $node));
		$this->assertSame('5', $xpath->evaluate('string(./a:Discount/a:Amount)', $node));
	}

	public function testExtractItemDiscountData()
	{
		$request = self::$cls->newInstance();
		$fn = self::$cls->getMethod('_extractItemDiscountData');
		$fn->setAccessible(true);
		$mockQuote = $this->getModelMock('sales/quote', array('getAppliedRuleIds'));
		$request->setQuote($mockQuote);
		$mockQuoteAddress = $this->getModelMock('sales/quote_address', array('getShippingDiscountAmount', 'getCouponCode'));
		$mockQuoteAddress->expects($this->any())
			->method('getCouponCode')
			->will($this->returnValue('somecouponcode'));
		$mockQuoteAddress->expects($this->any())
			->method('getShippingDiscountAmount')
			->will($this->returnValue(3));
		$mockItem = $this->getModelMock('sales/quote_item', array('getDiscountAmount'));
		$mockItem->expects($this->any())
			->method('getDiscountAmount')
			->will($this->returnValue(5));
		$mockItem->expects($this->any())
			->method('getAppliedRuleIds')
			->will($this->returnValue(''));
		$outData = array();
		$fn->invoke($request, $mockItem, $mockQuoteAddress, outData);
		$keys = array(
			'merchandise_discount_code',
			'merchandise_discount_amount',
			'merchandise_discount_calc_duty',
			'shipping_discount_code',
			'shipping_discount_amount',
			'shipping_discount_calc_duty',
		);
		foreach ($keys as $key) {
			$this->assertArrayHasKey($key, $outData);
		}
		$this->assertSame('_somecouponcode', $outData['merchandise_discount_code']);
		$this->assertSame(5, $outData['merchandise_discount_amount']);
		$this->assertSame(false, $outData['merchandise_discount_calc_duty']);
		$this->assertSame('_somecouponcode', $outData['shipping_discount_code']);
		$this->assertSame(3, $outData['shipping_discount_amount']);
		$this->assertSame(false, $outData['shipping_discount_calc_duty']);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testGetRateRequest()
	{
		$this->markTestIncomplete('needs to be updated');
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);

		$calc = new TrueAction_Eb2cTax_Overrides_Model_Calculation();
		$request = $calc->getTaxRequest($quote);
		$doc = $request->getDocument();
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);
		$node = $xpath->query('/a:TaxDutyQuoteRequest/a:Currency')->item(0);
		$this->assertSame('USD', $node->textContent);
		$node = $xpath->query('/a:TaxDutyQuoteRequest/a:VATInclusivePricing')->item(0);
		$this->assertSame('0', $node->textContent);
		$node = $xpath->query('/a:TaxDutyQuoteRequest/a:CustomerTaxId')->item(0);
		$this->assertSame('', $node->textContent);
		$node = $xpath->query('/a:TaxDutyQuoteRequest/a:BillingInformation')->item(0);
		$this->assertSame('4', $node->getAttribute('ref'));
		$parent = $xpath->query('/a:TaxDutyQuoteRequest/a:Shipping/a:Destinations/a:MailingAddress')->item(0);
		$this->assertSame('4', $parent->getAttribute('id'));

		// check the PersonName
		$node = $xpath->query('PersonName/a:LastName', $parent)->item(0);
		$this->assertSame('Guy', $node->textContent);
		$node = $xpath->query('PersonName/a:FirstName', $parent)->item(0);
		$this->assertSame('Test', $node->textContent);
		$node = $xpath->query('PersonName/a:Honorific', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->query('PersonName/a:MiddleName', $parent)->item(0);
		$this->assertNull($node);

		// verify the AddressNode
		$node = $xpath->query('Address/a:Line1', $parent)->item(0);
		$this->assertSame('1 RoseDale st', $node->textContent);
		$node = $xpath->query('Address/a:Line2', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->query('Address/a:Line3', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->query('Address/a:Line4', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->query('Address/a:City', $parent)->item(0);
		$this->assertSame('BaltImore', $node->textContent);
		$node = $xpath->query('Address/a:MainDivision', $parent)->item(0);
		$this->assertSame('MD', $node->textContent);
		$node = $xpath->query('Address/a:CountryCode', $parent)->item(0);
		$this->assertSame('US', $node->textContent);
		$node = $xpath->query('Address/a:PostalCode', $parent)->item(0);
		$this->assertSame('21229', $node->textContent);

		// verify the email address
		$parent = $xpath->query('/a:TaxDutyQuoteRequest/a:Shipping/a:Destinations')->item(0);
		$node = $xpath->query('Email', $parent)->item(0);
		$this->assertSame('foo@example.com', $node->getAttribute('id'));

		$node = $xpath->query('Email/a:EmailAddress', $parent)->item(0);
		$this->assertSame('foo@example.com', $node->textContent);
	}

	/**
	 * @test
	 * @loadFixture loadAdminAddressConfig.yaml
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @large
	 */
	public function testExtractAdminData()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$requestReflector = new ReflectionObject($request);
		$extractAdminDataMethod = $requestReflector->getMethod('_extractAdminData');
		$extractAdminDataMethod->setAccessible(true);

		$this->assertSame(
			array(
				'Line1' => '1075 First Avenue', 'Line2' => 'Line2', 'Line3' => 'Line3', 'Line4' => 'Line4', 'City' => 'King Of Prussia',
				'MainDivision' => 'PA', 'CountryCode' => 'US', 'PostalCode' => '19406'
			),
			$extractAdminDataMethod->invoke($request)
		);
	}

	public function providerExtractShippingData()
	{
		$mockQuoteItem = $this->getModelMock('sales/quote_item',
			array(
				'getEb2cShipFromAddressLine1', 'getEb2cShipFromAddressCity', 'getEb2cShipFromAddressMainDivision',
				'getEb2cShipFromAddressCountryCode', 'getEb2cShipFromAddressPostalCode'
			)
		);
		$mockQuoteItem->expects($this->any())
			->method('getEb2cShipFromAddressLine1')
			->will($this->returnValue('1075 First Avenue'));
		$mockQuoteItem->expects($this->any())
			->method('getEb2cShipFromAddressCity')
			->will($this->returnValue('King Of Prussia'));
		$mockQuoteItem->expects($this->any())
			->method('getEb2cShipFromAddressMainDivision')
			->will($this->returnValue('PA'));
		$mockQuoteItem->expects($this->any())
			->method('getEb2cShipFromAddressCountryCode')
			->will($this->returnValue('US'));
		$mockQuoteItem->expects($this->any())
			->method('getEb2cShipFromAddressPostalCode')
			->will($this->returnValue('19406'));

		return array(
			array($mockQuoteItem)
		);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @dataProvider providerExtractShippingData
	 * @large
	 */
	public function testExtractShippingData(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$requestReflector = new ReflectionObject($request);
		$extractShippingDataMethod = $requestReflector->getMethod('_extractShippingData');
		$extractShippingDataMethod->setAccessible(true);

		$this->assertSame(
			array(
				'Line1' => '1075 First Avenue', 'Line2' => 'Line2', 'Line3' => 'Line3', 'Line4' => 'Line4', 'City' => 'King Of Prussia',
				'MainDivision' => 'PA', 'CountryCode' => 'US', 'PostalCode' => '19406'
			),
			$extractShippingDataMethod->invoke($request, $item)
		);
	}

	public function providerBuildAdminOriginNode()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$parent = $domDocument->addElement('TaxDutyQuoteRequest', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;

		return array(
			array(
				$parent,
				array(
					'Line1' => '1075 First Avenue', 'Line2' => 'Line2', 'Line3' => 'Line3', 'Line4' => 'Line4', 'City' => 'King Of Prussia',
					'MainDivision' => 'PA', 'CountryCode' => 'US', 'PostalCode' => '19406'
				)
			)
		);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @dataProvider providerBuildAdminOriginNode
	 * @large
	 */
	public function testBuildAdminOriginNode(TrueAction_Dom_Element $parent, array $adminOrigin)
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$requestReflector = new ReflectionObject($request);
		$buildAdminOriginNodeMethod = $requestReflector->getMethod('_buildAdminOriginNode');
		$buildAdminOriginNodeMethod->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Dom_Element',
			$buildAdminOriginNodeMethod->invoke($request, $parent, $adminOrigin)
		);
	}


	public function providerBuildShippingOriginNode()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$parent = $domDocument->addElement('TaxDutyQuoteRequest', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;

		return array(
			array(
				$parent,
				array(
					'Line1' => '1075 First Avenue', 'Line2' => 'Line2', 'Line3' => 'Line3', 'Line4' => 'Line4', 'City' => 'King Of Prussia',
					'MainDivision' => 'PA', 'CountryCode' => 'US', 'PostalCode' => '19406'
				)
			)
		);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @dataProvider providerBuildShippingOriginNode
	 * @large
	 */
	public function testBuildShippingOriginNode(TrueAction_Dom_Element $parent, array $shippingOrigin)
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$requestReflector = new ReflectionObject($request);
		$buildShippingOriginNodeMethod = $requestReflector->getMethod('_buildShippingOriginNode');
		$buildShippingOriginNodeMethod->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Dom_Element',
			$buildShippingOriginNodeMethod->invoke($request, $parent, $shippingOrigin)
		);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @large
	 */
	public function testCheckShippingOriginAddresses()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$this->assertNull(
			$request->checkShippingOriginAddresses($quote)
		);

		// testing with invalid quote
		$quoteAMock = $this->getMock('Mage_Sales_Model_Quote', array('getId'));
		$quoteAMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0)
			);
		$this->assertNull(
			$request->checkShippingOriginAddresses($quoteAMock)
		);

		// testing when shippingOrigin has changed.
		$requestReflector = new ReflectionObject($request);
		$orderItemsProperty = $requestReflector->getProperty('_orderItems');
		$orderItemsProperty->setAccessible(true);
		$orderItemsProperty->setValue(
			$request, array('1' => array(
					'id' => 1,
					'ShippingOrigin' => array(

					'Line1' => '1075 First Avenue', 'Line2' => 'Line2', 'Line3' => 'Line3', 'Line4' => 'Line4', 'City' => 'King Of Prussia',
					'MainDivision' => 'PA', 'CountryCode' => 'US', 'PostalCode' => '19406'
				)
			))
		);
		$this->assertNull(
			$request->checkShippingOriginAddresses($quote)
		);

	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 * @large
	 */
	public function testCheckAdminOriginAddresses()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$this->assertNull(
			$request->checkAdminOriginAddresses()
		);

		// testing when adminOrigin has changed.
		$requestReflector = new ReflectionObject($request);
		$orderItemsProperty = $requestReflector->getProperty('_orderItems');
		$orderItemsProperty->setAccessible(true);
		$orderItemsProperty->setValue(
			$request, array('1' => array(
					'id' => 1,
					'AdminOrigin' => array(

					'Line1' => 'This is not a test, it\'s difficulty', 'Line2' => 'Line2', 'Line3' => 'Line3', 'Line4' => 'Line4', 'City' => 'King Of Prussia',
					'MainDivision' => 'PA', 'CountryCode' => 'US', 'PostalCode' => '19406'
				)
			))
		);
		$this->assertNull(
			$request->checkAdminOriginAddresses()
		);

		// Testing the behavior of the checkAdminOriginAddresses method
		// when the _hasChanged property is set from a previous process
		$requestReflector = new ReflectionObject($request);
		$hasChangesProperty = $requestReflector->getProperty('_hasChanges');
		$hasChangesProperty->setAccessible(true);
		$hasChangesProperty->setValue($request, true);
		$this->assertNull(
			$request->checkAdminOriginAddresses()
		);
	}
}
