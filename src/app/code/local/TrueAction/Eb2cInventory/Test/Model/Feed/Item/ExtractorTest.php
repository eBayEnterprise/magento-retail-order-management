<?php
class TrueAction_Eb2cInventory_Test_Model_Feed_Item_ExtractorTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * testing extractInventoryFeed method
	 * @test
	 */
	public function testExtractInventoryFeed()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<ItemInventories><Inventory operation_type="Change" gsi_client_id="66-000906034352" catalog_id="66" measurement="Level">
		<ItemId>
			<ClientItemId>000906034352</ClientItemId>
		</ItemId>
		<Measurements>
			<AvailableQuantity>99</AvailableQuantity>
			<BackorderQuantity>0</BackorderQuantity>
			<PendingQuantity>0</PendingQuantity>
			<DemandQuantity>1</DemandQuantity>
			<OnHandQuantity>100</OnHandQuantity>
		</Measurements>
	</Inventory></ItemInventories>');

		$this->assertCount(1, Mage::getModel('eb2cinventory/feed_item_extractor')->extractInventoryFeed($doc));
	}
}
