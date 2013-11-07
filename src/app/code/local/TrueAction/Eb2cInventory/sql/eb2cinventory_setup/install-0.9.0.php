<?php
/**
 * @category   TrueAction
 * @package    Eb2c_Inventory
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */

$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$installer->startSetup();
try{
	$entities = array(
		'order_item',
		'quote_address_item',
		'quote_item',
	);

	$inventoryAttributes = array(
		array(
			'name' => 'reservation_id',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'reservation_expires',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'qty_reserved',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'creation_time',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'display',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'delivery_window_from',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'delivery_window_to',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'shipping_window_from',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'shipping_window_to',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_line1',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_line2',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_line3',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_line4',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_city',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_main_division',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_country_code',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_postal_code',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
	);

	$pfx = 'eb2c_';

	foreach ($entities as $entity) {
		foreach ($inventoryAttributes as $a) {
			$installer->addAttribute(
				$entity,
				$pfx . $a['name'],
				$a['options']
			);
		}
	}
} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();
