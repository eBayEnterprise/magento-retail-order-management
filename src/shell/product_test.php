<?php

require_once 'abstract.php';

class TrueAction_Shell_Product_Test extends Mage_Shell_Abstract
{

	public function run()
	{
		$product = Mage::getModel('catalog/product')->load(1);

		$relatedIds = $product->getRelatedProductIds();
		$relatedLinksData = array();
		foreach ($relatedIds as $id) {
			$relatedLinksData[$id] = array();
		}
		$relatedLinksData[14] = array();
		$product->setRelatedLinkData($relatedLinksData);

		$product->save();
	}

}

$shell = new TrueAction_Shell_Product_Test();
$shell->run();