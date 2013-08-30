<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the TrueAction_Eb2cCore_Helper_Data class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Helper_Data
	 */
	public function buildEb2cCoreHelper()
	{
		$helperMock = $this->getMock(
			'TrueAction_Eb2cCore_Helper_Data',
			array('getApiUri')
		);
		$helperMock->expects($this->any())
			->method('getApiUri')
			->will($this->returnSelf());

		return $helperMock;
	}

	/**
	 * return a mock of the TrueAction_FileTransfer_Helper_Data class
	 *
	 * @return Mock_TrueAction_FileTransfer_Helper_Data
	 */
	public function buildFileTransferHelper()
	{
		$helperMock = $this->getMock(
			'TrueAction_FileTransfer_Helper_Data',
			array('getFile')
		);
		$helperMock->expects($this->any())
			->method('getFile')
			->will($this->returnValue(true));

		return $helperMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Config_Registry class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function buildEb2cCoreModelConfigRegistry()
	{
		$properties = new StdClass();
		$properties->feedLocalPath = 'var/TrueAction/Eb2c/Feed/Item/Master/';
		$properties->feedRemoteReceivedPath = '/Item/Master/';
		$properties->configPath = 'eb2ccore/general';
		$properties->feedEventType = 'ItemMaster';
		$properties->feedHeaderVersion = '2.3.0';
		$properties->destinationType = 'MAILBOX';
		$properties->catalogId = '70';
		$properties->clientId = 'TAN-CLI';

		return $properties;
	}

	/**
	 * return a mock of the TrueAction_Eb2cProduct_Helper_Constants class
	 *
	 * @return Mock_TrueAction_Eb2cProduct_Helper_Constants
	 */
	public function buildEb2cProductConstant()
	{
		$constantMock = $this->getMock(
			'TrueAction_Eb2cProduct_Helper_Constants',
			array()
		);

		return $constantMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Helper_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Helper_Feed
	 */
	public function buildEb2cCoreHelperFeed()
	{
		$helperFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Helper_Feed',
			array('validateHeader')
		);
		$helperFeedMock->expects($this->any())
			->method('validateHeader')
			->will($this->returnValue(true));

		return $helperFeedMock;
	}

	/**
	 * return an instiated object of TrueAction_Dom_Document class
	 *
	 * @return TrueAction_Dom_Document
	 */
	public function buildDomDocument()
	{
		return new TrueAction_Dom_Document('1.0', 'UTF-8');
	}

	/**
	 * this is a callback method that return a dev url base on the parameter pass
	 *
	 * @return string
	 */
	public function getOperationUriCallback()
	{
		$args = func_get_args();
		return 'none';
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Api class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Api
	 */
	public function buildEb2cCoreModelApi()
	{
		$modelApiMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Api',
			array('request', 'setUri')
		);
		$modelApiMock->expects($this->any())
			->method('request')
			->will($this->returnValue('<foo></foo>'));
		$modelApiMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());

		return $modelApiMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cProduct_Helper_Data class
	 *
	 * @return Mock_TrueAction_Eb2cProduct_Helper_Data
	 */
	public function buildEb2cProductHelper()
	{
		$helperMock = $this->getMock(
			'TrueAction_Eb2cProduct_Helper_Data',
			array(
				'getCoreHelper',
				'getFileTransferHelper',
				'getConfigModel',
				'getConstantHelper',
				'getCoreFeed',
				'getXmlNs',
				'getOperationUri',
				'getRequestId',
				'getReservationId',
				'getApiModel',
			)
		);
		$helperMock->expects($this->any())
			->method('getCoreHelper')
			->will($this->returnValue($this->buildEb2cCoreHelper()));
		$helperMock->expects($this->any())
			->method('getFileTransferHelper')
			->will($this->returnValue($this->buildFileTransferHelper()));
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildEb2cCoreModelConfigRegistry()));
		$helperMock->expects($this->any())
			->method('getConstantHelper')
			->will($this->returnValue($this->buildEb2cProductConstant()));
		$helperMock->expects($this->any())
			->method('getCoreFeed')
			->will($this->returnValue($this->buildEb2cCoreHelperFeed()));
		$helperMock->expects($this->any())
			->method('getXmlNs')
			->will($this->returnValue('http://api.gsicommerce.com/schema/checkout/1.0'));
		$helperMock->expects($this->any())
			->method('getOperationUri')
			->will($this->returnCallback(array($this, 'getOperationUriCallback')));
		$helperMock->expects($this->any())
			->method('getRequestId')
			->will($this->returnValue('TAN-CLI-ABCD-43'));
		$helperMock->expects($this->any())
			->method('getReservationId')
			->will($this->returnValue('TAN-CLI-ABCD-43'));
		$helperMock->expects($this->any())
			->method('getApiModel')
			->will($this->returnValue($this->buildEb2cCoreModelApi()));

		return $helperMock;
	}
}
