<?php

$installer = new Enterprise_GiftCardAccount_Model_Mysql4_Setup('core_setup');
$installer->startSetup();
try{
	// enterprise_giftcardaccount
	$tableName = $this->getTable('enterprise_giftcardaccount');
	$conn = $installer->getConnection();

	$conn->addColumn($tableName, 'eb2c_pan', 'varchar(255) DEFAULT NULL');
	$conn->addColumn($tableName, 'eb2c_pin', 'varchar(255) DEFAULT NULL');

	$conn->addKey($tableName, 'IDX_eb2c_pan', 'eb2c_pan');
	$conn->addKey($tableName, 'IDX_eb2c_pin', 'eb2c_pin');

} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();

$installer = new Mage_Core_Model_Resource_Setup('core_setup');
$installer->startSetup();
try{
	/**
	 * Create table 'eb2cpayment/paypal'
	 */
	$table = $installer->getConnection()
		->newTable($installer->getTable('eb2cpayment/paypal'))
		->addColumn('paypal_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
			'identity'  => true,
			'unsigned'  => true,
			'nullable'  => false,
			'primary'   => true,
		), 'paypal Id')
		->addColumn('quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
			'unsigned'  => true,
			'nullable'  => false,
			'default'   => '0',
		), 'Quote Id')
		->addColumn('eb2c_paypal_token', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c paypal token')
		->addColumn('eb2c_paypal_transaction_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c paypal transaction id')
		->addColumn('eb2c_paypal_payer_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c paypal payer id')
		->addIndex($installer->getIdxName('eb2cpayment/paypal', array('quote_id')),
		array('quote_id'));
	$installer->getConnection()->createTable($table);

} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();