<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeed()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/sample-feed-invalid-catalog-id.xml',
					__DIR__ . '/Xml/sample-feed-invalid-client-id.xml',
					__DIR__ . '/Xml/sample-feed-invalid-item-type.xml',
					__DIR__ . '/Xml/sample-feed.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToFolder')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundFolder')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundFolder')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveFolder')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorFolder')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpFolder')
			->will($this->returnValue('tmp'));

		return $coreModelFeedMock;
	}
}
