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
					__DIR__ . '/Xml/Item/sample-feed-invalid-catalog-id.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-invalid-client-id.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-invalid-item-type.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-add.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-add-nosale.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-update.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-update-nosale.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-bundle-product-delete.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-configurable-product-add.xml',
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
					__DIR__ . '/Xml/Item/sample-feed-grouped-product-add.xml',
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-invalid-catalog-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-invalid-client-id.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-invalid-Content-type.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-bundle-product-add-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-bundle-product-update.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-bundle-product-update-nosale.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-bundle-product-delete.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-configurable-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
				'_setCheckAndCreateFolder', 'setBaseFolder', 'lsInboundFolder', 'getInboundFolder', 'getOutboundFolder',
				'getArchiveFolder', 'getErrorFolder', 'getTmpFolder', '_mvToFolder', 'mvToInboundFolder', 'mvToOutboundFolder',
				'mvToArchiveFolder', 'mvToErrorFolder', 'mvToTmpFolder'
			)
		);
		$coreModelFeedMock->expects($this->any())
			->method('_setCheckAndCreateFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/'));
		$coreModelFeedMock->expects($this->any())
			->method('setBaseFolder')
			->will($this->returnSelf());
		$coreModelFeedMock->expects($this->any())
			->method('lsInboundFolder')
			->will($this->returnValue(
				array(
					__DIR__ . '/Xml/Content/sample-feed-grouped-product-add.xml',
				)));
		$coreModelFeedMock->expects($this->any())
			->method('getInboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/inbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getOutboundFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/outbound'));
		$coreModelFeedMock->expects($this->any())
			->method('getArchiveFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/archive'));
		$coreModelFeedMock->expects($this->any())
			->method('getErrorFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/error'));
		$coreModelFeedMock->expects($this->any())
			->method('getTmpFolder')
			->will($this->returnValue(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Content/Master/tmp'));
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
