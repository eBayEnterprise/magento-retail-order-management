<?php
class TrueAction_Eb2cProduct_Test_Model_Error_ConfirmationsTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test initFeed method with the following assumptions when call with 'ItemMaster' as a parameter
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::append will be call three times
	 *                once with the xml directive, second with the open root node and third with the content
	 *                return from calling the eb2cproduct helper method (generateMessageHeader)
	 * Expectation 2: the method TrueAction_Eb2cProduct_Helper_Data::generateMessageHeader will be called once with
	 *              the parameter 'ItemMaster' it will return some hard-coded message header string of nodes
	 * Expectation 3: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::initFeed will return itself
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::append
	 * @mock rueAction_Eb2cProduct_Helper_Data::generateMessageHeader
	 * @param string $headerMessage the header message response from the mocking generateMessageHeader
	 * @dataProvider dataProvider
	 */
	public function testInitFeed($headerMessage)
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('generateMessageHeader'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('generateMessageHeader')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnValue($headerMessage));

		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('append'))
			->getMock();
		$confirmationsModelMock->expects($this->at(0))
			->method('append')
			->with($this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::XML_DIRECTIVE))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(1))
			->method('append')
			->with($this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::XML_OPEN_ROOT_NODE))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(2))
			->method('append')
			->with($this->equalTo($headerMessage))
			->will($this->returnSelf());

		$this->assertSame($confirmationsModelMock, $confirmationsModelMock->initFeed('ItemMaster'));
	}

	/**
	 * Test loadFile method with the following assumptions when call with given file name as a parameter
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::_getSplFileInfo will be called once
	 *                with the file name parameter, this method will return a mock object of SplFileInfo, this object
	 *                will then be assigned to the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream
	 * Expectation 2: to prove the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream get set when calling
	 *                the method TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile, the test set the property to a known state of null
	 *                and then assert that it is the same as the mock SplFileInfo object after TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile
	 *                is called.
	 * Expectation 3: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile assert that it will return it self after calling with a file name
	 * Expectation 4: is similar to expectation 2, but this is after TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile there asserting that
	 *                the value in the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream is the same as the SplFileInfo mock
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::_getSplFileInfo
	 * @mock SplFileInfo
	 * @param string $fileName the file name to be passed to the loadFile method
	 * @dataProvider dataProvider
	 */
	public function testLoadFile($fileName)
	{
		$splFileInfoMock = $this->getMockBuilder('SplFileInfo')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_getSplFileInfo'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('_getSplFileInfo')
			->with($this->equalTo($fileName))
			->will($this->returnValue($splFileInfoMock));

		// set the class protected property '_fileStream' to a known state of null
		$this->_reflectProperty($confirmationsModelMock, '_fileStream')->setValue($confirmationsModelMock, null);

		$this->assertSame($confirmationsModelMock, $confirmationsModelMock->loadFile($fileName));
		$this->assertSame($splFileInfoMock, $this->_reflectProperty($confirmationsModelMock, '_fileStream')->getValue($confirmationsModelMock));
	}

	/**
	 * Test append method with the following assumptions when call with given content as a parameter
	 * Expectation 1: the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream will be checked if
	 *                it is not is null it will throw TrueAction_Eb2cProduct_Model_Error_Exception exception,
	 *                that is why in this set we are setting the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream
	 *                to a know state of mock object SplFileInfo
	 * Expectation 2: the mock object SplFileInfo, which is set to the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream, method
	 *                SplFileInfo::openFile is expected to be called once, which will return a mock stdClass object, which meant to replace mocking SplFileObject,
	 *                which cannot be mocked out because it will throw exception as it need a real file to be passed to its constructor method.
	 * Expectation 3: the mock stdClass object method stdClass::fwrite will be called once with the content it which it will return the
	 *                number of bytes the content appended to the file
	 * @mock SplFileInfo::openFile
	 * @mock stdClass::fwrite <---> SplFileObject::fwrite
	 * @param string $content the content to be appended to the file in error confirmation class stream
	 * @dataProvider dataProvider
	 */
	public function testAppend($content)
	{
		// we are faking mocking out 'SplFileObject' because it will throw an exception in the constructor which cannot be disabled
		// because it is expecting a real file to be passed to it so that it can create it if doesn't exist
		// since using the real object will create a real file, faking it out is a much better choice
		$splFileObjectMock = $this->getMockBuilder('stdClass')
			->disableOriginalConstructor()
			->setMethods(array('fwrite'))
			->getMock();
		$splFileObjectMock->expects($this->once())
			->method('fwrite')
			->with($this->equalTo($content . "\n"))
			->will($this->returnValue(strlen($content . "\n")));

		$splFileInfoMock = $this->getMockBuilder('SplFileInfo')
			->disableOriginalConstructor()
			->setMethods(array('openFile'))
			->getMock();
		$splFileInfoMock->expects($this->once())
			->method('openFile')
			->with($this->equalTo('a'))
			->will($this->returnValue($splFileObjectMock));

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_fileStream' to a known state of the mock object SplFileInfo
		$this->_reflectProperty($confirmations, '_fileStream')->setValue($confirmations, $splFileInfoMock);

		$this->assertSame($confirmations, $confirmations->append($content));
	}

	/**
	 * Test append method with the following assumptions when call with given content as a parameter
	 * Expectation 1: the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream will be checked if
	 *                it is not is null it will throw TrueAction_Eb2cProduct_Model_Error_Exception exception,
	 *                that is why in this set we are setting the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream
	 *                to a know state of null so that will throw the TrueAction_Eb2cProduct_Model_Error_Exception as annotated for this test
	 * @param string $content the content to be appended to the file in error confirmation class stream
	 * @dataProvider dataProvider
	 * @expectedException TrueAction_Eb2cProduct_Model_Error_Exception
	 */
	public function testAppendInvalidFileStreamException($content)
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_fileStream' to a known state of null
		$this->_reflectProperty($confirmations, '_fileStream')->setValue($confirmations, null);

		$confirmations->append($content);
	}

	/**
	 * Test close method with the following assumptions when called
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::append will be called once given  the closing root node
	 * Expectation 2: the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_fileStream to see if it has an object
	 *                if it is, it will set it to null to unreferenced the object in the class. That is why
	 *                the test is setting it to a known state with a mocked SplFileInfo object.
	 * Expectation 3: the following class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueMessage, _queueError, _queueConfirmation
	 *                will be set to an empty array
	 * Expectation 4: calling method TrueAction_Eb2cProduct_Model_Error_Confirmations::close will return itself
	 * @mock SplFileInfo
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::append
	 */
	public function testClose()
	{
		$splFileInfoMock = $this->getMockBuilder('SplFileInfo')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('append'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('append')
			->with($this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::XML_CLOSE_ROOT_NODE))
			->will($this->returnSelf());

		$this->_reflectProperty($confirmationsModelMock, '_fileStream')->setValue($confirmationsModelMock, $splFileInfoMock);

		$this->assertSame($confirmationsModelMock, $confirmationsModelMock->close());
		$this->assertNull($this->_reflectProperty($confirmationsModelMock, '_fileStream')->getValue($confirmationsModelMock));
		$this->assertEmpty($this->_reflectProperty($confirmationsModelMock, '_queueMessage')->getValue($confirmationsModelMock));
		$this->assertEmpty($this->_reflectProperty($confirmationsModelMock, '_queueError')->getValue($confirmationsModelMock));
		$this->assertEmpty($this->_reflectProperty($confirmationsModelMock, '_queueConfirmation')->getValue($confirmationsModelMock));
	}

	/**
	 * Test addMessage method with the following assumptions when call with given message template ($msgTemplate) and message ($message) as parameters
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::_getLangCode will be called once and will return the language code (en-us)
	 *                to be set in the array key (language_code) and then the parameters ($msgTemplate, $message) will be passed to the built-in sprint method
	 *                to be set in the array key (message_data).
	 * Expectation 2: the array that was built in expectation one will then be passed as the first parameter to the class method
	 *                TrueAction_Eb2cProduct_Helper_Data::mapPattern, and the second parameter is class constant for the error message node with mapped
	 *                place holder for the value in the first parameter
	 * Expectation 3: the return value from the mock method TrueAction_Eb2cProduct_Helper_Data::mapPattern will be set to the class property
	 *                TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueMessage as an array element, to prove this occurred the class property
	 *                is set to an empty array in the test, and is then proven to have an array element of the content the mapPattern return
	 * Expectation 4: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::addMessage will return itself as asserted in the test
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::_getLangCode
	 * @mock TrueAction_Eb2cProduct_Helper_Data::mapPattern
	 * @test
	 */
	public function testAddMessage()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('mapPattern'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('mapPattern')
			->with($this->isType('array'))
			->will($this->returnValue('<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_getLangCode'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('_getLangCode')
			->will($this->returnValue('en-us'));
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $confirmationsModelMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');
		// class property _queueMessage to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array());

		$this->assertInstanceOf('TrueAction_Eb2cProduct_Model_Error_Confirmations', $confirmations->addMessage(
			$confirmations::SKU_NOT_REMOVE, 'UnitTest Simulate Throw Exception on Dom load'
		));

		$this->assertSame(
			array('<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>'),
			$this->_reflectProperty($confirmations, '_queueMessage')->getValue($confirmations)
		);
	}

	/**
	 * Test addError method with the following assumptions when call with given message template ($type) and message ($fileName) as parameters
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::_getErrorTemplate will be called once with
	 *                for following parameter (ItemMaster, ItemMaster_TestSubset.xml), and it will return an array of keys and values
	 * Expectation 2: the value return from mock method TrueAction_Eb2cProduct_Model_Error_Confirmations::_getErrorTemplate will then pass as the first
	 *                parameter to class method TrueAction_Eb2cProduct_Helper_Data::mapPattern, and the second parameter is class constant for the error
	 *                open node with mapped place holder for the values
	 * Expectation 3: the return value from TrueAction_Eb2cProduct_Helper_Data::mapPattern will be mapped as the first place holder string format for the
	 *                sprintf method, the second parameter implode the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueMessage, and
	 *                the third parameter is a class constant for the error close node.
	 * Expectation 4: the test set the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueMessage to a know state with array with
	 *                element on it and after the addError run the _queueMessage class property is asserted to be empty as expected.
	 * Expectation 5: the test set the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueError of an empty array and when addError
	 *                is run the property is asserted to have array elements as expected
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::_getErrorTemplate
	 * @mock TrueAction_Eb2cProduct_Helper_Data::mapPattern
	 */
	public function testAddError()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('mapPattern'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('mapPattern')
			->with($this->isType('array'))
			->will($this->returnValue('<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_getErrorTemplate'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('_getErrorTemplate')
			->with($this->equalTo('ItemMaster'), $this->equalTo('ItemMaster_TestSubset.xml'))
			->will($this->returnValue(array(
				'feed_type' => 'ItemMaster',
				'feed_file_name' => 'ItemMaster_TestSubset.xml',
				'from' => TrueAction_Eb2cCore_Helper_Feed::DEST_ID
			)));
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $confirmationsModelMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');
		// class property _queueMessage and _queueError to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array(
			'<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>'
		));
		$this->_reflectProperty($confirmations, '_queueError')->setValue($confirmations, array());

		$this->assertSame($confirmations, $confirmations->addError('ItemMaster', 'ItemMaster_TestSubset.xml'));

		$this->assertSame(
			array('<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
</Error>'),
			$this->_reflectProperty($confirmations, '_queueError')->getValue($confirmations)
		);

		$this->assertEmpty($this->_reflectProperty($confirmations, '_queueMessage')->getValue($confirmations));
	}

	/**
	 * Test addErrorConfirmation method with the following assumptions when call with given sku as a parameter
	 * Expectation 1: the sku parameter will be assigned to an array with key (sku), and this array will be the first parameter pass to
	 *                the class method TrueAction_Eb2cProduct_Helper_Data::mapPattern and the second parameter will be a class constant that
	 *                has value for the error confirmation node.
	 * Expectation 2: the mocked method TrueAction_Eb2cProduct_Helper_Data::mapPattern will return value that will be mapped to the first
	 *                sprintf formatted string the second parameter to the sprintf method will be the implode result of the class property
	 *                TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueError the third parameter is the class constant for error
	 *                confirmation close node.
	 * Expectation 3: the test set the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueError to a know state with
	 *                array with element and set the property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation to a
	 *                state of empty array, when the test run the method addErrorConfirmation we can properly asserted that the class
	 *                property _queueConfirmation was an empty array and now it has an array with elements and we can also assert that
	 *                the class property _queueError had an array of element but now empty
	 * @mock TrueAction_Eb2cProduct_Helper_Data::mapPattern
	 */
	public function testAddErrorConfirmation()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('mapPattern'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('mapPattern')
			->with($this->equalTo(array('sku' => 'SK-ABC-1334')))
			->will($this->returnValue('<ErrorConfirmation unique_id="SK-ABC-1334">'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');
		// class property _queueConfirmation and _queueError to a known state
		$this->_reflectProperty($confirmations, '_queueError')->setValue($confirmations, array(
			'<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
				<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
			</Error>'
		));
		$this->_reflectProperty($confirmations, '_queueConfirmation')->setValue($confirmations, array());

		$this->assertSame($confirmations, $confirmations->addErrorConfirmation('SK-ABC-1334'));

		$this->assertSame(
			array('<ErrorConfirmation unique_id="SK-ABC-1334">
<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
				<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
			</Error>
</ErrorConfirmation>'
			),
			$this->_reflectProperty($confirmations, '_queueConfirmation')->getValue($confirmations)
		);

		$this->assertEmpty($this->_reflectProperty($confirmations, '_queueError')->getValue($confirmations));
	}

	/**
	 * Test flush method with the following assumptions when called
	 * Expectation 1: in order to fully test the TrueAction_Eb2cProduct_Model_Error_Confirmations::flush method we need to set
	 *                the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation to a known states with array with elements
	 * Expectation 2: since the test set the class property _queueConfirmation to an array with one element we expect that the class method
	 *                TrueAction_Eb2cProduct_Model_Error_Confirmations::append will be called once with the content of the first array element and return itself
	 * Expectation 3: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::flush is expected to return itself as expected
	 * Expectation 4: the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation is expected to be an empty array.
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::append
	 */
	public function testFlush()
	{
		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('append'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('append')
			->with($this->equalTo('<ErrorConfirmation unique_id="SK-ABC-1334">
<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
				<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
			</Error>
	</ErrorConfirmation>'))
			->will($this->returnSelf());

		// class property _queueConfirmation to a known state
		$this->_reflectProperty($confirmationsModelMock, '_queueConfirmation')->setValue($confirmationsModelMock, array('<ErrorConfirmation unique_id="SK-ABC-1334">
<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
				<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
			</Error>
	</ErrorConfirmation>'
		));

		$this->assertSame($confirmationsModelMock, $confirmationsModelMock->flush());

		$this->assertEmpty($this->_reflectProperty($confirmationsModelMock, '_queueConfirmation')->getValue($confirmationsModelMock));
	}

	/**
	 * Test _getErrorTemplate method with the following assumptions when call with given message template ($type) and message ($fileName) as parameters
	 * Expectation 1: passing type and filename to the tested method TrueAction_Eb2cProduct_Model_Error_Confirmations::_getErrorTemplate
	 *                will return an array with key (feed_type) that hold the value of the parameter type, another array key (feed_file_name),
	 *                with the value of filename parameter and one last array key (from) with the value of the eb2ccore helper class feed constant value
	 */
	public function testGetErrorTemplate()
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		$testData = array(
			array(
				'expect' => array('feed_type' => 'ItemMaster', 'feed_file_name' => 'ItemMaster_Test_Subset.xml', 'from' => TrueAction_Eb2cCore_Helper_Feed::DEST_ID),
				'type' => 'ItemMaster',
				'fileName' => 'ItemMaster_Test_Subset.xml'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $this->_reflectMethod($confirmations, '_getErrorTemplate')->invoke($confirmations, $data['type'], $data['fileName']));
		}
	}

	/**
	 * Test hasMessage method with the following assumptions when called
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Error_Confirmations::hasMessage revolved around the class
	 *                property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueMessage current state in the instantiated object
	 *                Therefore the test is testing two scenarios by setting the state of the class property _queueMessage to a known state
	 *                and then testing the return value of the hasMessage method
	 * Expectation 2: setting the state of the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueMessage to an empty array
	 *                the test assert that the method TrueAction_Eb2cProduct_Model_Error_Confirmations::hasMessage will return false
	 * Expectation 3: calling the TrueAction_Eb2cProduct_Model_Error_Confirmations::addMessage with parameter one being the class constant for dom load err
	 *                and the second parameter being a text string verbiage and the test can then make the assertion that
	 *                TrueAction_Eb2cProduct_Model_Error_Confirmations::hasMessage will return true
	 */
	public function testHasMessage()
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_queueMessage' to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array());
		$this->assertSame(false, $confirmations->hasMessage());

		$confirmations->addMessage($confirmations::SKU_NOT_REMOVE, 'UnitTest Simulate Throw Exception on Dom load');
		$this->assertSame(true, $confirmations->hasMessage());
	}

	/**
	 * Test hasError method with the following assumptions when called
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Error_Confirmations::hasError revolved around the class
	 *                property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueError current state in the instantiated object
	 *                Therefore the test is testing two scenarios by setting the state of the class property _queueError and _queueMessage to a known state
	 *                and then testing the return value of the hasError method
	 * Expectation 2: setting the state of the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueError and _queueMessage to an empty array
	 *                the test assert that the method TrueAction_Eb2cProduct_Model_Error_Confirmations::hasError will return false
	 * Expectation 3: calling the TrueAction_Eb2cProduct_Model_Error_Confirmations::addMessage with parameter one being the class constant for dom load err
	 *                and the second parameter being a text string verbiage and then calling the method
	 *                TrueAction_Eb2cProduct_Model_Error_Confirmations::addError with type as first parameter and file name as the second parameter
	 *                the test can then make the assertion that
	 *                TrueAction_Eb2cProduct_Model_Error_Confirmations::hasError will return true
	 */
	public function testHasError()
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_queueMessage' and '_queueError' to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array());
		$this->_reflectProperty($confirmations, '_queueError')->setValue($confirmations, array());
		$this->assertSame(false, $confirmations->hasError());

		$confirmations->addMessage($confirmations::SKU_NOT_REMOVE, 'UnitTest Simulate Throw Exception on Dom load')
			->addError('ItemMaster', 'ItemMaster_Test_Subset.xml');
		$this->assertSame(true, $confirmations->hasError());
	}

	/**
	 * Test hasErrorConfirmation method with the following assumptions when called
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Error_Confirmations::hasErrorConfirmation revolved around the class
	 *                property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation current state in the instantiated object
	 *                Therefore the test is testing two scenarios by setting the state of the class property _queueConfirmation,
	 *                _queueError and _queueMessage to a known state and then testing the return value of the hasErrorConfirmation method
	 * Expectation 2: setting the state of the class property TrueAction_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation, _queueError
	 *                and _queueMessage to an empty array
	 *                the test assert that the method TrueAction_Eb2cProduct_Model_Error_Confirmations::hasErrorConfirmation will return false
	 * Expectation 3: calling the TrueAction_Eb2cProduct_Model_Error_Confirmations::addMessage with parameter one being the class constant
	 *                for dom load err and the second parameter being a text string verbiage, then calling the method
	 *                TrueAction_Eb2cProduct_Model_Error_Confirmations::addError with type as first parameter and file name as the
	 *                second parameter and then calling the method TrueAction_Eb2cProduct_Model_Error_Confirmations::addErrorConfirmation with sku
	 *                the test can then make the assertion that TrueAction_Eb2cProduct_Model_Error_Confirmations::hasErrorConfirmation will return true
	 * @test
	 */
	public function testHasErrorConfirmation()
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_queueMessage', '_queueError' and '_queueConfirmation' to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array());
		$this->_reflectProperty($confirmations, '_queueError')->setValue($confirmations, array());
		$this->_reflectProperty($confirmations, '_queueConfirmation')->setValue($confirmations, array());
		$this->assertSame(false, $confirmations->hasErrorConfirmation());

		$confirmations->addMessage($confirmations::SKU_NOT_REMOVE, 'UnitTest Simulate Throw Exception on Dom load')
			->addError('ItemMaster', 'ItemMaster_Test_Subset.xml')
			->addErrorConfirmation('1234');
		$this->assertSame(true, $confirmations->hasErrorConfirmation());
	}

	/**
	 * Test process method with the following assumptions when call with given Varien_Event_Observer object as a parameter
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Error_Confirmations::process is expected to be called with a mock
	 *                Varien_Event_Observer object in which the mocked object method Varien_Event_Observer::getEvent will be called once
	 *                and will return a mocked Varien_Event object, the Varien_Event::getFeedDetails will be called once and will return
	 *                an array of an array of key elements just error_file which is needed for the processing the error confirmation files
	 * Expectation 2: the array return by the Varien_Event::getFeedDetails method will then use to load through each array of array with keys,
	 *                each array with key (error_file), will then call method TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile with the file name once,
	 *                then call the method TrueAction_Eb2cProduct_Model_Error_Confirmations::close once,
	 *                then call the method TrueAction_Eb2cProduct_Model_Error_Confirmations::transferFile with the file name as parameter once,
	 *                and then call the method TrueAction_Eb2cProduct_Model_Error_Confirmations::archive with the file name as parameter once
	 * Expectation 3: the TrueAction_Eb2cProduct_Model_Error_Confirmations::process method is expected to return itself
	 * @mock Varien_Event_Observer::getEvent
	 * @mock Varien_Event::getFeedDetails
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::close
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::transferFile
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::archive
	 */
	public function testProcess()
	{
		$event = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getFeedDetails'))
			->getMock();
		$event->expects($this->once())
			->method('getFeedDetails')
			->will($this->returnValue(array(
				array(
					'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
					'remote' => '/Inbox/Product',
					'timestamp' => '1364823587',
					'type' => 'ItemMaster',
					'error_file' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140115063947_MAGTNA_MAGT1.xml'
				)
			)));

		$observer = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observer->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($event));

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('loadFile', 'close', 'transferFile', 'archive'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('loadFile')
			->with($this->equalTo('TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140115063947_MAGTNA_MAGT1.xml'))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('close')
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('transferFile')
			->with($this->equalTo('TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140115063947_MAGTNA_MAGT1.xml'))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('archive')
			->with($this->equalTo('TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140115063947_MAGTNA_MAGT1.xml'))
			->will($this->returnSelf());

		$this->assertSame($confirmationsModelMock, $confirmationsModelMock->process($observer));
	}

	/**
	 * Test transferFile method with the following assumptions when call with given filename as a parameter
	 * Expectation 1: mocked the method of TrueAction_Eb2cProduct_Helper_Data::getConfigModel to return a mocked of
	 *                TrueAction_Eb2cCore_Model_Config_Registry class that return public magic class property (errorFeedRemoteMailbox)
	 * Expectation 2: mocked the method of TrueAction_Eb2cCore_Helper_Feed::sendFile with fileName as the first parameter
	 *                and the remotePath as the second parameter and then return itself, this will run only once
	 * Expectation 3: when the test run TrueAction_Eb2cProduct_Model_Error_Confirmations::transferFile it will return itself
	 * @mock TrueAction_Eb2cProduct_Helper_Data::getConfigModel
	 * @mock TrueAction_Eb2cCore_Model_Config_Registry
	 * @mock TrueAction_Eb2cCore_Helper_Feed::sendFile
	 * @param string $fileName the file to be sent
	 * @dataProvider dataProvider
	 */
	public function testTransferFile($fileName)
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getConfigModel')
			->with($this->equalTo(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID))
			->will($this->returnValue($this->buildCoreConfigRegistry(array('errorFeedRemoteMailbox' => '/'))));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('sendFile'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('sendFile')
			->with(
				$this->equalTo($fileName),
				$this->equalTo('/')
			)
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelperMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		$this->assertSame($confirmations, $confirmations->transferFile($fileName));
	}

	/**
	 * @see testTransferFile expectations except this test will mock the TrueAction_Eb2cCore_Helper_Feed::sendFile
	 *      method to throw an TrueAction_Eb2cCore_Exception_Feed_Transmissionfailure exception
	 * @mock TrueAction_Eb2cProduct_Helper_Data::getConfigModel
	 * @mock TrueAction_Eb2cCore_Model_Config_Registry
	 * @mock TrueAction_Eb2cCore_Helper_Feed::sendFile
	 * @param string $fileName the file to be sent
	 * @dataProvider dataProvider
	 */
	public function testTransferFileWithExeption($fileName)
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getConfigModel')
			->with($this->equalTo(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID))
			->will($this->returnValue((object) array(
				'errorFeedRemoteMailbox' => '/'
			)));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('sendFile'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('sendFile')
			->with(
				$this->equalTo($fileName),
				$this->equalTo('/')
			)
			->will($this->throwException(
				new TrueAction_Eb2cCore_Exception_Feed_Transmissionfailure('UnitTest Simulate Throw Exception on sendFile method')
			));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelperMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		$this->assertSame($confirmations, $confirmations->transferFile($fileName));
	}

	/**
	 * Test archive method with the following assumptions when call with given source as a parameter
	 * Expectation 1: mocked the TrueAction_Eb2cCore_Helper_Data::moveFile method with the following parameter
	 *               given source and the destination to move the file to, this method should run once.
	 * Expectation 2: when test run the TrueAction_Eb2cProduct_Model_Error_Confirmations::archive it will return it self
	 * @mock TrueAction_Eb2cCore_Helper_Data::moveFile
	 * @param string $source the file in the inbound folder
	 * @param string $destination the file to be moved to the archive folder
	 * @dataProvider dataProvider
	 */
	public function testArchive($source, $destination)
	{
		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('moveFile'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('moveFile')
			->with(
				$this->equalTo($source),
				$this->equalTo($destination)
			)
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		$this->assertSame($confirmations, $confirmations->archive($source));
	}

	/**
	 * Test processByOperationType method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::_processByOperationType will be
	 *                invoked and given an object of Varient_Event_Observer contain methods to get all skues that were deleted
	 *                and another method got get feed file detail. It will query the magento database for these
	 *                deleted skus and report any product that still exist in magento.
	 */
	public function testProcessByOperationType()
	{
		$skus = array('58-HTC038', '58-JKT8844');
		$type = 'ItemMaster';
		$file = 'local/Feed/ItemMaster/inbox/ItemMaster_Subset-Sample.xml';
		$errorFile = 'local/Feed/ItemMaster/outbound/ItemMaster_20140115063947_ABCD_1234.xml';
		$operationType = 'delete';
		$fileDetail = array('local' => $file, 'type' => $type, 'error_file' => $errorFile, 'operation_type' => $operationType);

		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getFeedDetail', 'getSkus', 'getOperationType'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getFeedDetail')
			->will($this->returnValue($fileDetail));
		$eventMock->expects($this->once())
			->method('getSkus')
			->will($this->returnValue($skus));
		$eventMock->expects($this->once())
			->method('getOperationType')
			->will($this->returnValue($operationType));

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$productCollection = Mage::getResourceModel('catalog/product_collection');

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('loadFile', '_getProductCollectionBySkus', '_addDeleteErrors'))
			->getMock();
		$errorConfirmationMock->expects($this->once())
			->method('loadFile')
			->with($this->identicalTo($errorFile))
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->once())
			->method('_getProductCollectionBySkus')
			->with($this->identicalTo($skus))
			->will($this->returnValue($productCollection));
		$errorConfirmationMock->expects($this->once())
			->method('_addDeleteErrors')
			->with($this->identicalTo($productCollection), $this->identicalTo(basename($file)), $this->identicalTo($type))
			->will($this->returnSelf());

		$this->assertSame($errorConfirmationMock, $errorConfirmationMock->processByOperationType($observerMock));
	}

	/**
	 * Test _addDeleteErrors method for the following expectations
	 * Expectation 1: given a mock object of class Mage_Catalog_Model_Resource_Product_Collection, a feed file name
	 *                and an event type to the method TrueAction_Eb2cProduct_Model_Error_Confirmations::_addDeleteErrors
	 *                when invoked by this test will check the count on the collection loop through all the product
	 *                on the collection call addMessage, addError, AddErrrorConfirmation and flush methods to error confirmation
	 *                to the file
	 */
	public function testAddDeleteErrors()
	{
		$skus = array('58-HTC038', '58-JKT8844');
		$type = 'ItemMaster';
		$fileName = 'ItemMaster_Subset-Sample.xml';

		$productCollection = Mage::getResourceModel('catalog/product_collection');
		foreach ($skus as $sku) {
			$productCollection->addItem(Mage::getModel('catalog/product')->addData(array('sku' => $sku)));
		}

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_appendError'))
			->getMock();
		$errorConfirmationMock->expects($this->at(0))
			->method('_appendError')
			->with(
				$this->identicalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_REMOVE),
				$this->identicalTo(''),
				$this->identicalTo($type),
				$this->identicalTo($fileName),
				$this->identicalTo($skus[0])
			)
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->at(1))
			->method('_appendError')
			->with(
				$this->identicalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_REMOVE),
				$this->identicalTo(''),
				$this->identicalTo($type),
				$this->identicalTo($fileName),
				$this->identicalTo($skus[1])
			)
			->will($this->returnSelf());

		$this->assertSame($errorConfirmationMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$errorConfirmationMock,
			'_addDeleteErrors',
			array($productCollection, $fileName, $type)
		));
	}

	/**
	 * Test _addImportErrors method for the following expectations
	 * Expectation 1: given a mock object of class Mage_Catalog_Model_Resource_Product_Collection, a list of imported skus, a feed file name
	 *                and an event type to the method TrueAction_Eb2cProduct_Model_Error_Confirmations::_addImportErrors
	 *                when invoked by this test will loop through all the imported skus and check if the sku is in
	 *                the collection if it is not it will add error confirmations
	 */
	public function testAddImportErrors()
	{
		$skus = array('58-HTC038', '58-JKT8844');
		$type = 'ItemMaster';
		$fileName = 'ItemMaster_Subset-Sample.xml';

		$collectionMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$collectionMock->expects($this->exactly(2))
			->method('getItemByColumnValue')
			->will($this->returnValueMap(array(
				array('sku', $skus[0], null),
				array('sku', $skus[1], null)
			)));

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_appendError'))
			->getMock();
		$errorConfirmationMock->expects($this->at(0))
			->method('_appendError')
			->with(
				$this->identicalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_IMPORTED),
				$this->identicalTo(''),
				$this->identicalTo($type),
				$this->identicalTo($fileName),
				$this->identicalTo($skus[0])
			)
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->at(1))
			->method('_appendError')
			->with(
				$this->identicalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_IMPORTED),
				$this->identicalTo(''),
				$this->identicalTo($type),
				$this->identicalTo($fileName),
				$this->identicalTo($skus[1])
			)
			->will($this->returnSelf());

		$this->assertSame($errorConfirmationMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$errorConfirmationMock,
			'_addImportErrors',
			array($collectionMock, $skus, $fileName, $type)
		));
	}

	/**
	 * Test _appendError method for the following expectations
	 * Expectation 1: given a mock object of class Mage_Catalog_Model_Resource_Product_Collection, a list of imported skus, a feed file name
	 *                and an event type to the method TrueAction_Eb2cProduct_Model_Error_Confirmations::_appendError
	 *                when invoked by this test will loop through all the imported skus and check if the sku is in
	 *                the collection if it is not it will add error confirmations
	 */
	public function testAppendError()
	{
		$sku = '58-HTC038';
		$type = 'ItemMaster';
		$fileName = 'ItemMaster_Subset-Sample.xml';

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('addMessage', 'addError', 'addErrorConfirmation', 'flush'))
			->getMock();
		$errorConfirmationMock->expects($this->once())
			->method('addMessage')
			->with($this->identicalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_IMPORTED), $this->identicalTo(''))
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->once())
			->method('addError')
			->with($this->identicalTo($type), $this->identicalTo($fileName))
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->once())
			->method('addErrorConfirmation')
			->with($this->identicalTo($sku))
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->once())
			->method('flush')
			->will($this->returnSelf());

		$this->assertSame($errorConfirmationMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$errorConfirmationMock,
			'_appendError',
			array(TrueAction_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_IMPORTED, '', $type, $fileName, $sku)
		));
	}

	/**
	 * Test _getProductCollectionBySkus method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Model_Feed_File::_getProductCollectionBySkus when
	 *                invoked by this test will be given an array of skus as parameter
	 *                with that parameter it will query the Mage_Catalog_Model_Resource_Product_Collection by skus
	 *                and return a collection of product
	 */
	public function testGetProductCollectionBySkus()
	{
		$skus = array('58-HTC038', '58-JKT8844');

		$collectionMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'addAttributeToSelect', 'load'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('sku'), $this->identicalTo($skus))
			->will($this->returnSelf());
		$collectionMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo(array('sku')))
			->will($this->returnSelf());
		$collectionMock->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collectionMock);

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($collectionMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$errorConfirmationMock,
			'_getProductCollectionBySkus',
			array($skus)
		));
	}
}
