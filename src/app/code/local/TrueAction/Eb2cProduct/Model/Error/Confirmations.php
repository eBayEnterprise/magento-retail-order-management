<?php
/**
 * The error confirmations class was implemented in such a way to adapt to the way the super product feed is currently functioning
 * How to use the error confirmation class is as follow:
 * Step 1: load the error confirmation file, which can be generated using external helper methods
 *            Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile('ItemMaster_20140107224605_12345_ABCD.xml');
 * Step 2: initialize primary data such as the xml directive, root node and message header
 *            Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::initFeed('ItemMaster');
 * Step 3: this step mean that within the integration where you have an error and you need add this error to an error confirmation feed file you
 *         should do as the example below demonstrate:
 *            Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::addMessage(self::DOM_LOAD_ERR, 'Dom exception throw while processing feed file');
 *                     TrueAction_Eb2cProduct_Model_Error_Confirmations::addError('ItemMaster', 'ItemMaster_TestSubset.xml');
 *                     TrueAction_Eb2cProduct_Model_Error_Confirmations::addErrorConfirmation('SK-ABC-1334');
 *                     TrueAction_Eb2cProduct_Model_Error_Confirmations::flush();
 *            Result: <ErrorConfirmation unique_id="SK-ABC-1334">
 *                        <Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
 *                              <Message xml:lang="en-US" code="">Error exception occurred: Dom exception throw while processing feed file</Message>
 *                        </Error>
 *                    </ErrorConfirmation>
 * Step 4: closing the file after successfully capturing all possible error
 *            Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::close();
 *
 * FYI: because of the way the super feed is setup, we have to dispatch an event and pass it all the product error_confirmation feed files that were created
 *      in this case the following step will need to happen to close all error confirmation file, send them to eb2c server and then move the files from
 *      outbound to archive
 * Step 5: dispatch 'product_feed_complete_error_confirmation' event
 *            Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::process();
 *            Summary: for each of the error confirmation feed files that has been process, close it with the root node, send the file to eb2c,
 *            and then move it to archive folder.
 */
class TrueAction_Eb2cProduct_Model_Error_Confirmations
{
	const XML_DIRECTIVE = '<?xml version="1.0" encoding="UTF-8"?>';
	const XML_OPEN_ROOT_NODE = '<ErrorConfirmations>';
	const XML_CLOSE_ROOT_NODE = '</ErrorConfirmations>';

	const XML_ERRCONFIRMATION_OPEN_NODE = '<ErrorConfirmation unique_id="{sku}">';
	const XML_ERRCONFIRMATION_CLOSE_NODE = '</ErrorConfirmation>';

	const XML_ERROR_OPEN_NODE = '<Error event_type="{feed_type}" file_name="{feed_file_name}" reported_from="{from}">';
	const XML_ERROR_CLOSE_NODE = '</Error>';

	const XML_MESSAGE_NODE = '<Message xml:lang="{language_code}" code="">{message_data}</Message>';

	const DEFAULT_ERR = 'Unknown error occurred: %s';
	const DOM_LOAD_ERR = 'Error exception occurred: %s';
	const BEFORE_PROCESS_DOM_ERR = 'Error exception occurred before processing the Dom document: %s';
	const EVENT_TYPE_ERR = 'Event Type error occurred: %s';
	const INVALID_HEADER_ERR = 'Invalid Header error occurred in feed file %s';
	const INVALID_DATA_ERR = 'Invalid Data error occurred in feed file %s';
	const INVALID_OPERATION_ERR = 'Invalid Operation Type Error exception occurred: %s';
	const INVALID_SKU_ERR = 'Invalid sku in feed file (%s)';
	const SAVE_PRODUCT_EXCEPTION_ERR = 'Saving product exception: %s';
	const INVALID_LANG_CODE_ERR = 'Invalid language code %s';
	const INVALID_ATTRIBUTE_OPERATION_ERR = 'Invalid attribute operation %s';
	const MISSING_ATTRIBUTE_OPERATION_ERR = 'missing custom attribute operation for "%s"';
	const MISSING_IN_ATTRIBUTE_SET_ERR = 'The following custom attribute is missing from the attribute set "%s"';
	const ADD_COLOR_OPTION_ERR = 'Adding new color option exception: "%s"';
	const COLOR_DESCRIPTION_ERR = 'Saving product with language color description exception: "%s"';
	const CONFIGURABLE_ATTRIBUTE_ERR = 'Processing configurable attribute throw exception: "%s"';
	const MISSING_SKU_ERR = 'Sku "%s" not in magento store for this inventory update feed.';

