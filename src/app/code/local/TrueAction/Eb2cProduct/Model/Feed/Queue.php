<?php
class TrueAction_Eb2cProduct_Model_Feed_Queue
	implements TrueAction_Eb2cProduct_Model_Feed_Queue_Interface
{

	/**
	 * add data to the processing queue
	 * @param Varien_Object $data   data to be processed
	 * @param string $operationType the operation to be performed with the unit; see interface for values.
	 * @param callback $proc        callable that will be used with $data as its argument.
	 */
	public function add($data, $operationType, $proc=null)
	{
		switch(strtoupper($operationType)) {
			case self::OPERATION_TYPE_ADD:
			case self::OPERATION_TYPE_UPDATE:
				$this->addUpsert($data, $proc);
				break;
			case self::OPERATION_TYPE_REMOVE:
				$this->addDelete($data, $proc);
				break;
			default:
				Mage::throwException(sprintf('invalid operation type [%s]', $operationType));
				// @codeCoverageIgnoreStart
				break;
		}
		//@codeCoverageIgnoreEnd
		return $this;
	}

	/**
	 * add data for a delete operation
	 * @param  mixed $data data to be processed
	 * @param  mixed $proc callable/callback to run on the data.
	 */
	public function addUpsert($data, $proc)
	{
	}

	/**
	 * add data for a delete operation
	 * @param array $data product data used to perform the delete
	 * @param  mixed $proc callable/callback to run on the data.
	 */
	public function addDelete($data, $proc)
	{
	}

	/**
	 * add a list of files to be processed and the process to handle all files.
	 * @param  mixed $data data to be processed
	 * @param  mixed $proc callable/callback to run on the data.
	 */
	public function addFiles($files, $proc)
	{
	}

	/**
	 * processes each item
	 * @return [type] [description]
	 */
	public function process()
	{
	}
}
