<?php
require_once 'abstract.php';

/**
 * Eb2c Feed Shell
 */
class TrueAction_Eb2c_Shell_Pim extends Mage_Shell_Abstract
{
	/**
	 * The 'main' of a Mage Shell Script
	 *
	 * @see usageHelp
	 */
	public function run()
	{
		$a = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect('entity_id');
		$pim = Mage::getModel('eb2cproduct/pim');
		$pim->buildFeed($a->getColumnValues('entity_id'));
	}
}

$feedProcessor = new TrueAction_Eb2c_Shell_Pim();
exit($feedProcessor->run());
