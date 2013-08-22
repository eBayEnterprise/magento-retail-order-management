<?php
$installer = $this;
$installer->startSetup();

Mage::log('starting install');
$defaultAttributes = Mage::getModel('eb2cproduct/attributes');
$installer->applyToAllSets($defaultAttributes);

$installer->endSetup();
