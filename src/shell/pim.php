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
		Mage::getModel('eb2cproduct/pim_collector')->runExport();
	}
}

$feedProcessor = new TrueAction_Eb2c_Shell_Pim();
exit($feedProcessor->run());
