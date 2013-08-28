<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		// FYI: instantiating using regular Mage::getHelper method create
		// a singleton oject which mess with load fixtures for the config
		$this->_helper = new TrueAction_Eb2cProduct_Helper_Data();
	}

	/**
	 * testing getCoreHelper method
	 *
	 * @test
	 */
	public function testGetCoreHelper()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Helper_Data',
			$this->_helper->getCoreHelper()
		);
	}

	/**
	 * testing getFileTransferHelper method
	 *
	 * @test
	 */
	public function testGetFileTransferHelper()
	{
		$this->assertInstanceOf(
			'TrueAction_FileTransfer_Helper_Data',
			$this->_helper->getFileTransferHelper()
		);
	}

	/**
	 * testing getConfigModel method
	 *
	 * @test
	 */
	public function testGetConfigModel()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Config_Registry',
			$this->_helper->getConfigModel()
		);
	}

	/**
	 * testing getConstantHelper method
	 *
	 * @test
	 */
	public function testGetConstantHelper()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Helper_Constants',
			$this->_helper->getConstantHelper()
		);
	}

	/**
	 * testing getCoreFeed method
	 *
	 * @test
	 */
	public function testGetCoreFeed()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Helper_Feed',
			$this->_helper->getCoreFeed()
		);
	}

	/**
	 * testing getDomDocument method
	 *
	 * @test
	 */
	public function testGetDomDocument()
	{
		$this->assertInstanceOf(
			'TrueAction_Dom_Document',
			$this->_helper->getDomDocument()
		);
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
			'https://developer-na.gsipartners.com/v1.10/stores/ABCD/product/none.xml',
			$this->_helper->getOperationUri('no_action')
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
			'TAN-CLI-ABCD-43',
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
			'TAN-CLI-ABCD-43',
			$this->_helper->getReservationId($entityId)
		);
	}

	/**
	 * testing getApiModel method
	 *
	 * @test
	 */
	public function testGetApiModel()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Api',
			$this->_helper->getApiModel()
		);
	}
}
