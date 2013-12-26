<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2cTax_Test_Model_RequestTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * @var Mage_Sales_Model_Quote (mock)
	 */
	public $quote = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	public $shipAddress = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	public $billAddress = null;

	/**
	 * @var ReflectionProperty(TrueAction_Eb2cTax_Model_Request::_xml)
	 */
	public $doc = null;

	/**
	 * @var ReflectionClass(TrueAction_Eb2cTax_Model_Request)
	 */
	public static $cls = null;

	/**
	 * path to the xsd file to validate against.
	 * @var string
	 */
	public static $xsdFile = '';

	public static $namespaceUri = 'http://api.gsicommerce.com/schema/checkout/1.0';

	public static $validShipFromAddress = array(
		'Line1' => '1 n rosedale st',
		'City' => 'baltimore',
		'MainDivision' => 'MD',
		'CountryCode' => 'US',
		'PostalCode' => '21229',
	);

	public $tdRequest    = null;
	public $destinations = null;
	public $shipGroups   = null;

	public static function setUpBeforeClass()
	{
		self::$xsdFile = __DIR__ . '/RequestTest/fixtures/TaxDutyFee-QuoteRequest-1.0.xsd';
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
	 * verify if any important part of the shipping address is missing
	 * @dataProvider dataProvider
	 * @loadExpectation
	 */
	public function testExtractShippingDataExceptions($expectation)
	{
		$addrData = $this->expected($expectation)->getData();
		$this->setExpectedException(
			'Mage_Core_Exception',
			'unable to extract Line1, City, and CountryCode parts of the ship from address'
		);
		$data = new Mage_Sales_Model_Quote_Item();
		$data->setData($addrData);
		$request = $this->getModelMockBuilder('eb2ctax/request')
			->disableOriginalConstructor()
			->getMock();
		$this->_reflectMethod($request, '_extractShippingData')->invoke($request, $data);
	}

	/**
	 * verify extracted data causes an exception when required fields have incorrect length
	 * @dataProvider dataProvider
	 */
	public function testExtractDestDataException($function, $value='', $isVirtual=false)
	{
		$this->setExpectedException('Mage_Core_Exception');
		$address = $this->_stubSimpleAddress();
		$address->$function($value);
		$request = $this->getModelMockBuilder('eb2ctax/request')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->_reflectMethod($request, '_extractDestData')
			->invoke($request, $address, $isVirtual);
	}

	/**
	 * Mock the core store model with the given code and id
	 * @param  string  $code Expected store code
	 * @param  integer $id   Expected store id
	 * @return Mock_Mage_Core_Model_Store
	 */
	protected function _stubStore($code='usa', $id=0)
	{
		$store = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getStoreCode', 'getId'))
			->getMock();
		$store->expects($this->any())
			->method('getStoreCode')
			->will($this->returnValue($code));
		$store->expects($this->any())
			->method('getId')
			->will($this->returnValue($id));
		return $store;
	}

	/**
	 * Create a simple address mock object
	 * @param   Mage_Sales_Model_Quote_Item[] $items array of non-nominal items for the address
	 * @return  Mock_Mage_Sales_Model_Quote_Address
	 */
	protected function _stubSimpleAddress($items=array())
	{
		return $this->_buildModelMock('sales/quote_address', array(
			'getId' => $this->returnValue(1),
			'getAllNonNominalItems' => $this->returnValue($items),
		))->setData(array(
			'address_id'                  => 1,
			'quote_id'                    => 1,
			'customer_id'                 => 5,
			'save_in_address_book'        => 1,
			'customer_address_id'         => 4,
			'qty'                         => 1.000,
			'address_type'                => 'billing',
			'email'                       => 'foo@example.com',
			'firstname'                   => 'test',
			'lastname'                    => 'guy',
			'street'                      => '1 Rosedale St',
			'city'                        => 'Baltimore',
			'region'                      => 'Maryland',
			'region_id'                   => 31,
			'postcode'                    => 21229,
			'country_id'                  => 'US',
			'telephone'                   => '(123) 456-7890',
			'same_as_billing'             => 0,
			'free_shipping'               => 0,
			'collect_shipping_rates'      => 0,
			'weight'                      => 0.0000,
			'subtotal'                    => 0.0000,
			'base_subtotal'               => 0.0000,
			'subtotal_with_discount'      => 0.0000,
			'base_subtotal_with_discount' => 0.0000,
			'tax_amount'                  => 0.0000,
		));
	}

	/**
	 * Mock a product model
	 * @param  boolean $isVirtual Is the product a virtual product or not
	 * @param  string  $taxCode   Product tax code
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	protected function _stubProduct($isVirtual=false, $taxCode=null)
	{
		return $this->_buildModelMock('catalog/product', array(
			'isVirtual'  => $this->returnValue($isVirtual),
			'hasTaxCode' => $this->returnValue(!is_null($taxCode)),
			'getTaxCode' => $this->returnValue($taxCode),
		));
	}

	/**
	 * Mock a sales quote item
	 * @param  integer                         $totalQty           Expected quantity of the item
	 * @param  Mage_Catalog_Model_Product      $product            Item product
	 * @param  integer                         $id                 Expected id of the item
	 * @param  string                          $sku                Expected sku of the item
	 * @param  Mage_Sales_Model_Quote_Item[]   $children           Array of child items
	 * @param  boolean                         $childrenCalculated Are children of this item calculated
	 * @param  integer                         $discountAmt        Expected discount amount of the item
	 * @return Mock_Mage_Sales_Model_Quote_Item                    The stubbed quote item.
	 */
	protected function _stubQuoteItem(
		$product=null, $totalQty=1, $id=1, $sku='12345',
		$children=array(), $childrenCalculated=false, $discountAmt=null,
		$shipFromData=null
	)
	{
		if (is_null($shipFromData)) {
			$shipFromData = self::$validShipFromAddress;
		}
		return $this->_buildModelMock('sales/quote_item', array(
			'getId'                              => $this->returnValue($id),
			'getSku'                             => $this->returnValue($sku),
			'getProduct'                         => $this->returnValue($product),
			'getTotalQty'                        => $this->returnValue($totalQty),
			'getHasChildren'                     => $this->returnValue(!empty($children)),
			'getChildren'                        => $this->returnValue($children),
			'isChildrenCalculated'               => $this->returnValue($childrenCalculated),
			'getDiscountAmount'                  => $this->returnValue($discountAmt),
			'getEb2cShipFromAddressLine1'        => $this->returnValue(isset($shipFromData['Line1']) ? $shipFromData['Line1'] : null),
			'getEb2cShipFromAddressLine2'        => $this->returnValue(isset($shipFromData['Line2']) ? $shipFromData['Line2'] : null),
			'getEb2cShipFromAddressLine3'        => $this->returnValue(isset($shipFromData['Line3']) ? $shipFromData['Line3'] : null),
			'getEb2cShipFromAddressLine4'        => $this->returnValue(isset($shipFromData['Line4']) ? $shipFromData['Line4'] : null),
			'getEb2cShipFromAddressCity'         => $this->returnValue(isset($shipFromData['City']) ? $shipFromData['City'] : null),
			'getEb2cShipFromAddressMainDivision' => $this->returnValue(isset($shipFromData['MainDivision']) ? $shipFromData['MainDivision'] : null),
			'getEb2cShipFromAddressCountryCode'  => $this->returnValue(isset($shipFromData['CountryCode']) ? $shipFromData['CountryCode'] : null),
			'getEb2cShipFromAddressPostalCode'   => $this->returnValue(isset($shipFromData['PostalCode']) ? $shipFromData['PostalCode'] : null),
		));
	}

	/**
	 * Mock a quote model with a virtual product, parent and child item and a single address
	 * @return Mock_Mage_Sales_Model_Quote
	 */
	protected function _stubVirtualQuote()
	{
		$product   = $this->_stubProduct(true);
		$childItem = $this->_stubQuoteItem($product, 1, 2);
		$item      = $this->_stubQuoteItem($product, 1, 1, 'parent', array($childItem), true);
		$address   = $this->_stubSimpleAddress(array($item));

		$mockQuote = $this->_buildModelMock('sales/quote', array(
			'getId'                   => $this->returnValue(1),
			'isVirtual'               => $this->returnValue(1),
			'getStore'                => $this->returnValue($this->_stubStore()),
			'getBillingAddress'       => $this->returnValue($address),
			'getAllAddresses'         => $this->returnValue(array($address)),
			'getAllShippingAddresses' => $this->returnValue(array()),
			'getItemById'             => $this->returnValueMap(array(array(1, $item), array(2, $childItem))),
			'load'                    => $this->returnSelf(),
		));
		$mockQuote->setData(array(
			'entity_id'             => 1,
			'store_id'              => 2,
			'is_active'             => 0,
			'is_virtual'            => 1,
			'is_multi_shipping'     => 0,
			'items_count'           => 1,
			'items_qty'             => 1.0000,
			'orig_order_id'         => 0,
			'store_to_base_rate'    => 1.0000,
			'store_to_quote_rate'   => 1.0000,
			'base_to_global_rate'   => 1.0000,
			'base_to_quote_rate'    => 1.0000,
			'global_currency_code'  => 'USD',
			'base_currency_code'    => 'USD',
			'store_currency_code'   => 'USD',
			'quote_currency_code'   => 'USD',
			'customer_id'           => 5,
			'customer_tax_class_id' => 3,
			'customer_group_id'     => 1,
			'customer_email'        => 'foo@example.com',
			'customer_firstname'    => 'test',
			'customer_lastname'     => 'guy',
			'customer_note_notify'  => 1,
			'customer_is_guest'     => 0,
			'remote_ip'             => '192.168.56.1',
			'reserved_order_id'     => 100000050,
			'is_changed'            => 1,
			'trigger_recollect'     => 0,
			'is_persistent'         => 0,
		));
		$this->replaceByMock('model', 'sales/quote', $mockQuote);
		return $mockQuote;
	}

	/**
	 * [mockVirtualQuote description]
	 * @return [type] [description]
	 */
	protected function _stubQuoteWithSku($sku)
	{
		$item    = $this->_stubQuoteItem($this->_stubProduct(true), 1, 1, $sku);
		$address = $this->_stubSimpleAddress(array($item));

		$mockQuote = $this->_buildModelMock('sales/quote', array(
			'getId'                   => $this->returnValue(1),
			'isVirtual'               => $this->returnValue(1),
			'getStore'                => $this->returnValue($this->_stubStore()),
			'getBillingAddress'       => $this->returnValue($address),
			'getAllAddresses'         => $this->returnValue(array($address)),
			'getAllShippingAddresses' => $this->returnValue(array()),
			'getItemById'             => $this->returnValueMap(array(array(1, $item))),
			'getCouponCode'           => $this->returnValue(''),
			'load'                    => $this->returnSelf(),
		));
		$mockQuote->setData(array(
			'entity_id'             => 1,
			'store_id'              => 2,
			'is_active'             => 0,
			'is_virtual'            => 1,
			'is_multi_shipping'     => 0,
			'items_count'           => 1,
			'items_qty'             => 1.0000,
			'orig_order_id'         => 0,
			'store_to_base_rate'    => 1.0000,
			'store_to_quote_rate'   => 1.0000,
			'base_to_global_rate'   => 1.0000,
			'base_to_quote_rate'    => 1.0000,
			'global_currency_code'  => 'USD',
			'base_currency_code'    => 'USD',
			'store_currency_code'   => 'USD',
			'quote_currency_code'   => 'USD',
			'customer_id'           => 5,
			'customer_tax_class_id' => 3,
			'customer_group_id'     => 1,
			'customer_email'        => 'foo@example.com',
			'customer_firstname'    => 'test',
			'customer_lastname'     => 'guy',
			'customer_note_notify'  => 1,
			'customer_is_guest'     => 0,
			'remote_ip'             => '192.168.56.1',
			'reserved_order_id'     => 100000050,
			'is_changed'            => 1,
			'trigger_recollect'     => 0,
			'is_persistent'         => 0,
		));
		$this->replaceByMock('model', 'sales/quote', $mockQuote);
		return $mockQuote;
	}

	/**
	 * [mockVirtualQuote description]
	 * @return [type] [description]
	 */
	protected function _stubSingleShipSameAsBill()
	{
		$store = $this->_stubStore();
		$product = $this->_stubProduct();
		// mock the items
		$itemA = $this->_stubQuoteItem($product, 1, 1, 1111);
		$itemA->setData(array(
			'item_id'                 => 1,
			'quote_id'                => 1,
			'product_id'              => 51,
			'store_id'                => 2,
			'is_virtual'              => 0,
			'sku'                     => 1111,
			'name'                    => 'Ottoman',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 20.0000,
			'qty'                     => 1.0000,
			'price'                   => 299.9900,
			'base_price'              => 299.9900,
			'row_total'               => 299.9900,
			'base_row_total'          => 299.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 20.0000,
			'product_type'            => 'simple',
			'base_cost'               => 50.0000,
			'price_incl_tax'          => 299.9900,
			'base_price_incl_tax'     => 299.9900,
			'row_total_incl_tax'      => 299.9900,
			'base_row_total_incl_tax' => 299.9900,
		));

		$itemB = $this->_stubQuoteItem($product, 1, 2, 1112);
		$itemB->setData(array(
			'item_id'                 => 2,
			'quote_id'                => 1,
			'product_id'              => 52,
			'store_id'                => 2,
			'is_virtual'              => 0,
			'sku'                     => 1112,
			'name'                    => 'Chair',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 50.0000,
			'qty'                     => 1.0000,
			'price'                   => 129.9900,
			'base_price'              => 129.9900,
			'row_total'               => 129.9900,
			'base_row_total'          => 129.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 50.0000,
			'product_type'            => 'simple',
			'base_cost'               => 50.0000,
			'price_incl_tax'          => 129.9900,
			'base_price_incl_tax'     => 129.9900,
			'row_total_incl_tax'      => 129.9900,
			'base_row_total_incl_tax' => 129.9900,
		));

		$itemC = $this->_stubQuoteItem($product, 1, 3, 1113);
		$itemC->setData(array(
			'item_id' => 3,
			'quote_id'                => 1,
			'product_id'              => 53,
			'store_id'                => 2,
			'is_virtual'              => 0,
			'sku'                     => 1113,
			'name'                    => 'Couch',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 200.0000,
			'qty'                     => 1.0000,
			'price'                   => 599.9900,
			'base_price'              => 599.9900,
			'row_total'               => 599.9900,
			'base_row_total'          => 599.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 200.0000,
			'product_type'            => 'simple',
			'base_cost'               => 200.0000,
			'price_incl_tax'          => 599.9900,
			'base_price_incl_tax'     => 599.9900,
			'row_total_incl_tax'      => 599.9900,
			'base_row_total_incl_tax' => 599.9900,
		));
		$items = array($itemA, $itemB, $itemC);

		// mock the billing addresses
		$addressA = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(1),
			'getAllNonNominalItems'      => $this->returnValue(array()),
			'getGroupedAllShippingRates' => $this->returnValue(array()),
		));
		$addressA->setData(array(
			'address_id'                  => 1,
			'quote_id'                    => 1,
			'customer_id'                 => 5,
			'save_in_address_book'        => 1,
			'customer_address_id'         => 4,
			'address_type'                => 'billing',
			'email'                       => 'foo@example.com',
			'firstname'                   => 'test',
			'prefix'                      => 'Mr.',
			'middlename'                  => 'mid',
			'lastname'                    => 'guy',
			'street'                      => '1 Rosedale St',
			'city'                        => 'Baltimore',
			'region'                      => 'Maryland',
			'region_id'                   => 31,
			'postcode'                    => 21229,
			'country_id'                  => 'US',
			'telephone'                   => '(123) 456-7890',
			'same_as_billing'             => 0,
			'free_shipping'               => 0,
			'collect_shipping_rates'      => 0,
			'weight'                      => 0.0000,
			'subtotal'                    => 0.0000,
			'base_subtotal'               => 0.0000,
			'subtotal_with_discount'      => 0.0000,
			'base_subtotal_with_discount' => 0.0000,
			'tax_amount'                  => 0.0000,
			'base_tax_amount'             => 0.0000,
			'shipping_amount'             => 0.0000,
			'base_shipping_amount'        => 0.0000,
			'shipping_tax_amount'         => 0.0000,
			'base_shipping_tax_amount'    => 0.0000,
			'discount_amount'             => 0.0000,
			'base_discount_amount'        => 0.0000,
			'grand_total'                 => 0.0000,
			'base_grand_total'            => 0.0000,
			'applied_taxes'               => 'a:0:{}',
			'subtotal_incl_tax'           => 0.0000,
			'shipping_incl_tax'           => 0.0000,
			'base_shipping_incl_tax'      => 0.0000,
		));

		// mock the shipping address
		$shippingRate = new Varien_Object(array('method' => 'flatrate', 'code' => 'flatrate_flatrate'));
		$addressB = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(2),
			'getAllNonNominalItems'      => $this->returnValue($items),
			'getGroupedAllShippingRates' => $this->returnValue(array('flatrate' => array($shippingRate))),
		));
		$addressB->setData(array(
			'address_id'                    => 2,
			'quote_id'                      => 1,
			'customer_id'                   => 5,
			'save_in_address_book'          => 0,
			'address_type'                  => 'shipping',
			'email'                         => 'foo@example.com',
			'firstname'                     => 'test',
			'prefix'                        => 'Mr.',
			'middlename'                    => 'mid',
			'lastname'                      => 'guy',
			'street'                        => '1 Rosedale St',
			'city'                          => 'Baltimore',
			'region'                        => 'Maryland',
			'region_id'                     => 31,
			'postcode'                      => 21229,
			'country_id'                    => 'US',
			'telephone'                     => '(123) 456-7890',
			'same_as_billing'               => 1,
			'free_shipping'                 => 0,
			'collect_shipping_rates'        => 0,
			'shipping_method'               => 'flatrate_flatrate',
			'shipping_description'          => 'Flat Rate - Fixed',
			'weight'                        => 270.0000,
			'subtotal'                      => 1029.9700,
			'base_subtotal'                 => 1029.9700,
			'subtotal_with_discount'        => 0.0000,
			'base_subtotal_with_discount'   => 0.0000,
			'tax_amount'                    => 0.0000,
			'base_tax_amount'               => 0.0000,
			'shipping_amount'               => 15.0000,
			'base_shipping_amount'          => 15.0000,
			'shipping_tax_amount'           => 0.0000,
			'base_shipping_tax_amount'      => 0.0000,
			'discount_amount'               => 0.0000,
			'base_discount_amount'          => 0.0000,
			'grand_total'                   => 1044.9700,
			'base_grand_total'              => 1044.9700,
			'applied_taxes'                 => 'a:0:{}',
			'shipping_discount_amount'      => 0.0000,
			'base_shipping_discount_amount' => 0.0000,
			'subtotal_incl_tax'             => 1029.9700,
			'hidden_tax_amount'             => 0.0000,
			'base_hidden_tax_amount'        => 0.0000,
			'shipping_hidden_tax_amount'    => 0.0000,
			'shipping_incl_tax'             => 15.0000,
			'base_shipping_incl_tax'        => 15.0000,
		));

		// mock the quote
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                   => $this->returnValue(1),
			'load'                    => $this->returnSelf(),
			'isVirtual'               => $this->returnValue(false),
			'getStore'                => $this->returnValue($store),
			'getBillingAddress'       => $this->returnValue($addressA),
			'getShippingAddress'      => $this->returnValue($addressB),
			'getAllAddresses'         => $this->returnValue(array($addressA, $addressB)),
			'getAllShippingAddresses' => $this->returnValue(array($addressB)),
			'getAllVisibleItems'      => $this->returnValue($items),
			'getItemById'             => $this->returnValueMap(array(
				array(1, $itemA),
				array(2, $itemB),
				array(3, $itemC),
			)),
		));
		$quote->setData(array(
			'entity_id'                   => 1,
			'store_id'                    => 0,
			'created_at'                  => '2013-06-27 17:32:54',
			'updated_at'                  => '2013-06-27 17:36:19',
			'is_active'                   => 0,
			'is_virtual'                  => 0,
			'is_multi_shipping'           => 0,
			'items_count'                 => 3,
			'items_qty'                   => 3.0000,
			'orig_order_id'               => 0,
			'store_to_base_rate'          => 1.0000,
			'store_to_quote_rate'         => 1.0000,
			'base_to_global_rate'         => 1.0000,
			'base_to_quote_rate'          => 1.0000,
			'global_currency_code'        => 'USD',
			'base_currency_code'          => 'USD',
			'store_currency_code'         => 'USD',
			'quote_currency_code'         => 'USD',
			'grand_total'                 => 1044.9700,
			'base_grand_total'            => 1044.9700,
			'customer_id'                 => 5,
			'customer_tax_class_id'       => 3,
			'customer_group_id'           => 1,
			'customer_email'              => 'foo@example.com',
			'customer_firstname'          => 'test',
			'customer_lastname'           => 'guy',
			'customer_note_notify'        => 1,
			'customer_is_guest'           => 0,
			'remote_ip'                   => '192.168.56.1',
			'reserved_order_id'           => 100000050,
			'subtotal'                    => 1029.9700,
			'base_subtotal'               => 1029.9700,
			'subtotal_with_discount'      => 1029.9700,
			'base_subtotal_with_discount' => 1029.9700,
			'is_changed'                  => 1,
			'trigger_recollect'           => 0,
			'is_persistent'               => 0,
		));
		$this->replaceByMock('model', 'sales/quote', $quote);
		return $quote;
	}

	/**
	 * [mockVirtualQuote description]
	 * @return [type] [description]
	 */
	protected function _stubSingleShipVirtual()
	{
		$store = $this->_stubStore();
		$product = $this->_stubProduct(true);

		// mock the items

		$itemA = $this->_stubQuoteItem($product, 1, 1, 1111);
		$itemA->setData(array(
			'item_id'                 => 1,
			'quote_id'                => 1,
			'product_id'              => 51,
			'store_id'                => 2,
			'is_virtual'              => 1,
			'sku'                     => 1111,
			'name'                    => 'Ottoman',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 20.0000,
			'qty'                     => 1.0000,
			'price'                   => 299.9900,
			'base_price'              => 299.9900,
			'row_total'               => 299.9900,
			'base_row_total'          => 299.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 20.0000,
			'product_type'            => 'simple',
			'base_cost'               => 50.0000,
			'price_incl_tax'          => 299.9900,
			'base_price_incl_tax'     => 299.9900,
			'row_total_incl_tax'      => 299.9900,
			'base_row_total_incl_tax' => 299.9900,
		));

		$itemB = $this->_stubQuoteItem($product, 1, 2, 1112);
		$itemB->setData(array(
			'item_id'                 => 2,
			'quote_id'                => 1,
			'product_id'              => 52,
			'store_id'                => 2,
			'is_virtual'              => 1,
			'sku'                     => 1112,
			'name'                    => 'Chair',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 50.0000,
			'qty'                     => 1.0000,
			'price'                   => 129.9900,
			'base_price'              => 129.9900,
			'row_total'               => 129.9900,
			'base_row_total'          => 129.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 50.0000,
			'product_type'            => 'simple',
			'base_cost'               => 50.0000,
			'price_incl_tax'          => 129.9900,
			'base_price_incl_tax'     => 129.9900,
			'row_total_incl_tax'      => 129.9900,
			'base_row_total_incl_tax' => 129.9900,
		));
		$itemC = $this->_stubQuoteItem($product, 1, 1, 1113);
		$itemC->setData(array(
			'item_id'                 => 3,
			'quote_id'                => 1,
			'product_id'              => 53,
			'store_id'                => 2,
			'is_virtual'              => 1,
			'sku'                     => 1113,
			'name'                    => 'Couch',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 200.0000,
			'qty'                     => 1.0000,
			'price'                   => 599.9900,
			'base_price'              => 599.9900,
			'row_total'               => 599.9900,
			'base_row_total'          => 599.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 200.0000,
			'product_type'            => 'simple',
			'base_cost'               => 200.0000,
			'price_incl_tax'          => 599.9900,
			'base_price_incl_tax'     => 599.9900,
			'row_total_incl_tax'      => 599.9900,
			'base_row_total_incl_tax' => 599.9900,
		));
		$items = array($itemA, $itemB, $itemC);

		// mock the billing addresses
		$addressA = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(1),
			'getAllNonNominalItems'      => $this->returnValue($items),
			'getGroupedAllShippingRates' => $this->returnValue(array()),
		));
		$addressA->setData(array(
			'address_id'                  => 1,
			'quote_id'                    => 1,
			'customer_id'                 => 5,
			'save_in_address_book'        => 1,
			'customer_address_id'         => 4,
			'address_type'                => 'billing',
			'email'                       => 'foo@example.com',
			'firstname'                   => 'test',
			'lastname'                    => 'guy',
			'street'                      => '1 Rosedale St',
			'city'                        => 'Baltimore',
			'region'                      => 'Maryland',
			'region_id'                   => 31,
			'postcode'                    => 21229,
			'country_id'                  => 'US',
			'telephone'                   => '(123) 456-7890',
			'same_as_billing'             => 0,
			'free_shipping'               => 0,
			'collect_shipping_rates'      => 0,
			'weight'                      => 0.0000,
			'subtotal'                    => 0.0000,
			'base_subtotal'               => 0.0000,
			'subtotal_with_discount'      => 0.0000,
			'base_subtotal_with_discount' => 0.0000,
			'tax_amount'                  => 0.0000,
			'base_tax_amount'             => 0.0000,
			'shipping_amount'             => 0.0000,
			'base_shipping_amount'        => 0.0000,
			'shipping_tax_amount'         => 0.0000,
			'base_shipping_tax_amount'    => 0.0000,
			'discount_amount'             => 0.0000,
			'base_discount_amount'        => 0.0000,
			'grand_total'                 => 0.0000,
			'base_grand_total'            => 0.0000,
			'applied_taxes'               => 'a:0:{}',
			'subtotal_incl_tax'           => 0.0000,
			'shipping_incl_tax'           => 0.0000,
			'base_shipping_incl_tax'      => 0.0000,
		));

		// mock the quote
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                   => $this->returnValue(1),
			'load'                    => $this->returnSelf(),
			'isVirtual'               => $this->returnValue(true),
			'getStore'                => $this->returnValue($store),
			'getBillingAddress'       => $this->returnValue($addressA),
			'getAllAddresses'         => $this->returnValue(array($addressA)),
			'getAllShippingAddresses' => $this->returnValue(array()),
			'getAllVisibleItems'      => $this->returnValue($items),
			'getItemById'             => $this->returnValueMap(array(array(1,
			$itemA), array(2,
			$itemB), array(3,
			$itemC),
			))
		));
		$quote->setData(array(
			'entity_id'                   => 1,
			'store_id'                    => 0,
			'created_at'                  => '2013-06-27 17:32:54',
			'updated_at'                  => '2013-06-27 17:36:19',
			'is_active'                   => 0,
			'is_virtual'                  => 0,
			'is_multi_shipping'           => 0,
			'items_count'                 => 3,
			'items_qty'                   => 3.0000,
			'orig_order_id'               => 0,
			'store_to_base_rate'          => 1.0000,
			'store_to_quote_rate'         => 1.0000,
			'base_to_global_rate'         => 1.0000,
			'base_to_quote_rate'          => 1.0000,
			'global_currency_code'        => 'USD',
			'base_currency_code'          => 'USD',
			'store_currency_code'         => 'USD',
			'quote_currency_code'         => 'USD',
			'grand_total'                 => 1044.9700,
			'base_grand_total'            => 1044.9700,
			'customer_id'                 => 5,
			'customer_tax_class_id'       => 3,
			'customer_group_id'           => 1,
			'customer_email'              => 'foo@example.com',
			'customer_firstname'          => 'test',
			'customer_lastname'           => 'guy',
			'customer_note_notify'        => 1,
			'customer_is_guest'           => 0,
			'remote_ip'                   => '192.168.56.1',
			'reserved_order_id'           => 100000050,
			'subtotal'                    => 1029.9700,
			'base_subtotal'               => 1029.9700,
			'subtotal_with_discount'      => 1029.9700,
			'base_subtotal_with_discount' => 1029.9700,
			'is_changed'                  => 1,
			'trigger_recollect'           => 0,
			'is_persistent'               => 0,
		));
		$this->replaceByMock('model', 'sales/quote', $quote);
		return $quote;
	}

	/**
	 * [mockVirtualQuote description]
	 * @return [type] [description]
	 */
	protected function _stubSingleShipSameAsBillVirtualMix()
	{
		$store = $this->_stubStore();

		$vProduct = $this->_stubProduct(true);
		$product = $this->_stubProduct(false);

		// mock the items
		$itemA = $this->_stubQuoteItem($product, 1, 1, 1111);
		$itemA->setData(array(
			'item_id'                 => 1,
			'quote_id'                => 1,
			'product_id'              => 51,
			'store_id'                => 2,
			'is_virtual'              => 0,
			'sku'                     => 1111,
			'name'                    => 'Ottoman',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 20.0000,
			'qty'                     => 1.0000,
			'price'                   => 299.9900,
			'base_price'              => 299.9900,
			'row_total'               => 299.9900,
			'base_row_total'          => 299.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 20.0000,
			'product_type'            => 'simple',
			'base_cost'               => 50.0000,
			'price_incl_tax'          => 299.9900,
			'base_price_incl_tax'     => 299.9900,
			'row_total_incl_tax'      => 299.9900,
			'base_row_total_incl_tax' => 299.9900,
		));

		$itemB = $this->_stubQuoteItem($vProduct, 1, 2, 1112);
		$itemB->setData(array(
			'item_id'                 => 2,
			'quote_id'                => 1,
			'product_id'              => 52,
			'store_id'                => 2,
			'is_virtual'              => 1,
			'sku'                     => 1112,
			'name'                    => 'Chair',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 50.0000,
			'qty'                     => 1.0000,
			'price'                   => 129.9900,
			'base_price'              => 129.9900,
			'row_total'               => 129.9900,
			'base_row_total'          => 129.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 50.0000,
			'product_type'            => 'simple',
			'base_cost'               => 50.0000,
			'price_incl_tax'          => 129.9900,
			'base_price_incl_tax'     => 129.9900,
			'row_total_incl_tax'      => 129.9900,
			'base_row_total_incl_tax' => 129.9900,
		));

		$itemC = $this->_stubQuoteItem($product, 1, 3, 1113);
		$itemC->setData(array(
			'item_id'                 => 3,
			'quote_id'                => 1,
			'product_id'              => 53,
			'store_id'                => 2,
			'is_virtual'              => 0,
			'sku'                     => 1113,
			'name'                    => 'Couch',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'no_discount'             => 0,
			'weight'                  => 200.0000,
			'qty'                     => 1.0000,
			'price'                   => 599.9900,
			'base_price'              => 599.9900,
			'row_total'               => 599.9900,
			'base_row_total'          => 599.9900,
			'row_total_with_discount' => 0.0000,
			'row_weight'              => 200.0000,
			'product_type'            => 'simple',
			'base_cost'               => 200.0000,
			'price_incl_tax'          => 599.9900,
			'base_price_incl_tax'     => 599.9900,
			'row_total_incl_tax'      => 599.9900,
			'base_row_total_incl_tax' => 599.9900,
		));
		$items = array($itemA, $itemB, $itemC);

		// mock the billing addresses
		$addressA = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(1),
			'getAllNonNominalItems'      => $this->returnValue(array()),
			'getGroupedAllShippingRates' => $this->returnValue(array()),
		));
		$addressA->setData(array(
			'address_id'                  => 1,
			'quote_id'                    => 1,
			'customer_id'                 => 5,
			'save_in_address_book'        => 1,
			'customer_address_id'         => 4,
			'address_type'                => 'billing',
			'email'                       => 'foo@example.com',
			'firstname'                   => 'test',
			'lastname'                    => 'guy',
			'street'                      => '1 Rosedale St',
			'city'                        => 'Baltimore',
			'region'                      => 'Maryland',
			'region_id'                   => 31,
			'postcode'                    => 21229,
			'country_id'                  => 'US',
			'telephone'                   => '(123) 456-7890',
			'same_as_billing'             => 0,
			'free_shipping'               => 0,
			'collect_shipping_rates'      => 0,
			'weight'                      => 0.0000,
			'subtotal'                    => 0.0000,
			'base_subtotal'               => 0.0000,
			'subtotal_with_discount'      => 0.0000,
			'base_subtotal_with_discount' => 0.0000,
			'tax_amount'                  => 0.0000,
			'base_tax_amount'             => 0.0000,
			'shipping_amount'             => 0.0000,
			'base_shipping_amount'        => 0.0000,
			'shipping_tax_amount'         => 0.0000,
			'base_shipping_tax_amount'    => 0.0000,
			'discount_amount'             => 0.0000,
			'base_discount_amount'        => 0.0000,
			'grand_total'                 => 0.0000,
			'base_grand_total'            => 0.0000,
			'applied_taxes'               => 'a:0:{}',
			'subtotal_incl_tax'           => 0.0000,
			'shipping_incl_tax'           => 0.0000,
			'base_shipping_incl_tax'      => 0.0000,
		));

		// mock the shipping address
		$shippingRate = new Varien_Object(array('method' => 'flatrate', 'code' => 'flatrate_flatrate'));
		$addressB = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(2),
			'getAllNonNominalItems'      => $this->returnValue(array($itemA, $itemB, $itemC)),
			'getGroupedAllShippingRates' => $this->returnValue(array('flatrate' => array($shippingRate))),
		));
		$addressB->setData(array(
			'address_id'                    => 2,
			'quote_id'                      => 1,
			'customer_id'                   => 5,
			'save_in_address_book'          => 0,
			'address_type'                  => 'shipping',
			'email'                         => 'foo@example.com',
			'firstname'                     => 'test',
			'lastname'                      => 'guy',
			'street'                        => '1 Rosedale St',
			'city'                          => 'Baltimore',
			'region'                        => 'Maryland',
			'region_id'                     => 31,
			'postcode'                      => 21229,
			'country_id'                    => 'US',
			'telephone'                     => '(123) 456-7890',
			'same_as_billing'               => 1,
			'free_shipping'                 => 0,
			'collect_shipping_rates'        => 0,
			'shipping_method'               => 'flatrate_flatrate',
			'shipping_description'          => 'Flat Rate - Fixed',
			'weight'                        => 270.0000,
			'subtotal'                      => 1029.9700,
			'base_subtotal'                 => 1029.9700,
			'subtotal_with_discount'        => 0.0000,
			'base_subtotal_with_discount'   => 0.0000,
			'tax_amount'                    => 0.0000,
			'base_tax_amount'               => 0.0000,
			'shipping_amount'               => 15.0000,
			'base_shipping_amount'          => 15.0000,
			'shipping_tax_amount'           => 0.0000,
			'base_shipping_tax_amount'      => 0.0000,
			'discount_amount'               => 0.0000,
			'base_discount_amount'          => 0.0000,
			'grand_total'                   => 1044.9700,
			'base_grand_total'              => 1044.9700,
			'applied_taxes'                 => 'a:0:{}',
			'shipping_discount_amount'      => 0.0000,
			'base_shipping_discount_amount' => 0.0000,
			'subtotal_incl_tax'             => 1029.9700,
			'hidden_tax_amount'             => 0.0000,
			'base_hidden_tax_amount'        => 0.0000,
			'shipping_hidden_tax_amount'    => 0.0000,
			'shipping_incl_tax'             => 15.0000,
			'base_shipping_incl_tax'        => 15.0000,
		));

		// mock the quote
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                   => $this->returnValue(1),
			'load'                    => $this->returnSelf(),
			'isVirtual'               => $this->returnValue(false),
			'getStore'                => $this->returnValue($store),
			'getBillingAddress'       => $this->returnValue($addressA),
			'getShippingAddress'      => $this->returnValue($addressB),
			'getAllAddresses'         => $this->returnValue(array($addressA, $addressB)),
			'getAllShippingAddresses' => $this->returnValue(array($addressB)),
			'getAllVisibleItems'      => $this->returnValue($items),
			'getItemById'             => $this->returnValueMap(array(
				array(1, $itemA),
				array(2, $itemB),
				array(3, $itemC),
			))
		));
		$quote->setData(array(
			'entity_id'                   => 1,
			'store_id'                    => 0,
			'created_at'                  => '2013-06-27 17:32:54',
			'updated_at'                  => '2013-06-27 17:36:19',
			'is_active'                   => 0,
			'is_virtual'                  => 0,
			'is_multi_shipping'           => 0,
			'items_count'                 => 3,
			'items_qty'                   => 3.0000,
			'orig_order_id'               => 0,
			'store_to_base_rate'          => 1.0000,
			'store_to_quote_rate'         => 1.0000,
			'base_to_global_rate'         => 1.0000,
			'base_to_quote_rate'          => 1.0000,
			'global_currency_code'        => 'USD',
			'base_currency_code'          => 'USD',
			'store_currency_code'         => 'USD',
			'quote_currency_code'         => 'USD',
			'grand_total'                 => 1044.9700,
			'base_grand_total'            => 1044.9700,
			'customer_id'                 => 5,
			'customer_tax_class_id'       => 3,
			'customer_group_id'           => 1,
			'customer_email'              => 'foo@example.com',
			'customer_firstname'          => 'test',
			'customer_lastname'           => 'guy',
			'customer_note_notify'        => 1,
			'customer_is_guest'           => 0,
			'remote_ip'                   => '192.168.56.1',
			'reserved_order_id'           => 100000050,
			'subtotal'                    => 1029.9700,
			'base_subtotal'               => 1029.9700,
			'subtotal_with_discount'      => 1029.9700,
			'base_subtotal_with_discount' => 1029.9700,
			'is_changed'                  => 1,
			'trigger_recollect'           => 0,
			'is_persistent'               => 0,
		));
		$this->replaceByMock('model', 'sales/quote', $quote);
		return $quote;
	}

	/**
	 * [mockVirtualQuote description]
	 * @return [type] [description]
	 */
	protected function _stubMultiShipNotSameAsBill()
	{
		$store = $this->_stubStore();

		$product = $this->_stubProduct(false, '12345');

		// mock the items
		$item = $this->_stubQuoteItem($product, 3, 4, 'n2610');
		$item->setData(array(
			'item_id'                       => 4,
			'quote_id'                      => 2,
			'created_at'                    => '2013-06-27 17:41:05',
			'updated_at'                    => '2013-06-27 17:41:37',
			'product_id'                    => 16,
			'store_id'                      => 2,
			'is_virtual'                    => 0,
			'sku'                           => 'n2610',
			'name'                          => 'Nokia 2610 Phone',
			'free_shipping'                 => 0,
			'is_qty_decimal'                => 0,
			'no_discount'                   => 0,
			'weight'                        => 3.2000,
			'qty'                           => 3.0000,
			'price'                         => 149.9900,
			'base_price'                    => 149.9900,
			'discount_percent'              => 0.0000,
			'discount_amount'               => 0.0000,
			'base_discount_amount'          => 0.0000,
			'tax_percent'                   => 0.0000,
			'tax_amount'                    => 0.0000,
			'base_tax_amount'               => 0.0000,
			'row_total'                     => 299.9800,
			'base_row_total'                => 299.9800,
			'row_total_with_discount'       => 0.0000,
			'row_weight'                    => 6.4000,
			'product_type'                  => 'simple',
			'weee_tax_applied'              => 'a:0:{}',
			'weee_tax_applied_amount'       => 0.0000,
			'weee_tax_applied_row_amount'   => 0.0000,
			'base_weee_tax_applied_amount'  => 0.0000,
			'weee_tax_disposition'          => 0.0000,
			'weee_tax_row_disposition'      => 0.0000,
			'base_weee_tax_disposition'     => 0.0000,
			'base_weee_tax_row_disposition' => 0.0000,
			'base_cost'                     => 20.0000,
			'price_incl_tax'                => 149.9900,
			'base_price_incl_tax'           => 149.9900,
			'row_total_incl_tax'            => 299.9800,
			'base_row_total_incl_tax'       => 299.9800,
		));
		$items = array($item);

		// mock the address items
		$addressItemA = $this->_buildModelMock('sales/quote_address_item', array(
			'getId'          => $this->returnValue(5),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
			'getQuoteItem'   => $this->returnValue($item),
		));
		$addressItemA->setData(array(
			'address_item_id'         => 5,
			'quote_address_id'        => 9,
			'quote_item_id'           => 4,
			'created_at'              => '2013-06-27 17:43:32',
			'updated_at'              => '2013-06-27 17:45:05',
			'weight'                  => 3.2000,
			'qty'                     => 2.0000,
			'discount_amount'         => 0.0000,
			'tax_amount'              => 0.0000,
			'row_total'               => 149.9900,
			'base_row_total'          => 149.9900,
			'row_total_with_discount' => 0.0000,
			'base_discount_amount'    => 0.0000,
			'base_tax_amount'         => 0.0000,
			'row_weight'              => 3.2000,
			'product_id'              => 16,
			'sku'                     => 'n2610',
			'name'                    => 'Nokia 2610 Phone',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'price'                   => 149.9900,
			'discount_percent'        => 0.0000,
			'tax_percent'             => 0.0000,
			'base_price'              => 149.9900,
			'price_incl_tax'          => 149.9900,
			'base_price_incl_tax'     => 149.9900,
			'row_total_incl_tax'      => 149.9900,
			'base_row_total_incl_tax' => 149.9900,
		));

		$addressItemB = $this->_buildModelMock('sales/quote_address_item', array(
			'getId'          => $this->returnValue(6),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
			'getQuoteItem'   => $this->returnValue($item),
		));
		$addressItemB->setData(array(
			'address_item_id'         => 6,
			'quote_address_id'        => 10,
			'quote_item_id'           => 4,
			'created_at'              => '2013-06-27 17:43:32',
			'updated_at'              => '2013-06-27 17:45:05',
			'weight'                  => 3.2000,
			'qty'                     => 1.0000,
			'discount_amount'         => 0.0000,
			'tax_amount'              => 12.3700,
			'row_total'               => 149.9900,
			'base_row_total'          => 149.9900,
			'row_total_with_discount' => 0.0000,
			'base_discount_amount'    => 0.0000,
			'base_tax_amount'         => 12.3700,
			'row_weight'              => 3.2000,
			'product_id'              => 16,
			'sku'                     => 'n2610',
			'name'                    => 'Nokia 2610 Phone',
			'free_shipping'           => 0,
			'is_qty_decimal'          => 0,
			'price'                   => 149.9900,
			'discount_percent'        => 0.0000,
			'tax_percent'             => 8.2500,
			'base_price'              => 149.9900,
			'price_incl_tax'          => 162.3600,
			'base_price_incl_tax'     => 162.3600,
			'row_total_incl_tax'      => 162.3600,
			'base_row_total_incl_tax' => 162.3600,
		));

		// mock the shipping address
		$shippingRate = new Varien_Object(array('method' => 'flatrate', 'code' => 'flatrate_flatrate'));
		$addressA = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(9),
			'getAllNonNominalItems'      => $this->returnValue(array($addressItemA)),
			'getGroupedAllShippingRates' => $this->returnValue(array('flatrate' => array($shippingRate))),
		));
		$addressA->setData(array(
			'address_id'                    => 9,
			'quote_id'                      => 2,
			'created_at'                    => '2013-06-27 17:43:32',
			'updated_at'                    => '2013-06-27 17:45:05',
			'customer_id'                   => 5,
			'save_in_address_book'          => 0,
			'customer_address_id'           => 4,
			'address_type'                  => 'shipping',
			'email'                         => 'foo@example.com',
			'firstname'                     => 'test',
			'lastname'                      => 'guy',
			'street'                        => '1 Rosedale St',
			'city'                          => 'Baltimore',
			'region'                        => 'Maryland',
			'region_id'                     => 31,
			'postcode'                      => 21229,
			'country_id'                    => 'US',
			'telephone'                     => '(123) 456-7890',
			'same_as_billing'               => 1,
			'free_shipping'                 => 0,
			'collect_shipping_rates'        => 0,
			'shipping_method'               => 'flatrate_flatrate',
			'shipping_description'          => 'Flat Rate - Fixed',
			'weight'                        => 3.2000,
			'subtotal'                      => 149.9900,
			'base_subtotal'                 => 149.9900,
			'subtotal_with_discount'        => 0.0000,
			'base_subtotal_with_discount'   => 0.0000,
			'tax_amount'                    => 0.0000,
			'base_tax_amount'               => 0.0000,
			'shipping_amount'               => 5.0000,
			'base_shipping_amount'          => 5.0000,
			'shipping_tax_amount'           => 0.0000,
			'base_shipping_tax_amount'      => 0.0000,
			'discount_amount'               => 0.0000,
			'base_discount_amount'          => 0.0000,
			'grand_total'                   => 154.9900,
			'base_grand_total'              => 154.9900,
			'applied_taxes'                 => 'a:0:{}',
			'base_customer_balance_amount'  => 0.0000,
			'customer_balance_amount'       => 0.0000,
			'gift_cards_amount'             => 0.0000,
			'base_gift_cards_amount'        => 0.0000,
			'gift_cards'                    => 'a:0:{}',
			'used_gift_cards'               => 'a:0:{}',
			'shipping_discount_amount'      => 0.0000,
			'base_shipping_discount_amount' => 0.0000,
			'subtotal_incl_tax'             => 149.9900,
			'hidden_tax_amount'             => 0.0000,
			'base_hidden_tax_amount'        => 0.0000,
			'shipping_hidden_tax_amount'    => 0.0000,
			'shipping_incl_tax'             => 5.0000,
			'base_shipping_incl_tax'        => 5.0000,
			'gw_base_price'                 => 0.0000,
			'gw_price'                      => 0.0000,
			'gw_items_base_price'           => 0.0000,
			'gw_items_price'                => 0.0000,
			'gw_card_base_price'            => 0.0000,
			'gw_card_price'                 => 0.0000,
			'gw_base_tax_amount'            => 0.0000,
			'gw_tax_amount'                 => 0.0000,
			'gw_items_base_tax_amount'      => 0.0000,
			'gw_items_tax_amount'           => 0.0000,
			'gw_card_base_tax_amount'       => 0.0000,
			'gw_card_tax_amount'            => 0.0000,
			'reward_points_balance'         => 0,
			'base_reward_currency_amount'   => 0.0000,
			'reward_currency_amount'        => 0.0000,
		));

		$addressB = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(10),
			'getAllNonNominalItems'      => $this->returnValue(array($addressItemB)),
			'getGroupedAllShippingRates' => $this->returnValue(array('flatrate' => array($shippingRate))),
		));
		$addressB->setData(array(
			'address_id'                    => 10,
			'quote_id'                      => 2,
			'created_at'                    => '2013-06-27 17:43:32',
			'updated_at'                    => '2013-06-27 17:45:05',
			'customer_id'                   => 5,
			'save_in_address_book'          => 0,
			'customer_address_id'           => 5,
			'address_type'                  => 'shipping',
			'email'                         => 'foo@example.com',
			'firstname'                     => 'extra',
			'lastname'                      => 'guy',
			'street'                        => '1 Shields',
			'city'                          => 'davis',
			'region'                        => 'California',
			'region_id'                     => 12,
			'postcode'                      => 90210,
			'country_id'                    => 'US',
			'telephone'                     => 1234567890,
			'same_as_billing'               => 1,
			'free_shipping'                 => 0,
			'collect_shipping_rates'        => 0,
			'shipping_method'               => 'flatrate_flatrate',
			'shipping_description'          => 'Flat Rate - Fixed',
			'weight'                        => 3.2000,
			'subtotal'                      => 149.9900,
			'base_subtotal'                 => 149.9900,
			'subtotal_with_discount'        => 0.0000,
			'base_subtotal_with_discount'   => 0.0000,
			'tax_amount'                    => 12.3700,
			'base_tax_amount'               => 12.3700,
			'shipping_amount'               => 5.0000,
			'base_shipping_amount'          => 5.0000,
			'shipping_tax_amount'           => 0.0000,
			'base_shipping_tax_amount'      => 0.0000,
			'discount_amount'               => 0.0000,
			'base_discount_amount'          => 0.0000,
			'grand_total'                   => 167.3600,
			'base_grand_total'              => 167.3600,
			'applied_taxes'                 => 'a:1:{s:14:\"US-CA-*-Rate 1\";a:6:{s:5:\"rates\";a:1:{i:0;a:6:{s:4:\"code\";s:14:\"US-CA-*-Rate 1\";s:5:\"title\";s:14:\"US-CA-*-Rate 1\";s:7:\"percent\";d:8.25;s:8:\"position\";s:1:\"1\";s:8:\"priority\";s:1:\"1\";s:7:\"rule_id\";s:1:\"1\";}}s:7:\"percent\";d:8.25;s:2:\"id\";s:14:\"US-CA-*-Rate 1\";s:7:\"process\";i:0;s:6:\"amount\";d:12.369999999999999;s:11:\"base_amount\";d:12.369999999999999;}}',
			'base_customer_balance_amount'  => 0.0000,
			'customer_balance_amount'       => 0.0000,
			'gift_cards_amount'             => 0.0000,
			'base_gift_cards_amount'        => 0.0000,
			'gift_cards'                    => 'a:0:{}',
			'used_gift_cards'               => 'a:0:{}',
			'shipping_discount_amount'      => 0.0000,
			'base_shipping_discount_amount' => 0.0000,
			'subtotal_incl_tax'             => 162.3600,
			'hidden_tax_amount'             => 0.0000,
			'base_hidden_tax_amount'        => 0.0000,
			'shipping_hidden_tax_amount'    => 0.0000,
			'shipping_incl_tax'             => 5.0000,
			'base_shipping_incl_tax'        => 5.0000,
			'gw_base_price'                 => 0.0000,
			'gw_price'                      => 0.0000,
			'gw_items_base_price'           => 0.0000,
			'gw_items_price'                => 0.0000,
			'gw_card_base_price'            => 0.0000,
			'gw_card_price'                 => 0.0000,
			'gw_base_tax_amount'            => 0.0000,
			'gw_tax_amount'                 => 0.0000,
			'gw_items_base_tax_amount'      => 0.0000,
			'gw_items_tax_amount'           => 0.0000,
			'gw_card_base_tax_amount'       => 0.0000,
			'gw_card_tax_amount'            => 0.0000,
		));

		// mock the billing addresses
		$addressC = $this->_buildModelMock('sales/quote_address', array(
			'getId'                      => $this->returnValue(11),
			'getAllNonNominalItems'      => $this->returnValue(array()),
			'getGroupedAllShippingRates' => $this->returnValue(array()),
		));
		$addressC->setData(array(
			'address_id' => 11,
			'quote_id'                     => 2,
			'created_at'                   => '2013-06-27 17:43:32',
			'updated_at'                   => '2013-06-27 17:45:05',
			'customer_id'                  => 5,
			'save_in_address_book'         => 0,
			'customer_address_id'          => 4,
			'address_type'                 => 'billing',
			'email'                        => 'foo@example.com',
			'firstname'                    => 'test',
			'lastname'                     => 'guy',
			'street'                       => '1 Rosedale St',
			'city'                         => 'Baltimore',
			'region'                       => 'Maryland',
			'region_id'                    => 31,
			'postcode'                     => 21229,
			'country_id'                   => 'US',
			'telephone'                    => '(123) 456-7890',
			'same_as_billing'              => 0,
			'free_shipping'                => 0,
			'collect_shipping_rates'       => 0,
			'weight'                       => 0.0000,
			'subtotal'                     => 0.0000,
			'base_subtotal'                => 0.0000,
			'subtotal_with_discount'       => 0.0000,
			'base_subtotal_with_discount'  => 0.0000,
			'tax_amount'                   => 0.0000,
			'base_tax_amount'              => 0.0000,
			'shipping_amount'              => 0.0000,
			'base_shipping_amount'         => 0.0000,
			'shipping_tax_amount'          => 0.0000,
			'base_shipping_tax_amount'     => 0.0000,
			'discount_amount'              => 0.0000,
			'base_discount_amount'         => 0.0000,
			'grand_total'                  => 0.0000,
			'base_grand_total'             => 0.0000,
			'applied_taxes'                => 'a:0:{}',
			'base_customer_balance_amount' => 0.0000,
			'customer_balance_amount'      => 0.0000,
			'gift_cards_amount'            => 0.0000,
			'base_gift_cards_amount'       => 0.0000,
			'gift_cards'                   => 'a:0:{}',
			'used_gift_cards'              => 'a:0:{}',
			'subtotal_incl_tax'            => 0.0000,
			'shipping_incl_tax'            => 0.0000,
			'base_shipping_incl_tax'       => 0.0000,
		));

		// mock the quote
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'                   => $this->returnValue(2),
			'load'                    => $this->returnSelf(),
			'isVirtual'               => $this->returnValue(false),
			'getStore'                => $this->returnValue($store),
			'getBillingAddress'       => $this->returnValue($addressC),
			'getShippingAddress'      => $this->returnValue($addressA),
			'getAllAddresses'         => $this->returnValue(array($addressA, $addressB, $addressC)),
			'getAllShippingAddresses' => $this->returnValue(array($addressA, $addressB)),
			'getAllVisibleItems'      => $this->returnValue($items),
			'getItemById'             => $this->returnValueMap(array(array(4, $item)))
		));
		$quote->setData(array(
			'entity_id'                   => 2,
			'store_id'                    => 2,
			'created_at'                  => '2013-06-27 17:41:05',
			'updated_at'                  => '2013-06-27 17:45:05',
			'is_active'                   => 0,
			'is_virtual'                  => 0,
			'is_multi_shipping'           => 1,
			'items_count'                 => 1,
			'items_qty'                   => 2.0000,
			'orig_order_id'               => 0,
			'store_to_base_rate'          => 1.0000,
			'store_to_quote_rate'         => 1.0000,
			'base_to_global_rate'         => 1.0000,
			'base_to_quote_rate'          => 1.0000,
			'global_currency_code'        => 'USD',
			'base_currency_code'          => 'USD',
			'store_currency_code'         => 'USD',
			'quote_currency_code'         => 'USD',
			'grand_total'                 => 322.3500,
			'base_grand_total'            => 322.3500,
			'customer_id'                 => 5,
			'customer_tax_class_id'       => 3,
			'customer_group_id'           => 1,
			'customer_email'              => 'foo@example.com',
			'customer_firstname'          => 'test',
			'customer_lastname'           => 'guy',
			'customer_note_notify'        => 1,
			'customer_is_guest'           => 0,
			'remote_ip'                   => '192.168.56.1',
			'reserved_order_id'           => 100000052,
			'subtotal'                    => 299.9800,
			'base_subtotal'               => 299.9800,
			'subtotal_with_discount'      => 299.9800,
			'base_subtotal_with_discount' => 299.9800,
			'trigger_recollect'           => 0,
		));
		$this->replaceByMock('model', 'sales/quote', $quote);
		return $quote;
	}

	/**
	 * @dataProvider getItemTaxClassProvider
	 * @loadExpectation
	 * NOTE: this test assumes tax_code can be retrieved from the product using
	 * $product->getTaxCode()
	 */
	public function testGetItemTaxClass($taxCode, $expectation)
	{
		$product = $this->_stubProduct(false, $taxCode);
		$item = $this->_buildModelMock('sales/quote_item', array('getProduct' => $this->returnValue($product)));
		$request = Mage::getModel('eb2ctax/request');
		$val = $this->_reflectMethod($request, '_getItemTaxClass')->invoke($request, $item);
		$e = $this->expected($expectation);
		$this->assertSame($e->getTaxCode(), $val);
	}

	/**
	 * @test
	 * @large
	 */
	public function testAddToDestination()
	{
		$fn      = $this->_reflectMethod('TrueAction_Eb2cTax_Model_Request', '_addToDestination');
		$d       = $this->_reflectProperty('TrueAction_Eb2cTax_Model_Request', '_destinations');
		$quote   = $this->_stubSingleShipSameAsBill();
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
		$fn = $this->_reflectMethod($request, '_buildDiscountNode');
		$doc = $request->getDocument();
		$node = $doc->createElement('root', null, 'http://example.com/foo');
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

		$node = $doc->createElement('root', null, 'http://example.com/foo'); // parent node
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
		$mockQuoteAddress = $this->getModelMock('sales/quote_address', array('getBaseShippingDiscountAmount', 'getCouponCode'));
		$mockQuoteAddress->expects($this->any())
			->method('getCouponCode')
			->will($this->returnValue('somecouponcode'));
		$mockQuoteAddress->expects($this->any())
			->method('getBaseShippingDiscountAmount')
			->will($this->returnValue(3));
		$mockItem = $this->getModelMock('sales/quote_item', array('getBaseDiscountAmount'));
		$mockItem->expects($this->any())
			->method('getBaseDiscountAmount')
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
			$this->assertArrayHasKey($key,
			$outData);
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
	 */
	public function testExtractAdminData()
	{
		$request = Mage::getModel('eb2ctax/request');

		$mockConfig = $this->getModelMock(
			'eb2ccore/config_registry',
			array('getConfig', '__get', 'addConfigModel')
		);
		$mockConfig->expects($this->any())
			->method('addConfigModel')
			->with($this->identicalTo(Mage::getSingleton('eb2ctax/config')))
			->will($this->returnSelf());
		$mockConfig->expects($this->any())
			->method('getConfig')
			->will($this->returnValueMap(array(
				array('admin_origin_line1', null, '1075 First Ave'),
				array('admin_origin_line2', null, 'STE 123'),
				array('admin_origin_line3', null, 'BLDG 4'),
				array('admin_origin_line4', null, ''),
			)));
		$mockConfig->expects($this->any())
			->method('__get')
			->will($this->returnValueMap(array(
				array('adminOriginCity', 'King of Prussia'),
				array('adminOriginMainDivision', 'PA'),
				array('adminOriginCountryCode', 'US'),
				array('adminOriginPostalCode', '19406'),
			)));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mockConfig);

		$requestReflector = new ReflectionObject($request);
		$extractAdminDataMethod = $requestReflector->getMethod('_extractAdminData');
		$extractAdminDataMethod->setAccessible(true);

		$this->assertSame(
			array(
				'Lines'        => array('1075 First Ave', 'STE 123', 'BLDG 4', ''),
				'City'         => 'King of Prussia',
				'MainDivision' => 'PA',
				'CountryCode'  => 'US',
				'PostalCode'   => '19406'
			),
			$extractAdminDataMethod->invoke($request)
		);
	}

	public function providerExtractShippingData()
	{
		$mockQuoteItem = $this->getModelMock('sales/quote_item', array(
			'getEb2cShipFromAddressLine1',
			'getEb2cShipFromAddressCity',
			'getEb2cShipFromAddressMainDivision',
			'getEb2cShipFromAddressCountryCode',
			'getEb2cShipFromAddressPostalCode'
		));
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

		// mock the address items
		$mockAddressItem = $this->_buildModelMock('sales/quote_address_item', array(
			'getId'          => $this->returnValue(5),
			'getHasChildren' => $this->returnValue(false),
			'getQuoteItem'   => $this->returnValue($mockQuoteItem),
		));

		return array(
			array($mockQuoteItem),
			array($mockAddressItem),
		);
	}

	/**
	 * @test
	 * @dataProvider providerExtractShippingData
	 */
	public function testExtractShippingData(Mage_Sales_Model_Quote_Item_Abstract $item)
	{
		$request = Mage::getModel('eb2ctax/request');

		$requestReflector = new ReflectionObject($request);
		$extractShippingDataMethod = $requestReflector->getMethod('_extractShippingData');
		$extractShippingDataMethod->setAccessible(true);

		$this->assertSame(array(
			'Line1'        => '1075 First Avenue',
			'Line2'        => '',
			'Line3'        => '',
			'Line4'        => '',
			'City'         => 'King Of Prussia',
			'MainDivision' => 'PA',
			'CountryCode'  => 'US',
			'PostalCode'   => '19406',
		), $extractShippingDataMethod->invoke($request, $item));
	}

	/**
	 * @test
	 */
	public function testBuildAdminOriginNode()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$parent = $domDocument->addElement('TaxDutyQuoteRequest', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;
		$adminOrigin = array(
			'Lines'        => array('1075 First Avenue', 'STE 123', 'BLDG 2', ''),
			'City'         => 'King Of Prussia',
			'MainDivision' => 'PA',
			'CountryCode'  => 'US',
			'PostalCode'   => '19406',
		);
		$request = Mage::getModel('eb2ctax/request');

		$requestReflector = new ReflectionObject($request);
		$buildAdminOriginNodeMethod = $requestReflector->getMethod('_buildAdminOriginNode');
		$buildAdminOriginNodeMethod->setAccessible(true);
		$buildAdminOriginNodeMethod->invoke($request, $parent, $adminOrigin);
		$this->assertSame(
			'<TaxDutyQuoteRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><AdminOrigin><Line1>1075 First Avenue</Line1><Line2>STE 123</Line2><Line3>BLDG 2</Line3><City>King Of Prussia</City><MainDivision>PA</MainDivision><CountryCode>US</CountryCode><PostalCode>19406</PostalCode></AdminOrigin></TaxDutyQuoteRequest>',
			$parent->C14N()
		);
	}

	/**
	 * @test
	 */
	public function testBuildShippingOriginNode()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$parent = $domDocument->addElement('TaxDutyQuoteRequest', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;
		$shippingOrigin = array(
			'Line1'        => '1075 First Avenue',
			'Line2'        => 'Line2',
			'Line3'        => 'Line3',
			'Line4'        => 'Line4',
			'City'         => 'King Of Prussia',
			'MainDivision' => 'PA',
			'CountryCode'  => 'US',
			'PostalCode'   => '19406',
		);
		$request = Mage::getModel('eb2ctax/request');
		$requestReflector = new ReflectionObject($request);
		$buildShippingOriginNodeMethod = $requestReflector->getMethod('_buildShippingOriginNode');
		$buildShippingOriginNodeMethod->setAccessible(true);
		$buildShippingOriginNodeMethod->invoke($request, $parent, $shippingOrigin);
		$this->assertSame(
			'<TaxDutyQuoteRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><ShippingOrigin><Line1>1075 First Avenue</Line1><City>King Of Prussia</City><MainDivision>PA</MainDivision><CountryCode>US</CountryCode><PostalCode>19406</PostalCode></ShippingOrigin></TaxDutyQuoteRequest>',
			$parent->C14N()
		);
	}

	/**
	 * Test getting the "original price" for an item.
	 * Provider will give different combinations of prices,
	 * correct price should always be 12.34.
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testGettingOriginalPriceForItem($originalCustomPrice, $customPrice, $originalPrice, $basePrice)
	{
		$item = $this->getModelMock('sales/quote_item', array(
			'hasOriginalCustomPrice',
			'getOriginalCustomPrice',
			'hasCustomPrice',
			'getCustomPrice',
			'hasOriginalPrice',
			'getOriginalPrice',
			'getBasePrice',
		));
		$item->expects($this->any())
			->method('hasOriginalCustomPrice')
			->will($this->returnValue(!is_null($originalCustomPrice)));
		$item->expects($this->any())
			->method('getOriginalCustomPrice')
			->will($this->returnValue($originalCustomPrice));
		$item->expects($this->any())
			->method('hasCustomPrice')
			->will($this->returnValue(!is_null($customPrice)));
		$item->expects($this->any())
			->method('getCustomPrice')
			->will($this->returnValue($customPrice));
		$item->expects($this->any())
			->method('hasOriginalPrice')
			->will($this->returnValue(!is_null($originalPrice)));
		$item->expects($this->any())
			->method('getOriginalPrice')
			->will($this->returnValue($originalPrice));
		$item->expects($this->any())
			->method('getBasePrice')
			->will($this->returnValue($basePrice));
		$request = Mage::getModel('eb2ctax/request');
		$getItemOriginalPrice = $this->_reflectMethod($request, '_getItemOriginalPrice');
		$price = $getItemOriginalPrice->invoke($request, $item);
		$this->assertSame(12.34, $price);
	}

	/**
	 * Test getting all items for an address - should return all non-nominal, "visible" items
	 * @test
	 */
	public function testGettingItemsForAddress()
	{
		$items = array();
		$items[] = $this->getModelMock('sales/quote_item', array('getParentItemId'));
		$items[0]->expects($this->any())
			->method('getParentItemId')
			->will($this->returnValue(23));
		$items[] = $this->getModelMock('sales/quote_item', array('getParentItemId'));
		$items[1]->expects($this->any())
			->method('getParentItemId')
			->will($this->returnValue(null));
		$address = $this->getModelMock('sales/quote_address', array('getAllNonNominalItems'));
		$address->expects($this->any())
			->method('getAllNonNominalItems')
			->will($this->returnValue($items));

		$request = Mage::getModel('eb2ctax/request');
		$method = $this->_reflectMethod($request, '_getItemsForAddress');
		$itemsForAddress = $method->invoke($request, $address);

		$this->assertSame(1, count($itemsForAddress));
		$this->assertSame($items[1], $itemsForAddress[0]);
	}

	/**
	 * verify item data is extracted properly
	 * @param  bool $isVirtual   true if the item is virtual; false otherwise
	 * @param  bool $hasShipFrom true if the item has the ship from address; false otherwise
	 * @test
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testExtractItemData($isVirtual, $hasShipFrom)
	{
		$product = $this->buildModelStub('catalog/product', array(
			'isVirtual' => $isVirtual
		));

		$item = $this->buildModelStub('sales/quote_item', array(
			'getId' => 1,
			'getSku' => 'the_sku',
			'getName' => 'the item',
			'getHtsCode' => 'hts code',
			'getQty' => 1,
			'getBaseRowTotal' => 50.0,
			'getProduct' => $product,
		));

		$address = $this->buildModelStub('sales/quote_address', array(
			'getBaseShippingAmount' => 5.0,
		));

		$request = $this->buildModelMock('eb2ctax/request', array(
			'_getItemOriginalPrice',
			'_getItemTaxClass',
			'_getShippingTaxClass',
			'_extractAdminData',
			'_extractShippingData',
			'_extractItemDiscountData',
		));

		$request->expects($this->once())
			->method('_getItemOriginalPrice')
			->with($this->identicalTo($item))
			->will($this->returnValue(51.0));
		$request->expects($this->once())
			->method('_getItemTaxClass')
			->with($this->identicalTo($item))
			->will($this->returnValue('tax class'));
		$request->expects($this->once())
			->method('_getShippingTaxClass')
			->will($this->returnValue('shipping tax class'));
		$request->expects($this->once())
			->method('_extractAdminData')
			->will($this->returnValue('the admin data'));
		$request->expects($this->once())
			->method('_extractItemDiscountData')
			->with(
				$this->identicalTo($item),
				$this->identicalTo($address),
				$this->isType('array')
			)
			->will($this->returnArgument(2));
		if ($hasShipFrom && !$isVirtual) {
			$request->expects($this->once())
				->method('_extractShippingData')
				->will($this->returnValue('ship from data'));
		}
		elseif (!$hasShipFrom && !$isVirtual) {
			$request->expects($this->once())
				->method('_extractShippingData')
				->with($this->identicalTo($item))
				->will($this->throwException(new Mage_Core_Exception('shipfrom data missing')));
		}
		else {
			$request->expects($this->never())
				->method('_extractShippingData');
		}
		$result = $this->_reflectMethod($request, '_extractItemData')
			->invoke($request, $item, $address);
		$e = $this->expected('test-%s-%s', $isVirtual, $hasShipFrom);
		$this->assertEquals($e->getResult(), $result);
	}

	/**
	 * verify the processaddress function makes calls with the correct argument types.
	 * @test
	 */
	public function testProcessAddress()
	{
		$store = $this->buildModelStub('core/store', array('getId' => 99), null);
		$billingAddress = $this->buildModelStub('sales/quote_address', array());
		$quote = $this->buildModelStub(
			'sales/quote',
			array(
				'getStore' => $store,
				'getBillingAddress' => $billingAddress,
				'getQuoteCurrencyCode' => 'USD',
				'getItemsCount' => 1,
			),
			null
		);
		$item = $this->_stubQuoteItem();
		$address = $this->buildModelStub('sales/quote_address', array('getQuote' => $quote));

		$request = $this->buildModelMock('eb2ctax/request', array(
			'_isQuoteUsable',
			'_extractDestData',
			'_getItemsForAddress',
			'_addBillingDestination',
			'setQuoteCurrencyCode',
			'_processItem',
		));
		$request->expects($this->once())
			->method('_addBillingDestination')
			->with($this->identicalTo($billingAddress));
		$request->expects($this->once())
			->method('setQuoteCurrencyCode')
			->with($this->identicalTo('USD'))
			->will($this->returnSelf());
		$request->expects($this->once())
			->method('_getItemsForAddress')
			->with($this->identicalTo($address))
			->will($this->returnValue(array($item)));
		$request->expects($this->once())
			->method('_isQuoteUsable')
			->with($this->identicalTo($quote))
			->will($this->returnValue(true));
		$request->expects($this->atLeastOnce())
			->method('_processItem')
			->with(
				$this->isInstanceOf('Mage_Sales_Model_Quote_Item_Abstract'),
				$this->isInstanceOf('Mage_Sales_Model_Quote_Address')
			);
		$request->processAddress($address);
		$this->assertSame(99, $this->_reflectProperty($request, '_storeId')->getValue($request));
		$this->assertTrue($request->isValid());
	}

	public function testProcessAddressInvalid()
	{
		$quote = $this->_stubMultiShipNotSameAsBill();
		$addressGetter = 'getShippingAddress';
		$scenario = 'shipping-address';
		$address = $quote->$addressGetter();

		$request = $this->buildModelMock('eb2ctax/request', array(
			'_isQuoteUsable',
		));
		$request->expects($this->once())
			->method('_isQuoteUsable')
			->will($this->returnValue(false));
		$request->processAddress($address);
		$this->assertFalse($request->isValid());
	}
	/**
	 * Test processing an item - e.g. adding the item to a destination based on the
	 * address the item belongs to. When ginen an item with calculated child items,
	 * the method should iterate through the child items and process each one. When
	 * given an item without children, the method should add that item to a destination group.
	 * @test
	 */
	public function testProcessItem()
	{
		$parentItem = $this->getModelMock(
			'sales/quote_item',
			array('getHasChildren', 'isChildrenCalculated', 'getChildren')
		);
		$child = $this->getModelMock('sales/quote_item', array('getHasChildren', 'getProduct'));
		$address = $this->getModelMock('sales/quote_address');
		$product = $this->getModelMock('catalog/product', array('isVirtual'));
		$request = $this->getModelMockBuilder('eb2ctax/request')
			->disableOriginalConstructor()
			->setMethods(array('_addToDestination'))
			->getMock();

		$parentItem
			->expects($this->once())
			->method('getHasChildren')
			->will($this->returnValue(true));
		$parentItem
			->expects($this->once())
			->method('isChildrenCalculated')
			->will($this->returnValue(true));
		$parentItem
			->expects($this->once())
			->method('getChildren')
			->will($this->returnValue(array($child)));
		$child
			->expects($this->once())
			->method('getHasChildren')
			->will($this->returnValue(false));
		$child
			->expects($this->once())
			->method('getProduct')
			->will($this->returnValue($product));
		$product
			->expects($this->once())
			->method('isVirtual')
			->will($this->returnValue(false));
		$request
			->expects($this->once())
			->method('_addToDestination')
			->with($this->identicalTo($child), $this->identicalTo($address), $this->identicalTo(false));

		$method = $this->_reflectMethod($request, '_processItem');
		$this->assertSame($request, $method->invoke($request, $parentItem, $address));
	}
	/**
	 * verify the billing destination gets properly extracted
	 * @test
	 */
	public function testAddBillingDestination()
	{
		$address = $this->buildModelStub('sales/quote_address', array(
			'getTaxId' => 'taxid',
		));
		$request = $this->buildModelMock('eb2ctax/request', array(
			'setBillingAddressTaxId',
			'_getDestinationId',
			'_extractDestData'
		));
		$request->expects($this->once())
			->method('setBillingAddressTaxId')
			->with($this->identicalTo('taxid'))
			->will($this->returnSelf());
		$request->expects($this->once())
			->method('_getDestinationId')
			->with($this->identicalTo($address))
			->will($this->returnValue('destinationid'));
		$request->expects($this->once())
			->method('_extractDestData')
			->with($this->identicalTo($address))
			->will($this->returnValue(array('destination data')));

		$this->_reflectMethod($request, '_addBillingDestination')->invoke($request, $address);
		$this->assertSame('destinationid', $this->_reflectProperty($request, '_billingInfoRef')->getValue($request));
		$destinations = $this->_reflectProperty($request, '_destinations')->getValue($request);
		$this->assertSame(array('destinationid' => array('destination data')), $destinations);
	}

	public function testAddBillingDestinationException()
	{
		$address = $this->buildModelStub('sales/quote_address', array(
			'getTaxId' => 'taxid',
		));
		$request = $this->buildModelMock('eb2ctax/request', array(
			'setBillingAddressTaxId',
			'_getDestinationId',
			'_extractDestData'
		));
		$request->expects($this->any())
			->method('_getDestinationId')
			->will($this->returnValue('destinationid'));
		$request->expects($this->once())
			->method('_extractDestData')
			->will($this->throwException(new Mage_Core_Exception('test exception')));
		$this->setExpectedException('Mage_Core_Exception', 'Unable to extract the billing address: ');
		$this->_reflectMethod($request, '_addBillingDestination')->invoke($request, $address);
	}
}
