<?php
class TrueAction_Eb2cCore_Test_Helper_Api_ActionTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * throw an exception.
	 * @
	 */
	public function testThrowException()
	{
		$this->setExpectedException('TrueAction_Eb2cCore_Exception_Critical');
		Mage::helper('eb2ccore/api_action')->throwException();
	}
	/**
	 * always return an empty string.
	 * @test
	 */
	public function testReturnEmpty()
	{
		$this->assertSame('', Mage::helper('eb2ccore/api_action')->returnEmpty());
	}
	/**
	 * return the response body
	 * @test
	 */
	public function testReturnBody()
	{
		$response = $this->getMock('Zend_Http_Response', array('getStatus', 'isSuccessful', 'getBody'), array(200, array()));
		$response->expects($this->once())
			->method('getBody')
			->will($this->returnValue('the response text'));
		$this->assertSame('the response text', Mage::helper('eb2ccore/api_action')->returnBody($response));
	}
	/**
	 * display a general notice in the cart when a request is configured to
	 * fail loudly
	 * @test
	 */
	public function testDisplayDefaultMessage()
	{
		$session = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$this->replaceByMock('singleton', 'core/session', $session);

		$session->expects($this->once())
			->method('addError')
			->with($this->identicalTo(TrueAction_Eb2cCore_Helper_Api_Action::TRUEACTION_EB2CCORE_REQUEST_FAILED))
			->will($this->returnSelf());
		$this->assertSame('', Mage::helper('eb2ccore/api_action')->displayDefaultMessage());
	}
}
