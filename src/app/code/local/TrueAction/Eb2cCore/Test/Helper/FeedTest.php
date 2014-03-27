<?php
class TrueAction_Eb2cCore_Test_Helper_FeedTest
	extends TrueAction_Eb2cCore_Test_Base
{
	const FEED_PATH = '/FeedTest/fixtures/';
	private function _getDoc($feed)
	{
		$d = Mage::helper('eb2ccore')->getNewDomDocument();
		$d->load(__DIR__ . self::FEED_PATH . $feed . '.xml');
		return $d;
	}
	public function providerValidateHeader()
	{
		return array(
			array('bad-event-feed'),
			array('good-feed'),
			array('no-event-feed'),
		);
	}

	/**
	 * @dataProvider dataProvider
	 * @loadFixture
	 * @loadExpectation
	 */
	public function testGetMessageDate($feedDir)
	{
		$vfs = $this->getFixture()->getVfs();
		$url = $vfs->url('var/eb2c/' . $feedDir . '/feed.xml');
		$testModel = Mage::helper('eb2ccore/feed');
		$e = $this->expected($feedDir);
		$dateObj = $testModel->getMessageDate($url);
		$this->assertNotNull($dateObj);
		$this->assertSame(
			$e->getDate(),
			$dateObj->format('Y-m-d H:i:s')
		);
	}

	/**
	 * @dataProvider dataProvider
	 * @loadFixture
	 */
	public function testGetMessageDateFail($feedDir)
	{
		$vfs = $this->getFixture()->getVfs();
		$url = $vfs->url('var/eb2c/' . $feedDir . '/feed.xml');
		$testModel = Mage::helper('eb2ccore/feed');
		$dateObj = $testModel->getMessageDate($url);
		$this->assertNotNull($dateObj);
		$this->assertSame(filemtime($url), $dateObj->getTimeStamp());
	}

	/**
	 * validateHeader should be true when the header has the expected DestinationId and Event Type nodes.
	 * @test
	 * @dataProvider providerValidateHeader
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeader($feed)
	{
		$doc = $this->_getDoc($feed);
		$isValid = $this->expected($feed)->getIsValid();
		$this->assertSame($isValid, Mage::helper('eb2ccore/feed')->validateHeader($doc, 'GoodEvent'));
	}

	/**
	 * @test
	 */
	public function testFileTransferDefaultIsSftp()
	{
		$hlpr = Mage::helper('eb2ccore/feed');
		$configData = Mage::helper('filetransfer')
			->getInitData($hlpr::FILETRANSFER_CONFIG_PATH);
		$this->assertSame('sftp', $configData['protocol_code']);
	}

	/**
	 * Test getHeaderConfig method
	 * @loadExpectation
	 */
	public function testGetHeaderConfig()
	{
		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfigData', '_doConfigTranslation'))
			->getMock();
		foreach (range(0, 1) as $idx) {
			$pccd = $this->expected("pccd_${idx}");
			$mData = $pccd->getData();
			$feedHelperMock->expects($this->at($idx))
				->method('getConfigData')
				->with($this->equalTo($mData['with']))
				->will($this->returnValue($mData['will']));
		}

		$dct = $this->expected('dct');
		$feedHelperMock->expects($this->at(2))
			->method('_doConfigTranslation')
			->with($this->isType('array'))
			->will($this->returnValue($dct['will']));

		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelperMock);
		$this->_reflectProperty($feedHelperMock, '_feedTypeHeaderConf')->setValue(
			$feedHelperMock,
			array('ItemMaster' => 'eb2cproduct/item_master_feed/outbound/message_header')
		);

		$testData = array(
			array(
				'expect' => array(),
				'feedType' => 'unknown'
			),
			array(
				'expect' => $dct['will'],
				'feedType' => 'ItemMaster'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $feedHelperMock->getHeaderConfig($data['feedType']));
		}
	}

	/**
	 * Test getFileNameConfig method
	 * @loadExpectation
	 */
	public function testGetFileNameConfig()
	{
		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfigData', '_doConfigTranslation'))
			->getMock();
		$pccd = $this->expected('pccd');
		$feedHelperMock->expects($this->once())
			->method('getConfigData')
			->with($this->equalTo($pccd['with']))
			->will($this->returnValue($pccd['will']));
		$dct = $this->expected('dct');
		$feedHelperMock->expects($this->once())
			->method('_doConfigTranslation')
			->with($this->isType('array'))
			->will($this->returnValue($dct['will']));

		$testData = array(
			array(
				'expect' => $dct['will'],
				'feedType' => 'ItemMaster'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $feedHelperMock->getFileNameConfig($data['feedType']));
		}
	}

	/**
	 * Test _doConfigTranslation method with following expectations
	 * Testing when this test invoked doConfigTranslation it will call the configuration
	 * callback methods and build the key value array
	 */
	public function testDoConfigTranslation()
	{
		$return = array('standard' => 'GSI', 'source_id' => 'ABCD');
		// when the value is string return static value and when
		// the value is an array it will get pass to invoke callback
		$map = array('standard' => 'GSI', 'source_id' => array('type' => 'helper'));

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('invokeCallback'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('invokeCallback')
			->with($this->identicalTo($map['source_id']))
			->will($this->returnValue('ABCD'));

		$this->assertSame(
			$return,
			$this->_reflectMethod($feedHelperMock, '_doConfigTranslation')->invoke($feedHelperMock, $map)
		);
	}

	/**
	 * Test getConfig method
	 * @test
	 */
	public function testGetConfig()
	{
		$hlpr = Mage::helper('eb2ccore/feed');
		// set class property '_config' to a known state
		$this->_reflectProperty($hlpr, '_config')->setValue($hlpr, null);

		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Config_Registry',
			$hlpr->getConfig()
		);

		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Config_Registry',
			$this->_reflectProperty($hlpr, '_config')->getValue($hlpr)
		);
	}

	/**
	 * Test invokeCallback method
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testInvokeCallback($provider)
	{
		$iteration = $provider['iteration'];
		$meta = $provider['meta'];
		$expect = $this->expected('expect')->getData($iteration['name']);
		if ($expect === '') {
			$expect = null;
		}
		$mockMethod = (trim($iteration['mock_method']) === '')? array() : array($iteration['mock_method']);

		if (!empty($mockMethod)) {
			switch ($iteration['mock_type']) {
				case 'helper':
					$mock = $this->getHelperMockBuilder($iteration['mock_class'])
						->disableOriginalConstructor()
						->setMethods($mockMethod)
						->getMock();
					break;
				default:
					$mock = $this->getModelMockBuilder($iteration['mock_class'])
						->disableOriginalConstructor()
						->setMethods($mockMethod)
						->getMock();
					break;
			}

			$mock->expects($this->once())
				->method($iteration['mock_method'])
				->will($this->returnValue($iteration['mock_return']));

			$this->replaceByMock($iteration['mock_type'], $iteration['mock_class'], $mock);
		}

		$helper = Mage::helper('eb2ccore/feed');
		$this->assertSame($expect, $this->_reflectMethod($helper, 'invokeCallback')->invoke($helper, $meta));
	}

	/**
	 * Test getStoreId method
	 * @test
	 */
	public function testGetStoreId()
	{
		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfig'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('getConfig')
			->will($this->returnValue((object) array('storeId' => 'ABCD')));
		$this->assertSame('ABCD', $feedHelperMock->getStoreId());
	}

	/**
	 * Test getClientId method
	 * @test
	 */
	public function testGetClientId()
	{
		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfig'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('getConfig')
			->will($this->returnValue((object) array('clientId' => '1234')));
		$this->assertSame('1234', $feedHelperMock->getClientId());
	}

	/**
	 * Test getMessageId method
	 * As per the XSD, the message id has to be <= 20 characters long
	 * @test
	 */
	public function testGetMessageId()
	{
		$this->assertLessThanOrEqual(20, strlen(Mage::helper('eb2ccore/feed')->getMessageId()));
	}

	/**
	 * Test getCorrelationId method
	 * As per the XSD, the correlation id has to be <= 20 characters long
	 * @test
	 */
	public function testGetCorrelationId()
	{
		$this->assertLessThanOrEqual(20, strlen(Mage::helper('eb2ccore/feed')->getCorrelationId()));
	}
}
