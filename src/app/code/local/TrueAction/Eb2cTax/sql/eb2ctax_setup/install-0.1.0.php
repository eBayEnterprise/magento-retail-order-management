<?php
Mage::log(sprintf('[ %s ] Installing Eb2cTax 0.1.0', get_class($this)), Zend_Log::DEBUG);

$conn = $this->getConnection();
$name = $this->getTable('eb2ctax/response_quote');

try {
	$table = $conn->newTable($name);
} catch (Exception $e) {
	Mage::log(sprintf('[ %s ] %s', get_class($this), $e->getMessage()), Zend_Log::CRIT);
}
$table
	->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned' => true,
		'identity' => true,
		'nullable' => false,
		'primary'  => true,
	), 'TaxQuote id')
	->addColumn('quote_item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Quote Item Id')
	->addColumn('type', Varien_Db_Ddl_Table::TYPE_INTEGER, 1, array(
		'unsigned' => true,
	), 'TaxQuote type')
	->addColumn('code', Varien_Db_Ddl_Table::TYPE_TEXT, 128, array(
		'nullable' => true,
	), 'Code')
	->addColumn('situs', Varien_Db_Ddl_Table::TYPE_TEXT, 128, array(
		'nullable' => true,
	), 'Situs')
	->addColumn('effective_rate', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(20, 6), array(), 'Effective Rate')
	->addColumn('taxable_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(20, 6), array(), 'Taxable Amount')
	->addColumn('calculated_tax', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(20, 6), array(), 'Calculated Tax')
	->addColumn('quote_address_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Quote Address Item Id');

$conn->createTable($table);
