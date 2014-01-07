<?php
/**
 * Upgrade to add eb2c status time stamp to order
 */
Mage::log(sprintf('[ %s ] Installing Eb2cOrder 0.9.1', get_class($this)), Zend_Log::DEBUG);
$installer = $this;
$installer->startSetup();
try{
	$entities = array(
		'order',
	);

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
		array( 'name' => 'status_applied',   'options' => $typeTimestampOptions),
		array( 'name' => 'status_timestamp', 'options' => $typeTimestampOptions),
		array( 'name' => 'status_type',      'options' => $typeTextOptions),
	);

	$pfx = 'eb2c_order_';

	foreach ($entities as $entity) {
		foreach ($eb2cStatusAttributes as $a) {
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
