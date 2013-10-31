<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Content
	extends TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	public function __construct()
	{
		parent::__construct();
		$this->_extractors = array(
			Mage::getModel('eb2cproduct/feed_extractor_xpath', array($this->_extractMap)),
			Mage::getModel('eb2cproduct/feed_extractor_color', array(
				array('color' => 'ExtendedAttributes/ColorAttributes/Color'),
				array('code' => 'Code/text()')
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('product_links' => 'ProductLinks/ProductLink'),
				array(
					// Type of link relationship.
					'link_type' => './@link_type',
					// Operation to take with the product link. ("Add", "Delete")
					'operation_type' => './@operation_type',
					// Unique ID (SKU) for the linked product.
					'link_to_unique_id' => 'LinkToUniqueID/text()',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('category_links' => 'CategoryLinks/CategoryLink'),
				array(
					// if category is the default
					'default' => './@default', // (bool)
					// Used to link products across catalogs.
					'catalog_id' => './@catalog_id',
					// Operation to take with the category.
					'import_mode' => './@import_mode',
					// Unique ID (SKU) for the linked product.
					'name' => 'Name',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('title' => 'BaseAttributes/Title'),
				array(
					// Targeted store language
					'lang' => './@xml:lang',
					// Localized product title
					'title' => './text()',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_color', array(
				array('color' => 'ExtendedAttributes/ColorAttributes/Color'),
				array('code' => 'Code/text()')
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('long_description' => 'ExtendedAttributes/LongDescription'),
				array(
					// Targeted store language
					'lang' => './@xml:lang',
					// Localized product title
					'long_description' => '.',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('short_description' => 'ExtendedAttributes/ShortDescription'),
				array(
					// Targeted store language
					'lang' => './@xml:lang',
					// short description of the item.
					'short_description' => '.',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('search_keywords' => 'ExtendedAttributes/SearchKeywords'),
				array(
					// Targeted store language
					'lang' => './@xml:lang',
					// search keywords of the item.
					'search_keywords' => '.',
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('custom_attributes' => 'CustomAttributes/Attribute'),
				array(
					// Custom attribute name.
					'name' => './@name',
					// Operation to take with the attribute. ("Add", "Change", "Delete")
					'operation_type' => './@operation_type',
					// Operation to take with the product link. ("Add", "Delete")
					'lang' => './@xml:lang',
					// Unique ID (SKU) for the linked product.
					'value' => 'Value',
				)
			)),
		);
		$this->_baseXpath = '/ContentMaster/Content';
		$this->_feedLocalPath = $this->_config->contentFeedLocalPath;
		$this->_feedRemotePath = $this->_config->contentFeedRemoteReceivedPath;
		$this->_feedFilePattern = $this->_config->contentFeedFilePattern;
		$this->_feedEventType = $this->_config->contentFeedEventType;
	}

	protected $_extractMap = array(
		'gift_wrapping_available' => 'ExtendedAttributes/GiftWrap/text()', // bool
		'catalog_id' => './@catalog_id',
		'gsi_client_id' => './@gsi_client_id',
		'gsi_store_id' => './@gsi_store_id',
		'unique_id' => 'UniqueID/text()',
		'style_id' => 'StyleID/text()',
	);
}
