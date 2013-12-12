<?php
class TrueAction_Eb2cProduct_Model_Feed_Queue
	implements TrueAction_Eb2cProduct_Model_Feed_Queueing_Interface
{
	/**
	 * model that processes the feeds
	 * @var TrueAction_Eb2cProduct_Model_Feed_Processor
	 */
	private $_processor;

	protected $_upsertList;

	protected $_deletionList;

	protected $_maxEntries = 100;

	protected $_maxTotalEntries = 150;

	public function __construct()
	{
		$this->_upsertList = new ArrayObject();
		$this->_deletionList = new ArrayObject();
		$this->_processor = Mage::getModel('eb2cproduct/feed_processor');
		$config = Mage::helper('eb2cproduct')->getConfigModel();
	}

	/**
	 * add data to the processing queue
	 * @param Varien_Object $data   data to be processed
	 * @param string $operationType the operation to be performed with the unit; see interface for values.
	 */
	public function add($data, $operationType)
	{
		switch(strtoupper($operationType)) {
			case self::OPERATION_TYPE_ADD:
			case self::OPERATION_TYPE_UPDATE:
				$this->_upsertList->append($data);
				break;
			case self::OPERATION_TYPE_REMOVE:
				$this->_deletionList->append($data);
				break;
			default:
				throw new TrueAction_Eb2cProduct_Model_Feed_Exception(sprintf('invalid operation type [%s]', $operationType));
				break;
		}
		if ($this->_isAtEntryLimit()) {
			$this->process();
		}
		return $this;
	}

	/**
	 * process the items in the queue
	 */
	public function process()
	{
		$this->_processor->processDeletions($this->_deletionList->getIterator());
		$this->_deletionList->exchangeArray(array());

		$this->_processor->processUpdates($this->_upsertList->getIterator());
		$this->_upsertList->exchangeArray(array());
	}

	/**
	 * @return boolean true if the amount of entries queued meets a limit
	 */
	protected function _isAtEntryLimit()
	{
		return $this->_deletionList->count() >= $this->_maxEntries ||
			$this->_upsertList->count() >= $this->_maxEntries ||
			($this->_deletionList->count() + $this->_upsertList->count()) >= $this->_maxTotalEntries;
	}
}
