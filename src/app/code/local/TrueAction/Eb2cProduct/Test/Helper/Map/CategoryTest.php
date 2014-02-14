<?php
class TrueAction_Eb2cProduct_Test_Helper_MapTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Provide DOMNodeLists of CategoryLink/Name nodes and a collection of categories.
	 *
	 * @return array[]
	 */
	public function provideCategoryNameNodes()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML('
			<CategoryLinks>
				<CategoryLink import_mode="Update">
					<Name>Luma Root</Name>
				</CategoryLink>
				<CategoryLink import_mode="Update">
					<Name>Luma Root-Shoes</Name>
				</CategoryLink>
				<CategoryLink import_mode="Update">
					<Name>Luma Root-Shoes-Boots</Name>
				</CategoryLink>
				<CategoryLink import_mode="Update">
					<Name>Luma Root-Outerwear-Jackets</Name>
				</CategoryLink>
			</CategoryLinks>
		');
		$xp = new DOMXpath($doc);
		$nodes = $xp->query('/CategoryLinks/CategoryLink[@import_mode!="Delete"]/Name');
		$catCol = $this->getResourceModelMockBuilder('catalog/category_collection')
			->setMethods(array('addAttributeToSelect', 'getColumnValues', 'getAllIds'))
			->getMock();
		$catCol
			->expects($this->any())
			->method('addAttributeToSelect')
			->with($this->identicalTo(array('name', 'path', 'id')))
			->will($this->returnSelf());
		$ids = array(0, 1, 2, 30, 31);
		$names = array('Luma Root', 'Shoes', 'Boots', 'Outerwear', 'Jackets');
		$paths = array(
			'0', // Luma Root
			'0/1', // Luma Root-Shoes
			'0/1/2', // Luma Root-Shoes-Boots
			'0/30', // Luma Root-Outerwear
			'0/30/31', // Luma Root-Outerwear-Jackets
		);
		$catCol
			->expects($this->any())
			->method('getColumnValues')
			->will($this->returnValueMap(array(
				array('name', $names),
				array('path', $paths),
			)));
		$catCol
			->expects($this->any())
			->method('getAllIds')
			->will($this->returnValue($ids));
		return array(
			array($nodes, $catCol)
		);
	}
	/**
	 * Confirm that extractCategoryIds returns the expected ids for the given xml nodes.
	 *
	 * @test
	 * @dataProvider provideCategoryNameNodes
	 */
	public function testExtractCategoryIds(DOMNodeList $nodes, Mage_Catalog_Model_Resource_Category_Collection $catCol)
	{
		$this->replaceByMock('resource_model', 'catalog/category_collection', $catCol);
		$this->assertSame(
			array(0,1,2,31),
			Mage::helper('eb2cproduct/map_category')->extractCategoryIds($nodes)
		);
	}
}
