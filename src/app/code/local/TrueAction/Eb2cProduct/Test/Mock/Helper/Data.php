<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Config_Registry class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function buildEb2cCoreModelConfigRegistry()
	{
		$properties = new StdClass();
		$properties->configPath = 'eb2ccore/general';
		$properties->destinationType = 'MAILBOX';
		$properties->catalogId = '70';
		$properties->clientId = 'TAN-CLI';
		$properties->contentFeedLocalPath = 'vfs://testBase/feed_content_master/';
		$properties->contentFeedRemoteReceivedPath = '/Content/Master/';
		$properties->contentFeedFilePattern = 'Content*.xml';
		$properties->contentFeedEventType = 'ContentMaster';
		$properties->contentFeedHeaderVersion = '2.3.0';
		$properties->itemFeedLocalPath = 'vfs://testBase/feed_item_master/';
		$properties->itemFeedRemoteReceivedPath = '/Item/Master/';
		$properties->itemFeedFilePattern = 'ItemMaster*.xml';
		$properties->itemFeedEventType = 'ItemMaster';
		$properties->itemFeedHeaderVersion = '2.3.0';
		$properties->iShipFeedLocalPath = 'vfs://testBase/feed_i_ship/';
		$properties->iShipFeedRemoteReceivedPath = '/I/Ship/';
		$properties->iShipFeedFilePattern = 'IShip*.xml';
		$properties->iShipFeedEventType = 'IShip';
		$properties->iShipFeedHeaderVersion = '2.3.0';

		return $properties;
	}

	/**
	 * return a mock of the TrueAction_Eb2cProduct_Helper_Data class
	 *
	 * @return Mock_TrueAction_Eb2cProduct_Helper_Data
	 */
	public function buildEb2cProductHelper()
	{
		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();

		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildEb2cCoreModelConfigRegistry()));

		return $helperMock;
	}

	/**
	 * replacing by mock of the eb2cproduct helper class
	 *
	 * @return void
	 */
	public function replaceByMockProductHelper()
	{
		$this->replaceByMock('helper', 'eb2cproduct', $this->buildEb2cProductHelper());
	}

	/**
	 * replacing by mock of the eb2ccore helper class
	 *
	 * @return void
	 */
	public function replaceByMockCoreHelper()
	{
		// with invalid ftp setting
		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();

		$coreHelperMock->expects($this->any())
			->method('getNewDomDocument')
			->will($this->returnValue(new TrueAction_Dom_Document('1.0', 'UTF-8')));

		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);
	}

	/**
	 * replacing by mock of the eb2ccore helper class
	 *
	 * @return void
	 */
	public function replaceByMockCoreHelperFeed()
	{
		$coreHelperFeedMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('validateHeader', '_construct'))
			->getMock();

		$coreHelperFeedMock->expects($this->any())
			->method('validateHeader')
			->will($this->returnValue(true));
		$coreHelperFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		$this->replaceByMock('helper', 'eb2ccore/feed', $coreHelperFeedMock);
	}

	/**
	 * replacing by mock of the filetransfer helper class
	 *
	 * @return void
	 */
	public function replaceByMockFileTransferHelper()
	{
		// with invalid ftp setting
		$filetransferHelperMock = $this->getHelperMockBuilder('filetransfer/data')
			->disableOriginalConstructor()
			->setMethods(array('getAllFiles'))
			->getMock();

		$filetransferHelperMock->expects($this->any())
			->method('getAllFiles')
			->will($this->returnValue(true));

		$this->replaceByMock('helper', 'filetransfer', $filetransferHelperMock);
	}

	/**
	 * replacing by mock of the filetransfer helper class
	 *
	 * @return void
	 */
	public function replaceByMockFileTransferHelperThrowConnectionException()
	{
		// with invalid ftp setting
		$filetransferHelperMock = $this->getHelperMockBuilder('filetransfer/data')
			->disableOriginalConstructor()
			->setMethods(array('getAllFiles'))
			->getMock();

		$filetransferHelperMock->expects($this->any())
			->method('getAllFiles')
			->will($this->throwException(new TrueAction_FileTransfer_Exception_Connection));

		$this->replaceByMock('helper', 'filetransfer', $filetransferHelperMock);
	}

	/**
	 * replacing by mock of the filetransfer helper class
	 *
	 * @return void
	 */
	public function replaceByMockFileTransferHelperThrowAuthenticationException()
	{
		// with invalid ftp setting
		$filetransferHelperMock = $this->getHelperMockBuilder('filetransfer/data')
			->disableOriginalConstructor()
			->setMethods(array('getAllFiles'))
			->getMock();

		$filetransferHelperMock->expects($this->any())
			->method('getAllFiles')
			->will($this->throwException(new TrueAction_FileTransfer_Exception_Authentication));

		$this->replaceByMock('helper', 'filetransfer', $filetransferHelperMock);
	}

	/**
	 * replacing by mock of the filetransfer helper class
	 *
	 * @return void
	 */
	public function replaceByMockFileTransferHelperThrowTransferException()
	{
		// with invalid ftp setting
		$filetransferHelperMock = $this->getHelperMockBuilder('filetransfer/data')
			->disableOriginalConstructor()
			->setMethods(array('getAllFiles'))
			->getMock();

		$filetransferHelperMock->expects($this->any())
			->method('getAllFiles')
			->will($this->throwException(new TrueAction_FileTransfer_Exception_Transfer));

		$this->replaceByMock('helper', 'filetransfer', $filetransferHelperMock);
	}
}