	/**
	 * @var array hold message string xml node
	 */
	protected $_queueMessage = array();

	/**
	 * @var array hold error string xml node
	 */
	protected $_queueError = array();

	/**
	 * @var array hold error confirmation string xml node
	 */
	protected $_queueConfirmation = array();

	/**
	 * @var SplFileInfo
	 */
	protected $_fileStream = null;

	/**
	 * @var string
	 */
	protected $_defaultLangCode = null;

	/**
	 * check if the default language code is set to the class
	 * property _defaultLangCode, if not then set else return the value
	 * of the class property
	 * @return string
	 */
	protected function _getLangCode()
	{
		if (trim($this->_defaultLangCode) === '') {
			$this->_defaultLangCode = Mage::helper('eb2cproduct')->getDefaultLanguageCode();
		}
		return $this->_defaultLangCode;
	}

	/**
	 * abstracting instantiating SplFileInfo
	 * @param string $file file with full path
	 * @param string $mode optional default to writable
	 * @return SplFileInfo
	 * @codeCoverageIgnore
	 */
	protected function _getSplFileInfo($file)
	{
		return new SplFileInfo($file);
	}

	/**
	 * load the file into SplFileInfo object on writable mode ('w') if not exist
	 * otherwise load it on append mode ('a')
	 * @param string $fileName
	 * @return self
	 */
	public function loadFile($fileName)
	{
		$this->_fileStream = $this->_getSplFileInfo($fileName);
		return $this;
	}

	/**
	 * insert initial data to feed file such as
	 * appending xml directive, open root node, and message header
	 * @param string $feedType known feed types are (ItemMaster, Content, iShip, Price, ImageMaster, ItemInventories)
	 * @return self
	 */
	public function initFeed($feedType)
	{
		$this->append(self::XML_DIRECTIVE)
			->append(self::XML_OPEN_ROOT_NODE)
			->append(Mage::helper('eb2cproduct')->generateMessageHeader($feedType));

		return $this;
	}

	/**
	 * append contents to a file, if fileStream property null throw exception
	 * else append the content to the file in the defined file stream
	 * @param string $content the content to append to the file
	 * @return self
	 * @throws TrueAction_Eb2cProduct_Model_Error_Exception
	 */
	public function append($content='')
	{
		if (is_null($this->_fileStream)) {
			throw new TrueAction_Eb2cProduct_Model_Error_Exception(sprintf(
				'[ %s ] Error Confirmations file stream is not instantiated', __CLASS__
			));
		}
		$this->_fileStream->openFile('a')->fwrite("$content\n");
		return $this;
	}

	/**
	 * append the closing root node to the error feed file
	 * then close file stream and reset all queues to empty array
	 * @return self
	 */
	public function close()
	{
		$this->append(self::XML_CLOSE_ROOT_NODE);
		if ($this->_fileStream) {
			$this->_fileStream = null;
		}
		$this->_queueMessage = array();
		$this->_queueError = array();
		$this->_queueConfirmation = array();
		return $this;
	}

	/**
	 * add error messages to the message queue
	 * @param string $msgTemplate, for the message constant
	 * @param string $message, can be filename or an actually exception message content
	 * @return self
	 */
	public function addMessage($msgTemplate, $message)
	{
		$this->_queueMessage[] = Mage::helper('eb2cproduct')->mapPattern(
			array(
				'language_code' => $this->_getLangCode(),
				'message_data' => sprintf($msgTemplate, $message)
			),
			self::XML_MESSAGE_NODE
		);
		return $this;
	}

