<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Catalog_Test_Model_Feed_AckTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test _getConfigMapValue method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Catalog_Model_Feed_Ack::_getConfigMapValue
	 *                giving it a key for EbayEnterprise_Catalog_Model_Feed_Ack::_configMap array
	 *                if this array is empty it will build it using the config registry and known class constant
	 *                and then check if the given key exist in the configMap and return the value map to it
	 */
	public function testGetConfigMapValue()
	{
		$cfgKey = EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORT_ARCHIVE;
		$configMap = array(
			'feedExportArchive' => 'path/to/export/archive',
			'feedImportArchive' => 'path/to/import/archive',
			'feedOutboxDirectory' => 'path/to/outbox/directory',
			'feedAckInbox' => 'path/to/ack/inbox',
			'feedSentDirectory' => 'path/to/send/directory',
			'ackResendTimeLimit' => 5,
			'feedAckErrorDirectory' => 'path/to/ack/error/directory',
		);

		$map = array(
			EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORT_ARCHIVE => $configMap['feedExportArchive'],
			EbayEnterprise_Catalog_Model_Feed_Ack::CFG_IMPORT_ARCHIVE => $configMap['feedImportArchive'],
			EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORT_OUTBOX => $configMap['feedOutboxDirectory'],
			EbayEnterprise_Catalog_Model_Feed_Ack::CFG_IMPORTED_ACK_DIR => $configMap['feedAckInbox'],
			EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORTED_FEED_DIR => $configMap['feedSentDirectory'],
			EbayEnterprise_Catalog_Model_Feed_Ack::CFG_WAIT_TIME_LIMIT => $configMap['ackResendTimeLimit'],
			EbayEnterprise_Catalog_Model_Feed_Ack::CFG_ERROR_DIRECTORY => $configMap['feedAckErrorDirectory'],
		);

		$this->replaceCoreConfigRegistry($configMap);

		$ackMock = Mage::getModel('ebayenterprise_catalog/feed_ack');

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($ackMock, '_configMap', []);

		$this->assertSame(
			$map[$cfgKey],
			EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_getConfigMapValue', array($cfgKey))
		);
		$this->assertSame($map, EcomDev_Utils_Reflection::getRestrictedPropertyValue($ackMock, '_configMap'));
	}

	/**
	 * Test _listFilesByCfgKey method for the following expectations
	 * Expectation 1: the method TruAction_Eb2cCore_Model_Feed_Export_Ack::_listFilesByCfgKey
	 *                when invoked by this test will call the method EbayEnterprise_Catalog_Model_Feed_Ack::_getConfigMapValue
	 *                give a constant key to retrieve the configured path of the exported file that were exported
	 *                with this configuration get all exported files
	 */
	public function testListFilesByCfgKey()
	{
		$cfgKey = EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORTED_FEED_DIR;
		$absolutePath = '/root/path/to/host/site/var/path/to/exported/sent/files/';

		$exportedFiles = array($absolutePath . 'ImageMaster_abc_123.xml', $absolutePath . 'Pim_abc_123.xml');

		$ackMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_ack')
			->disableOriginalConstructor()
			->setMethods(array('_buildPath', '_listFiles'))
			->getMock();
		$ackMock->expects($this->once())
			->method('_buildPath')
			->with($this->identicalTo($cfgKey))
			->will($this->returnValue($absolutePath));
		$ackMock->expects($this->once())
			->method('_listFiles')
			->with($this->identicalTo($absolutePath))
			->will($this->returnValue($exportedFiles));

		$this->assertSame($exportedFiles, EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_listFilesByCfgKey', array($cfgKey)));
	}

	/**
	 * Test _getImportedAckFiles method for the following expectations
	 * Expectation 1: the method TruAction_Eb2cCore_Model_Feed_Export_Ack::_getImportedAckFiles
	 *                when invoked by this test will call the method EbayEnterprise_Catalog_Model_Feed_Ack::_listFilesByCfgKey
	 *                given the constant key for getting configuration path to imported directory it will return an array list
	 *                of imported ack files, this list of will be loop through for each imported acknowledgment file it will
	 *                call the EbayEnterprise_Catalog_Model_Feed_Ack::_extractAckExportedFile method given an acknowledgment
	 *                file in will it will return an array of with key 'ack' which map to the ack file and a key of
	 *                of 'related' which is the exported file the ack file is acknowledging.
	 */
	public function testGetImportedAckFiles()
	{
		$cfgKey = EbayEnterprise_Catalog_Model_Feed_Ack::CFG_IMPORTED_ACK_DIR;
		$exportedDir = '/full/path/to/where/exported/file/exists';
		$listImportedFiles = array(
			'/full/path/to/imported/ack/file_for_pim.xml',
			'/full/path/to/imported/ack/file_for_image.xml'
		);
		$importedFiles = array(
			array('ack' => $listImportedFiles[0], 'related' => $exportedDir . '/pim.xml'),
			array('ack' => $listImportedFiles[1], 'related' => $exportedDir . '/image.xml'),
		);

		$ackMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_ack')
			->disableOriginalConstructor()
			->setMethods(array('_listFilesByCfgKey', '_extractExportedFile'))
			->getMock();
		$ackMock->expects($this->once())
			->method('_listFilesByCfgKey')
			->with($this->identicalTo($cfgKey))
			->will($this->returnValue($listImportedFiles));
		$ackMock->expects($this->exactly(2))
			->method('_extractExportedFile')
			->will($this->returnValueMap(array(
				array($listImportedFiles[0], $importedFiles[0]),
				array($listImportedFiles[1], $importedFiles[1]),
			)));

		$this->assertSame($importedFiles, EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_getImportedAckFiles', []));
	}

	/**
	 * Test EbayEnterprise_Catalog_Model_Feed_Ack::_extractExportedFile method for the following expectations
	 * Expectation 1: the test will invoke the method EbayEnterprise_Catalog_Model_Feed_Ack::_extractExportedFile
	 *                given an acknowledgment file in which it will invoked the metheo
	 *                EbayEnterprise_Catalog_Model_Feed_Ack::_buildPath pass the cfg key to build the full path
	 *                to the exported directory and then return the return value from calling the method
	 *                EbayEnterprise_Catalog_Model_Feed_Ack::_extractAckExportedFile which will return an array
	 *                with key map to the given ack file and the extracted exported file the ack file is for
	 */
	public function testExtractExportedFile()
	{

		$ackFile = '/full/path/to/an/act/file/ack_file.xml';
		$exportedDir = '/full/path/to/exported/directory';
		$exportedFile = 'pim.xml';
		$map = array(
			EbayEnterprise_Catalog_Model_Feed_Ack::ACK_KEY => $ackFile,
			EbayEnterprise_Catalog_Model_Feed_Ack::RELATED_KEY => $exportedDir . DS . $exportedFile
		);
		$ackMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_ack')
			->disableOriginalConstructor()
			->setMethods(array('_buildPath', '_extractAckExportedFile'))
			->getMock();
		$ackMock->expects($this->once())
			->method('_buildPath')
			->with($this->identicalTo(EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORTED_FEED_DIR))
			->will($this->returnValue($exportedDir));
		$ackMock->expects($this->once())
			->method('_extractAckExportedFile')
			->with($this->identicalTo($ackFile), $this->identicalTo($exportedDir))
			->will($this->returnValue($map));

		$this->assertSame($map, EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_extractExportedFile', array($ackFile)));
	}

	/**
	 * Test _extractAckExportedFile method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Catalog_Model_Feed_Ack::_extractAckExportedFile
	 *                given an acknowledgment file it will call EbayEnterprise_Eb2cCore_Helper_Data::getNewDomDocument
	 *                method which will return DOMDocument object and the load the ack file, then call the
	 *                EbayEnterprise_Eb2cCore_Helper_Data::getNewDomXPath method giving it the DOMDocument object
	 * Expectation 2: the method EbayEnterprise_Eb2cCore_Helper_Data::getDomElement will be invoked given the DOMDocument object
	 *                in which will return the DOMElement object, then this object will be use as the second parameter of the
	 *                next called method on the xpath object query method where the first parameter is the xpath query
	 *                to extract the related exported file
	 */
	public function testExtractAckExportedFile()
	{
		$value = 'pim.xml';
		$ackFile = 'full/path/ack/file/ack_pim.xml';
		$exportedDir = 'full/path/to/where/exported/send/file/exist';

		$result = ['ack' => $ackFile, 'related' => $exportedDir . DS . $value];

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(['load'])
			->getMock();
		$docMock->expects($this->once())
			->method('load')
			->with($this->identicalTo($ackFile))
			->will($this->returnSelf());

		$elementMock = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$domNodeListMock = $this->getMockBuilder('DOMNodeList')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$xpathMock = $this->getMockBuilder('DOMXPath')
			->disableOriginalConstructor()
			->setMethods(['query'])
			->getMock();
		$xpathMock->expects($this->once())
			->method('query')
			->with(
				$this->identicalTo(EbayEnterprise_Catalog_Model_Feed_Ack::XPATH_ACK_EXPORTED_FILE),
				$this->identicalTo($elementMock)
			)
			->will($this->returnValue($domNodeListMock));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(['getNewDomDocument', 'getNewDomXPath', 'getDomElement', 'extractNodeVal'])
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($docMock));
		$coreHelperMock->expects($this->once())
			->method('getNewDomXPath')
			->with($this->identicalTo($docMock))
			->will($this->returnValue($xpathMock));
		$coreHelperMock->expects($this->once())
			->method('getDomElement')
			->with($this->identicalTo($docMock))
			->will($this->returnValue($elementMock));
		$coreHelperMock->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($domNodeListMock))
			->will($this->returnValue($value));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$ackMock = Mage::getModel('ebayenterprise_catalog/feed_ack');
		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_extractAckExportedFile', [$ackFile, $exportedDir]));
	}

	/**
	 * Test _getAck method with the following expectation
	 * Expectation 1: this test will invoked the EbayEnterprise_Catalog_Model_Feed_Ack::_getAck
	 *                method and give it an exported file as its first parameter and an array of
	 *                imported acknowledgment files in which the array of imported acknowledgment
	 *                files will be test if is empty null will be return otherwise it will
	 *                proceed to loop through all the imported acknowledgment files and compare the
	 *                acknowledgment file for related exported file against the given exported file
	 *                when a match if found return ack file, if no match found return null
	 */
	public function testGetAck()
	{
		$testData = [
			[
				'expect' => null,
				'exportedFile' => '/path/to/exported/file/ImageMaster_ABCD_1234_2019388848477.xml',
				'importedAck' => []
			],
			[
				'expect' => 'path/to/sent/Ack_ExportPim_ABCD_1234_7383478477422.xml',
				'exportedFile' => '/path/to/exported/file/ExportPim_ABCD_1234_3019399948411.xml',
				'importedAck' => [[
					'ack' => 'path/to/sent/Ack_ExportPim_ABCD_1234_7383478477422.xml',
					'related' => '/path/to/exported/file/ExportPim_ABCD_1234_3019399948411.xml'
				]],
			],
		];

		$ack = Mage::getModel('ebayenterprise_catalog/feed_ack');
		foreach ($testData as $data) {
			$this->assertSame($data['expect'], EcomDev_Utils_Reflection::invokeRestrictedMethod($ack, '_getAck', [$data['exportedFile'], $data['importedAck']]));
		}
	}

	/**
	 * Test _mvTo method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Catalog_Model_Feed_Ack::_mvTo
	 *                invoked by this test and given exported file and will move the file and return self
	 * Expectation 2: the class property EbayEnterprise_Catalog_Model_Feed_Ack::_config has been set to a
	 *                known state of a mock registry with magic property set in order to simulate getting
	 *                the send directory configuration and building out the destination file
	 */
	public function testMvTo()
	{
		$sourceFile = 'absolute/path/to/exported/ImageMaster_abc_123.xml';
		$absolutePath = 'absolute/path/to/destination/directory';
		$destination = $absolutePath . DS . basename($sourceFile);
		$cfgKey = EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORT_ARCHIVE;

		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(['moveFile', 'removeFile'])
			->getMock();
		$helperMock->expects($this->once())
			->method('moveFile')
			->with($this->identicalTo($sourceFile), $this->identicalTo($destination))
			->will($this->returnValue(null));
		$helperMock->expects($this->once())
			->method('removeFile')
			->with($this->identicalTo($sourceFile))
			->will($this->returnValue(null));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$ackMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_ack')
			->setMethods(['_buildPath'])
			->getMock();
		$ackMock->expects($this->once())
			->method('_buildPath')
			->with($this->identicalTo($cfgKey))
			->will($this->returnValue($absolutePath));

		$this->assertSame($ackMock, EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_mvTo', [$sourceFile, $cfgKey]));
	}

	/**
	 * @see testMvTo but this time we are testing when the method EbayEnterprise_Eb2cCore_Helper_Data::moveFile
	 *      throw a EbayEnterprise_Catalog_Exception_Feed_File exception
	 */
	public function testMvToMoveFileThrowException()
	{
		$sourceFile = 'absolute/path/to/exported/ImageMaster_abc_123.xml';
		$absolutePath = 'absolute/path/to/destination/directory';
		$destination = $absolutePath . DS . basename($sourceFile);
		$cfgKey = EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORT_ARCHIVE;

		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(['moveFile'])
			->getMock();
		$helperMock->expects($this->once())
			->method('moveFile')
			->with($this->identicalTo($sourceFile), $this->identicalTo($destination))
			->will($this->throwException(new EbayEnterprise_Catalog_Exception_Feed_File('simulate move file exception')));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$ackMock = $this->getModelMock('ebayenterprise_catalog/feed_ack', array('_buildPath'));
		$ackMock->expects($this->once())
			->method('_buildPath')
			->with($this->identicalTo($cfgKey))
			->will($this->returnValue($absolutePath));

		$this->assertSame($ackMock, EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_mvTo', array($sourceFile, $cfgKey)));
	}

	/**
	 * @see testMvTo but this time we are testing when the method EbayEnterprise_Eb2cCore_Helper_Data::removeFile
	 *      throw a EbayEnterprise_Catalog_Exception_Feed_File exception
	 */
	public function testMvToRemoveFileThrowException()
	{
		$sourceFile = 'absolute/path/to/exported/ImageMaster_abc_123.xml';
		$absolutePath = 'absolute/path/to/destination/directory';
		$destination = $absolutePath . DS . basename($sourceFile);
		$cfgKey = EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORT_ARCHIVE;

		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(['moveFile', 'removeFile'])
			->getMock();
		$helperMock->expects($this->once())
			->method('moveFile')
			->with($this->identicalTo($sourceFile), $this->identicalTo($destination))
			->will($this->returnValue(null));
		$helperMock->expects($this->once())
			->method('removeFile')
			->with($this->identicalTo($sourceFile))
			->will($this->throwException(new EbayEnterprise_Catalog_Exception_Feed_File('simulate remove file exception')));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$ackMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_ack')
			->setMethods(['_buildPath'])
			->getMock();
		$ackMock->expects($this->once())
			->method('_buildPath')
			->with($this->identicalTo($cfgKey))
			->will($this->returnValue($absolutePath));

		$this->assertSame($ackMock, EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_mvTo', [$sourceFile, $cfgKey]));
	}

	/**
	 * Test _buildPath method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Catalog_Model_Feed_Ack::_buildPath given a
	 *                configuration key it will return the absolute path
	 */
	public function testBuildPath()
	{
		$cfgKey = EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORTED_FEED_DIR;
		$cfgValue = 'path/to/exported/sent/directory';

		$absolutePath = '/root/path/to/host/site/' . $cfgValue;
		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(['getAbsolutePath'])
			->getMock();
		$helperMock->expects($this->any())
			->method('getAbsolutePath')
			->with($this->identicalTo($cfgValue), $this->identicalTo(EbayEnterprise_Catalog_Model_Feed_Ack::SCOPE_VAR))
			->will($this->returnValue($absolutePath));

		$ack = Mage::getModel('ebayenterprise_catalog/feed_ack', ['core_helper' => $helperMock]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($ack, '_configMap', [$cfgKey => $cfgValue]);

		$this->assertSame($absolutePath, EcomDev_Utils_Reflection::invokeRestrictedMethod($ack, '_buildPath', [$cfgKey]));
	}

	/**
	 * Test _isTimedOut method for the following expectations
	 * Expectation 1: When this test invoked the  EbayEnterprise_Catalog_Model_Feed_Ack::_isTimedOut
	 *                method given the exported sourceFile it will determine if the sourceFile has exceeded
	 *                the configuration waiting time by first calling the method EbayEnterprise_Eb2cCore_Helper_Data::getFileTimeElapse
	 *                given the source file and then comparing it against the self::_configMap with key constant for
	 *                referencing the value for waiting time
	 */
	public function testIsTimedOut()
	{
		$sourceFile = '/path/to/some/exported/file_123_ab2c.xml';
		$fileElapseTime = 15;
		$cfgValue = 5;
		$rValue = true;

		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(['getFileTimeElapse'])
			->getMock();
		$helperMock->expects($this->once())
			->method('getFileTimeElapse')
			->with($this->identicalTo($sourceFile))
			->will($this->returnValue($fileElapseTime));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$ackMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_ack')
			->setMethods(['_getConfigMapValue'])
			->getMock();
		$ackMock->expects($this->once())
			->method('_getConfigMapValue')
			->with($this->identicalTo(EbayEnterprise_Catalog_Model_Feed_Ack::CFG_WAIT_TIME_LIMIT))
			->will($this->returnValue($cfgValue));

		$this->assertSame($rValue, EcomDev_Utils_Reflection::invokeRestrictedMethod($ackMock, '_isTimedOut', [$sourceFile]));
	}

	/**
	 * Test process method for the following expectations
	 * Expectation 1: the method TruAction_Eb2cCore_Model_Feed_Export_Ack::process
	 *                when invoked by this test will call TruAction_Eb2cCore_Model_Feed_Export_Ack::_listFilesByCfgKey
	 *                method which will return an array of exported feeds, then invoked the method
	 *                TruAction_Eb2cCore_Model_Feed_Export_Ack::_getExportedAckFiles which will return an
	 *                array of imported acknowledgment feeds
	 */
	public function testProcess()
	{
		$exportedFiles = ['path/to/exported/files/some_exported_file.xml'];
		$importedFiles = [
			[
				'ack' => 'path/to/imported/sent/ack_file.xml',
				'related' => 'some_exported_file.xml'
			],
		];

		$ackMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_ack')
			->setMethods(['_listFilesByCfgKey', '_getImportedAckFiles', '_getAck', '_mvTo'])
			->getMock();
		$ackMock->expects($this->once())
			->method('_listFilesByCfgKey')
			->with($this->identicalTo(EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORTED_FEED_DIR))
			->will($this->returnValue($exportedFiles));
		$ackMock->expects($this->once())
			->method('_getImportedAckFiles')
			->will($this->returnValue($importedFiles));
		$ackMock->expects($this->once())
			->method('_getAck')
			->with($this->identicalTo($exportedFiles[0]), $this->identicalTo($importedFiles))
			->will($this->returnValue($importedFiles[0]['ack']));
		$ackMock->expects($this->at(3))
			->method('_mvTo')
			->with(
				$this->identicalTo($exportedFiles[0]),
				$this->identicalTo(EbayEnterprise_Catalog_Model_Feed_Ack::CFG_EXPORT_ARCHIVE)
			)
			->will($this->returnSelf());
		$ackMock->expects($this->at(4))
			->method('_mvTo')
			->with(
				$this->identicalTo($importedFiles[0]['ack']),
				$this->identicalTo(EbayEnterprise_Catalog_Model_Feed_Ack::CFG_IMPORT_ARCHIVE)
			)
			->will($this->returnSelf());

		$this->assertSame($ackMock, $ackMock->process());
	}

	/**
	 * @see testProcess except testing when the exported files has no imported acknowledgment file
	 */
	public function testProcessWhenNoAckFileOfExportedFeed()
	{
		$loggerStub = $this->getHelperMock('ebayenterprise_magelog/data');
		$loggerStub->expects($this->once())
			->method('logCrit');
		$exportedFiles = array('path/to/exported/files/some_exported_file.xml');
		$importedFiles = [];
		$resendable = true;

		$ackMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_ack')
			->setMethods(['_listFilesByCfgKey', '_getImportedAckFiles', '_getAck', '_isTimedOut', '_mvTo'])
			->getMock();
		$ackMock->expects($this->once())
			->method('_listFilesByCfgKey')
			->will($this->returnValue($exportedFiles));
		$ackMock->expects($this->once())
			->method('_getImportedAckFiles')
			->will($this->returnValue($importedFiles));
		$ackMock->expects($this->once())
			->method('_getAck')
			->with($this->identicalTo($exportedFiles[0]), $this->identicalTo($importedFiles))
			->will($this->returnValue(null));
		$ackMock->expects($this->once())
			->method('_isTimedOut')
			->with($this->identicalTo($exportedFiles[0]))
			->will($this->returnValue($resendable));
		$ackMock->expects($this->once())
			->method('_mvTo')
			->with(
				$this->identicalTo($exportedFiles[0]),
				$this->identicalTo(EbayEnterprise_Catalog_Model_Feed_Ack::CFG_ERROR_DIRECTORY)
			)
			->will($this->returnSelf());

		EcomDev_Utils_Reflection::setRestrictedPropertyValues($ackMock, ['_logger' => $loggerStub]);
		$this->assertSame($ackMock, $ackMock->process());
	}
}
