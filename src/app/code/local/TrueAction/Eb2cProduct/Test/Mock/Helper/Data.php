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
		$properties->contentFeedLocalPath = 'var/TrueAction/Eb2c/Feed/Content/Master/';
		$properties->contentFeedRemoteReceivedPath = '/Content/Master/';
		$properties->contentFeedEventType = 'ContentMaster';
		$properties->contentFeedHeaderVersion = '2.3.0';
		$properties->itemFeedLocalPath = 'var/TrueAction/Eb2c/Feed/Item/Master/';
		$properties->itemFeedRemoteReceivedPath = '/Item/Master/';
		$properties->itemFeedEventType = 'ItemMaster';
		$properties->itemFeedHeaderVersion = '2.3.0';

		return $properties;
	}

	/**
	 * return a mock of the TrueAction_Eb2cProduct_Helper_Data class
	 *
	 * @return Mock_TrueAction_Eb2cProduct_Helper_Data
	 */
	public function buildEb2cProductHelper()
	{
		$helperMock = $this->getHelperMock(
			'eb2cproduct/data',
			array('getConfigModel')
		);
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
	public function replaceByMockCoreHelperInvalidSftpSettings()
	{
		// with invalid ftp setting
		$coreHelperMock = $this->getHelperMock('eb2ccore/data', array('isValidFtpSettings'));
		$coreHelperMock->expects($this->any())
			->method('isValidFtpSettings')
			->will($this->returnValue(false));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);
	}

	/**
	 * replacing by mock of the eb2ccore helper class
	 *
	 * @return void
	 */
	public function replaceByMockCoreHelperValidSftpSettings()
	{
		// with invalid ftp setting
		$coreHelperMock = $this->getHelperMock('eb2ccore/data', array('isValidFtpSettings'));
		$coreHelperMock->expects($this->any())
			->method('isValidFtpSettings')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);
	}

	/**
	 * replacing by mock of the eb2ccore helper class
	 *
	 * @return void
	 */
	public function replaceByMockCoreHelperFeed()
	{
		$coreHelperFeedMock = $this->getHelperMock('eb2ccore/feed', array('validateHeader'));
		$coreHelperFeedMock->expects($this->any())
			->method('validateHeader')
			->will($this->returnValue(true));
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
		$filetransferHelperMock = $this->getHelperMock('filetransfer/data', array('getFile'));
		$filetransferHelperMock->expects($this->any())
			->method('getFile')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'filetransfer', $filetransferHelperMock);
	}
}
