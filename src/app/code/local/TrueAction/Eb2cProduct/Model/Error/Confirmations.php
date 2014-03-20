<?php
/**
 * The error confirmations class was implemented in such a way to adapt to the
 * way the super product feed is currently functioning
 * How to use the error confirmation class is as follow:
 *
 * Step 1: load the error confirmation file, which can be generated using
 *   external helper methods
 *   Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile('ItemMaster_20140107224605_12345_ABCD.xml');
 * Step 2: initialize primary data such as the xml directive, root node
 *   and message header
 *   Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::initFeed('ItemMaster');
 * Step 3: this step mean that within the integration where you have an
 *   error and you need add this error to an error confirmation feed file you
 *   should do as the example below demonstrate:
 *   Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::addMessage(
 *     self::DOM_LOAD_ERR,
 *     'Dom exception throw while processing feed file'
 *   );
 *   TrueAction_Eb2cProduct_Model_Error_Confirmations::addError(
 *     'ItemMaster',
 *     'ItemMaster_TestSubset.xml'
 *   );
 *   TrueAction_Eb2cProduct_Model_Error_Confirmations::addErrorConfirmation(
 *     'SK-ABC-1334'
 *   );
 *   TrueAction_Eb2cProduct_Model_Error_Confirmations::flush();
 *   Result:
 *   <ErrorConfirmation unique_id="SK-ABC-1334">
 *     <Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
 *       <Message xml:lang="en-US" code="">Error exception occurred: Dom exception throw while processing feed file</Message>
 *     </Error>
 *   </ErrorConfirmation>
 * Step 4: closing the file after successfully capturing all possible error
 *   Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::close();
 *
 * FYI: because of the way the super feed is setup, we have to dispatch an
 *   event and pass it all the product error_confirmation feed files that
 *   were created in this case the following step will need to happen to
 *   close all error confirmation file, send them to eb2c server and then move
 *   the files from outbound to archive
 * Step 5: dispatch 'product_feed_complete_error_confirmation' event
 *   Example: TrueAction_Eb2cProduct_Model_Error_Confirmations::process();
 * Summary: for each of the error confirmation feed files that has been process,
 *   close it with the root node, send the file to eb2c, and then move it to
 *   archive folder.
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

	const SKU_NOT_REMOVE = 'The feed process failed to remove this product of operation type "delete"';
	const SKU_NOT_IMPORTED = 'The feed process failed to import this product of operation type "add" or "change"';

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
	 * @param string $feedType
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
		$coreFeed = Mage::getModel(
			'eb2ccore/feed',
			array('feed_config' => Mage::helper('eb2ccore/feed')->getConfig()->errorFeed)
		);
		foreach ($feedDetails as $detail) {
			$fileName = $detail['error_file'];
			$this->loadFile($fileName)
				->close();
			$coreFeed->mvToLocalDirectory($fileName);
		}
		return $this;
	}

	/**
	 * listen for 'product_feed_process_operation_type_error_confirmation' event dispatch
	 * get the feed detail and an array of skus that were deleted get a collection of product by the skus
	 * if there's any product in the collection start adding error confirmation node to the error confirmation file
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function processByOperationType(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$detail = $event->getFeedDetail();
		$skus = $event->getSkus();
		$operationType = $event->getOperationType();
		$collection = $this->_getProductCollectionBySkus($skus);

		$fileName = basename($detail['local']);
		$errorFile = $detail['error_file'];
		$type = $detail['type'];

		$this->loadFile($errorFile);

		Mage::log(
			sprintf(
				'[%s] start error confirmation for %d %sed product(s) on file (%s).',
				__CLASS__, count($skus), $operationType, $fileName
			),
			Zend_Log::DEBUG
		);

		return ($operationType === 'delete')?
			$this->_addDeleteErrors($collection, $fileName, $type) :
			$this->_addImportErrors($collection, $skus, $fileName, $type);
	}

	/**
	 * given a Mage_Catalog_Model_Resource_Product_Collection object if there's any product
	 * add them to the error confirmation file
	 * @param Mage_Catalog_Model_Resource_Product_Collection $collection
	 * @param string $fileName the file the sku was found on
	 * @param string $type the event type
	 * @return self
	 */
	protected function _addDeleteErrors(
		Mage_Catalog_Model_Resource_Product_Collection $collection, $fileName, $type
	)
	{
		if ($collection->count()) {
			foreach ($collection as $product) {
				$this->_appendError(self::SKU_NOT_REMOVE, '', $type, $fileName, $product->getSku());
			}
		}
		return $this;
	}

	/**
	 * given a Mage_Catalog_Model_Resource_Product_Collection object and a list of skus
	 * if the skus in the list of sku is not in the collection then add error confirmation node
	 * to feed file about sku was not imported
	 * @param Mage_Catalog_Model_Resource_Product_Collection $collection
	 * @param array $skus list of skus that were suppose to be imported
	 * @param string $fileName the file the sku was found on
	 * @param string $type the event type
	 * @return self
	 */
	protected function _addImportErrors(
		Mage_Catalog_Model_Resource_Product_Collection $collection, array $skus, $fileName, $type
	)
	{
		foreach ($skus as $sku) {
			$product = $collection->getItemByColumnValue('sku', $sku);
			if (is_null($product)) {
				$this->_appendError(self::SKU_NOT_IMPORTED, '', $type, $fileName, $sku);
			}
		}
		return $this;
	}

	/**
	 * given message template, message, event type, file name and a sku call
	 * the neccessary method to add and error confirmation node with these data
	 * @param string $template the message template to use
	 * @param string $message the message to be mapped to the template
	 * @param string $type the event type
	 * @param string $fileName the file this error occurred on
	 * @param string $sku the sku this error is for
	 * @return self
	 */
	protected function _appendError($template, $message, $type, $fileName, $sku)
	{
		return $this->addMessage($template, $message)
			->addError($type, $fileName)
			->addErrorConfirmation($sku)
			->flush();
	}

	/**
	 * given a list of skus query the Mage_Catalog_Model_Resource_Product_Collection
	 * for all product in this list of skus
	 * @param array $skus the list of skus to filter the collection for
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected function _getProductCollectionBySkus(array $skus)
	{
		return Mage::getResourceModel('catalog/product_collection')
			->addFieldToFilter('sku', $skus)
			->addAttributeToSelect(array('sku'))
			->load();
	}
}
