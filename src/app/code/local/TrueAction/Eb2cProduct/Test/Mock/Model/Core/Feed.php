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
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-invalid-catalog-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithInvalidFeedClientId()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-invalid-client-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithInvalidFeedItemType()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-invalid-item-type.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithBundleProductsAddNosale()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-add-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdate()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-update.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdateNosale()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-update-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithBundleProductsDelete()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-delete.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedFortItemMasterWithConfigurableProductsAdd()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-configurable-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForItemMasterWithGroupedProductsAdd()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Item/sample-feed-grouped-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithInvalidFeedCatalogId()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-invalid-catalog-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithInvalidFeedClientId()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-invalid-client-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithInvalidFeedContentType()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-invalid-Content-type.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithValidProduct()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithBundleProductsAddNosale()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-bundle-product-add-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithBundleProductsUpdate()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-bundle-product-update.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithBundleProductsUpdateNosale()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-bundle-product-update-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithBundleProductsDelete()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-bundle-product-delete.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedFortContentMasterWithConfigurableProductsAdd()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-configurable-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForContentMasterWithGroupedProductsAdd()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseDir')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-grouped-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpDir')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithInvalidFeedCatalogId()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithInvalidFeedClientId()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithInvalidFeedItemType()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipAddProduct()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithProductsAddNosale()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithProductsUpdate()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithProductsUpdateNosale()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
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

		return $coreModelFeedMock;
	}

	/**
	 * return a mock of the TrueAction_Eb2cCore_Model_Feed class
	 *
	 * @return Mock_TrueAction_Eb2cCore_Model_Feed
	 */
	public function buildEb2cCoreModelFeedForIShipWithProductsDelete()
	{
		$coreModelFeedMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Feed',
			array(
				'_setCheckAndCreateDir', 'setBaseDir', 'lsInboundDir', 'getInboundDir', 'getOutboundDir',
				'getArchiveDir', 'getErrorDir', 'getTmpDir', '_mvToDir', 'mvToInboundDir', 'mvToOutboundDir',
				'mvToArchiveDir', 'mvToErrorDir', 'mvToTmpDir', 'fetchFeedsFromRemote'
			)
		);
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

		return $coreModelFeedMock;
	}
}
