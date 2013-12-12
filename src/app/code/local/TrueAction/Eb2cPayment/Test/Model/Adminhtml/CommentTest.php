<?php
class TrueAction_Eb2cPayment_Test_Model_Adminhtml_CommentTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test getCommentText method
	 * @test
	 */
	public function testGetCommentText()
	{
		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->setMethods(array('__'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('__')
			->with($this->equalTo('TrueAction_Eb2cPayment_Admin_System_Config_PBridge_Comments'))
			->will($this->returnValue('click here to <a href="%s">Configure Payment Bridge</a>'));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

		$paymentSectionUrl = 'http://example.com/admin/system_config/edit/section/payment/key/12233/';
		$commentModelMock = $this->getModelMockBuilder('eb2cpayment/adminhtml_comment')
			->disableOriginalConstructor()
			->setMethods(array('_getUrl'))
			->getMock();
		$commentModelMock->expects($this->once())
			->method('_getUrl')
			->will($this->returnValue($paymentSectionUrl));

		$this->assertSame(
			sprintf('click here to <a href="%s">Configure Payment Bridge</a>', $paymentSectionUrl),
			$commentModelMock->getCommentText()
		);
	}

	/**
	 * Test _getUrl method
	 * @test
	 */
	public function testGetUrl()
	{
		$adminhtmlHelperMock = $this->getHelperMockBuilder('adminhtml/data')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$adminhtmlHelperMock::staticExpects($this->once())
			->method('getUrl')
			->with($this->equalTo('adminhtml/system_config/edit'), $this->equalTo(array('section' => 'payment')))
			->will($this->returnValue('http://example.com/admin/system_config/edit/section/payment/key/12233/'));
		$this->replaceByMock('helper', 'adminhtml', $adminhtmlHelperMock);

		$commentModelMock = $this->getModelMockBuilder('eb2cpayment/adminhtml_comment')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(
			'http://example.com/admin/system_config/edit/section/payment/key/12233/',
			$this->_reflectMethod($commentModelMock, '_getUrl')->invoke($commentModelMock)
		);
	}
}
