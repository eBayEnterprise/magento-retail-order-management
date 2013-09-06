<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Helper_Data extends EcomDev_PHPUnit_Test_Case
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
	 * return a mock of the TrueAction_Eb2cCore_Model_Config_Registry class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function buildEb2cCoreModelConfigRegistry()
	{
		$properties = new StdClass();
		$properties->catalogId = '70';
		$properties->clientId = 'TAN-CLI';

		return $properties;
	}

	/**
	 * return a mock of the TrueAction_Eb2cPayment_Helper_Constants class
	 *
	 * @return Mock_TrueAction_Eb2cPayment_Helper_Constants
	 */
	public function buildEb2cPaymentConstant()
	{
		return new TrueAction_Eb2cPayment_Helper_Constants();
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
	 * return a mock of the TrueAction_Eb2cPayment_Helper_Data class
	 *
	 * @return Mock_TrueAction_Eb2cPayment_Helper_Data
	 */
	public function buildEb2cPaymentHelper()
	{
		$helperMock = $this->getMockBuilder(
			'TrueAction_Eb2cPayment_Helper_Data',
			array(
				'getConfigModel',
				'getXmlNs',
				'getOperationUri',
				'getRequestId',
				'getReservationId',
				'getApiModel',
				'isValidFtpSettings',
			))
			->disableOriginalConstructor()
			->getMock();
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
		$helperMock->expects($this->any())
			->method('getApiModel')
			->will($this->returnValue($this->buildEb2cCoreModelApi()));
		$helperMock->expects($this->any())
			->method('isValidFtpSettings')
			->will($this->returnValue(true));

		return $helperMock;
	}

	/**
	 * replacing by mock of the eb2ccore helper class
	 *
	 * @return void
	 */
	public function replaceByMockCoreHelper()
	{
		$coreHelperMock = $this->getHelperMock('eb2ccore/data', array('getApiUri'));
		$coreHelperMock->expects($this->any())
			->method('getApiUri')
			->will($this->returnCallback(array($this, 'getOperationUriCallback')));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);
	}

	/**
	 * replacing by mock of the enterprise_giftcardaccount helper class
	 *
	 * @return void
	 */
	public function replaceByMockEnterpriseGiftCardAccountHelper()
	{
		$enterpriseGiftCardAccountHelperMock = $this->getHelperMock('enterprise_giftcardaccount/data', array('getCards', 'setCards'));
		$enterpriseGiftCardAccountHelperMock->expects($this->any())
			->method('getCards')
			->will($this->returnValue(array(array('i' => 1))));
		$enterpriseGiftCardAccountHelperMock->expects($this->any())
			->method('setCards')
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'enterprise_giftcardaccount', $enterpriseGiftCardAccountHelperMock);
	}
}
