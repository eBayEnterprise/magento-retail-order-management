<?php
require_once 'abstract.php';

/**
 * Eb2c Category Shell
 */
class TrueAction_Eb2c_Shell_Category extends Mage_Shell_Abstract
{
	private $_categoryObject;
	private $_attributeSetId;
	private $_defaultParentCategoryId;

	/**
	 * Instantiate the catalog/category
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_categoryObject = Mage::getModel('catalog/category');
		$this->_attributeSetId = $this->_getCategoryAttributeSetId();
		$this->_defaultParentCategoryId = $this->_getDefaultParentCategoryId();
	}

	/**
	 * getting category attribute set id.
	 * @return int, the category attribute set id
	 */
	protected function _getCategoryAttributeSetId()
	{
		return (int) Mage::getSingleton('eav/config')
			->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'attribute_set_id')
			->getEntityType()
			->getDefaultAttributeSetId();
	}

	/**
	 * load category by name
	 * @param string $categoryName, the category name to filter the category table
	 * @return Mage_Catalog_Model_Category
	 */
	protected function _loadCategoryByName($categoryName)
	{
		return Mage::getModel('catalog/category')
			->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('name', array('eq' => $categoryName))
			->load()
			->getFirstItem();
	}

	/**
	 * get parent default category id
	 * @return int, default parent category id
	 */
	protected function _getDefaultParentCategoryId()
	{
		return Mage::getModel('catalog/category')->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('parent_id', array('eq' => 0))
			->load()
			->getFirstItem()
			->getId();
	}

	/**
	 * add category to magento, check if already exist and return the category id
	 * @param string $categoryName, the category to either add or get category id from magento
	 * @param string $path, delimited string of the category depth path
	 * @return int, the category id
	 */
	protected function _addCategory($categoryName, $path)
	{
		$categoryId = 0;
		if (trim($categoryName) !== '') {
			// let's check if category already exists
			$this->_categoryObject = $this->_loadCategoryByName($categoryName);
			$categoryId = $this->_categoryObject->getId();
			if (!$categoryId) {
				// category doesn't currently exists let's add it.
				try {
					$this->_categoryObject->setAttributeSetId($this->_attributeSetId)
						->setStoreId(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
						->addData(array(
							'name' => $categoryName,
							'path' => $path, // parent relationship..
							'description' => $categoryName,
							'is_active' => 1,
							'is_anchor' => 0, //for layered navigation
							'page_layout' => 'default',
							'url_key' => Mage::helper('catalog/product_url')->format($categoryName), // URL to access this category
							'image' => null,
							'thumbnail' => null,
						))
						->save();

					$categoryId = $this->_categoryObject->getId();
				} catch (Mage_Core_Exception $e) {
					Mage::logException($e);
				} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
					Mage::log(
						sprintf(
							'[ %s ] The following error has occurred while adding categories (%d)',
							__CLASS__, $e->getMessage()
						),
						Zend_Log::DEBUG
					);
				}
			}
		}

		return $categoryId;
	}

	/**
	 * The 'main' of a Mage Shell Script
	 * @see usageHelp
	 */
	public function run()
	{
		if( !count($this->_args) ) {
			echo $this->usageHelp();
			return 0;
		}

		$errors = 0;
		if (isset($this->_args['categories'])) {
			$categories = explode('-', $this->_args['categories']);
			$path = $this->_defaultParentCategoryId;
			foreach ($categories as $category) {
				if (!is_numeric($category)) {
					$path .= '/' . $this->_addCategory(ucwords($category), $path);
					$this->_log(sprintf('Adding Category: %s', ucwords($category)));
				}
			}
		}

		$this->_log('Script Ended');
		return $errors;
	}

	/**
	 * Log a message
	 *
	 * @param string $message to log
	 * @param log level
	 */
	private function _log($message)
	{
		echo '[' . __CLASS__ . '] ' . basename(__FILE__) . ': ' . $message . "\n";
	}

	/**
	 * Return some help text
	 *
	 * @return string
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		$msg = <<<USAGE

Usage: php -f $scriptName -- [options]
  -categories     list_of_categories (Watch out for shell escapes)
  -validate  config (Ensures all categories configured are valid)
  help       This help

Configured and Enabled categories:

USAGE;
		return $msg . " Done!!!\n";
	}
}

$categoryProcessor = new TrueAction_Eb2c_Shell_category();
exit($categoryProcessor->run());
