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
	 * make sure the exceptions don't kill the function.
	 */
	public function testProcessQuoteAddressException()
	{
		for ($i = 1; $i < 3; ++$i) {
			$quote = $this->_stubMultiShipNotSameAsBill();
			$request = $this->getModelMock('eb2ctax/request', array(
				'getQuote',
				'_extractDestData',
			));
			$request->expects($this->any())
				->method('getQuote')
				->will($this->returnValue($quote));
			$request->expects($this->at($i))
				->method('_extractDestData')
				->will($this->throwException(new Mage_Core_Exception('address fail')));
			$this->_reflectMethod($request, '_processQuote')->invoke($request);
		}
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
	protected function _stubStore($code='usa', $id=2)
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
		$children=array(), $childrenCalculated=false, $discountAmt=null
	)
	{
		return $this->_buildModelMock('sales/quote_item', array(
			'getId'                => $this->returnValue($id),
			'getSku'               => $this->returnValue($sku),
			'getProduct'           => $this->returnValue($product),
			'getTotalQty'          => $this->returnValue($totalQty),
			'getHasChildren'       => $this->returnValue(!empty($children)),
			'getChildren'          => $this->returnValue($children),
			'isChildrenCalculated' => $this->returnValue($childrenCalculated),
			'getDiscountAmount'    => $this->returnValue($discountAmt),
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
			'getItemById'             => $this->returnValueMap(array(array(1, $item), array(2, $childItem)))
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
		$itemA = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(1),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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

		$itemB = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(2),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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

		$itemC = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(3),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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
		$itemA = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(1),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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

		$itemB = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(2),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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
		$itemC = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(3),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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
		$itemA = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(1),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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

		$itemB = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(2),
			'getProduct'     => $this->returnValue($vProduct),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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

		$itemC = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(3),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'          => $this->returnValue(4),
			'getProduct'     => $this->returnValue($product),
			'getHasChildren' => $this->returnValue(false),
			'getStore'       => $this->returnValue($store),
		));
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
		return $quote;
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckDiscounts()
	{
		$vProduct = $this->_stubProduct(true);
		$item = $this->_stubQuoteItem($vProduct, 1, 1, 'parent_sku');
		$address = $this->_stubSimpleAddress(array($item));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'             => $this->returnValue(1),
			'getCouponCode'     => $this->returnValue(''),
			'getBillingAddress' => $this->returnValue($address),
			'getAllAddresses'   => $this->returnValue(array($address)),
			'getStore'          => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array(
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
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());

		$item = $this->_stubQuoteItem($vProduct, 1, 1, 'parent_sku', array(), false, 10.00);
		$address = $this->_stubSimpleAddress(array($item));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'             => $this->returnValue(1),
			'getCouponCode'     => $this->returnValue('10off'),
			'getBillingAddress' => $this->returnValue($address),
			'getAllAddresses'   => $this->returnValue(array($address)),
			'getStore'          => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array(
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
		$request->checkDiscounts($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckDiscountsCouponCode()
	{
		$vProduct = $this->_stubProduct(true);
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'             => $this->returnValue(1),
			'getSku'            => $this->returnValue('parent_sku'),
			'getProduct'        => $this->returnValue($vProduct),
			'getDiscountAmount' => $this->returnValue(10.00),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                 => $this->returnValue(1),
			'getAllNonNominalItems' => $this->returnValue(array($item)),
		));
		$address->setData(array(
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
		));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'             => $this->returnValue(1),
			'getCouponCode'     => $this->returnValue('10off'),
			'getBillingAddress' => $this->returnValue($address),
			'getAllAddresses'   => $this->returnValue(array($address)),
			'getStore'          => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array(
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
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		// coupon code changes   -> invalidates the quote
		$this->assertTrue($request->isValid());

		// Change the discount on the quote. This should invalidate the request.
		$quote->expects($this->any())
			->method('getCouponCode')
			->will($this->returnValue('10off2'));

		$request->checkDiscounts($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckDiscountShippingAmount()
	{
		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'      => $this->returnValue(1),
			'getSku'     => $this->returnValue('parent_sku'),
			'getProduct' => $this->returnValue($vProduct),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                     => $this->returnValue(1),
			'getAllNonNominalItems'     => $this->returnValue(array($item)),
			'getShippingDiscountAmount' => $this->returnValue(5),
		));
		$address->setData(array(
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
		));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'             => $this->returnValue(1),
			'getBillingAddress' => $this->returnValue($address),
			'getAllAddresses'   => $this->returnValue(array($address)),
			'getStore'          => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array(
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
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		// coupon code changes   -> invalidates the quote
		$this->assertTrue($request->isValid());

		$vProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$vProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));
		$item = $this->_buildModelMock('sales/quote_item', array(
			'getId'      => $this->returnValue(1),
			'getSku'     => $this->returnValue('parent_sku'),
			'getProduct' => $this->returnValue($vProduct),
		));
		$address = $this->_buildModelMock('sales/quote_address', array(
			'getId'                     => $this->returnValue(1),
			'getAllNonNominalItems'     => $this->returnValue(array($item)),
			'getShippingDiscountAmount' => $this->returnValue(0),
		));
		$address->setData(array(
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
		));
		$quote = $this->_buildModelMock('sales/quote', array(
			'getId'             => $this->returnValue(1),
			'getBillingAddress' => $this->returnValue($address),
			'getAllAddresses'   => $this->returnValue(array($address)),
			'getStore'          => $this->returnValue(Mage::app()->getStore()),
		));
		$quote->setData(array(
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
		$request->checkDiscounts($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @dataProvider getItemTaxClassProvider
	 * @loadExpectation
	 * NOTE: this test assumes tax_code can be retrieved from the product using
	 * $product->getTaxCode()
	 */
	public function  testGetItemTaxClass($taxCode, $expectation)
	{
		$product = $this->_stubProduct(false, $taxCode);
		$item = $this->_buildModelMock('sales/quote_item', array('getProduct' => $this->returnValue($product)));
		$request = Mage::getModel('eb2ctax/request');
		$val = $this->_reflectMethod($request, '_getItemTaxClass')->invoke($request, $item);
		$e = $this->expected($expectation);
		$this->assertSame($e->getTaxCode(), $val);
	}

	/**
	 * verify the request is valid only when:
	 *  1. the the quote is valid and has, at least, a billing address.
	 *  2. there are no detected changes between the current quote and the information in the request.
	 *  3. the amount of items (skus) in the quote is the same as recorded in the request.
	 *
	 * @large
	 */
	public function testIsValid()
	{
		$quote = $this->_stubVirtualQuote();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$hasChanges = $this->_reflectProperty($request, '_hasChanges');
		$this->assertFalse($hasChanges->getValue($request));
		$this->assertTrue((bool) $request->getQuote());
		$this->assertTrue((bool) $request->getQuote()->getId());
		$this->assertTrue((bool) $request->getQuote()->getBillingAddress());
		$this->assertTrue((bool) $request->getQuote()->getBillingAddress()->getId());
		$itemQuantities = $this->_reflectProperty($request, '_itemQuantities')->getValue($request);
		$this->assertSame((int) $request->getQuote()->getItemsQty(), count($itemQuantities));
		$this->assertTrue($request->isValid());
		$request->invalidate();
		$this->assertTrue($hasChanges->getValue($request));
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 * @loadFixture loadAdminOriginConfig.yaml
	 */
	public function testValidateWithXsd()
	{
		$this->_setupBaseUrl();
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$helper = $this->getHelperMock('tax/data', array('getNamespaceUri'));
		$helper->expects($this->atLeastOnce())
			->method('getNamespaceUri')
			->will($this->returnValue(self::$namespaceUri));
		$this->_reflectProperty($request, '_helper')->setValue($request, $helper);

		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}

	/**
	 * @test
	 * @large
	 */
	public function testValidateWithXsdVirtual()
	{
		$quote = $this->_stubSingleShipVirtual();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$helper = $this->getHelperMock('tax/data', array('getNamespaceUri'));
		$helper->expects($this->atLeastOnce())
			->method('getNamespaceUri')
			->will($this->returnValue(self::$namespaceUri));
		$this->_reflectProperty($request, '_helper')->setValue($request, $helper);

		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}

	/**
	 * @test
	 * @large
	 */
	public function testValidateWithXsdMultiShip()
	{
		$quote = $this->_stubMultiShipNotSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$helper = $this->getHelperMock('tax/data', array('getNamespaceUri'));
		$helper->expects($this->atLeastOnce())
			->method('getNamespaceUri')
			->will($this->returnValue(self::$namespaceUri));
		$this->_reflectProperty($request, '_helper')->setValue($request, $helper);

		$itemQuantities = $this->_reflectProperty($request, '_itemQuantities')->getValue($request);
		$this->assertSame(count($itemQuantities), $quote->getItemsCount());

		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckAddresses()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// changing address information should invalidate the request
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckAddressesNoChanges()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// passing in a quote with no changes should not invalidate the request
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckAddressesEmptyQuote()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// passing in an unusable quote should invalidate the request
		$request->checkAddresses(Mage::getModel('sales/quote'));
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckAddressesNullQuote()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// passing in an unusable quote should invalidate the request
		$request->checkAddresses(null);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckAddressesChangeMultishipState()
	{
		$this->_setupBaseUrl();
		$this->_mockCookie();
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		// switching to multishipping will invalidate the request
		$quote->setIsMultiShipping(1);
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckAddressesVirtual()
	{
		$quote   = $this->_stubVirtualQuote();
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
		$quote = $this->_stubMultiShipNotSameAsBill();
		$val = Mage::getStoreConfig('eb2ctax/api/namespace_uri', $quote->getStore());
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());

		$quote = $this->_stubMultiShipNotSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$addresses = $quote->getAllAddresses();
		$addresses[2]->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testMultishipping()
	{
		$quote = $this->_stubMultiShipNotSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$helper = $this->getHelperMock('tax/data', array('getNamespaceUri'));
		$helper->expects($this->atLeastOnce())
			->method('getNamespaceUri')
			->will($this->returnValue(self::$namespaceUri));
		$this->_reflectProperty($request, '_helper')->setValue($request, $helper);

		$doc = $request->getDocument();
		$x = new DOMXPath($doc);

		$this->assertNotNull($doc->documentElement);
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
		$ls = $x->query('//a:OrderItem');
		$this->assertSame(2, $ls->length);
		$expected = array('5' => '2', '6' => '1');
		$id = $x->evaluate('string(./@lineNumber)', $ls->item(0));
		$this->assertSame($expected[$id], $x->evaluate('string(./a:Quantity)', $ls->item(0)));
		$id = $x->evaluate('string(./@lineNumber)', $ls->item(1));
		$this->assertSame($expected[$id], $x->evaluate('string(./a:Quantity)', $ls->item(1)));
	}

	/**
	 * @test
	 * @large
	 */
	public function testVirtualPhysicalMix()
	{
		$quote = $this->_stubSingleShipSameAsBillVirtualMix();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		// billing address
		$nodeA = $doc->getElementById('_1');
		$this->assertNotNull($nodeA);
		$this->assertSame('MailingAddress', $nodeA->tagName);
		// email address for the virtual item
		$node = $doc->getElementById('_2_virtual');
		$this->assertNotNull($node);
		$this->assertSame('Email', $node->tagName);
		// shipping address which should be same as billing address.
		$nodeB = $doc->getElementById('_2');
		$this->assertNotNull($nodeB);
		$this->assertSame('MailingAddress', $nodeB->tagName);

		$x = new DOMXPath($doc);
		$x->registerNamespace('a', $doc->documentElement->namespaceURI);
		$this->assertSame(
			$x->evaluate('string(./a:PersonName/a:LastName)', $nodeA),
			$x->evaluate('string(./a:PersonName/a:LastName)', $nodeB)
		);
		$this->assertSame(
			$x->evaluate('string(./a:PersonName/a:FirstName)', $nodeA),
			$x->evaluate('string(./a:PersonName/a:FirstName)', $nodeB)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:Line1)', $nodeA),
			$x->evaluate('string(./a:Address/a:Line1)', $nodeB)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:City)', $nodeA),
			$x->evaluate('string(./a:Address/a:City)', $nodeB)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:MainDivision)', $nodeA),
			$x->evaluate('string(./a:Address/a:MainDivision)', $nodeB)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:CountryCode)', $nodeA),
			$x->evaluate('string(./a:Address/a:CountryCode)', $nodeB)
		);
		$this->assertSame(
			$x->evaluate('string(./a:Address/a:PostalCode)', $nodeA),
			$x->evaluate('string(./a:Address/a:PostalCode)', $nodeB)
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
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(3);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$items   = $quote->getAllVisibleItems();
		$item    = $items[0];
		$request->checkItemQty($item);
		$this->assertTrue($request->isValid());
		$item->setData('qty', 5);
		$request->checkItemQty($item);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testWithNoSku()
	{
		$quote = $this->_stubQuoteWithSku(null);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$this->assertFalse($request->isValid());

		$quote = $this->_stubQuoteWithSku('');
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckSkuWithLongSku()
	{
		$quote   = $this->_stubQuoteWithSku('123456789012345678901');
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

	/**
	 * @test
	 * @large
	 */
	public function testCheckDiscountsNoChanges()
	{
		$quote = $this->_stubQuoteWithSku('1234');
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		$request->checkDiscounts($quote);
		$this->assertTrue($request->isValid());
	}

	/**
	 * @test
	 * @large
	 */
	public function testCheckDiscountsNullQuote()
	{
		$quote = $this->_stubQuoteWithSku('parent_sku');
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());

		$request->checkDiscounts(null);
		$this->assertFalse($request->isValid());
	}

	public function testCheckDiscountsEmptyQuote()
	{
		$quote = $this->_stubQuoteWithSku('parent_sku');
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());

		$request->checkDiscounts(Mage::getModel('sales/quote'));
		$this->assertFalse($request->isValid());
	}

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
		$node = $doc->createElement('root', null, 'http:/www.example.com/foo');
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
	 * @large
	 */
	public function testGetRateRequest()
	{
		$quote = $this->_stubSingleShipSameAsBillVirtualMix();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
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
	 * @loadFixture loadAdminOriginConfig.yaml
	 * @large
	 */
	public function testExtractAdminData()
	{
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$requestReflector = new ReflectionObject($request);
		$extractAdminDataMethod = $requestReflector->getMethod('_extractAdminData');
		$extractAdminDataMethod->setAccessible(true);

		$this->assertSame(array(
			'Lines'        => array('1075 First Avenue', 'N/A', 'N/A', 'N/A'),
			'City'         => 'King Of Prussia',
			'MainDivision' => 'PA',
			'CountryCode'  => 'US',
			'PostalCode'   => '19406'
		), $extractAdminDataMethod->invoke($request));
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
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$requestReflector = new ReflectionObject($request);
		$extractShippingDataMethod = $requestReflector->getMethod('_extractShippingData');
		$extractShippingDataMethod->setAccessible(true);

		$this->assertSame(array(
			'Line1'        => '1075 First Avenue',
			'City'         => 'King Of Prussia',
			'MainDivision' => 'PA',
			'CountryCode'  => 'US',
			'PostalCode'   => '19406',
		), $extractShippingDataMethod->invoke($request, $item));
	}

	public function providerBuildAdminOriginNode()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$parent = $domDocument->addElement('TaxDutyQuoteRequest', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;
		return array(array($parent, array(
			'Lines'        => array('1075 First Avenue', 'N/A', 'N/A', 'N/A'),
			'City'         => 'King Of Prussia',
			'MainDivision' => 'PA',
			'CountryCode'  => 'US',
			'PostalCode'   => '19406',
		)));
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @dataProvider providerBuildAdminOriginNode
	 * @large
	 */
	public function testBuildAdminOriginNode(TrueAction_Dom_Element $parent, array $adminOrigin)
	{
		$quote = $this->_stubSingleShipSameAsBill();
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
		return array(array($parent, array(
			'Line1'        => '1075 First Avenue',
			'Line2'        => 'Line2',
			'Line3'        => 'Line3',
			'Line4'        => 'Line4',
			'City'         => 'King Of Prussia',
			'MainDivision' => 'PA',
			'CountryCode'  => 'US',
			'PostalCode'   => '19406',
		)));
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @dataProvider providerBuildShippingOriginNode
	 * @large
	 */
	public function testBuildShippingOriginNode(TrueAction_Dom_Element $parent, array $shippingOrigin)
	{
		$quote = $this->_stubSingleShipSameAsBill();
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
	 * @large
	 */
	public function testCheckShippingOriginAddresses()
	{
		$quote = $this->_stubSingleShipSameAsBill();
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
		$orderItemsProperty->setValue($request, array(
			'1' => array(
				'id' => 1,
				'ShippingOrigin' => array(
					'Line1'        => '1075 First Avenue',
					'Line2'        => 'Line2',
					'Line3'        => 'Line3',
					'Line4'        => 'Line4',
					'City'         => 'King Of Prussia',
					'MainDivision' => 'PA',
					'CountryCode'  => 'US',
					'PostalCode'   => '19406',
				)
			)
		));
		$this->assertNull($request->checkShippingOriginAddresses($quote));
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @large
	 */
	public function testCheckAdminOriginAddresses()
	{
		$quote = $this->_stubSingleShipSameAsBill();
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));

		$this->assertNull(
			$request->checkAdminOriginAddresses()
		);

		// testing when adminOrigin has changed.
		$requestReflector = new ReflectionObject($request);
		$orderItemsProperty = $requestReflector->getProperty('_orderItems');
		$orderItemsProperty->setAccessible(true);
		$orderItemsProperty->setValue($request, array(
			'1' => array(
				'id'           => 1,
				'AdminOrigin'  => array(
					'Line1'        => 'This is not a test, it\'s difficulty',
					'Line2'        => 'Line2',
					'Line3'        => 'Line3',
					'Line4'        => 'Line4',
					'City'         => 'King Of Prussia',
					'MainDivision' => 'PA',
					'CountryCode'  => 'US',
					'PostalCode'   => '19406'
				)
			)
		));
		$this->assertNull($request->checkAdminOriginAddresses());

		// Testing the behavior of the checkAdminOriginAddresses method
		// when the _hasChanged property is set from a previous process
		$requestReflector = new ReflectionObject($request);
		$hasChangesProperty = $requestReflector->getProperty('_hasChanges');
		$hasChangesProperty->setAccessible(true);
		$hasChangesProperty->setValue($request, true);
		$this->assertNull($request->checkAdminOriginAddresses());
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
}
