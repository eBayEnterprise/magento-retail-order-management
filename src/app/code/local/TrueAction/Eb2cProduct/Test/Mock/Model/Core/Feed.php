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
	public function buildEb2cCoreModelFeedForItemMasterWithInvalidFeedCatalogId()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-invalid-catalog-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithInvalidFeedClientId()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-invalid-client-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithInvalidFeedItemType()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-invalid-item-type.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-bundle-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithSimpleProductsAddNosale()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-simple-product-add-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdate()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-bundle-product-update.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdateNosale()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-bundle-product-update-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithProductsDelete()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-product-delete.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedFortItemMasterWithConfigurableProductsAdd()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-configurable-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithGroupedProductsAdd()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_item_master/inbound/sample-feed-grouped-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_item_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithInvalidFeedCatalogId()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-invalid-catalog-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithInvalidFeedClientId()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-invalid-client-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithInvalidFeedContentType()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-invalid-Content-type.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithValidProduct()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithBundleProductsAddNosale()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-bundle-product-add-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithBundleProductsUpdate()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-bundle-product-update.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithBundleProductsUpdateNosale()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-bundle-product-update-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithBundleProductsDelete()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-bundle-product-delete.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedFortContentMasterWithConfigurableProductsAdd()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-configurable-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithGroupedProductsAdd()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_content_master/inbound/sample-feed-grouped-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_content_master/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithInvalidFeedCatalogId()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_i_ship/inbound/sample-feed-invalid-catalog-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithInvalidFeedClientId()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_i_ship/inbound/sample-feed-invalid-client-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithInvalidFeedItemType()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_i_ship/inbound/sample-feed-invalid-item-type.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipAddProduct()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_i_ship/inbound/sample-feed-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithProductsAddNosale()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_i_ship/inbound/sample-feed-product-add-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithProductsUpdate()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_i_ship/inbound/sample-feed-product-update.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithProductsUpdateNosale()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_i_ship/inbound/sample-feed-product-update-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithProductsDelete()
	{
		$coreModelFeedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote', '_construct'
			))
			->getMock();
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					'vfs://testBase/feed_i_ship/inbound/sample-feed-product-delete.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue('vfs://testBase/feed_i_ship/tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('_mvToDir')
			->will($this->returnValue('anywhere'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToInboundDir')
			->will($this->returnValue('inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToOutboundDir')
			->will($this->returnValue('outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue('archive'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToErrorDir')
			->will($this->returnValue('error'));
		$coreModelFeedMock->expects($this->any())
			->method('mvToTmpDir')
			->will($this->returnValue('tmp'));
		$coreModelFeedMock->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(null));
		$coreModelFeedMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		return $coreModelFeedMock;
	}
}
