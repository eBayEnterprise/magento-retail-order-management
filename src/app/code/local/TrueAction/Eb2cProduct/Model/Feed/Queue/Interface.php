<?php
interface TrueAction_Eb2cProduct_Model_Feed_Queue_Interface {
	/**
	 * operation types.
	 * denote wether the unit will be used for adding, deleting or updating
	 * product data.
	 */
	const OPERATION_TYPE_ADD = 'ADD';
	const OPERATION_TYPE_UPDATE = 'UPDATE';
	const OPERATION_TYPE_REMOVE = 'REMOVE';

	/**
	 * add data to the processing queue
	 * @param Varien_Object $data   data to be processed
	 * @param string $operationType the operation to be performed with the unit; see interface for values.
	 * @param callback $proc        callable that will be used with $data as its argument.
	 */
	public function add($data, $operationType, $proc=null);

	/**
	 * queue an item up for deletion
	 * @param  mixed $data data to be processed
	 * @param  mixed $proc callable/callback to run on the data.
	 * @return self
	 */
	public function addUpsert($data, $proc);

	/**
	 * add data for a delete operation
	 * @param [type] $data [description]
	 * @param [type] $proc [description]
	 */
	public function addDelete($data, $proc);

	/**
	 * add a list of files to be processed and the process to handle all files.
	 * @param array $files file list to be processed
	 * @param mixed $proc  callback that will handle each file in $files
	 */
	public function addFiles($files, $proc);

	/**
	 * processes each item
	 * @return [type] [description]
	 */
	public function process();
}
