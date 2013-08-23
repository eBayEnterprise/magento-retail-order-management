<?php
$installer = $this;
$installer->startSetup();

$defaultAttributes = Mage::getModel('eb2cproduct/attributes');
$installer->applyToAllSets($defaultAttributes);

$installer->endSetup();
