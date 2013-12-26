<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Helper_DataTest extends TrueAction_Eb2cCore_Test_Base
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->clearStoreConfigCache();
		// FYI: instantiating using regular Mage::getHelper method create
		// a singleton oject which mess with load fixtures for the config
		$this->_helper = new TrueAction_Eb2cInventory_Helper_Data();
	}

	/**
	 * testing getXmlNs method
	 *
	 * @test
	 */
	public function testGetXmlNs()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			$this->_helper->getXmlNs()
		);
	}

	/**
	 * testing getOperationUri method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/quantity/get.xml',
			$this->_helper->getOperationUri('check_quantity')
		);

		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/details/get.xml',
			$this->_helper->getOperationUri('get_inventory_details')
		);

		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/allocations/create.xml',
			$this->_helper->getOperationUri('allocate_inventory')
		);

		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id/inventory/allocations/delete.xml',
			$this->_helper->getOperationUri('rollback_allocation')
		);
	}

	/**
	 * testing getOperationUri method with a store other than the default
	 *
	 * @test
	 * @loadFixture
	 */
	public function testGetOperationUriNonDefaultStore()
	{
		$this->assertSame('store_id2', Mage::getStoreConfig('eb2ccore/general/store_id', 'canada'), 'storeid for canada not retrieved');
		$this->setCurrentStore('canada');
		// check to make sure that if the current store has another value for store id,
		// the store level value is chosen over the default.
		$this->assertSame(
			'https://api.example.com/vM.m/stores/store_id2/inventory/allocations/delete.xml',
			$this->_helper->getOperationUri('rollback_allocation')
		);
	}

	public function providerGetRequestId()
	{
		return array(
			array(43)
		);
	}

	/**
	 * testing helper data getRequestId method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetRequestId
	 */
	public function testGetRequestId($entityId)
	{
		$this->assertSame(
			'client_id-store_id-43',
			$this->_helper->getRequestId($entityId)
		);
	}

	public function providerGetReservationId()
	{
		return array(
			array(43)
		);
	}

	/**
	 * testing helper data getReservationId method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetReservationId
	 */
	public function testGetReservationId($entityId)
	{
		$this->assertSame(
			'client_id-store_id-43',
			$this->_helper->getReservationId($entityId)
		);
	}
	/**
	 * Create a stub Mage_Sales_Model_Quote_Item for checking if the item is inventoried
	 * @param  boolean $isVirtual Is the item virtual
	 * @param  boolean $isManaged Is the product managed stock
	 * @return Mock_Mage_Sales_Model_Quote_Item Stub which will report the item as being virtual and/or with a "managed_stock" stock item
	 */
	protected function _stubQuoteItem($isVirtual, $isManaged)
	{
		$item = $this->getModelMock('sales/quote_item', array('getProduct', 'getIsVirtual'));
		$prod = $this->getModelMock('catalog/product', array('getStockItem'));
		$stockItem = $this->getModelMock('cataloginventory/stock_item', array('getManageStock'));
		$item
			->expects($this->any())
			->method('getIsVirtual')
			->will($this->returnValue($isVirtual));
		$item
			->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($prod));
		$prod
			->expects($this->any())
			->method('getStockItem')
			->will($this->returnValue($stockItem));
		$stockItem
			->expects($this->any())
			->method('getManageStock')
			->will($this->returnValue($isManaged));
		return $item;
	}
	/**
	 * Data provider for the testFilterInventoredItem test. Providers a quote item
	 * and whether that item should be considered an "inventoried" item
	 * @return array Arguments array with Mage_Sales_Model_Quote_Item and boolean
	 */
	public function providerFilterInventoriedItem()
	{
		return array(
			array($this->_stubQuoteItem(true, true), false),
			array($this->_stubQuoteItem(false, false), false),
			array($this->_stubQuoteItem(false, true), true),
		);
	}
	/**
	 * Test detecting an item that is or is not inventoried.
	 * @param  Mage_Sales_Model_Quote_Item $item          Quote item
	 * @param  boolean                     $isInventoried Is the item inventoried
	 * @test
	 * @dataProvider providerFilterInventoriedItem
	 */
	public function testFilterInventoriedItem($item, $isInventoried)
	{
		$this->assertSame($isInventoried, Mage::helper('eb2cinventory')->isItemInventoried($item));
	}

	/**
	 * Test filtering a list of quote items down to only those that are inventoried items
	 * @return [type] [description]
	 */
	public function testGetInventoriedItemsFromQuote()
	{
		$items = array(
			Mage::getModel('sales/quote_item'),
			Mage::getModel('sales/quote_item'),
		);
		$inventoryItems = array($items[0]);
		$helper = $this->getHelperMock('eb2cinventory/data', array('isItemInventoried'));
		$this->replaceByMock('helper', 'eb2cinventory', $helper);
		$helper
			->expects($this->exactly(2))
			->method('isItemInventoried')
			->with($this->isInstanceOf('Mage_Sales_Model_Quote_Item'))
			->will($this->onConsecutiveCalls(array(true, false)));
		$this->assertSame(
			$inventoryItems,
			Mage::helper('eb2cinventory')->getInventoriedItems($items)
		);
	}
	/**
	 * Data provider for the testBlockingStatusCodes method. Providers a status code
	 * and whether or not is should be considered a blocking status.
	 * @return array Args arrays or ints and bools
	 */
	public function providerBlockingStatusCodes()
	{
		return array(
			// all 2XX range codes OK
			array(200, false),
			// all 3XX range codes OK
			array(302, false),
			// 400 (Bad Request) through 407 (Proxy Authentication Required) should all be blocking
			array(400, true),
			array(401, true),
			array(402, true),
			array(403, true),
			array(406, true),
			array(407, true),
			// 408 (Request Timeout) should be non-blocking
			array(408, false),
			// Rest of the 4XX range, 409 (Conflict) - 417 (Expectation Failed) should block
			array(409, true),
			array(410, true),
			// 500 (Internal Server Error) through 504 (Gateway Timeout) should be non-blocking
			// potential issue on EP end
			array(500, false),
			array(502, false),
			// 505 (HTTP Version Not Supported) likely means the version of HTTP Magento end is using will have to change
			// and is a blocking issue
			array(505, true),
		);
	}
	/**
	 * Test detecting a blocking vs non-blocking status code. For the most part:
	 * Status codes indicating errors on the Magento end should be blocking, e.g. most 4XX codes.
	 * Status codes that indicate an error on the EP side should usually be non-blocking, e.g. most 5XX codes.
	 * Status codes that indicate success should, obviously, be non-blocking.
	 * @param  int    $statusCode HTTP Status code
	 * @param  bool   $isBlocking Is this a blocking status code
	 * @test
	 * @dataProvider providerBlockingStatusCodes
	 */
	public function testBlockingStatusCodes($statusCode, $isBlocking)
	{
		$this->assertSame($isBlocking, Mage::helper('eb2cinventory')->isBlockingStatus($statusCode));
	}
}
