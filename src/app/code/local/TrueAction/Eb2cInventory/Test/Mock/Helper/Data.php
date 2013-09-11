<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cInventory_Test_Mock_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Config_Registry class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function buildEb2cCoreModelConfigRegistry()
	{
		$properties = new StdClass();
		$properties->feedLocalPath = 'var/TrueAction/Eb2c/Feed/Item/Inventories/';
		$properties->feedRemoteReceivedPath = '/Item/Inventories/';
		$properties->configPath = 'eb2ccore/general';
		$properties->feedEventType = 'ItemInventories';
		$properties->feedHeaderVersion = '2.3.0';
		$properties->destinationType = 'MAILBOX';
		$properties->catalogId = '70';
		$properties->clientId = 'TAN-CLI';

		return $properties;
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
	 * return a mock of the TrueAction_Eb2cInventory_Helper_Data class
	 *
	 * @return Mock_TrueAction_Eb2cInventory_Helper_Data
	 */
	public function buildEb2cInventoryHelper()
	{
		$helperMock = $this->getMock(
			'StdClass',
			array('__construct', 'getConfigModel', 'getXmlNs', 'getOperationUri', 'getRequestId', 'getReservationId')
		);
		$helperMock->expects($this->any())
			->method('__construct')
			->will($this->returnSelf());
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildEb2cCoreModelConfigRegistry()));
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

		return $helperMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cInventory_Helper_Data class
	 *
	 * @return Mock_TrueAction_Eb2cInventory_Helper_Data
	 */
	public function buildEb2cInventoryHelperWithInvalidFtpSettings()
	{
		$helperMock = $this->getMock(
			'StdClass',
			array('__construct', 'getConfigModel', 'getXmlNs', 'getOperationUri', 'getRequestId', 'getReservationId')
		);
		$helperMock->expects($this->any())
			->method('__construct')
			->will($this->returnSelf());
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildEb2cCoreModelConfigRegistry()));
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

		return $helperMock;
	}
}
