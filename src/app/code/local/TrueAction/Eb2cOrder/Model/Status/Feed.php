<?php
/**
 * Order Status processing Class, gets Order Status feeds from remote
 */
class TrueAction_Eb2cOrder_Model_Status_Feed extends Mage_Core_Model_Abstract
{
	private $_config;
	private $_coreFeedHelper;
	private $_localIo;
	private $_remoteIo;

	private $_fileInfo;
	private $_event;

	protected function _construct()
	{
		$helper = Mage::helper('eb2corder');
		$this->_config = $helper->getConfig();
		$this->_coreFeedHelper = $helper->getCoreFeedHelper();

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs = array(
			'base_dir' => $this->_config->statusFeedLocalPath
		);
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}
		$this->_localIo = Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);

		// Set up remote conduit:
		$this->_remoteIo = Mage::helper('filetransfer');
	}

	/**
	 * Fetch the remote files.
	 */
	private function _fetchFeedsFromRemote()
	{
		$this->_remoteIo->getFile(
			$this->_localIo->getInboundDir(),
			$this->_config->statusFeedRemotePath,
			$this->_config->fileTransferConfigPath
		);	// Gets the files. 
	}

	/**
	 * Loops through all files found in the Inbound Dir.
	 */
	public function processFeeds()
	{
		$this->_fetchFeedsFromRemote();
		foreach( $this->_localIo->lsInboundDir() as $xmlFeedFile ) {
			$this->processFile($xmlFeedFile);
		}
		return true;
	}

	/**
	 * Processes a single xml file.
	 */
	public function processFile($xmlFile)
	{
		// Load the XML:
		$dom = new TrueAction_Dom_Document();
		try {
			$dom->load($xmlFile);
		}
		catch(Exception $e) {
			Mage::logException($e);
			return false;
		}

		// Validate Eb2c Header Information:
		if (!$this->_coreFeedHelper->validateHeader($dom, $this->_config->statusFeedEventType, $this->_config->statusFeedHeaderVersion)) {
			Mage::log('File ' . $xmlFile . ': Invalid header', Zend_Log::ERR);
			return false;
		}

		// OrderStatusUpdate is the root nood of each Status Feed file:
		foreach( $dom->getElementsByTagName('OrderStatusUpdate') as $orderStatusUpdate) {
			// Load the attributes into the _fileInfo array, not yet well defined, but recordCount, for example, seems useful.
			$this->_loadFileInfo($orderStatusUpdate, array('fileType', 'fileStartTime', 'fileEndTime', 'recordCount'));
			// OrderStatusEvents wraps all of our OrderStatusEvents
			foreach( $dom->getElementsByTagName('OrderStatusEvents') as $eventSets ) {
				foreach ($eventSets->getElementsByTagName('OrderStatusEvent') as $eventNode ) {
					$this->_processOneEvent($eventNode);
				}
			}
		}

		Mage::log('File ' . $xmlFile 
			. sprintf(': Processed %d of %d, %d errors',
			$this->_fileInfo['recordsProcessed'],
			$this->_fileInfo['recordCount'],
		$this->_fileInfo['recordsWithErrors']) );

		if( $this->_fileInfo['recordsWithErrors'] ) {
			$this->_localIo->mvToErrorDir($xmlFile);
		}
		else {
			$this->_localIo->mvToArchiveDir($xmlFile);
		}
		return true;
	}


	/**
	 * Processes a single eb2c event
	 *
	 * @param $eventNode - node containing a single event to process
	 */
	private function _processOneEvent($eventNode)
	{
		$this->_event = array();	// The Plan: flatten the event into an array.

		$this->_loadNodes( 
			$eventNode,
			array(
				'OrderStatusEventTimeStamp',
				'StoreCode',
				'OrderId',
				'StatusId',
				'ProcessTypeKey',
				'StatusName',
			)
		);

		$this->_loadNodes(
			$eventNode->getElementsByTagName('OrderEventDetail')->item(0),
			array(
				'OrderLineId',
				'ItemId',
				'Qty',
			)
		);
		$this->_runEventProcessor();			// The name of the method to process an event is based on ProcessTypeKey
		$this->_fileInfo['recordsProcessed']++;
	}

	/**
	 * Adds values to the event array
	 *
	 * @param node pointing at some XML parent for which we which to parse the children 
	 * @tagSet array of tag names from which to get a value
	 *
	 */
	private function _loadNodes($node, $tagSet)
	{
		foreach( $tagSet as $tag ) {
			$this->_event[$tag] = $node->getElementsByTagName($tag)->item(0)->nodeValue;
		}
	}

	/**
	 * Get name of the function used to process this particular event. 
	 *
	 * @return string name of method to use to process this event
	 */
	private function _runEventProcessor()
	{
		$funcName = '_process' . str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($this->_event['ProcessTypeKey']))));
		if (method_exists($this, $funcName) ) {
			return $this->$funcName();
		}
		else {
			Mage::log('Error: ' . $funcName . ' is undefined, unprocessed record: ', print_r($this->_event, true));
			$this->_fileInfo['recordsWithErrors']++;
			return false;
		}
	}


	/**
	 * Load File information, taken from the root node's attributes
	 * 
	 * @param node pointing at root node of the OrderStatus document
	 * @attrSet array of attributes from which to get a value
	 *
	 */
	private function _loadFileInfo($node, $attrSet)
	{
		$this->_fileInfo = array();
		foreach($attrSet as $attr ) {
			$this->_fileInfo[$attr] = $node->getAttribute($attr);
		}
		$this->_fileInfo['recordsProcessed'] = 0;
		$this->_fileInfo['recordsOk'] = 0;
		$this->_fileInfo['recordsWithErrors'] = 0;
	}

	/**
	 * Process an Order Fulfillment Event
	 *
	 * @return bool  
	 */
	private function _processOrderFulfillment()
	{
		$this->_fileInfo['recordsOk']++;		// I assume ... ?
		return true;
	}
}
