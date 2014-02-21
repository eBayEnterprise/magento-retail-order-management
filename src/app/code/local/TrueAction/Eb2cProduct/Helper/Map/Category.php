<?php
/**
 * EB2C represents category names in straightforward xml like the following:
 * <code>
 *   <CategoryLinks>
 *     <CategoryLink import_mode="Update">
 *       <Name>Luma Root</Name>
 *     </CategoryLink>
 *     <CategoryLink import_mode="Update">
 *       <Name>Luma Root-Shoes</Name>
 *     </CategoryLink>
 *     <CategoryLink import_mode="Update">
 *       <Name>Luma Root-Shoes-Boots</Name>
 *     </CategoryLink>
 *     <CategoryLink import_mode="Update">
 *       <Name>Luma Root-Outerwear-Jackets</Name>
 *     </CategoryLink>
 *   </CategoryLinks>
 * </code>
 *
 * In its current form, this Magento extension places a given product into
 * each category in a dash-delimited hierarchy of category names. In the
 * above example, the product should be in Luma Root, Luma Root/Shoes,
 * Luma Root/Shoes/Boots, and Luma Root/Outerwear/Jackets, but *not* in
 * Luma Root/Outerwear. The categories themselves are expected to already
 * exist in Magento, and the leftmost category name is always the root
 * category. The code is mostly about mapping the names to category id
 * paths.
 */
class TrueAction_Eb2cProduct_Helper_Map_Category extends Mage_Core_Helper_Abstract
{
	/**
	 * Memoized map of name paths to ids.
	 *
	 * @var array
	 */
	protected $_namePathToIdMap = array();
	/**
	 * Convert a list of CategoryLink Name nodes into an array of category
	 * ids the given product should be in.
	 *
	 * @param DOMNodeList $nodes
	 * @return array
	 */
	public function extractCategoryIds(DOMNodeList $nodes)
	{
		return array_filter(
			array_map(array($this, '_mapNamePathToId'), $this->_convertNodesToDashNames($nodes)),
			function($id) {
				// Strip elements where the path was not found.
				// @see _mapNamePathToId
				return $id !== -1;
			}
		);
	}
	/**
	 * Convert a list of nodes into an array of dash-delimited category names.
	 *
	 * @param DOMNodeList $nodes CategoryLink/Name nodes
	 * @return array
	 */
	protected function _convertNodesToDashNames(DOMNodeList $nodes)
	{
		$dashNames = array();
		foreach($nodes as $node) {
			$dashNames[] = 'Root Catalog-' . trim($node->nodeValue);
		}
		return array_unique($dashNames);
	}
	/**
	 * Map a dash-delimited path of category names to an individual category
	 * id.
	 *
	 * Magento categories already internally have a path attribute of
	 * category ids such as "0/1/4/5". Each part of the path is the id of an
	 * individual category. The rightmost id is the entity_id of the category
	 * with that path, and the leftmost id is the id of the root category.
	 *
	 * @see self::_namePathToIdMap
	 * @param string $namePath a dash-delimited path of category names
	 * @return string a slash-delimited path of category ids.
	 */
	protected function _mapNamePathToId($namePath)
	{
		if (empty($this->_namePathToIdMap)) {
			$catColl = Mage::getResourceModel('catalog/category_collection')
				->addAttributeToSelect(array('name', 'path', 'id'));
			// Process the ids in reverse order to get high id numbers first.
			$ids = array_reverse($catColl->getAllIds());
			$names = array_reverse($catColl->getColumnValues('name'));
			$this->_namePathToIdMap = array_combine(
				array_map(function($path) use ($ids, $names) {
					// replace each id in a path with the category name for that
					// category id, and replace slashes with dashes.
					return strtr(str_replace($ids, $names, $path), '/', '-');
				}, array_reverse($catColl->getColumnValues('path'))),
				$ids
			);
		}
		if (isset($this->_namePathToIdMap[$namePath])) {
			return $this->_namePathToIdMap[$namePath];
		} else {
			Mage::helper('trueaction_magelog')->logWarn('[ %s ] No category was found with path matching "%s".', array(__CLASS__, $namePath));
			return -1;
		}
	}
}

