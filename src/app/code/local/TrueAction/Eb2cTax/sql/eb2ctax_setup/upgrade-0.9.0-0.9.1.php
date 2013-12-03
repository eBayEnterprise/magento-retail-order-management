<?php
Mage::log(sprintf('[ %s ] Upgrade Eb2cTax 0.9.0', get_class($this)), Zend_Log::DEBUG);

$installer = $this;
$installer->startSetup();

$taxTable = $installer->getTable('eb2ctax/response_quote');
$installer->getConnection()
	->addColumn($taxTable, 'tax_type', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxType reported by the tax service',
	));
$installer->getConnection()
	->addColumn($taxTable, 'taxability', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'Taxability of the item',
	));
$installer->getConnection()
	->addColumn($taxTable, 'jurisdiction', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Jurisdiction',
	));
$installer->getConnection()
	->addColumn($taxTable, 'jurisdiction_id', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Jurisdiction Id',
	));
$installer->getConnection()
	->addColumn($taxTable, 'jurisdiction_level', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Jurisdiction Level',
	));
$installer->getConnection()
	->addColumn($taxTable, 'imposition', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Imposition',
	));
$installer->getConnection()
	->addColumn($taxTable, 'imposition_type', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Imposition type',
	));
$installer->endSetup();
