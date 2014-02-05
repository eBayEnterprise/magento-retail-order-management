<?php
/**
 * Upgrade to add eb2c status time stamp to order
 */
Mage::log('[ ' . __CLASS__ . ' ] Upgrading Eb2cOrder 0.9.0 -> 1.0.0.18', Zend_Log::INFO);
$installer = $this;
$installer->startSetup();
try{
	$typeTextOptions = array (
		'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
		'visible'  => true,
		'required' => false,
	);

	$typeTimestampOptions = array (
		'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
		'visible'  => true,
		'required' => false,
	);

	$eb2cStatusAttributes = array(
		array(
			'name' => 'status_applied',
			'options' => array(
				'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
				'visible'  => true,
				'required' => false,
				'comment'  => 'Timestamp when the status was applied, local Mage time',
			)
		),
		array(
			'name'    => 'status_timestamp',
			'options' => array(
				'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
				'visible'  => true,
				'required' => false,
				'comment'  => 'Timestamp from the remote system',
			)
		),
		array(
			'name'    => 'status_type',      
			'options' => array (
				'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible'  => true,
				'required' => false,
				'comment'  => 'Text value of status from the remote system',
			)
		)
	);

	$pfx = 'eb2c_order_';
	$table = $installer->getTable('sales/order');
	foreach ($eb2cStatusAttributes as $a) {
		$columnName = $pfx . $a['name'];
		$installer
			->getConnection()
			->addColumn($table, $columnName, $a['options']);
	}
} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();
