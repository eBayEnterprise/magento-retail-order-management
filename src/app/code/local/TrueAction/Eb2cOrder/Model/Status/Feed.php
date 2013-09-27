<?php
/**
 * Order Status processing Class, gets Order Status feeds from remote
 */
class TrueAction_Eb2cOrder_Model_Status_Feed
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	private $_headerNodeNames = array('OrderStatusEventTimeStamp', 'StoreCode', 'OrderId', 'StatusId', 'ProcessTypeKey', 'StatusName');
	private $_detailNodeNames = array('OrderLineId', 'ItemId', 'Qty');

	private $_fileInfo;
	private $_event;

	private $_mageOrder;

	protected function _construct()
	{
		$this->setFeedConfig(Mage::helper('eb2corder')->getConfig());

		$this->setFeedRemotePath  ($this->getFeedConfig()->statusFeedRemotePath);
		$this->setFeedFilePattern ($this->getFeedConfig()->statusFeedFilePattern);
		$this->setFeedLocalPath   ($this->getFeedConfig()->statusFeedLocalPath);
		$this->setFeedEventType   ($this->getFeedConfig()->statusFeedEventType);

		parent::_construct();
	}

	/**
	 * Process DOM, logs info and errors
	 *
	 */
	public function processDom(TrueAction_Dom_Document $dom)
	{
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

		Mage::log( sprintf('[ %s ] %s, Processed %d of %d, %d errors',
			__CLASS__,
			$dom->documentURI,
			$this->_fileInfo['recordsProcessed'],
			$this->_fileInfo['recordCount'],
		$this->_fileInfo['recordsWithErrors']), Zend_Log::INFO );
	}

	/**
	 * Processes a single eb2c OrderStatusEvent
	 *
	 * @todo: if loadOrder fails, continue processing XML and do ... something.
	 * @param $eventNode - node containing a single event to process
	 * @return int Number of OrderEventDetails we looked at
	 */
	private function _processStatusEvent($eventNode)
	{
		$this->_event = array();

		foreach( $this->_headerNodeNames as $tag ) {
			$this->_event['Header'][$tag] = $eventNode->getElementsByTagName($tag)->item(0)->nodeValue;
		}

		$this->_mageOrder = $this->_loadOrder();
		$this->_fileInfo['recordsProcessed']++;
		$this->_fileInfo['recordsOk']++;

		$i = 0;
		foreach ($eventNode->getElementsByTagName('OrderEventDetail') as $eventDetailNode ) {
			foreach( $this->_detailNodeNames as $tag ) {
				$this->_event['Details'][$i][$tag] = $eventDetailNode->getElementsByTagName($tag)->item(0)->nodeValue;
			}
			$this->_loadOrderItem($i);
			$this->_fileInfo['recordsProcessed']++;
			$i++;
		}

		// The name of the function that knows how to process this event.
		$funcName = '_process' . $this->_camelize(strtolower($this->_event['Header']['ProcessTypeKey']));
		if (method_exists($this, $funcName) ) {
			$this->$funcName();
		} else {
			Mage::log( '[' . __CLASS__ . "] Error: $funcName undefined, Can't process " . print_r($this->_event['Header'], true), Zend_Log::ERR);
			$this->_fileInfo['recordsWithErrors']++;
		}
		$this->_event = null;
		return $i;
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
		return $this;
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
	 */
	private function _loadOrderItem($lineId)
	{
		$this->_event['Details'][$lineId]['mageOrderItem'] =
			Mage::getModel('sales/order_item')->load($this->_event['Details'][$lineId]['OrderLineId']);
		return $this;
	}

	/**
	 * Process an Order Fulfillment Event
	 * @todo Finish when ProcessKey 'ORDER_FULFILLMENT' spec'd
	 *  See: https://trueaction.atlassian.net/wiki/display/EBC/Orders#Orders-OrderStatusCodeMapping
	 * @return bool
	 */
	private function _processOrderFulfillment()
	{
		$this->_fileInfo['recordsOk']++;
		return true;
	}

	/**
	 * Return Order
	 * @todo Finish when ProcessKey 'Return Order' spec'd
	 *  See: https://trueaction.atlassian.net/wiki/display/EBC/Orders#Orders-OrderStatusCodeMapping
	 * @return bool
	 */
	private function _processReturnOrder()
	{
		$this->_fileInfo['recordsOk']++;
		return true;
	}

	/**
	 * Sales Order
	 * @todo Finish when ProcessKey 'Sales Order' spec'd
	 *   See: https://trueaction.atlassian.net/wiki/display/EBC/Orders#Orders-OrderStatusCodeMapping
	 * @return bool
	 */
	private function _processSalesOrder()
	{
		$this->_fileInfo['recordsOk']++;
		return true;
	}
}
