<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_AllocationTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_allocation;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_allocation = Mage::getModel('eb2cinventory/allocation');

		// @fixme can this be a mock helper?
		$newHelper = new TrueAction_Eb2cInventory_Helper_Data();
		$allocationReflector = new ReflectionObject($this->_allocation);
		$helper = $allocationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_allocation, $newHelper);

		// @fixme avoid setupBaseUrl.
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
	}

	public function buildQuoteMock()
	{
		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
			array('getShippingMethod', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode', 'getAllItems')
		);
		$addressMock->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('USPS: 3 Day Select')
			);
		$addressMock->expects($this->any())
			->method('getStreet')
			->will($this->returnValue('1938 Some Street')
			);
		$addressMock->expects($this->any())
			->method('getCity')
			->will($this->returnValue('King of Prussia')
			);
		$addressMock->expects($this->any())
			->method('getRegion')
			->will($this->returnValue('Pennsylvania')
			);
		$addressMock->expects($this->any())
			->method('getCountryId')
			->will($this->returnValue('US')
			);
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue('19726')
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getQty', 'getId', 'getSku', 'getItemId', 'getQuote', 'save')
		);

		$addressMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);

		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('getItemId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'save', 'getAllAddresses')
		);

		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock)
			);

		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($addressMock)
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($addressMock))
			);

		return $quoteMock;
	}

	public function providerAllocateQuoteItems()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing allocating quote items
	 *
	 * @test
	 * @dataProvider providerAllocateQuoteItems
	 * @loadFixture loadConfig.yaml
	 */
	public function testAllocateQuoteItems($quote)
	{
		$inventoryHelperMock = $this->getMock(
			'TrueAction_Eb2cInventory_Helper_Data',
			array('getOperationUri')
		);
		$inventoryHelperMock->expects($this->any())
			->method('getOperationUri')
			->will($this->returnValue('http://eb2c.rgabriel.mage.tandev.net/eb2c/api/request/AllocationResponseMessage.xml'));

		$allocationReflector = new ReflectionObject($this->_allocation);
		$helper = $allocationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_allocation, $inventoryHelperMock);

		// testing when you can allocated inventory
		$this->assertNotNull(
			$this->_allocation->allocateQuoteItems($quote)
		);
	}

	/**
	 * testing when allocating quote item API call throw an exception
	 *
	 * @test
	 * @dataProvider providerAllocateQuoteItems
	 * @loadFixture loadConfig.yaml
	 */
	public function testAllocateQuoteItemsWithApiCallException($quote)
	{
		$apiModelMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Api',
			array('setUri', 'request')
		);
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());

		$apiModelMock->expects($this->any())
			->method('request')
			->will(
				$this->throwException(new Exception)
			);

		$inventoryHelper = Mage::helper('eb2cinventory');
		$inventoryReflector = new ReflectionObject($inventoryHelper);
		$apiModel = $inventoryReflector->getProperty('apiModel');
		$apiModel->setAccessible(true);
		$apiModel->setValue($inventoryHelper, $apiModelMock);

		$allocationReflector = new ReflectionObject($this->_allocation);
		$helper = $allocationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_allocation, $inventoryHelper);

		$this->assertSame(
			'',
			trim($this->_allocation->allocateQuoteItems($quote))
		);
	}

	public function providerBuildAllocationRequestMessage()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing building inventory details request message
	 *
	 * @test
	 * @dataProvider providerBuildAllocationRequestMessage
	 * @loadFixture loadConfig.yaml
	 */
	public function testBuildAllocationRequestMessage($quote)
	{
		// testing when you can allocated inventory
		$this->assertNotNull(
			$this->_allocation->buildAllocationRequestMessage($quote)
		);
	}

	public function providerBuildAllocationRequestMessageWithException()
	{
		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
			array('getShippingMethod', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode', 'getAllItems')
		);
		$addressMock->expects($this->any())
			->method('getShippingMethod')
			->will($this->returnValue('USPS: 3 Day Select')
			);
		$addressMock->expects($this->any())
			->method('getStreet')
			->will($this->returnValue('1938 Some Street')
			);
		$addressMock->expects($this->any())
			->method('getCity')
			->will($this->returnValue('King of Prussia')
			);
		$addressMock->expects($this->any())
			->method('getRegion')
			->will($this->returnValue('Pennsylvania')
			);
		$addressMock->expects($this->any())
			->method('getCountryId')
			->will($this->returnValue('US')
			);
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue('19726')
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getQty', 'getId', 'getSku', 'getItemId')
		);

		$addressMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);

		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue($this->throwException(new Exception)));
		$itemMock->expects($this->any())
			->method('getItemId')
			->will($this->returnValue(1)
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'save', 'getAllAddresses')
		);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		$quoteMock->expects($this->any())
			->method('getShippingAddress')
			->will($this->returnValue($this->throwException(new Exception))
			);
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock)
			);
		$quoteMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($addressMock))
			);

		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing building allocation request message
	 *
	 * @test
	 * @dataProvider providerBuildAllocationRequestMessageWithException
	 * @loadFixture loadConfig.yaml
	 */
	public function testBuildAllocationRequestMessageWithException($quote)
	{
		// testing when building the allocation message throw an exception
		$this->assertNotNull(
			$this->_allocation->buildAllocationRequestMessage($quote)
		);
	}

	public function providerProcessAllocation()
	{
		$allocationData = array(
			array(
				'lineId' => 1,
				'reservation_id' => 'TAN_DEV_CLI-ABC-44',
				'reservation_expires' => '2013-06-20 15:02:20',
				'qty' => 0
			)
		);

		return array(
			array($this->buildQuoteMock(), $allocationData)
		);
	}

	/**
	 * testing processing allocation data
	 *
	 * @test
	 * @dataProvider providerProcessAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessAllocation($quote, $allocationData)
	{
		$this->assertSame(
			array('Sorry, item "SKU-1234" out of stock.'),
			$this->_allocation->processAllocation($quote, $allocationData)
		);
	}

	public function providerUpdateQuoteWithEb2cAllocation()
	{
		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getQty', 'getId', 'getSku', 'getItemId', 'getQuote', 'save')
		);
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(2)
			);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('getItemId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($this->buildQuoteMock())
			);

		$quoteData = array(
			'lineId' => 1,
			'reservation_id' => 'TAN_DEV_CLI-ABC-44',
			'reservation_expires' => '2013-06-20 15:02:20',
			'qty' => 1
		);

		return array(
			array($itemMock, $quoteData)
		);
	}

	/**
	 * testing _updateQuoteWithEb2cAllocation method
	 *
	 * @test
	 * @dataProvider providerUpdateQuoteWithEb2cAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testUpdateQuoteWithEb2cAllocation($quoteItem, $quoteData)
	{
		$allocationReflector = new ReflectionObject($this->_allocation);
		$updateQuoteWithAllocation = $allocationReflector->getMethod('_updateQuoteWithEb2cAllocation');
		$updateQuoteWithAllocation->setAccessible(true);
		$this->assertSame(
			'Sorry, we only have 1 of item "SKU-1234" in stock.',
			$updateQuoteWithAllocation->invoke($this->_allocation, $quoteItem, $quoteData)
		);
	}

	public function providerRollbackAllocation()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing rollbackAllocation method
	 *
	 * @test
	 * @dataProvider providerRollbackAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testRollbackAllocation($quote)
	{
		// testing when you can rolling back allocated inventory
		$this->assertNotNull(
			$this->_allocation->rollbackAllocation($quote)
		);
	}

	/**
	 * testing when rolling back allocation quote item API call throw an exception
	 *
	 * @test
	 * @dataProvider providerRollbackAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testRollbackAllocationWithApiCallException($quote)
	{
		$apiModelMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Api',
			array('setUri', 'request')
		);
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());

		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->throwException(new Exception));

		$inventoryHelper = Mage::helper('eb2cinventory');
		$inventoryReflector = new ReflectionObject($inventoryHelper);
		$apiModel = $inventoryReflector->getProperty('apiModel');
		$apiModel->setAccessible(true);
		$apiModel->setValue($inventoryHelper, $apiModelMock);

		$allocationReflector = new ReflectionObject($this->_allocation);
		$helper = $allocationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_allocation, $inventoryHelper);

		$this->assertSame(
			'',
			trim($this->_allocation->rollbackAllocation($quote))
		);
	}

	public function providerHasAllocation()
	{
		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getEb2cReservationId')
		);
		$itemMock->expects($this->any())
			->method('getEb2cReservationId')
			->will($this->returnValue('FAKE-RESERVATION-ID')
			);
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems')
		);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing when allocation data is found in quote items
	 *
	 * @test
	 * @dataProvider providerHasAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testHasAllocation($quote)
	{
		// testing when building the allocation message throw an exception
		$this->assertSame(
			true,
			$this->_allocation->hasAllocation($quote)
		);
	}

	public function providerIsExpired()
	{
		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getEb2cReservationExpires')
		);
		$itemMock->expects($this->any())
			->method('getEb2cReservationExpires')
			->will($this->returnValue('2013-06-26 16:42:20')
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems')
		);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing isExpired method
	 *
	 * @test
	 * @dataProvider providerIsExpired
	 * @loadFixture loadConfig.yaml
	 */
	public function testIsExpired($quote)
	{
		$this->assertSame(
			true,
			$this->_allocation->isExpired($quote)
		);
	}

	public function providerIsExpiredReturnFalse()
	{
		$expiredDateTime = new DateTime(gmdate('c'));
		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getEb2cReservationExpires')
		);
		$itemMock->expects($this->any())
			->method('getEb2cReservationExpires')
			->will($this->returnValue($expiredDateTime->format('c'))
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems')
		);
		$quoteMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);
		return array(
			array($quoteMock)
		);
	}

	/**
	 * testing isExpired method
	 *
	 * @test
	 * @dataProvider providerIsExpiredReturnFalse
	 * @loadFixture loadConfig.yaml
	 */
	public function testIsExpiredReturnFalse($quote)
	{
		$this->assertSame(
			false,
			$this->_allocation->isExpired($quote)
		);
	}
}
