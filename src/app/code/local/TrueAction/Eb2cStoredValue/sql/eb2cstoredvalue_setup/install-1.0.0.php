<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
// load id for customer entity
$read = Mage::getSingleton('core/resource')->getConnection('core_read');
$eid = $read->fetchRow(
	"select entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code = 'customer'"
);
$customerTypeId = $eid['entity_type_id'];

$attrPan = array(
	'type' => 'varchar',
	'input' => 'text',
	'label' => 'Account Pan Code',
	'global' => 1,
	'required' => 0,
	'default' => '',
	'position' => '100'
);

$attrPin = array(
	'type' => 'varchar',
	'input' => 'text',
	'label' => 'Account Pin',
	'global' => 1,
	'required' => 0,
	'default' => '',
	'position' => '100'
);

$attrDate = array(
	'type' => 'datetime',
	'input' => 'label',
	'label' => 'Account update date',
	'global' => 1,
	'required' => 0,
	'default' => '',
	'position' => '100'
);

$attrAction = array(
	'type' => 'varchar',
	'input' => 'text',
	'label' => 'Account Action',
	'global' => 1,
	'required' => 0,
	'default' => '',
	'position' => '100'
);

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$setup->addAttribute($customerTypeId, 'eb_sv_payment_account_pan', $attrPan);
$setup->addAttribute($customerTypeId, 'eb_sv_payment_account_pin', $attrPin);
$setup->addAttribute($customerTypeId, 'eb_sv_payment_account_action', $attrAction);

// Since 1.4.2.0 this is necessary!
$eavConfig = Mage::getSingleton('eav/config');

$attribute = $eavConfig->getAttribute($customerTypeId, 'eb_sv_payment_account_pan');
$attribute->setData('used_in_forms', array('customer_account_edit', 'customer_account_create', 'adminhtml_customer'));
$attribute->save();

$attribute = $eavConfig->getAttribute($customerTypeId, 'eb_sv_payment_account_pin');
$attribute->setData('used_in_forms', array('customer_account_edit', 'customer_account_create', 'adminhtml_customer'));
$attribute->save();

$attribute = $eavConfig->getAttribute($customerTypeId, 'eb_sv_payment_account_update');
$attribute->setData('used_in_forms', array('customer_account_edit', 'customer_account_create', 'adminhtml_customer'));
$attribute->save();

$attribute = $eavConfig->getAttribute($customerTypeId, 'eb_sv_payment_account_action');
$attribute->setData('used_in_forms', array('customer_account_edit', 'customer_account_create', 'adminhtml_customer'));
$attribute->save();

// Add new fields to quote_payment and order_payment table

$installer->getConnection()->addColumn(
	$installer->getTable('sales/quote_payment'),
	'storedvalue_pan',
	array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'lenght' => 255,
		'comment' => 'storedvalue pan Code'
	)
);

$installer->getConnection()->addColumn(
	$installer->getTable('sales/quote_payment'),
	'storedvalue_pin',
	array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'lenght' => 255,
		'comment' => 'storedvalue pin'
	)
);

$installer->getConnection()->addColumn(
	$installer->getTable('sales/quote_payment'),
	'storedvalue_action',
	array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'lenght' => 255,
		'comment' => 'storedvalue action Code'
	)
);

$installer->getConnection()->addColumn(
	$installer->getTable('sales/order_payment'),
	'storedvalue_pan',
	array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'lenght' => 255,
		'comment' => 'storedvalue pan Code'
	)
);

$installer->getConnection()->addColumn(
	$installer->getTable('sales/order_payment'),
	'storedvalue_pin',
	array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'lenght' => 255,
		'comment' => 'storedvalue pin'
	)
);

$installer->getConnection()->addColumn(
	$installer->getTable('sales/quote_payment'),
	'storedvalue_action',
	array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'lenght' => 255,
		'comment' => 'storedvalue action Code'
	)
);

// End setup
$installer->endSetup();
// EOF
