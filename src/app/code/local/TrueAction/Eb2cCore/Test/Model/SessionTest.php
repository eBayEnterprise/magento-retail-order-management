<?php

class TrueAction_Eb2cCore_Test_Model_SessionTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Create a stub quote item scripted to return the given sku and qty
	 * @param  string  $sku       Quote item sku
	 * @param  int     $qty       Qty of the item in the cart
	 * @param  boolean $isVirtual Is the item virtual
	 * @return Mock_Mage_Sales_Model_Quote_Item The stub quote item
	 */
	protected function _stubQuoteItem($sku, $qty, $isVirtual)
	{
		$item = $this->getModelMock('sales/quote_item', array('getSku', 'getQty', 'getIsVirtual'));
		$item
			->expects($this->any())
			->method('getSku')
			->will($this->returnValue($sku));
		$item
			->expects($this->any())
			->method('getQty')
			->will($this->returnValue($qty));
		$item
			->expects($this->any())
			->method('getIsVirtual')
			->will($this->returnValue($isVirtual));
		return $item;
	}
	/**
	 * Test extracting quote item sku data from a quote
	 * @test
	 */
	public function testExtractQuoteSkuData()
	{
		$quote = $this->getModelMock('sales/quote', array('getAllVisibleItems'));
		$helper = $this->getHelperMock('eb2cinventory/data', array('isItemInventoried'));
		$this->replaceByMock('helper', 'eb2cinventory', $helper);

		// first item is managed, second is not
		$items = array(
			$this->_stubQuoteItem('45-123', 3, false),
			$this->_stubQuoteItem('45-234', 1, false),
			$this->_stubQuoteItem('45-345', 1, true),
		);

		$helper
			->expects($this->any())
			->method('isItemInventoried')
			// returnValueMap to return first item as managed, second as nonmanaged
			->will($this->returnValueMap(array(
				array($items[0], true),
				array($items[1], false),
				array($items[2], false),
			)));
		$quote
			->expects($this->any())
			->method('getAllVisibleItems')
			->will($this->returnValue($items));

		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$method = $this->_reflectMethod($session, '_extractQuoteSkuData');
		$this->assertSame(
			array(
				'45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3),
				'45-234' => array('managed' => false, 'virtual' => false, 'qty' => 1),
				'45-345' => array('managed' => false, 'virtual' => true, 'qty' => 1),
			),
			$method->invoke($session, $quote)
		);
	}
	/**
	 * @test
	 */
	public function testExtractAddressData()
	{
		$addressData = array(
			'street' => array('630 Allendale Rd'),
			'city' => 'King of Prussia',
			'region_code' => 'PA',
			'country_id' => 'US',
			'postcode' => '19406',
		);
		$address = $this->getModelMock(
			'sales/quote_address',
			array('getStreet', 'getCity', 'getRegionCode', 'getCountryId', 'getPostcode')
		);
		$address
			->expects($this->any())
			->method('getStreet')
			->will($this->returnValue($addressData['street']));
		$address
			->expects($this->any())
			->method('getCity')
			->will($this->returnValue($addressData['city']));
		$address
			->expects($this->any())
			->method('getRegionCode')
			->will($this->returnValue($addressData['region_code']));
		$address
			->expects($this->any())
			->method('getCountryId')
			->will($this->returnValue($addressData['country_id']));
		$address
			->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue($addressData['postcode']));

		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$method = $this->_reflectMethod($session, '_extractAddressData');
		$this->assertSame($addressData, $method->invoke($session, $address));
	}
	public function providerTestExtractShippingData()
	{
		$quoteWithAddresses = $this->getModelMock('sales/quote', array('getAllShippingAddresses'));
		$address = $this->getModelMock(
			'sales/quote_address',
			array('getShippingMethod')
		);

		$quoteWithAddresses
			->expects($this->any())
			->method('getAllShippingAddresses')
			->will($this->returnValue(array($address)));
		$address
			->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('flatrate'));
		$addressData = array(
			'street' => array('123 Main St'),
			'city' => 'King of Prussia',
			'region_code' => 'PA',
			'country_id' => 'US',
			'postcode' => '19406',
		);

		$quoteNoAddresses = $this->getModelMock('sales/quote', array('getAllShippingAddresses'));
		$quoteNoAddresses
			->expects($this->any())
			->method('getAllShippingAddresses')
			->will($this->returnValue(array()));

		return array(
			array(
				$quoteWithAddresses,
				$address,
				$addressData,
				array(
					array(
						'method' => 'flatrate',
						'address' => $addressData,
					)
				),
			),
			array(
				$quoteNoAddresses,
				null,
				array(),
				array(),
			),
		);
	}
	/**
	 * Test extracting shipping data from a quote object. Should return array of data when shipping
	 * data exists. Empty array otherwise.
	 * @param  Mage_Sales_Model_Quote         $quote           The quote object
	 * @param  Mage_Sales_Model_Quote_Address $address         Shipping address for quote
	 * @param  array                          $addressData     Data extracted from shipping address
	 * @param  array                          $shippingExtract The exptected array of data
	 * @test
	 * @dataProvider providerTestExtractShippingData
	 */
	public function testExtractShippingData($quote, $address, $addressData, $shippingExtract)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_extractAddressData'))
			->getMock();
		if ($address && $addressData) {
			$session
				->expects($this->any())
				->method('_extractAddressData')
				->with($this->identicalTo($address))
				->will($this->returnValue($addressData));
		}
		$method = $this->_reflectMethod($session, '_extractQuoteShippingData');
		$this->assertSame($shippingExtract, $method->invoke($session, $quote));
	}
	/**
	 * Test extracting the current coupon code from a quote - should just get the
	 * coupon code from the quote and return it.
	 * @test
	 */
	public function testExtractCouponData()
	{
		$couponCode = 'coupon-code-123';
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$quote = $this->getModelMock('sales/quote', array('getCouponCode'));
		$quote->expects($this->once())->method('getCouponCode')->will($this->returnValue($couponCode));
		$method = $this->_reflectMethod($session, '_extractQuoteCouponData');
		$this->assertSame($couponCode, $method->invoke($session, $quote));
	}
	public function providerExtractBillingData()
	{
		return array(
			array($this->getModelMock('sales/quote_address'), array('street' => array('630 Allendale Rd'), 'city' => 'King of Prussia')),
			array(null, array()),
		);
	}
	/**
	 * Test extracting billing address data from a quote. When the quote has a billing
	 * address, should return array of address data (extracted via _extractAddressData).
	 * When the quote does not have a billing address, should return an empty array.
	 * @param  Mage_Sales_Model_Quote_Address|null $billingAddress Quote billing address object
	 * @param  array                               $extractedData  Data exptected to be extracted
	 * @test
	 * @dataProvider providerExtractBillingData
	 */
	public function testExtractQuoteBillingData($billingAddress, $extractedData)
	{
		$quote = $this->getModelMock('sales/quote', array('getBillingAddress'));
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_extractAddressData'))
			->getMock();

		$quote->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue($billingAddress));
		if ($billingAddress) {
			$session
				->expects($this->once())
				->method('_extractAddressData')
				->with($this->identicalTo($billingAddress))
				->will($this->returnValue($extractedData));
		} else {
			$session->expects($this->never())
				->method('_extractAddressData');
		}

		$method = $this->_reflectMethod($session, '_extractQuoteBillingData');
		$this->assertSame($extractedData, $method->invoke($session, $quote));
	}
	/**
	 * @test
	 * @dataProvider providerExtractBillingData
	 */
	public function testExtractQuoteData()
	{
		$quote = $this->getModelMock('sales/quote');
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_extractQuoteCouponData', '_extractQuoteBillingData', '_extractQuoteShippingData', '_extractQuoteSkuData'))
			->getMock();
		$couponData = 'coupon-code-123';
		$shipData = array(array('method' => 'flatrate', 'address' => array('street' => array('630 Allendale Rd'), 'city' => 'King of Prussia')));
		$billData = array('street' => array('1075 1st Ave'), 'city' => 'King of Prussia');
		$skuData = array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3));

		$session->expects($this->once())->method('_extractQuoteBillingData')->with($this->identicalTo($quote))->will($this->returnValue($billData));
		$session->expects($this->once())->method('_extractQuoteCouponData')->with($this->identicalTo($quote))->will($this->returnValue($couponData));
		$session->expects($this->once())->method('_extractQuoteShippingData')->with($this->identicalTo($quote))->will($this->returnValue($shipData));
		$session->expects($this->once())->method('_extractQuoteSkuData')->with($this->identicalTo($quote))->will($this->returnValue($skuData));

		$method = $this->_reflectMethod($session, '_extractQuoteData');
		$this->assertSame(
			array('billing' => $billData, 'coupon' => $couponData, 'shipping' => $shipData, 'skus' => $skuData),
			$method->invoke($session, $quote)
		);
	}
	/**
	 * Data provider for diffBilling test. Providers array of old address data,
	 * new address data and the expected diff.
	 * @return array Args array
	 */
	public function providerDiffBilling()
	{
		$old = array('street' => array('1075 1st Ave'), 'city' => 'King of Prussia');
		$new = array('street' => array('630 Allendale Rd'), 'city' => 'King of Prussia');
		return array(
			array($old, $new, array('billing' => $new)),
			array($new, $new, array()),
			array(array(), $new, array('billing' => $new)),
		);
	}
	/**
	 * Test diffing billing data. When data has changed, should return array
	 * with "billing" key containing the changed data. Currently, this either returns
	 * an empty array or the array with "billing" set to the full address data.
	 * @param  array  $old  Old billing address
	 * @param  array  $new  New billing address
	 * @param  array  $diff Expected diff
	 * @test
	 * @dataProvider providerDiffBilling
	 */
	public function testDiffBilling($old, $new, $diff)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$method = $this->_reflectMethod($session, '_diffBilling');
		$this->assertSame($diff, $method->invoke($session, $old, $new));
	}
	/**
	 * Data provider for the diffCoupon test. Providers old coupon code,
	 * new coupon code and expected diff.
	 * @return array Args array
	 */
	public function providerDiffCoupon()
	{
		return array(
			array(null, 'free-lewt', array('coupon' => 'free-lewt')),
			array('zomg', 'free-lewt', array('coupon' => 'free-lewt')),
			array('zomg', null, array('coupon' => null)),
			array('free-lewt', 'free-lewt', array()),
		);
	}
	/**
	 * Test diffing an old coupon code to a new one. When coupon has changed, should return
	 * array with 'coupon' key containing the new code. When no change, should return
	 * an empty array.
	 * @param  string $old  Exiting coupon code
	 * @param  string $new  New coupon code
	 * @param  array  $diff Expected diff
	 * @test
	 * @dataProvider providerDiffCoupon
	 */
	public function testDiffCoupon($old, $new, $diff)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$method = $this->_reflectMethod($session, '_diffCoupon');
		$this->assertSame($diff, $method->invoke($session, $old, $new));
	}
	/**
	 * Data provider for the diffShipping test. Providers a set of
	 * "old" shipping data, a set of "new" shipping data and what the
	 * diff is expected to be.
	 * @return array Args array
	 */
	public function providerDiffShipping()
	{
		$old = array(array('method' => 'flatrate', 'address' => array('street' => array('630 Allendale Rd'), 'city' => 'King of Prussia')));
		$new = array(array('method' => 'standard', 'address' => array('street' => array('630 Allendale Rd'), 'city' => 'San Jose')));
		return array(
			array(null, $new, array('shipping' => $new)),
			array($new, $new, array()),
			array($old, $new, array('shipping' => $new)),
			array($new, null, array('shipping' => null)),
		);
	}
	/**
	 * Test diffing old shipping data to new shipping data. This check should
	 * be pretty dumb in that if any address data has changed, the address should
	 * be considered to have completely changed.
	 * @param  array  $old  Old address data
	 * @param  array  $new  New address data
	 * @param  array  $diff Expected diff of the data
	 * @test
	 * @dataProvider providerDiffShipping
	 */
	public function testDiffShipping($old, $new, $diff)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$method = $this->_reflectMethod($session, '_diffShipping');
		$this->assertSame($diff, $method->invoke($session, $old, $new));
	}
	/**
	 * Data provider for the diffSkus test. Provides set of "old" sku data,
	 * set of "new" sku data and what the expected diff should be.
	 * @return array Args array
	 */
	public function providerDiffSkus()
	{
		$old = array(
			'45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3),
			'45-234' => array('managed' => true, 'virtual' => false, 'qty' => 2),
		);
		$update = array(
			'45-123' => array('managed' => true, 'virtual' => false, 'qty' => 5),
			'45-234' => array('managed' => true, 'virtual' => false, 'qty' => 2),
		);
		$add = array(
			'45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3),
			'45-234' => array('managed' => true, 'virtual' => false, 'qty' => 2),
			'45-345' => array('managed' => false, 'virtual' => true, 'qty' => 1)
		);
		$delete = array(
			'45-234' => array('managed' => true, 'virtual' => false, 'qty' => 2),
		);
		return array(
			// when no old quote data, diff should be all new data
			array(
				array(),
				$old,
				array('skus' => $old),
			),
			// when updated, only items that were updated should be included
			array(
				$old,
				$update,
				array('skus' => array('45-123' => $update['45-123'])),
			),
			// when items are added, only added items should be included
			array(
				$old,
				$add,
				array('skus' => array('45-345' => $add['45-345'])),
			),
			// when deleted, the deleted item should be included with qty of 0
			array(
				$old,
				$delete,
				array('skus' => array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 0))),
			),
		);
	}
	/**
	 * Test diffing arrays of sku/item/quantity data. Any items that are updated
	 * should be present with the updated quantity, any items that were added
	 * should be present with the current quantity, any items that were deleted
	 * should be present with a quantity of 0.
	 * @param  array  $old  Old skus/item data
	 * @param  array  $new  New skus/item data
	 * @param  array  $diff Expected diff
	 * @test
	 * @dataProvider providerDiffSkus
	 */
	public function testDiffSkus($old, $new, $diff)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$method = $this->_reflectMethod($session, '_diffSkus');
		$this->assertSame($diff, $method->invoke($session, $old, $new));
	}
	public function providerDiffQuoteData()
	{
		$empty = array();
		$old = array('billing' => array(), 'coupon' => null, 'shipping' => array(), 'skus' => array());
		$new = array('billing' => array(), 'coupon' => 'free-lewt', 'shipping' => array(), 'skus' => array());

		$billDiff = array('billing' => array('street' => array('630 Allendale Rd')));
		$couponDiff = array('coupon' => 'zomg');
		$shipDiff = array('shipping' => array(array('method' => 'flatrate')));
		$itemDiff = array('skus' => array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3)));
		return array(
			//    old     new   bill       coupon       shipping   items      final
			array($old,   $new, $billDiff, $empty,      $empty,    $empty,    $billDiff),
			array($old,   $new, $billDiff, $couponDiff, $empty,    $empty,    $billDiff + $couponDiff),
			array($old,   $new, $empty,    $couponDiff, $shipDiff, $empty,    $couponDiff + $shipDiff),
			array($old,   $new, $billDiff, $couponDiff, $shipDiff, $itemDiff, $billDiff + $couponDiff + $shipDiff + $itemDiff),
		);
	}
	/**
	 * Test diffing new quote data to old quote data. This test is only for when both
	 * sets of quote data are set and not empty.
	 * @param  array $old      Old quote data
	 * @param  array $new      New quote data
	 * @param  array $billing  Diff of billing data
	 * @param  array $coupon   Diff of coupon data
	 * @param  array $shipping Diff of shipping data
	 * @param  array $items    Diff of item data
	 * @param  array $final    Expected diff of full qoute
	 * @test
	 * @dataProvider providerDiffQuoteData
	 */
	public function testDiffQuoteData($old, $new, $billing, $coupon, $shipping, $items, $final)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_hasInventoryExpired', '_diffBilling', '_diffCoupon', '_diffShipping', '_diffSkus'))
			->getMock();
		// assume none of the data has expired for now
		$session->expects($this->any())->method('_hasInventoryExpired')->will($this->returnValue(false));
		$session
			->expects($this->any())
			->method('_diffBilling')
			->with($this->identicalTo($old['billing']), $this->identicalTo($new['billing']))
			->will($this->returnValue($billing));
		$session
			->expects($this->any())
			->method('_diffCoupon')
			->with($this->identicalTo($old['coupon']), $this->identicalTo($new['coupon']))
			->will($this->returnValue($coupon));
		$session
			->expects($this->any())
			->method('_diffShipping')
			->with($this->identicalTo($old['shipping']), $this->identicalTo($new['shipping']))
			->will($this->returnValue($shipping));
		$session
			->expects($this->any())
			->method('_diffSkus')
			->with($this->identicalTo($old['skus']), $this->identicalTo($new['skus']))
			->will($this->returnValue($items));

		$method = $this->_reflectMethod($session, '_diffQuoteData');
		$this->assertSame($final, $method->invoke($session, $old, $new));
	}
	/**
	 * When the "old" quote data is empty, all of the new quote data should be returned
	 * as the changes - consider whole quote as having changed.
	 * @test
	 */
	public function testDiffQuoteDataReturnNewQuoteDataWhenOldQuoteIsEmpty()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$old = array();
		$new = array('all', 'of', 'this', 'should', 'be', 'returned', 'as', 'is');

		$method = $this->_reflectMethod($session, '_diffQuoteData');
		$this->assertSame($new, $method->invoke($session, $old, $new));
	}
	/**
	 * When quote data has expired, the entire new quote should be returned as the
	 * changes to the quote - consider whole quote as having changed.
	 * @test
	 */
	public function testDiffQuoteDataReturnNewQuoteDataWhenDataExpired()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_hasInventoryExpired'))
			->getMock();
		$old = array('not', 'empty', 'expired', 'data');
		$new = array('all', 'of', 'this', 'is', 'returned', 'when', 'old', 'quote', 'expired');

		$session
			->expects($this->once())
			->method('_hasInventoryExpired')
			->with($this->identicalTo($old))
			->will($this->returnValue(true));

		$method = $this->_reflectMethod($session, '_diffQuoteData');
		$this->assertSame($new, $method->invoke($session, $old, $new));
	}
	/**
	 * Test getting the oldest possible unexpired timestamp - time X minutes ago, where
	 * X is the config setting for the inventory expiration.
	 * @test
	 */
	public function testGetInventoryTimeout()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$method = $this->_reflectMethod($session, '_getInventoryTimeout');
		$this->replaceCoreConfigRegistry(array('inventoryExpirationTime' => 15));
		$this->assertInstanceOf('DateTime', $method->invoke($session));
	}
	/**
	 * Data provider for the testHasQuoteExpired tests. Provide the quote data
	 * and whether or not the quantity has expired.
	 * @return array $quoteData and $isExpired arguments
	 */
	public function providerHasInventoryExpired()
	{
		return array(
			// last_updated far in the past, should fail
			array(array('last_updated' => '2000-01-01T12:00:00+00:00'), true),
			// last_updated within limit should pass
			array(array('last_updated' => gmdate('c')), false),
			// last_updated doesn't exist should auto-fail, considered never updated
			array(array(), true),
		);
	}
	/**
	 * Test checking if quote data in the session has expired
	 * @param  array   $quoteData Array of quote data, possibly containing a 'last_updated' key
	 * @param  boolean $isExpired Should the quote data be considered expired
	 * @test
	 * @dataProvider providerHasInventoryExpired
	 */
	public function testHasInventoryExpired($quoteData, $isExpired)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_getInventoryTimeout'))
			->getMock();
		$session
			->expects($this->any())
			->method('_getInventoryTimeout')
			->will($this->returnValue(new DateTime('15 minutes ago')));

		$method = $this->_reflectMethod($session, '_hasInventoryExpired');
		$this->assertSame($isExpired, $method->invoke($session, $quoteData));
	}
	/**
	 * Test getting the tax update required flag. Should just return the
	 * value of the magic data used to store the flag
	 * @test
	 */
	public function testIsTaxUpdateRequired()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$session->setTaxUpdateRequiredFlag(true);
		$this->assertTrue($session->isTaxUpdateRequired());
		$session->setTaxUpdateRequiredFlag(false);
		$this->assertFalse($session->isTaxUpdateRequired());
	}
	/**
	 * Test getting the quantity update required flag. Should just return the
	 * value of the magic data used to store the flag
	 * @test
	 */
	public function testIsQuantityUpdateRequired()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$session->setQuantityUpdateRequiredFlag(true);
		$this->assertTrue($session->isQuantityUpdateRequired());
		$session->setQuantityUpdateRequiredFlag(false);
		$this->assertFalse($session->isQuantityUpdateRequired());
	}
	/**
	 * Test getting the details update required flag. Should just return the value
	 * of the magic data used to store the flag
	 * @test
	 */
	public function testIsDetailsUpdateRequired()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$session->setDetailsUpdateRequiredFlag(true);
		$this->assertTrue($session->isDetailsUpdateRequired());
		$session->setDetailsUpdateRequiredFlag(false);
		$this->assertFalse($session->isDetailsUpdateRequired());
	}
	/**
	 * Test resetting the tax update required flag. Calling this method should force the
	 * flag go to be unset.
	 * @test
	 */
	public function testResetTaxUpdateRequired()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$session->setTaxUpdateRequiredFlag(true);
		$this->assertSame($session, $session->resetTaxUpdateRequired());
		$this->assertNull($session->getTaxUpdateRequiredFlag());
	}
	/**
	 * Test resetting the inventory quantity update required flag. Calling this method should
	 * force the flag go to be unset.
	 * @test
	 */
	public function testResetQuantityUpdateRequired()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$session->setQuantityUpdateRequiredFlag(true);
		$this->assertSame($session, $session->resetQuantityUpdateRequired());
		$this->assertNull($session->getQuantityUpdateRequiredFlag());
	}
	/**
	 * Test resetting the inventory details update required flag. Calling this method should force the
	 * flag go to be unset.
	 * @test
	 */
	public function testResetDetailsUpdateRequired()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$session->setTaxUpdateRequiredFlag(true);
		$this->assertSame($session, $session->resetDetailsUpdateRequired());
		$this->assertNull($session->getDetailsUpdateRequiredFlag());
	}
	/**
	 * Test getting the diff of the "old" quote to the new quote. Should just return
	 * whatever was set in the "magic" data when the quote data was last updated.
	 * @test
	 */
	public function testGetQuoteChanges()
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$changes = array('skus' => array('45-123' => array('qty' => 3)));
		$session->setQuoteChanges($changes);
		$this->assertSame($changes, $session->getQuoteChanges());
	}
	/**
	 * Data provider for the updateWithQuote test method. Provides the set of changes
	 * in the quote and whether the set of changes should result in taxes, quantity
	 * and/or details being flagged to be updated.
	 * @return array Args array of booleans for which flags should be set
	 */
	public function providerUpdateWithQuote()
	{
		return array(
			//    currFlag newFlag
			array(true,    false),
			array(false,   true),
			array(false,   false),
		);
	}
	/**
	 * Test updating the quote data stored in the session with a new quote. Method should
	 * extract data from the new quote (using appropriate methods), and then compare it to
	 * the previously checked quote data (again, using appropriate methods). The diff data
	 * should then be examined for indicating that an update is needed to tax and/or inventory
	 * requests. After running this method, the quote data, diff data and all flags should
	 * be updated based on the changes made to the quote.
	 * @param  boolean $currFlag    Is details already flagged for updates
	 * @param  boolean $changeFlag  Should these changes require details updates
	 * @test
	 * @dataProvider providerUpdateWithQuote
	 */
	public function testUpdateWithQuote($currFlag, $changeFlag) {
		$quote = $this->getModelMock('sales/quote');
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array(
				'getCurrentQuoteData', 'setCurrentQuoteData', 'setQuoteChanges',
				'_extractQuoteData', '_diffQuoteData',
				'getTaxUpdateRequiredFlag', 'getQuantityUpdateRequiredFlag', 'getDetailsUpdateRequiredFlag',
				'setTaxUpdateRequiredFlag', 'setQuantityUpdateRequiredFlag', 'setDetailsUpdateRequiredFlag',
				'_changeRequiresTaxUpdate', '_changeRequiresQuantityUpdate', '_changeRequiresDetailsUpdate',
			))
			->getMock();

		$newData = array('skus' => array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3)));
		$currentData = array('last_updated' => 'timestamp this was last updated');
		$diffData = array('skus' => array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3)));
		$newDataWithStamp = array('skus' => $newData['skus'], 'last_updated' => $currentData['last_updated']);

		$session
			->expects($this->any())
			->method('getCurrentQuoteData')
			->will($this->returnValue($currentData));
		$session
			->expects($this->once())
			->method('_extractQuoteData')
			->with($this->identicalTo($quote))
			->will($this->returnValue($newData));
		$session
			->expects($this->once())
			->method('_diffQuoteData')
			->with($this->identicalTo($currentData), $this->identicalTo($newDataWithStamp))
			->will($this->returnValue($diffData));

		// tax flag
		$session
			->expects($this->any())
			->method('getTaxUpdateRequiredFlag')
			->will($this->returnValue($currFlag));
		$session
			->expects($this->any())
			->method('_changeRequiresTaxUpdate')
			->with($this->identicalTo($newDataWithStamp), $this->identicalTo($diffData))
			->will($this->returnValue($changeFlag));
		$session
			->expects($this->once())
			->method('setTaxUpdateRequiredFlag')
			->with($this->identicalTo($changeFlag))
			->will($this->returnSelf());
		// quantity flag
		$session
			->expects($this->any())
			->method('getQuantityUpdateRequiredFlag')
			->will($this->returnValue($currFlag));
		$session
			->expects($this->any())
			->method('_changeRequiresQuantityUpdate')
			->with($this->identicalTo($newDataWithStamp), $this->identicalTo($diffData))
			->will($this->returnValue($changeFlag));
		$session
			->expects($this->once())
			->method('setQuantityUpdateRequiredFlag')
			->with($this->identicalTo($changeFlag))
			->will($this->returnSelf());
		// details flag
		$session
			->expects($this->any())
			->method('getDetailsUpdateRequiredFlag')
			->will($this->returnValue($currFlag));
		$session
			->expects($this->any())
			->method('_changeRequiresDetailsUpdate')
			->with($this->identicalTo($newDataWithStamp), $this->identicalTo($diffData))
			->will($this->returnValue($changeFlag));
		$session
			->expects($this->once())
			->method('setDetailsUpdateRequiredFlag')
			->with($this->identicalTo($changeFlag))
			->will($this->returnSelf());

		$session
			->expects($this->once())
			->method('setCurrentQuoteData')
			->with($this->identicalTo($newDataWithStamp))
			->will($this->returnSelf());
		$session
			->expects($this->once())
			->method('setQuoteChanges')
			->with($this->identicalTo($diffData))
			->will($this->returnSelf());

		$this->assertSame($session, $session->updateWithQuote($quote));
	}
	/**
	 * Test checking an array to have any item with the given key set to a truthy value.
	 * @test
	 */
	public function testAnyItem()
	{
		$items = array(array('foo' => true, 'bar' => false), array('foo' => false, 'bar' => false), array('foo' => true));
		$session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
		$method = $this->_reflectMethod($session, '_anyItem');
		$this->assertTrue($method->invoke($session, $items, 'foo'));
		$this->assertFalse($method->invoke($session, $items, 'bar'));
	}
	/**
	 * Test checking for any item in a list of items to include a virtual item - an
	 * item with a 'virtual' key set to true.
	 * @test
	 */
	public function testItemsIncludeVirtualItem()
	{
		$items = array('45-123' => array());
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_anyItem'))
			->getMock();
		// _anyItem will detect item, just make sure it's given the list of items and the right key
		$session
			->expects($this->once())
			->method('_anyItem')
			->with($this->identicalTo($items), $this->identicalTo('virtual'))
			->will($this->returnValue(true));
		$method = $this->_reflectMethod($session, '_itemsIncludeVirtualItem');
		$this->assertTrue($method->invoke($session, $items));
	}
	/**
	 * Test checking for any item in a list of items to include a managed stock
	 * item - an item with a 'managed' key set to true.
	 * @test
	 */
	public function testItemsIncludeManagedItem()
	{
		$items = array('45-123' => array());
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_anyItem'))
			->getMock();
		// _anyItem will detect item, just make sure it's given the list of items and the right key
		$session
			->expects($this->once())
			->method('_anyItem')
			->with($this->identicalTo($items), $this->identicalTo('managed'))
			->will($this->returnValue(true));
		$method = $this->_reflectMethod($session, '_itemsIncludeManagedItem');
		$this->assertTrue($method->invoke($session, $items));
	}
	/**
	 * Data provider of sample quote data changes. Providers an array of changes,
	 * whether the quote has virtual items and which flags should be set to true based on the
	 * diff data.
	 * @return array Args array of quote changes, if the quote has virtual items and how each flag should be set
	 */
	public function providerQuoteDiffs()
	{
		$quoteData = array('skus' => array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3)));
		$coupon = array('coupon' => 'zomg-deelz');
		$billing = array('billing' => array('street' => array('123 Main St'), 'city' => 'King of Prussia'));
		$shipping = array('shipping' => array(array('method' => 'flatrate')));
		$managed = array('skus' => array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 5)));
		$noManaged = array('skus' => array('45-234' => array('managed' => false, 'virtual' => true, 'qty' => 2)));

		return array(
			//    quoteData   diff        has virtual hasManged tax    qty    deets
			array($quoteData, $coupon,    false,      true,     true,  false, false),
			array($quoteData, $billing,   false,      true,     false, false, false),
			array($quoteData, $billing,   true,       true,     true,  false, false),
			array($quoteData, $shipping,  false,      true,     true,  false, true),
			array($quoteData, $shipping,  false,      false,    true,  false, false),
			array($quoteData, $managed,   false,      true,     true,  true,  true),
			array($quoteData, $noManaged, true,       false,    true,  false, false),
		);
	}
	/**
	 * Test checking the quote diff data for requiring tax data to be updated.
	 * @param  array   $quoteData  Array of quote data
	 * @param  array   $diffData   Array of quote changes
	 * @param  boolean $hasVirtual Does the quote contain virtual items
	 * @param  boolean $hasManaged Does the quote contain managed stock items
	 * @param  boolean $flagTax    Should this flag tax
	 * @test
	 * @dataProvider providerQuoteDiffs
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function testChangeRequiresTaxUpdate(
		$quoteData, $diffData, $hasVirtual, $hasManaged, $flagTax, $flagQty, $flagDeets
	) {
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_itemsIncludeVirtualItem', '_itemsIncludeManagedItem'))
			->getMock();
		$session
			->expects($this->any())
			->method('_itemsIncludeVirtualItem')
			->will($this->returnValue($hasVirtual));
		$session
			->expects($this->any())
			->method('_itemsIncludeManagedItem')
			->will($this->returnValue($hasManaged));
		$method = $this->_reflectMethod($session, '_changeRequiresTaxUpdate');
		$this->assertSame($flagTax, $method->invoke($session, $quoteData, $diffData));
	}
	/**
	 * Test checking the quote diff data for requiring tax data to be updated.
	 * @param  array   $quoteData  Array of quote data
	 * @param  array   $diffData   Array of quote changes
	 * @param  boolean $hasVirtual Does the quote contain virtual items
	 * @param  boolean $hasManaged Does the quote contain managed stock items
	 * @param  boolean $flagTax    Should this flag tax
	 * @param  boolean $flagQty    Should this flag quantity
	 * @param  boolean $flagDeets  Should this flag details
	 * @test
	 * @dataProvider providerQuoteDiffs
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function testChangeRequiresQuantityUpdate(
		$quoteData, $diffData, $hasVirtual, $hasManaged, $flagTax, $flagQty, $flagDeets
	) {
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_itemsIncludeVirtualItem', '_itemsIncludeManagedItem'))
			->getMock();
		$session
			->expects($this->any())
			->method('_itemsIncludeVirtualItem')
			->will($this->returnValue($hasVirtual));
		$session
			->expects($this->any())
			->method('_itemsIncludeManagedItem')
			->will($this->returnValue($hasManaged));
		$method = $this->_reflectMethod($session, '_changeRequiresQuantityUpdate');
		$this->assertSame($flagQty, $method->invoke($session, $quoteData, $diffData));
	}
	/**
	 * Test checking the quote diff data for requiring tax data to be updated.
	 * @param  array   $quoteData  Array of quote data
	 * @param  array   $diffData   Array of quote changes
	 * @param  boolean $hasVirtual Does the quote contain virtual items
	 * @param  boolean $hasManaged Does the quote contain managed stock items
	 * @param  boolean $flagTax    Should this flag tax
	 * @param  boolean $flagQty    Should this flag quantity
	 * @param  boolean $flagDeets  Should this flag details
	 * @test
	 * @dataProvider providerQuoteDiffs
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function testChangeRequiresDetailsUpdate(
		$quoteData, $diffData, $hasVirtual, $hasManaged, $flagTax, $flagQty, $flagDeets
	) {
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('_itemsIncludeVirtualItem', '_itemsIncludeManagedItem'))
			->getMock();
		$session
			->expects($this->any())
			->method('_itemsIncludeVirtualItem')
			->will($this->returnValue($hasVirtual));
		$session
			->expects($this->any())
			->method('_itemsIncludeManagedItem')
			->will($this->returnValue($hasManaged));
		$method = $this->_reflectMethod($session, '_changeRequiresDetailsUpdate');
		$this->assertSame($flagDeets, $method->invoke($session, $quoteData, $diffData));
	}
	/**
	 * Test updating quote inventory details with a given qoute. Should
	 * extract sku data from the quote and update the "current" quote data in the
	 * session with the updated list of items. Calling this method should also
	 * update the "last_updated" timestamp on the quote data.
	 * @test
	 */
	public function testUpdateQuoteInventory()
	{
		$lastUpdated = '2000-01-01T12:00:00+00:00';
		$skuDataExtract = array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 3));
		$oldData = array(
			'billing' => array(),
			'coupon' => 'free-lewt',
			'shipping' => array(),
			'skus' => array('45-123' => array('managed' => true, 'virtual' => false, 'qty' => 2), '45-234' => array('managed' => true, 'virtual' => false, 'qty' => 3)),
			'last_updated' => $lastUpdated,
		);

		$quote = $this->getModelMock('sales/quote');
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('getCurrentQuoteData', '_extractQuoteSkuData', 'setCurrentQuoteData'))
			->getMock();

		$session
			->expects($this->once())
			->method('getCurrentQuoteData')
			->will($this->returnValue($oldData));
		$session
			->expects($this->once())
			->method('_extractQuoteSkuData')
			->with($this->identicalTo($quote))
			->will($this->returnValue($skuDataExtract));
		$session
			->expects($this->once())
			->method('setCurrentQuoteData')
			->with($this->callback(
				// make sure the new quote data (arg to the callback) has been updated
				// as expected - billing, coupon and shipping data should all remain
				// the same. skus should match data extracted from the quote.
				// last_updated should have been updated to the current time - best
				// test I can think of for that is just making sure the new time is more recent that the old one
				function ($quoteData) use ($oldData, $skuDataExtract) {
					return $quoteData['billing'] === $oldData['billing'] &&
						$quoteData['coupon'] === $oldData['coupon'] &&
						$quoteData['shipping'] === $oldData['shipping'] &&
						$quoteData['skus'] === $skuDataExtract &&
						new DateTime($quoteData['last_updated']) > new DateTime($oldData['last_updated']);
				}
			));

		$this->assertSame($session, $session->updateQuoteInventory($quote));
	}

	public function provideTrueFalseSequence()
	{
		return array(
			array(true, false, true),
			array(false, true, true),
			array(false, false, false),
			array(null, true, true),
		);
	}
	/**
	 * make sure that once the value becomes true it remains true not matter what is set
	 * @param  bool $init    initial flag value
	 * @param  bool $current new value
	 * @param  bool $result  expected result
	 * @test
	 * @dataProvider provideTrueFalseSequence()
	 */
	public function testSetTaxUpdateRequired($init, $current, $result)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('noMockedMethods'))
			->getMock();
		$session->setTaxUpdateRequiredFlag($init);
		$session->setTaxUpdateRequired($current);
		$this->assertSame($result, $session->getTaxUpdateRequiredFlag());
	}
	/**
	 * make sure that once the value becomes true it remains true not matter what is set
	 * @param  bool $init    initial flag value
	 * @param  bool $current new value
	 * @param  bool $result  expected result
	 * @test
	 * @dataProvider provideTrueFalseSequence()
	 */
	public function testSetQuantityUpdateRequired($init, $current, $result)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('noMockedMethods'))
			->getMock();
		$session->setQuantityUpdateRequiredFlag($init);
		$session->setQuantityUpdateRequired($current);
		$this->assertSame($result, $session->getQuantityUpdateRequiredFlag());
	}
	/**
	 * make sure that once the value becomes true it remains true not matter what is set
	 * @param  bool $init    initial flag value
	 * @param  bool $current new value
	 * @param  bool $result  expected result
	 * @test
	 * @dataProvider provideTrueFalseSequence()
	 */
	public function testDetailsTaxUpdateRequired($init, $current, $result)
	{
		$session = $this->getModelMockBuilder('eb2ccore/session')
			->disableOriginalConstructor()
			->setMethods(array('noMockedMethods'))
			->getMock();
		$session->setDetailsUpdateRequiredFlag($init);
		$session->setDetailsUpdateRequired($current);
		$this->assertSame($result, $session->getDetailsUpdateRequiredFlag());
	}
}
