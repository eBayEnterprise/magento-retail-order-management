<?php
/**
 * Add fields to quote/ order for JSC 41st Parameter and associated orderContext fields
 */
$installer = $this;
$installer->startSetup();
try{
	$entities = array(
		'order',
		'quote',
	);

	$typeTextOptions = array (
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'visible' => true,
		'required' => false,
	);

	$fraudAttributes = array(
		array( 'name' => 'char_set',		'options' => $typeTextOptions),
		array( 'name' => 'content_types',	'options' => $typeTextOptions),
		array( 'name' => 'encoding',		'options' => $typeTextOptions),
		array( 'name' => 'host_name',		'options' => $typeTextOptions),
		array( 'name' => 'ip_address',		'options' => $typeTextOptions),
		array( 'name' => 'javascript_data',	'options' => $typeTextOptions),
		array( 'name' => 'language',		'options' => $typeTextOptions),
		array( 'name' => 'referrer',		'options' => $typeTextOptions),
		array( 'name' => 'session_id',		'options' => $typeTextOptions),
		array( 'name' => 'user_agent',		'options' => $typeTextOptions),
	);

	$pfx = 'eb2c_fraud_';

	foreach ($entities as $entity) {
		foreach ($fraudAttributes as $a) {
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
