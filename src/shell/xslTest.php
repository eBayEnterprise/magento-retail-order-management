<?php
require_once 'abstract.php';

/**
 * Eb2c XSL Test
 */
class TrueAction_Eb2c_Xslt_Test extends Mage_Shell_Abstract
{
	const DEFAULT_XSLT_SHEET = '/var/www/mage_2014_02_18/src/.modman/eb2c/src/app/code/local/TrueAction/Eb2cProduct/xslt/default-language-template.xsl';

	public function xslCallBack(DOMDocument $xslDoc, array $siteFilter)
	{
		$helper = Mage::helper('eb2cproduct');
		$helper->appendXslTemplateMatchNode($xslDoc, "/*/Item[(@catalog_id and @catalog_id!='{$siteFilter['catalog_id']}')]");
		$helper->appendXslTemplateMatchNode($xslDoc, sprintf("/*/Item[(@gsi_client_id and @gsi_client_id!='%s')]", $siteFilter['client_id']));
		$helper->appendXslTemplateMatchNode($xslDoc, sprintf("/*/Item[(@gsi_store_id and @gsi_store_id!='%s')]", $siteFilter['store_id']));

		$xslDoc->loadXML($xslDoc->saveXML());
		//echo $xslDoc->saveXML();
		return;
	}

	public function run()
	{
		$websites = Mage::helper('eb2cproduct')->loadWebsiteFilters();
		foreach($websites as $storeId=>$website) {
			echo "=========> PROCESSING: Store Id $storeId\n";
			print_r($website);
			$xmlDoc = Mage::helper('eb2ccore')->getNewDomDocument();
			$xmlDoc->load('/home/mwest/SampleData/eb2c-proof-and-subset-feeds/Data/CleanSubsetXml/ItemMaster_MultiSite.xml');
			$transformed = Mage::helper('eb2cproduct')
				->splitDomByXslt($xmlDoc, self::DEFAULT_XSLT_SHEET, 
					array('lang_code' => $website['lang_code']),
					array($this, 'xslCallBack'),
					$website);

			$transformed->save("/tmp/{$storeId}.xml" );
			echo "=========> END PROCESSING: $storeId\n";
		}
	}

	/**
	 * Return some help text
	 *
	 * @return string
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		return 
"Usage: php -f $scriptName -- [options]
  help This help\n";
	}
}

$tester = new TrueAction_Eb2c_Xslt_Test();
$tester->run();
