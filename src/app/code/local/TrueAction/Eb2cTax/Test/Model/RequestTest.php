<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2cTax_Test_Model_RequestTest extends TrueAction_Eb2cTax_Test_Base
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
	}


	public function getItemTaxClassProvider()
	{
		return array(
			array(null, '0-1'),
			array('', '0-2'),
			array('1', '0-3'),
			array('123453434', '0-4'),
			array('33333333333333333333333333333333333333331', '0-5'),
		);
	}

	/**
	 * @dataProvider getItemTaxClassProvider
	 * @loadExpectation
	 * NOTE: this test assumes tax_code can be retrieved from the product using
	 * $product->getTaxCode()
	 */
	public function  testGetItemTaxClass($taxCode, $expectation)
	{
		$product = $this->_buildModelMock('catalog/product', array(
			'isVirtual' => $this->returnValue(false), 
			'hasTaxCode' => $this->returnValue(true),
			'getTaxCode' => $this->returnValue($taxCode),
		));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getProduct' => $this->returnValue($product),
		));
		$request = Mage::getModel('eb2ctax/request');
		$val = $this->_reflectMethod($request, '_getItemTaxClass')->invoke($request, $item);
		$e = $this->expected($expectation);
		$this->assertSame($e->getTaxCode(), $val);
	}

	protected function _mockVirtualQuote()
	{
		$product = $this->getModelMock('catalog/product', array('isVirtual'));
		$product->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$childItem = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(2),
			'getSku'               => $this->returnValue('1111'),
			'getProduct'           => $this->returnValue($product),
			'getHasChildren'       => $this->returnValue(false),
		));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($product),
			'getHasChildren'       => $this->returnValue(true),
			'isChildrenCalculated' => $this->returnValue(true),
			'getChildren'          => $this->returnValue(array($childItem)),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$mockQuote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'isVirtual'             => $this->returnValue(1),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getAllShippingAddresses' => $this->returnValue(array()),
			'getItemById'           => $this->returnValueMap(array(
				array(1, $item),
				array(2, $childItem)
			))
		));
		$mockQuote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		return $mockQuote;
	}

	public function testIsValid()
	{
		$quote = $this->_mockVirtualQuote();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$hasChanges = $this->_reflectProperty($request, '_hasChanges');
		$this->assertFalse($hasChanges->getValue($request));
		$this->assertTrue((bool)$request->getQuote());
		$this->assertTrue((bool)$request->getQuote()->getId());
		$this->assertTrue((bool)$request->getQuote()->getBillingAddress());
		$this->assertTrue((bool)$request->getQuote()->getBillingAddress()->getId());
		$orderItems = $this->_reflectProperty($request, '_orderItems')->getValue($request);
		$this->assertSame((int)$request->getQuote()->getItemsCount(), count($orderItems));
		$this->assertTrue($request->isValid());
		$request->invalidate();
		$this->assertTrue($hasChanges->getValue($request));
		$this->assertFalse($request->isValid());
	}

	public function testValidateWithXsd()
	{
		$this->_setupBaseUrl();
		$quote = $this->_mockSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$doc->formatOutput = true;
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}

	public function testValidateWithXsdVirtual()
	{
		$this->_setupBaseUrl();
		$quote = $this->_mockSingleShipVirtual();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}

	public function testValidateWithXsdMultiShip()
	{
		$this->_setupBaseUrl();
		$quote = $this->_mockMultiShipNotSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}

	public function testCheckAddresses()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_mockSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// changing address information should invalidate the request
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	public function testCheckAddressesNoChanges()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_mockSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// passing in a quote with no changes should not invalidate the request
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
	}

	public function testCheckAddressesEmptyQuote()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_mockSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// passing in an unusable quote should invalidate the request
		$request->checkAddresses(Mage::getModel('sales/quote'));
		$this->assertFalse($request->isValid());
	}

	public function testCheckAddressesNullQuote()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_mockSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// passing in an unusable quote should invalidate the request
		$request->checkAddresses(null);
		$this->assertFalse($request->isValid());
	}

	public function testCheckAddressesChangeMultishipState()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_mockSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// switching to multishipping will invalidate the request
		$quote->setIsMultiShipping(1);
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	public function testCheckAddressesVirtual()
	{
		$quote   = $this->_mockVirtualQuote();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @large
	 * @test
	 */
	public function testCheckAddressMultishipping()
	{
		$this->_setupBaseUrl();
		$quote   = $this->_mockMultiShipNotSameAsBill();
		$val     = Mage::getStoreConfig('eb2ctax/api/namespace_uri', $quote->getStore());
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());

		$quote   = $this->_mockMultiShipNotSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$addresses = $quote->getAllAddresses();
		$addresses[2]->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	public function testMultishipping()
	{
		$this->_setupBaseUrl();
		$quote   = $this->_mockMultiShipNotSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc     = $request->getDocument();
		$x       = new DOMXPath($doc);
		$x->registerNamespace('a', $doc->documentElement->namespaceURI);
		$this->assertNotNull($doc->documentElement->namespaceURI);
		// there should be 3 mailing address nodes;
		// 1 for the billing address; 2 for the shipping addresses
		$doc->formatOutput = true;
		$this->assertSame(1, $x->query('//a:Destinations/a:MailingAddress[@id = "_11"]')->length);
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

	public function testVirtualPhysicalMix()
	{
		$quote   = $this->_mockSingleShipSameAsBillVirtualMix();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc     = $request->getDocument();
		// billing address
		$node1 = $doc->getElementById('_1');
		$this->assertNotNull($node1);
		$this->assertSame('MailingAddress', $node1->tagName);
		// email address for the virtual item
		$node = $doc->getElementById('_2_virtual');
		$this->assertNotNull($node);
		$this->assertSame('Email', $node->tagName);
		// shipping address which should be same as billing address.
		$node2 = $doc->getElementById('_2');
		$this->assertNotNull($node2);
		$this->assertSame('MailingAddress', $node2->tagName);

		$x = new DOMXPath($doc);
		$x->registerNamespace('a', $doc->documentElement->namespaceURI);
		$this->assertSame(
			$x->evaluate('string(./a:PersonName/a:LastName)', $node1),
			$x->evaluate('string(./a:PersonName/a:LastName)', $node2)
		);
		$this->assertSame(
			$x->evaluate('string(./a:PersonName/a:FirstName)', $node1),
			$x->evaluate('string(./a:PersonName/a:FirstName)', $node2)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:Line1)', $node1),
			$x->evaluate('string(./a:Address/a:Line1)', $node2)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:City)', $node1),
			$x->evaluate('string(./a:Address/a:City)', $node2)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:MainDivision)', $node1),
			$x->evaluate('string(./a:Address/a:MainDivision)', $node2)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:CountryCode)', $node1),
			$x->evaluate('string(./a:Address/a:CountryCode)', $node2)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:PostalCode)', $node1),
			$x->evaluate('string(./a:Address/a:PostalCode)', $node2)
		);
	}

	/**
	 * @test
	 * @large
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBilling.yaml
	 */
	public function testCheckItemQty()
	{
		$this->_setupBaseUrl();
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

	public function testWithNoSku()
	{
		$quote = $this->_mockQuoteWithSku(null);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$this->assertFalse($request->isValid());

		$quote = $this->_mockQuoteWithSku('');
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$this->assertFalse($request->isValid());
	}

	public function testCheckSkuWithLongSku()
	{
		$quote   = $this->_mockQuoteWithSku("123456789012345678901");
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
		$this->assertSame(1, $ls->length);
	}

	public function testCheckDiscounts()
	{
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getCouponCode'         => $this->returnValue(''),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());

		// item gets 10 discount -> invalidates the quote
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
			'getDiscountAmount'    => $this->returnValue(10.00),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getCouponCode'         => $this->returnValue('10off'),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));

		$request->checkDiscounts($quote);
		$this->assertFalse($request->isValid());
	}

	public function testCheckDiscountsCouponCode()
	{
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
			'getDiscountAmount'    => $this->returnValue(10.00),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getCouponCode'         => $this->returnValue('10off'),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		// coupon code changes   -> invalidates the quote
		$this->assertTrue($request->isValid());

		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
			'getDiscountAmount'    => $this->returnValue(10.00),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getCouponCode'         => $this->returnValue('10off2'),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));

		$request->checkDiscounts($quote);
		$this->assertFalse($request->isValid());
	}

	public function testCheckDiscountShippingAmount()
	{
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
			'getShippingDiscountAmount'=> $this->returnValue(5),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		// coupon code changes   -> invalidates the quote
		$this->assertTrue($request->isValid());

		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
			'getShippingDiscountAmount'=> $this->returnValue(0),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));

		$request->checkDiscounts($quote);
		$this->assertFalse($request->isValid());
	}

	public function testCheckDiscountsNoChanges()
	{
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getCouponCode'         => $this->returnValue(''),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		$request->checkDiscounts($quote);
		$this->assertTrue($request->isValid());
	}

	public function testCheckDiscountsNullQuote()
	{
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getCouponCode'         => $this->returnValue(''),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());

		$request->checkDiscounts(null);
		$this->assertFalse($request->isValid());
	}

	public function testCheckDiscountsEmptyQuote()
	{
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue('parent_sku'),
			'getProduct'           => $this->returnValue($vProduct),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'getCouponCode'         => $this->returnValue(''),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());

		$request->checkDiscounts(Mage::getModel('sales/quote'));
		$this->assertFalse($request->isValid());
	}


	public function testAddToDestination()
	{
		$fn      = $this->_reflectMethod('TrueAction_Eb2cTax_Model_Request', '_addToDestination');
		$d       = $this->_reflectProperty('TrueAction_Eb2cTax_Model_Request', '_destinations');
		$quote   = $this->_mockSingleShipSameAsBill();
		$items   = $quote->getAllVisibleItems();
		$request = Mage::getModel('eb2ctax/request');
		$request->setQuote($quote);

		$fn->invoke($request, $items[0], $quote->getBillingAddress());
		$destinations  = $d->getValue($request);
		$destinationId = '_' . $quote->getBillingAddress()->getId();
		$this->assertArrayHasKey($destinationId, $destinations);

		$fn->invoke($request, $items[0], $quote->getBillingAddress(), true);
		$destinations = $d->getValue($request);
		$virtualId    = '_' . $quote->getBillingAddress()->getId() . '_virtual';
		$this->assertArrayHasKey($virtualId, $destinations);
	}

	public function testBuildDiscountNode()
	{
		$request = Mage::getModel('eb2ctax/request');
		$fn   = $this->_reflectMethod($request, '_buildDiscountNode');
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
		$request = Mage::getModel('eb2ctax/request');
		$fn = $this->_reflectMethod($request, '_extractItemDiscountData');
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
		$outData = $fn->invoke($request, $mockItem, $mockQuoteAddress, array());
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

	public function testGetRateRequest()
	{
		$quote = $this->_mockSingleShipSameAsBillVirtualMix();
		$request = Mage::helper('tax')->getCalculator()->getTaxRequest($quote);
		$doc = $request->getDocument();
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);
		$val = $xpath->evaluate('string(/a:TaxDutyQuoteRequest/a:Currency)');
		$this->assertSame('USD', $val);
		$val = $xpath->evaluate('string(/a:TaxDutyQuoteRequest/a:VATInclusivePricing)');
		$this->assertSame('0', $val);
		$val = $xpath->evaluate('string(/a:TaxDutyQuoteRequest/a:CustomerTaxId)');
		$this->assertSame('', $val);
		$val = $xpath->evaluate('string(/a:TaxDutyQuoteRequest/a:BillingInformation/@ref)');
		$this->assertSame('_1', $val);
		$parent = $xpath->query('/a:TaxDutyQuoteRequest/a:Shipping/a:Destinations/a:MailingAddress')->item(0);
		$this->assertSame('_1', $parent->getAttribute('id'));

		// check the PersonName
		$val = $xpath->evaluate('string(a:PersonName/a:LastName)', $parent);
		$this->assertSame('guy', $val);
		$val = $xpath->evaluate('string(a:PersonName/a:FirstName)', $parent);
		$this->assertSame('test', $val);
		$node = $xpath->evaluate('a:PersonName/a:Honorific', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->evaluate('a:PersonName/a:MiddleName', $parent)->item(0);
		$this->assertNull($node);

		// verify the AddressNode
		$val = $xpath->evaluate('string(a:Address/a:Line1)', $parent);
		$this->assertSame('1 Rosedale St', $val);
		// the other street lines should not exist since there was no data
		$node = $xpath->evaluate('a:Address/a:Line2', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->evaluate('a:Address/a:Line3', $parent)->item(0);
		$this->assertNull($node);
		$node = $xpath->evaluate('a:Address/a:Line4', $parent)->item(0);
		$this->assertNull($node);

		$val = $xpath->evaluate('string(a:Address/a:City)', $parent);
		$this->assertSame('Baltimore', $val);
		$val = $xpath->evaluate('string(a:Address/a:MainDivision)', $parent);
		$this->assertSame('MD', $val);
		$val = $xpath->evaluate('string(a:Address/a:CountryCode)', $parent);
		$this->assertSame('US', $val);
		$val = $xpath->evaluate('string(a:Address/a:PostalCode)', $parent);
		$this->assertSame('21229', $val);

		// verify the email address
		$parent = $xpath->evaluate('/a:TaxDutyQuoteRequest/a:Shipping/a:Destinations')->item(0);
		$val = $xpath->evaluate('string(a:Email/@id)', $parent);
		$this->assertSame('_2_virtual', $val);

		$val = $xpath->evaluate('string(a:Email/a:EmailAddress)', $parent);
		$this->assertSame('foo@example.com', $val);
	}

	/**
	 * @test
	 * @loadFixture loadAdminAddressConfig.yaml
	 * @loadFixture base.yaml
	 * @large
	 */
	public function testExtractAdminData()
	{
		$quote = $this->_mockSingleShipSameAsBill();
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
	 * @dataProvider providerExtractShippingData
	 * @large
	 */
	public function testExtractShippingData(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		$quote = $this->_mockSingleShipSameAsBill();
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
	 * @dataProvider providerBuildAdminOriginNode
	 * @large
	 */
	public function testBuildAdminOriginNode(TrueAction_Dom_Element $parent, array $adminOrigin)
	{
		$quote = $this->_mockSingleShipSameAsBill();
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
	 * @dataProvider providerBuildShippingOriginNode
	 * @large
	 */
	public function testBuildShippingOriginNode(TrueAction_Dom_Element $parent, array $shippingOrigin)
	{
		$quote = $this->_mockSingleShipSameAsBill();
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
	 */
	public function testCheckShippingOriginAddresses()
	{
		$quote = $this->_mockSingleShipSameAsBill();
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
	 * @large
	 */
	public function testCheckAdminOriginAddresses()
	{
		$quote = $this->_mockSingleShipSameAsBill();
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

	protected function _mockQuoteWithSku($sku)
	{
		$product = $this->getModelMock('catalog/product', array('isVirtual'));
		$product->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue(1),
			'getSku'               => $this->returnValue($sku),
			'getProduct'           => $this->returnValue($product),
			'getHasChildren'       => $this->returnValue(false),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                   => $this->returnValue(1),
			'getAllNonNominalItems'   => $this->returnValue(array($item)),
		));
		$address->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, ));
		$mockQuote = $this->_buildModelMock('sales/quote', array(
			'getId'                 => $this->returnValue(1),
			'isVirtual'             => $this->returnValue(1),
			'getStore'              => $this->returnValue(Mage::app()->getStore()),
			'getBillingAddress'     => $this->returnValue($address),
			'getAllAddresses'       => $this->returnValue(array($address)),
			'getAllShippingAddresses' => $this->returnValue(array()),
			'getItemById'           => $this->returnValueMap(array(
				array(1, $item),
			))
		));
		$mockQuote->setData(array('entity_id' => 1, 'store_id' => 2, 'is_active' => 0, 'is_virtual' => 1, 'is_multi_shipping' => 0, 'items_count' => 1, 'items_qty' => 1.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		return $mockQuote;
	}

	protected function _mockSingleShipSameAsBill()
	{
		$store = Mage::app()->getStore();
		$product = $this->getModelMock('catalog/product', array('isVirtual'));
		$product->expects($this->any())->method('isVirtual')
			->will($this->returnValue(false));

		// mock the items
		$item1 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(1),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item1->setData(array('item_id' => 1, 'quote_id' => 1, 'product_id' => 51, 'store_id' => 2, 'is_virtual' => 0, 'sku' => 1111, 'name' => "Ottoman", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 20.0000, 'qty' => 1.0000, 'price' => 299.9900, 'base_price' => 299.9900, 'row_total' => 299.9900, 'base_row_total' => 299.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 20.0000, 'product_type' => "simple", 'base_cost' => 50.0000, 'price_incl_tax' => 299.9900, 'base_price_incl_tax' => 299.9900, 'row_total_incl_tax' => 299.9900, 'base_row_total_incl_tax' => 299.9900,));

		$item2 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(2),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item2->setData(array('item_id' => 2, 'quote_id' => 1, 'product_id' => 52, 'store_id' => 2, 'is_virtual' => 0, 'sku' => 1112, 'name' => "Chair", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 50.0000, 'qty' => 1.0000, 'price' => 129.9900, 'base_price' => 129.9900, 'row_total' => 129.9900, 'base_row_total' => 129.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 50.0000, 'product_type' => "simple", 'base_cost' => 50.0000, 'price_incl_tax' => 129.9900, 'base_price_incl_tax' => 129.9900, 'row_total_incl_tax' => 129.9900, 'base_row_total_incl_tax' => 129.9900,));

		$item3 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(3),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item3->setData(array('item_id' => 3, 'quote_id' => 1, 'product_id' => 53, 'store_id' => 2, 'is_virtual' => 0, 'sku' => 1113, 'name' => "Couch", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 200.0000, 'qty' => 1.0000, 'price' => 599.9900, 'base_price' => 599.9900, 'row_total' => 599.9900, 'base_row_total' => 599.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 200.0000, 'product_type' => "simple", 'base_cost' => 200.0000, 'price_incl_tax' => 599.9900, 'base_price_incl_tax' => 599.9900, 'row_total_incl_tax' => 599.9900, 'base_row_total_incl_tax' => 599.9900,));
		$items = array($item1, $item2, $item3);

		// mock the billing addresses
		$address1 = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(1),
			'getAllNonNominalItems'      => $this->returnValue(array()),
			'getGroupedAllShippingRates' => $this->returnValue(array()),
		));
		$address1->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'prefix' => 'Mr.', 'middlename' => 'mid', 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'shipping_amount' => 0.0000, 'base_shipping_amount' => 0.0000, 'shipping_tax_amount' => 0.0000, 'base_shipping_tax_amount' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'grand_total' => 0.0000, 'base_grand_total' => 0.0000, 'applied_taxes' => "a:0:{}", 'subtotal_incl_tax' => 0.0000, 'shipping_incl_tax' => 0.0000, 'base_shipping_incl_tax' => 0.0000,));

		// mock the shipping address
		$shippingRate = new Varien_Object(array('method' => 'flatrate', 'code' => 'flatrate_flatrate'));
		$address2 = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(2),
			'getAllNonNominalItems'      => $this->returnValue($items),
			'getGroupedAllShippingRates' => $this->returnValue(array('flatrate' => array($shippingRate))),
		));
		$address2->setData(array('address_id' => 2, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 0, 'address_type' => "shipping", 'email' => "foo@example.com", 'firstname' => "test", 'prefix' => 'Mr.', 'middlename' => 'mid', 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 1, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'shipping_method' => "flatrate_flatrate", 'shipping_description' => "Flat Rate - Fixed", 'weight' => 270.0000, 'subtotal' => 1029.9700, 'base_subtotal' => 1029.9700, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'shipping_amount' => 15.0000, 'base_shipping_amount' => 15.0000, 'shipping_tax_amount' => 0.0000, 'base_shipping_tax_amount' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'grand_total' => 1044.9700, 'base_grand_total' => 1044.9700, 'applied_taxes' => "a:0:{}", 'shipping_discount_amount' => 0.0000, 'base_shipping_discount_amount' => 0.0000, 'subtotal_incl_tax' => 1029.9700, 'hidden_tax_amount' => 0.0000, 'base_hidden_tax_amount' => 0.0000, 'shipping_hidden_tax_amount' => 0.0000, 'shipping_incl_tax' => 15.0000, 'base_shipping_incl_tax' => 15.0000,));

		// mock the quote
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'              => $this->returnValue(1),
			'isVirtual'          => $this->returnValue(false),
			'getStore'           => $this->returnValue($store),
			'getBillingAddress'  => $this->returnValue($address1),
			'getShippingAddress' => $this->returnValue($address2),
			'getAllAddresses'    => $this->returnValue(array($address1, $address2)),
			'getAllShippingAddresses' => $this->returnValue(array($address2)),
			'getAllVisibleItems' => $this->returnValue($items),
			'getItemById'        => $this->returnValueMap(array(
				array(1, $item1),
				array(2, $item2),
				array(3, $item3),
			))
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 0, 'created_at' => "2013-06-27 17:32:54", 'updated_at' => "2013-06-27 17:36:19", 'is_active' => 0, 'is_virtual' => 0, 'is_multi_shipping' => 0, 'items_count' => 3, 'items_qty' => 3.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'grand_total' => 1044.9700, 'base_grand_total' => 1044.9700, 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'subtotal' => 1029.9700, 'base_subtotal' => 1029.9700, 'subtotal_with_discount' => 1029.9700, 'base_subtotal_with_discount' => 1029.9700, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		return $quote;
	}

	protected function _mockSingleShipVirtual()
	{
		$store = Mage::app()->getStore();
		$product = $this->getModelMock('catalog/product', array('isVirtual'));
		$product->expects($this->any())->method('isVirtual')
			->will($this->returnValue(true));

		// mock the items
		$item1 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(1),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item1->setData(array('item_id' => 1, 'quote_id' => 1, 'product_id' => 51, 'store_id' => 2, 'is_virtual' => 1, 'sku' => 1111, 'name' => "Ottoman", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 20.0000, 'qty' => 1.0000, 'price' => 299.9900, 'base_price' => 299.9900, 'row_total' => 299.9900, 'base_row_total' => 299.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 20.0000, 'product_type' => "simple", 'base_cost' => 50.0000, 'price_incl_tax' => 299.9900, 'base_price_incl_tax' => 299.9900, 'row_total_incl_tax' => 299.9900, 'base_row_total_incl_tax' => 299.9900,));

		$item2 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(2),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item2->setData(array('item_id' => 2, 'quote_id' => 1, 'product_id' => 52, 'store_id' => 2, 'is_virtual' => 1, 'sku' => 1112, 'name' => "Chair", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 50.0000, 'qty' => 1.0000, 'price' => 129.9900, 'base_price' => 129.9900, 'row_total' => 129.9900, 'base_row_total' => 129.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 50.0000, 'product_type' => "simple", 'base_cost' => 50.0000, 'price_incl_tax' => 129.9900, 'base_price_incl_tax' => 129.9900, 'row_total_incl_tax' => 129.9900, 'base_row_total_incl_tax' => 129.9900,));

		$item3 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(3),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item3->setData(array('item_id' => 3, 'quote_id' => 1, 'product_id' => 53, 'store_id' => 2, 'is_virtual' => 1, 'sku' => 1113, 'name' => "Couch", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 200.0000, 'qty' => 1.0000, 'price' => 599.9900, 'base_price' => 599.9900, 'row_total' => 599.9900, 'base_row_total' => 599.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 200.0000, 'product_type' => "simple", 'base_cost' => 200.0000, 'price_incl_tax' => 599.9900, 'base_price_incl_tax' => 599.9900, 'row_total_incl_tax' => 599.9900, 'base_row_total_incl_tax' => 599.9900,));
		$items = array($item1, $item2, $item3);

		// mock the billing addresses
		$address1 = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(1),
			'getAllNonNominalItems'      => $this->returnValue($items),
			'getGroupedAllShippingRates' => $this->returnValue(array()),
		));
		$address1->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'shipping_amount' => 0.0000, 'base_shipping_amount' => 0.0000, 'shipping_tax_amount' => 0.0000, 'base_shipping_tax_amount' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'grand_total' => 0.0000, 'base_grand_total' => 0.0000, 'applied_taxes' => "a:0:{}", 'subtotal_incl_tax' => 0.0000, 'shipping_incl_tax' => 0.0000, 'base_shipping_incl_tax' => 0.0000,));

		// mock the quote
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'              => $this->returnValue(1),
			'isVirtual'          => $this->returnValue(true),
			'getStore'           => $this->returnValue($store),
			'getBillingAddress'  => $this->returnValue($address1),
			'getAllAddresses'    => $this->returnValue(array($address1)),
			'getAllShippingAddresses' => $this->returnValue(array()),
			'getAllVisibleItems' => $this->returnValue($items),
			'getItemById'        => $this->returnValueMap(array(
				array(1, $item1),
				array(2, $item2),
				array(3, $item3),
			))
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 0, 'created_at' => "2013-06-27 17:32:54", 'updated_at' => "2013-06-27 17:36:19", 'is_active' => 0, 'is_virtual' => 0, 'is_multi_shipping' => 0, 'items_count' => 3, 'items_qty' => 3.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'grand_total' => 1044.9700, 'base_grand_total' => 1044.9700, 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'subtotal' => 1029.9700, 'base_subtotal' => 1029.9700, 'subtotal_with_discount' => 1029.9700, 'base_subtotal_with_discount' => 1029.9700, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		return $quote;
	}

	protected function _mockSingleShipSameAsBillVirtualMix()
	{
		$store = Mage::app()->getStore();
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())->method('isVirtual')
			->will($this->returnValue(true));
		$product = $this->getModelMock('catalog/product', array('isVirtual'));
		$product->expects($this->any())->method('isVirtual')
			->will($this->returnValue(false));

		// mock the items
		$item1 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(1),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item1->setData(array('item_id' => 1, 'quote_id' => 1, 'product_id' => 51, 'store_id' => 2, 'is_virtual' => 0, 'sku' => 1111, 'name' => "Ottoman", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 20.0000, 'qty' => 1.0000, 'price' => 299.9900, 'base_price' => 299.9900, 'row_total' => 299.9900, 'base_row_total' => 299.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 20.0000, 'product_type' => "simple", 'base_cost' => 50.0000, 'price_incl_tax' => 299.9900, 'base_price_incl_tax' => 299.9900, 'row_total_incl_tax' => 299.9900, 'base_row_total_incl_tax' => 299.9900,));

		$item2 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(2),
			'getProduct'     => $this->returnValue($vProduct),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item2->setData(array('item_id' => 2, 'quote_id' => 1, 'product_id' => 52, 'store_id' => 2, 'is_virtual' => 1, 'sku' => 1112, 'name' => "Chair", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 50.0000, 'qty' => 1.0000, 'price' => 129.9900, 'base_price' => 129.9900, 'row_total' => 129.9900, 'base_row_total' => 129.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 50.0000, 'product_type' => "simple", 'base_cost' => 50.0000, 'price_incl_tax' => 129.9900, 'base_price_incl_tax' => 129.9900, 'row_total_incl_tax' => 129.9900, 'base_row_total_incl_tax' => 129.9900,));

		$item3 = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(3),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item3->setData(array('item_id' => 3, 'quote_id' => 1, 'product_id' => 53, 'store_id' => 2, 'is_virtual' => 0, 'sku' => 1113, 'name' => "Couch", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 200.0000, 'qty' => 1.0000, 'price' => 599.9900, 'base_price' => 599.9900, 'row_total' => 599.9900, 'base_row_total' => 599.9900, 'row_total_with_discount' => 0.0000, 'row_weight' => 200.0000, 'product_type' => "simple", 'base_cost' => 200.0000, 'price_incl_tax' => 599.9900, 'base_price_incl_tax' => 599.9900, 'row_total_incl_tax' => 599.9900, 'base_row_total_incl_tax' => 599.9900,));
		$items = array($item1, $item2, $item3);

		// mock the billing addresses
		$address1 = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(1),
			'getAllNonNominalItems'      => $this->returnValue(array()),
			'getGroupedAllShippingRates' => $this->returnValue(array()),
		));
		$address1->setData(array('address_id' => 1, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 1, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'shipping_amount' => 0.0000, 'base_shipping_amount' => 0.0000, 'shipping_tax_amount' => 0.0000, 'base_shipping_tax_amount' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'grand_total' => 0.0000, 'base_grand_total' => 0.0000, 'applied_taxes' => "a:0:{}", 'subtotal_incl_tax' => 0.0000, 'shipping_incl_tax' => 0.0000, 'base_shipping_incl_tax' => 0.0000,));

		// mock the shipping address
		$shippingRate = new Varien_Object(array('method' => 'flatrate', 'code' => 'flatrate_flatrate'));
		$address2 = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(2),
			'getAllNonNominalItems'      => $this->returnValue(array($item1, $item2, $item3)),
			'getGroupedAllShippingRates' => $this->returnValue(array('flatrate' => array($shippingRate))),
		));
		$address2->setData(array('address_id' => 2, 'quote_id' => 1, 'customer_id' => 5, 'save_in_address_book' => 0, 'address_type' => "shipping", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 1, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'shipping_method' => "flatrate_flatrate", 'shipping_description' => "Flat Rate - Fixed", 'weight' => 270.0000, 'subtotal' => 1029.9700, 'base_subtotal' => 1029.9700, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'shipping_amount' => 15.0000, 'base_shipping_amount' => 15.0000, 'shipping_tax_amount' => 0.0000, 'base_shipping_tax_amount' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'grand_total' => 1044.9700, 'base_grand_total' => 1044.9700, 'applied_taxes' => "a:0:{}", 'shipping_discount_amount' => 0.0000, 'base_shipping_discount_amount' => 0.0000, 'subtotal_incl_tax' => 1029.9700, 'hidden_tax_amount' => 0.0000, 'base_hidden_tax_amount' => 0.0000, 'shipping_hidden_tax_amount' => 0.0000, 'shipping_incl_tax' => 15.0000, 'base_shipping_incl_tax' => 15.0000,));

		// mock the quote
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'              => $this->returnValue(1),
			'isVirtual'          => $this->returnValue(false),
			'getStore'           => $this->returnValue($store),
			'getBillingAddress'  => $this->returnValue($address1),
			'getShippingAddress' => $this->returnValue($address2),
			'getAllAddresses'    => $this->returnValue(array($address1, $address2)),
			'getAllShippingAddresses' => $this->returnValue(array($address2)),
			'getAllVisibleItems' => $this->returnValue($items),
			'getItemById'        => $this->returnValueMap(array(
				array(1, $item1),
				array(2, $item2),
				array(3, $item3),
			))
		));
		$quote->setData(array('entity_id' => 1, 'store_id' => 0, 'created_at' => "2013-06-27 17:32:54", 'updated_at' => "2013-06-27 17:36:19", 'is_active' => 0, 'is_virtual' => 0, 'is_multi_shipping' => 0, 'items_count' => 3, 'items_qty' => 3.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'grand_total' => 1044.9700, 'base_grand_total' => 1044.9700, 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000050, 'subtotal' => 1029.9700, 'base_subtotal' => 1029.9700, 'subtotal_with_discount' => 1029.9700, 'base_subtotal_with_discount' => 1029.9700, 'is_changed' => 1, 'trigger_recollect' => 0, 'is_persistent' => 0,));
		return $quote;
	}

	protected function _mockMultiShipNotSameAsBill()
	{
		$store = Mage::app()->getStore();
		$product = $this->_buildModelMock('catalog/product', array(
			'isVirtual' => $this->returnValue(false), 
			'hasTaxCode' => $this->returnValue(true),
			'getTaxCode' => $this->returnValue('12345'),
		));

		// mock the items
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(4),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$item->setData(array('item_id' => 4, 'quote_id' => 2, 'created_at' => "2013-06-27 17:41:05", 'updated_at' => "2013-06-27 17:41:37", 'product_id' => 16, 'store_id' => 2, 'is_virtual' => 0, 'sku' => "n2610", 'name' => "Nokia 2610 Phone", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'no_discount' => 0, 'weight' => 3.2000, 'qty' => 3.0000, 'price' => 149.9900, 'base_price' => 149.9900, 'discount_percent' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'tax_percent' => 0.0000, 'tax_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'row_total' => 299.9800, 'base_row_total' => 299.9800, 'row_total_with_discount' => 0.0000, 'row_weight' => 6.4000, 'product_type' => "simple", 'weee_tax_applied' => "a:0:{}", 'weee_tax_applied_amount' => 0.0000, 'weee_tax_applied_row_amount' => 0.0000, 'base_weee_tax_applied_amount' => 0.0000, 'weee_tax_disposition' => 0.0000, 'weee_tax_row_disposition' => 0.0000, 'base_weee_tax_disposition' => 0.0000, 'base_weee_tax_row_disposition' => 0.0000, 'base_cost' => 20.0000, 'price_incl_tax' => 149.9900, 'base_price_incl_tax' => 149.9900, 'row_total_incl_tax' => 299.9800, 'base_row_total_incl_tax' => 299.9800, ));
		$items = array($item);

		// mock the address items
		$addressItem1 = $this->_buildModelMock('sales/quote_address_item', array(
			'getId'          => $this->returnValue(5),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$addressItem1->setData(array('address_item_id' => 5, 'quote_address_id' => 9, 'quote_item_id' => 4, 'created_at' => "2013-06-27 17:43:32", 'updated_at' => "2013-06-27 17:45:05", 'weight' => 3.2000, 'qty' => 2.0000, 'discount_amount' => 0.0000, 'tax_amount' => 0.0000, 'row_total' => 149.9900, 'base_row_total' => 149.9900, 'row_total_with_discount' => 0.0000, 'base_discount_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'row_weight' => 3.2000, 'product_id' => 16, 'sku' => "n2610", 'name' => "Nokia 2610 Phone", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'price' => 149.9900, 'discount_percent' => 0.0000, 'tax_percent' => 0.0000, 'base_price' => 149.9900, 'price_incl_tax' => 149.9900, 'base_price_incl_tax' => 149.9900, 'row_total_incl_tax' => 149.9900, 'base_row_total_incl_tax' => 149.9900,));

		$addressItem2 = $this->_buildModelMock('sales/quote_address_item', array(
			'getId'          => $this->returnValue(6),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
		$addressItem2->setData(array('address_item_id' => 6, 'quote_address_id' => 10, 'quote_item_id' => 4, 'created_at' => "2013-06-27 17:43:32", 'updated_at' => "2013-06-27 17:45:05", 'weight' => 3.2000, 'qty' => 1.0000, 'discount_amount' => 0.0000, 'tax_amount' => 12.3700, 'row_total' => 149.9900, 'base_row_total' => 149.9900, 'row_total_with_discount' => 0.0000, 'base_discount_amount' => 0.0000, 'base_tax_amount' => 12.3700, 'row_weight' => 3.2000, 'product_id' => 16, 'sku' => "n2610", 'name' => "Nokia 2610 Phone", 'free_shipping' => 0, 'is_qty_decimal' => 0, 'price' => 149.9900, 'discount_percent' => 0.0000, 'tax_percent' => 8.2500, 'base_price' => 149.9900, 'price_incl_tax' => 162.3600, 'base_price_incl_tax' => 162.3600, 'row_total_incl_tax' => 162.3600, 'base_row_total_incl_tax' => 162.3600,));

		// mock the shipping address
		$shippingRate = new Varien_Object(array('method' => 'flatrate', 'code' => 'flatrate_flatrate'));
		$address1 = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(9),
			'getAllNonNominalItems'      => $this->returnValue(array($addressItem1)),
			'getGroupedAllShippingRates' => $this->returnValue(array('flatrate' => array($shippingRate))),
		));
		$address1->setData(array('address_id' => 9, 'quote_id' => 2, 'created_at' => "2013-06-27 17:43:32", 'updated_at' => "2013-06-27 17:45:05", 'customer_id' => 5, 'save_in_address_book' => 0, 'customer_address_id' => 4, 'address_type' => "shipping", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 1, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'shipping_method' => "flatrate_flatrate", 'shipping_description' => "Flat Rate - Fixed", 'weight' => 3.2000, 'subtotal' => 149.9900, 'base_subtotal' => 149.9900, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'shipping_amount' => 5.0000, 'base_shipping_amount' => 5.0000, 'shipping_tax_amount' => 0.0000, 'base_shipping_tax_amount' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'grand_total' => 154.9900, 'base_grand_total' => 154.9900, 'applied_taxes' => "a:0:{}", 'base_customer_balance_amount' => 0.0000, 'customer_balance_amount' => 0.0000, 'gift_cards_amount' => 0.0000, 'base_gift_cards_amount' => 0.0000, 'gift_cards' => "a:0:{}", 'used_gift_cards' => "a:0:{}", 'shipping_discount_amount' => 0.0000, 'base_shipping_discount_amount' => 0.0000, 'subtotal_incl_tax' => 149.9900, 'hidden_tax_amount' => 0.0000, 'base_hidden_tax_amount' => 0.0000, 'shipping_hidden_tax_amount' => 0.0000, 'shipping_incl_tax' => 5.0000, 'base_shipping_incl_tax' => 5.0000, 'gw_base_price' => 0.0000, 'gw_price' => 0.0000, 'gw_items_base_price' => 0.0000, 'gw_items_price' => 0.0000, 'gw_card_base_price' => 0.0000, 'gw_card_price' => 0.0000, 'gw_base_tax_amount' => 0.0000, 'gw_tax_amount' => 0.0000, 'gw_items_base_tax_amount' => 0.0000, 'gw_items_tax_amount' => 0.0000, 'gw_card_base_tax_amount' => 0.0000, 'gw_card_tax_amount' => 0.0000, 'reward_points_balance' => 0, 'base_reward_currency_amount' => 0.0000, 'reward_currency_amount' => 0.0000,));

		$address2 = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(10),
			'getAllNonNominalItems'      => $this->returnValue(array($addressItem2)),
			'getGroupedAllShippingRates' => $this->returnValue(array('flatrate' => array($shippingRate))),
		));
		$address2->setData(array('address_id' => 10, 'quote_id' => 2, 'created_at' => "2013-06-27 17:43:32", 'updated_at' => "2013-06-27 17:45:05", 'customer_id' => 5, 'save_in_address_book' => 0, 'customer_address_id' => 5, 'address_type' => "shipping", 'email' => "foo@example.com", 'firstname' => "extra", 'lastname' => "guy", 'street' => "1 Shields", 'city' => "davis", 'region' => "California", 'region_id' => 12, 'postcode' => 90210, 'country_id' => "US", 'telephone' => 1234567890, 'same_as_billing' => 1, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'shipping_method' => "flatrate_flatrate", 'shipping_description' => "Flat Rate - Fixed", 'weight' => 3.2000, 'subtotal' => 149.9900, 'base_subtotal' => 149.9900, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 12.3700, 'base_tax_amount' => 12.3700, 'shipping_amount' => 5.0000, 'base_shipping_amount' => 5.0000, 'shipping_tax_amount' => 0.0000, 'base_shipping_tax_amount' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'grand_total' => 167.3600, 'base_grand_total' => 167.3600, 'applied_taxes' => 'a:1:{s:14:\"US-CA-*-Rate 1\";a:6:{s:5:\"rates\";a:1:{i:0;a:6:{s:4:\"code\";s:14:\"US-CA-*-Rate 1\";s:5:\"title\";s:14:\"US-CA-*-Rate 1\";s:7:\"percent\";d:8.25;s:8:\"position\";s:1:\"1\";s:8:\"priority\";s:1:\"1\";s:7:\"rule_id\";s:1:\"1\";}}s:7:\"percent\";d:8.25;s:2:\"id\";s:14:\"US-CA-*-Rate 1\";s:7:\"process\";i:0;s:6:\"amount\";d:12.369999999999999;s:11:\"base_amount\";d:12.369999999999999;}}', 'base_customer_balance_amount' => 0.0000, 'customer_balance_amount' => 0.0000, 'gift_cards_amount' => 0.0000, 'base_gift_cards_amount' => 0.0000, 'gift_cards' => "a:0:{}", 'used_gift_cards' => "a:0:{}", 'shipping_discount_amount' => 0.0000, 'base_shipping_discount_amount' => 0.0000, 'subtotal_incl_tax' => 162.3600, 'hidden_tax_amount' => 0.0000, 'base_hidden_tax_amount' => 0.0000, 'shipping_hidden_tax_amount' => 0.0000, 'shipping_incl_tax' => 5.0000, 'base_shipping_incl_tax' => 5.0000, 'gw_base_price' => 0.0000, 'gw_price' => 0.0000, 'gw_items_base_price' => 0.0000, 'gw_items_price' => 0.0000, 'gw_card_base_price' => 0.0000, 'gw_card_price' => 0.0000, 'gw_base_tax_amount' => 0.0000, 'gw_tax_amount' => 0.0000, 'gw_items_base_tax_amount' => 0.0000, 'gw_items_tax_amount' => 0.0000, 'gw_card_base_tax_amount' => 0.0000, 'gw_card_tax_amount' => 0.0000,));

		// mock the billing addresses
		$address3 = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(11),
			'getAllNonNominalItems'      => $this->returnValue(array()),
			'getGroupedAllShippingRates' => $this->returnValue(array()),
		));
		$address3->setData(array('address_id' => 11, 'quote_id' => 2, 'created_at' => "2013-06-27 17:43:32", 'updated_at' => "2013-06-27 17:45:05", 'customer_id' => 5, 'save_in_address_book' => 0, 'customer_address_id' => 4, 'address_type' => "billing", 'email' => "foo@example.com", 'firstname' => "test", 'lastname' => "guy", 'street' => "1 Rosedale St", 'city' => "Baltimore", 'region' => "Maryland", 'region_id' => 31, 'postcode' => 21229, 'country_id' => "US", 'telephone' => "(123) 456-7890", 'same_as_billing' => 0, 'free_shipping' => 0, 'collect_shipping_rates' => 0, 'weight' => 0.0000, 'subtotal' => 0.0000, 'base_subtotal' => 0.0000, 'subtotal_with_discount' => 0.0000, 'base_subtotal_with_discount' => 0.0000, 'tax_amount' => 0.0000, 'base_tax_amount' => 0.0000, 'shipping_amount' => 0.0000, 'base_shipping_amount' => 0.0000, 'shipping_tax_amount' => 0.0000, 'base_shipping_tax_amount' => 0.0000, 'discount_amount' => 0.0000, 'base_discount_amount' => 0.0000, 'grand_total' => 0.0000, 'base_grand_total' => 0.0000, 'applied_taxes' => "a:0:{}", 'base_customer_balance_amount' => 0.0000, 'customer_balance_amount' => 0.0000, 'gift_cards_amount' => 0.0000, 'base_gift_cards_amount' => 0.0000, 'gift_cards' => "a:0:{}", 'used_gift_cards' => "a:0:{}", 'subtotal_incl_tax' => 0.0000, 'shipping_incl_tax' => 0.0000, 'base_shipping_incl_tax' => 0.0000, ));

		// mock the quote
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'              => $this->returnValue(2),
			'isVirtual'          => $this->returnValue(false),
			'getStore'           => $this->returnValue($store),
			'getBillingAddress'  => $this->returnValue($address3),
			'getShippingAddress' => $this->returnValue($address1),
			'getAllAddresses'    => $this->returnValue(array($address1, $address2, $address3)),
			'getAllShippingAddresses' => $this->returnValue(array($address1, $address2)),
			'getAllVisibleItems' => $this->returnValue($items),
			'getItemById'        => $this->returnValueMap(array(
				array(4, $item),
			))
		));
		$quote->setData(array('entity_id' => 2, 'store_id' => 2, 'created_at' => "2013-06-27 17:41:05", 'updated_at' => "2013-06-27 17:45:05", 'is_active' => 0, 'is_virtual' => 0, 'is_multi_shipping' => 1, 'items_count' => 1, 'items_qty' => 2.0000, 'orig_order_id' => 0, 'store_to_base_rate' => 1.0000, 'store_to_quote_rate' => 1.0000, 'base_to_global_rate' => 1.0000, 'base_to_quote_rate' => 1.0000, 'global_currency_code' => "USD", 'base_currency_code' => "USD", 'store_currency_code' => "USD", 'quote_currency_code' => "USD", 'grand_total' => 322.3500, 'base_grand_total' => 322.3500, 'customer_id' => 5, 'customer_tax_class_id' => 3, 'customer_group_id' => 1, 'customer_email' => "foo@example.com", 'customer_firstname' => "test", 'customer_lastname' => "guy", 'customer_note_notify' => 1, 'customer_is_guest' => 0, 'remote_ip' => "192.168.56.1", 'reserved_order_id' => 100000052, 'subtotal' => 299.9800, 'base_subtotal' => 299.9800, 'subtotal_with_discount' => 299.9800, 'base_subtotal_with_discount' => 299.9800, 'trigger_recollect' => 0, ));
		return $quote;
	}



}