	/**
	 * add error messages to the error queue data under
	 * the error node and then reset error message queue to an empty array
	 * @param string $type
	 * @param string $fileName
	 * @return self
	 */
	public function addError($type, $fileName)
	{
		$this->_queueError[] = sprintf(
			"%s\n%s\n%s",
			Mage::helper('eb2cproduct')->mapPattern($this->_getErrorTemplate($type, $fileName), self::XML_ERROR_OPEN_NODE),
			implode("\n", $this->_queueMessage),
			self::XML_ERROR_CLOSE_NODE
		);
		// reset message queue
		$this->_queueMessage = array();
		return $this;
	}

	/**
	 * add error to the error confirmation queue and then
	 * reset the error queue to an empty array
	 * @param string $sku
	 * @return self
	 */
	public function addErrorConfirmation($sku)
	{
		$this->_queueConfirmation[] = sprintf(
			"%s\n%s\n%s",
			Mage::helper('eb2cproduct')->mapPattern(array('sku' => $sku), self::XML_ERRCONFIRMATION_OPEN_NODE),
			implode("\n", $this->_queueError),
			self::XML_ERRCONFIRMATION_CLOSE_NODE
		);
		// reset error queue
		$this->_queueError = array();
		return $this;
	}

	/**
	 * append all the error confirmation items into the feed file
	 * and reset the confirmation queue into an empty array
	 * @return self
	 */
	public function flush()
	{
		$this->append(implode("\n", $this->_queueConfirmation));
		$this->_queueConfirmation = array();
		return $this;
	}

	/**
	 * get error template
	 * @param string $type
	 * @param string $fileName
	 * @return array
	 */
	protected function _getErrorTemplate($type, $fileName)
	{
		return array(
			'feed_type' => $type,
			'feed_file_name' => $fileName,
			'from' => TrueAction_Eb2cCore_Helper_Feed::DEST_ID
		);
	}

	/**
	 * check if error messages queue has data
	 * @return bool true when message queue is not empty other false
	 */
	public function hasMessage()
	{
		return !empty($this->_queueMessage);
	}

	/**
	 * check if error error queue has data
	 * @return bool true when error queue is not empty other false
	 */
	public function hasError()
	{
		return !empty($this->_queueError);
	}

	/**
	 * check if error error confirmation queue has data
	 * @return bool true when error confirmation queue is not empty other false
	 */
	public function hasErrorConfirmation()
	{
		return !empty($this->_queueConfirmation);
	}

	/**
	 * listen for 'product_feed_complete_error_confirmation' event dispatch
	 * get the feed detail information and begin closing each error confirmation file with the closing xml root node
	 * send them via SFTP protocol and move local file to archive folder
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function process(Varien_Event_Observer $observer)
	{
		$feedDetails = $observer->getEvent()->getFeedDetails();
		foreach ($feedDetails as $detail) {
			$fileName = $detail['error_file'];
			$this->loadFile($fileName)
				->close()
				->transferFile($fileName)
				->archive($fileName);
		}
		return $this;
	}

	/**
	 * take a file and transfer it to eb2c server
	 * @param string $fileName
	 * @return self
	 */
	public function transferFile($fileName)
	{
		$remotePath = Mage::helper('eb2cproduct')
			->getConfigModel(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
			->errorFeedRemoteMailbox;
		try {
			Mage::helper('eb2ccore/feed')->sendFile($fileName, $remotePath);
		} catch(TrueAction_Eb2cCore_Exception_Feed_Transmissionfailure $e) {
			Mage::log(sprintf('Error Confirmation (%s)', $e->getMessage()), Zend_Log::ERR);
		}
		return $this;
	}

	/**
	 * move error file to the archive folder
	 * @param string $source
	 * @return self
	 */
	public function archive($source)
	{
		$destination = str_replace('outbound', 'archive', $source);
		Mage::helper('eb2ccore')->moveFile($source, $destination);
		return $this;
	}
}
