<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_Overrides_CartTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_cart;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_cart = Mage::getModel('eb2cinventoryoverride/cart');
	}

	public function providerAddProduct()
	{
		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('getId', 'getWebsiteIds', 'hasOptionsValidationFail')
		);
		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$productMock->expects($this->any())
			->method('getWebsiteIds')
			->will($this->returnValue(array(0,1,2,3,4)));
		$productMock->expects($this->any())
			->method('hasOptionsValidationFail')
			->will($this->returnValue(true));

		return array(
			array($productMock, null)
		);
	}

	/**
	 * testing addProduct method
	 *
	 * @test
	 * @large
	 * @dataProvider providerAddProduct
	 * @loadFixture loadWebsiteConfig.yaml
	 */
	public function testAddProduct($productInfo, $requestInfo=null)
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('addProduct')
		);
		$quoteMock->expects($this->any())
			->method('addProduct')
			->will($this->returnValue(array('some message')));

		$this->_cart->setQuote($quoteMock);

		$session = Mage::getSingleton('checkout/session');
		$session->setData('use_notice', null);

		$this->assertNotNull(
			$this->_cart->addProduct($productInfo, $requestInfo)
		);
	}

	/**
	 * testing addProduct method, with exception being thrown
	 *
	 * @test
	 * @large
	 * @dataProvider providerAddProduct
	 * @expectedException Mage_Core_Exception
	 * @loadFixture loadWebsiteConfig.yaml
	 */
	public function testAddProductWithThrownException($productInfo, $requestInfo=null)
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('addProduct')
		);
		$quoteMock->expects($this->any())
			->method('addProduct')
			->will($this->throwException(new Mage_Core_Exception('Unit test Exception')));

		$this->_cart->setQuote($quoteMock);

		$this->assertNotNull(
			$this->_cart->addProduct($productInfo, $requestInfo)
		);
	}

	public function providerAddProductInvalidProductId()
	{
		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('getId', 'getWebsiteIds', 'hasOptionsValidationFail')
		);
		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnValue('testing...'));
		$productMock->expects($this->any())
			->method('getWebsiteIds')
			->will($this->returnValue(array(0,1,2,3,4)));
		$productMock->expects($this->any())
			->method('hasOptionsValidationFail')
			->will($this->returnValue(true));

		return array(
			array($productMock, null)
		);
	}

	/**
	 * testing addProduct method
	 *
	 * @test
	 * @large
	 * @expectedException Mage_Core_Exception
	 * @dataProvider providerAddProductInvalidProductId
	 * @loadFixture loadWebsiteConfig.yaml
	 */
	public function testAddProductInvalidProductId($productInfo, $requestInfo=null)
	{
		$this->assertNotNull(
			$this->_cart->addProduct($productInfo, $requestInfo)
		);
	}

	public function providerUpdateItem()
	{
		return array(
			array(1, null, null)
		);
	}

	/**
	 * testing addProduct method
	 *
	 * @test
	 * @large
	 * @dataProvider providerUpdateItem
	 * @loadFixture loadWebsiteConfig.yaml
	 */
	public function testUpdateItem($itemId, $requestInfo=null, $updatingParams=null)
	{
		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('getId', 'getWebsiteIds', 'hasOptionsValidationFail')
		);
		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnSelf());
		$productMock->expects($this->any())
			->method('getWebsiteIds')
			->will($this->returnValue(array(0,1,2,3,4)));
		$productMock->expects($this->any())
			->method('hasOptionsValidationFail')
			->will($this->returnValue(true));

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getProduct')
		);
		$itemMock->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($productMock));

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('updateItem', 'getItemById')
		);
		$quoteMock->expects($this->any())
			->method('updateItem')
			->will($this->returnSelf());
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue($itemMock));

		$this->_cart->setQuote($quoteMock);

		$this->assertNotNull(
			$this->_cart->updateItem($itemId, $requestInfo, $updatingParams)
		);
	}

	public function providerUpdateItemMissingItemException()
	{
		return array(
			array(0, null, null)
		);
	}

	/**
	 * testing addProduct method
	 *
	 * @test
	 * @large
	 * @expectedException Mage_Core_Exception
	 * @dataProvider providerUpdateItemMissingItemException
	 * @loadFixture loadWebsiteConfig.yaml
	 */
	public function testUpdateItemMissingItemException($itemId, $requestInfo=null, $updatingParams=null)
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('updateItem', 'getItemById')
		);
		$quoteMock->expects($this->any())
			->method('updateItem')
			->will($this->returnValue(array('some message')));
		$quoteMock->expects($this->any())
			->method('getItemById')
			->will($this->returnValue(null));

		$this->_cart->setQuote($quoteMock);

		$this->assertNotNull(
			$this->_cart->updateItem($itemId, $requestInfo, $updatingParams)
		);
	}
}
