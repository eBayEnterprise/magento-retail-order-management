<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_observer;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();

		$this->_observer = Mage::getModel('eb2cinventory/observer');

		$cartMock = $this->getModelMockBuilder('checkout/cart', array('getCheckoutSession', 'addNotice'))
			->disableOriginalConstructor()
			->getMock();
		$cartMock->expects($this->any())
			->method('getCheckoutSession')
			->will($this->returnSelf());
		$cartMock->expects($this->any())
			->method('addNotice')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'checkout/cart', $cartMock);
	}

	public function providerCheckEb2cInventoryQuantity()
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('collectTotals', 'save', 'deleteItem')
		);
		$quoteMock->expects($this->any())
			->method('collectTotals')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('save')
			->will($this->returnValue(1)
			);
		$quoteMock->expects($this->any())
			->method('deleteItem')
			->will($this->returnValue(1)
			);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getId', 'getQty', 'setQty', 'getProductId', 'getSku', 'getQuote')
		);
		$itemMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('setQty')
			->will($this->returnSelf()
			);
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getProductId')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getSku')
			->will($this->returnValue('SKU-1234')
			);
		$itemMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock)
			);
		$itemMock->expects($this->any())
			->method('setQty')
			->will($this->returnSelf()
			);

		$eventMock = $this->getMock(
			'Varien_Event',
			array('getItem')
		);
		$eventMock->expects($this->any())
			->method('getItem')
			->will($this->returnValue($itemMock));

		$observerMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing when eb2c quantity check is out of stock
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 * @dataProvider providerCheckEb2cInventoryQuantity
	 * @loadFixture loadConfig.yaml
	 */
	public function testCheckEb2cInventoryQuantityOutOfStock($observer)
	{
		// testing when available stock is less, than what shopper requested.
		$quantityMock = $this->getMock(
			'TrueAction_Eb2cInventory_Model_Quantity',
			array('requestQuantity')
		);
		$quantityMock->expects($this->any())
			->method('requestQuantity')
			->will($this->returnValue(0)
			);

		$this->_observer->setQuantity($quantityMock);

		$this->assertNull(
			$this->_observer->checkEb2cInventoryQuantity($observer)
		);
	}

	/**
	 * testing when eb2c quantity check is less than what shopper requested
	 *
	 * @test
	 * @medium
	 * @dataProvider providerCheckEb2cInventoryQuantity
	 * @loadFixture loadConfig.yaml
	 */
	public function testCheckEb2cInventoryQuantityLessThanRequested($observer)
	{
		// testing when available stock is less, than what shopper requested.
		$quantityMock = $this->getMock(
			'TrueAction_Eb2cInventory_Model_Quantity',
			array('requestQuantity')
		);
		$quantityMock->expects($this->any())
			->method('requestQuantity')
			->will($this->returnValue(0.5)
			);

		$this->_observer->setQuantity($quantityMock);

		$this->assertNull(
			$this->_observer->checkEb2cInventoryQuantity($observer)
		);
	}

	/**
	 * testing when eb2c quantity check is less than what shopper requested
	 *
	 * @test
	 * @medium
	 * @dataProvider providerCheckEb2cInventoryQuantity
	 * @loadFixture loadConfig.yaml
	 */
	public function testCheckEb2cInventoryQuantityRollbackExistingAllocation($observer)
	{
		$allocationMock = $this->getModelMock(
			'eb2cinventory/allocation',
			array('hasAllocation', 'rollbackAllocation')
		);
		$allocationMock->expects($this->once())
			->method('hasAllocation')
			->with($this->identicalTo($observer->getEvent()->getItem()->getQuote()))
			->will($this->returnValue(true));
		$allocationMock->expects($this->once())
			->method('rollbackAllocation')
			->with($this->identicalTo($observer->getEvent()->getItem()->getQuote()));

		// testing when available stock is less, than what shopper requested.
		$quantityMock = $this->getModelMock('eb2cinventory/quantity', array('requestQuantity'));
		$quantityMock->expects($this->any())
			->method('requestQuantity')
			->will($this->returnValue(1));

		$this->_observer->setQuantity($quantityMock);
		$this->_observer->setAllocation($allocationMock);

		$this->assertNull(
			$this->_observer->checkEb2cInventoryQuantity($observer)
		);
	}

	public function providerProcessInventoryDetails()
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
			->will($this->returnValue(array('1938 Some Street'))
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
			array('getQty', 'getId', 'getSku')
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

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById')
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
		$eventMock = $this->getMock(
			'Varien_Event',
			array('getQuote')
		);
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$observerMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing inventory detail observer
	 *
	 * @test
	 * @dataProvider providerProcessInventoryDetails
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessInventoryDetails($observer)
	{
		$this->assertNull(
			$this->_observer->processInventoryDetails($observer)
		);
	}

	public function providerProcessEb2cAllocation()
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
			->will($this->returnValue(array('1938 Some Street'))
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
			array('getQty', 'getId', 'getSku', 'save')
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
			->method('save')
			->will($this->returnValue(1)
			);

		$addressMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'getAllAddresses')
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
			->method('getAllAddresses')
			->will($this->returnValue(array($addressMock))
			);
		$eventMock = $this->getMock(
			'Varien_Event',
			array('getQuote')
		);
		$eventMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($quoteMock));

		$observerMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing processing allocation observer
	 *
	 * @test
	 * @dataProvider providerProcessEb2cAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessEb2cAllocation($observer)
	{
		$quote = $observer->getEvent()->getQuote();
		// testing when you can allocated inventory
		$this->assertNull(
			$this->_observer->processEb2cAllocation($observer)
		);
	}

	/**
	 * testing when a quantity can no be allocated for a quote
	 *
	 * @test
	 * @expectedException TrueAction_Eb2cInventory_Model_Allocation_Exception
	 * @dataProvider providerProcessEb2cAllocation
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessEb2cAllocationError($observer)
	{
		$sessionMock = $this->getModelMockBuilder('checkout/session', array('addError'))
			->disableOriginalConstructor()
			->getMock();
		$sessionMock->expects($this->any())
			->method('addError')
			->will($this->returnSelf());
		$this->replaceByMock('singleton', 'checkout/session', $sessionMock);

		$inventoryHelperMock = $this->getHelperMock('eb2cinventory/data', array('getOperationUri'));
		$inventoryHelperMock->expects($this->any())
			->method('getOperationUri')
			->will($this->returnValue('http://eb2c.rgabriel.mage.tandev.net/eb2c/api/request/AllocationResponseMessage.xml'));
		$this->replaceByMock('helper', 'eb2cinventory', $inventoryHelperMock);

		$alloc = Mage::getModel('eb2cinventory/allocation');
		$quote = $observer->getEvent()->getQuote();
		$response = $alloc->allocateQuoteItems($quote);

		// testing when allocation error occurred.
		$allocationMock = $this->getModelMock(
			'eb2cinventory/allocation',
			array('processAllocation', 'allocateQuoteItems')
		);
		$allocationMock->expects($this->any())
			->method('processAllocation')
			->will($this->returnValue(array(array('Sorry, item "2610" out of stock.'))));
		$allocationMock->expects($this->any())
			->method('allocateQuoteItems')
			->will($this->returnValue($response));

		$this->_observer->setAllocation($allocationMock);

		$this->assertNull(
			$this->_observer->processEb2cAllocation($observer)
		);
	}

	public function providerRollbackOnRemoveItemInReservedCart()
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
			->will($this->returnValue(array('1938 Some Street'))
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
			array('getQty', 'getId', 'getSku', 'save', 'getQuote')
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
			->method('save')
			->will($this->returnValue(1)
			);

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getAllItems', 'getShippingAddress', 'getItemById', 'getAllAddresses')
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
			->method('getAllAddresses')
			->will($this->returnValue(array($quoteMock))
			);
		$eventMock = $this->getMock(
			'Varien_Event',
			array('getQuoteItem')
		);
		$eventMock->expects($this->any())
			->method('getQuoteItem')
			->will($this->returnValue($itemMock));

		$observerMock = $this->getMock(
			'Varien_Event_Observer',
			array('getEvent')
		);
		$observerMock->expects($this->any())
			->method('getEvent')
			->will($this->returnValue($eventMock));
		return array(
			array($observerMock)
		);
	}

	/**
	 * testing processing rolling back allocation on delete observer
	 *
	 * @test
	 * @dataProvider providerRollbackOnRemoveItemInReservedCart
	 * @loadFixture loadConfig.yaml
	 */
	public function testRollbackOnRemoveItemInReservedCart($observer)
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
				$this->returnValue('')
			);
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		// mockup to allow rollback on event to occur.
		$allocationMock = $this->getMock(
			'TrueAction_Eb2cInventory_Model_Allocation',
			array('hasAllocation')
		);
		$allocationMock->expects($this->any())
			->method('hasAllocation')
			->will($this->returnValue(true));

		$this->_observer->setAllocation($allocationMock);

		$this->assertNull(
			$this->_observer->rollbackOnRemoveItemInReservedCart($observer)
		);
	}
}
