<?php
/**
 * Order Status processing Class, gets Order Status feeds from remote
 */
class TrueAction_Eb2cOrder_Model_Status_Feed extends Mage_Core_Model_Abstract
{
	private $_config;
	private $_helper;
	private $_localIo;
	private $_remoteIo;

	private $_fileInfo;
	private $_event;

	private $_mageOrder;
	private $_mageOrderItem;


	protected function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();

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
		if (!$this->_helper->getCoreFeedHelper()->validateHeader($dom, $this->_config->statusFeedEventType, $this->_config->statusFeedHeaderVersion)) {
			Mage::log('File ' . $xmlFile . ': Invalid header', Zend_Log::ERR);
			return false;
		}

		// OrderStatusUpdate is the root node of each Status Feed file:
		foreach( $dom->getElementsByTagName('OrderStatusUpdate') as $orderStatusUpdate) {
			// Load the attributes into the _fileInfo array, not yet well defined, but recordCount, for example, seems useful.
			$this->_loadFileInfo($orderStatusUpdate, array('fileType', 'fileStartTime', 'fileEndTime', 'recordCount'));
			// OrderStatusEvents wraps all of our OrderStatusEvents
			foreach( $dom->getElementsByTagName('OrderStatusEvents') as $eventSets ) {
				foreach ($eventSets->getElementsByTagName('OrderStatusEvent') as $eventNode ) {
					$this->_processStatusEvent($eventNode);
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
	 * Processes a single eb2c OrderStatusEvent
	 *
	 * @param $eventNode - node containing a single event to process
	 */
	private function _processStatusEvent($eventNode)
	{
		$this->_event = array();

		foreach( array(
				'OrderStatusEventTimeStamp',
				'StoreCode',
				'OrderId',
				'StatusId',
				'ProcessTypeKey',
				'StatusName',) as $tag )
		{
			$this->_event['Header'][$tag] = $eventNode->getElementsByTagName($tag)->item(0)->nodeValue;
		}
		$this->_fileInfo['recordsProcessed']++;

		// The name of the function that knows how to process this event. 
		$funcName = '_process' 
			. str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($this->_event['Header']['ProcessTypeKey']))));

		$this->_mageOrder = $this->_loadOrder();
		// TODO: I have to trudge on regardless of whether I find the order, I need a test here
		$this->_fileInfo['recordsProcessed']++; // I found the record
		$this->_fileInfo['recordsOk']++;  // TODO: Is there some case, if found, that it's not OK? Not sure.

		$i=0;
		foreach ($eventNode->getElementsByTagName('OrderEventDetail') as $eventDetailNode ) {
			foreach( array(
					'OrderLineId',
					'ItemId',
					'Qty') as $tag )
			{
				$this->_event['Details'][$i][$tag] = $eventDetailNode->getElementsByTagName($tag)->item(0)->nodeValue;
			}
			$this->_mageOrderItem = $this->_loadOrderItem($i);
			if (method_exists($this, $funcName) ) {
				$this->$funcName();
			}
			else {
				Mage::log('Error: ' . $funcName . ' is undefined, unprocessed record: ', print_r($this->_event['Details'][$i], true));
				$this->_fileInfo['recordsWithErrors']++;
			}
			$i++;
			$this->_fileInfo['recordsProcessed']++;
		}
		$this->_event = null;
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
	 * Get the Magento order
	 * 
	 * @return Mage_Sales_Model_Order
	 */
	private function _loadOrder()
	{
		return Mage::getModel('sales/order')->loadByIncrementId($this->_event['Header']['OrderId']);
	}

	/**
	 * Get a single magento order item
	 * 
	 * @param which array element to get the item for.
	 * @return Mage_Sales_Model_Order_Item
	 */
	private function _loadOrderItem($lineId)
	{
		return Mage::getModel('sales/order_item')->load($this->_event['Details'][$lineId]['OrderLineId']);
	}

	/**
	 * Process an Order Fulfillment Event
	 *
	 * @return bool  
	 */
	private function _processOrderFulfillment()
	{
		$this->_fileInfo['recordsOk']++;
		return true;
	}


	/**
	 * Return Order
	 *
	 * @return bool
	 */
	private function _processReturnOrder()
	{
		$this->_fileInfo['recordsOk']++;
		return true;
	}


	/**
	 * Sales Order
	 *
	 * @return bool
	 */
	private function _processSalesOrder()
	{
		$this->_fileInfo['recordsOk']++;
		return true;
	}
}
