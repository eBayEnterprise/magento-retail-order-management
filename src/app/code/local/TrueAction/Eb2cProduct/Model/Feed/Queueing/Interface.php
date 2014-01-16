<?php
interface TrueAction_Eb2cProduct_Model_Feed_Queueing_Interface {
	/**
	 * operation types.
	 * denote wether the unit will be used for adding, deleting or updating
	 * product data.
	 */
	const OPERATION_TYPE_ADD = 'ADD';
	const OPERATION_TYPE_UPDATE = 'CHANGE';
	const OPERATION_TYPE_REMOVE = 'DELETE';

	/**
	 * add data to the processing queue
	 * @param Varien_Object $data   data to be processed
	 * @param string $operationType the operation to be performed with the unit; see interface for values.
	 */
	public function add($data, $operationType);

	/**
	 * processes each item
	 * @return [type] [description]
	 */
	public function process();
}
