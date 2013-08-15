<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelResourceProduct()
	{
		$catalogModelResourceProductMock = $this->getMock(
			'Mage_Catalog_Model_Resource_Product',
			array('_getDefaultAttributes')
		);
		$catalogModelResourceProductMock->expects($this->any())
			->method('_getDefaultAttributes')
			->will($this->returnValue(array()));

		return $catalogModelResourceProductMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Factory class
	 *
	 * @return Mock_Mage_Catalog_Model_Factory
	 */
	public function buildCatalogModelFactory()
	{
		$catalogModelFactoryMock = $this->getMock(
			'Mage_Catalog_Model_Factory',
			array('getCategoryUrlRewriteHelper')
		);
		$catalogModelFactoryMock->expects($this->any())
			->method('getCategoryUrlRewriteHelper')
			->will($this->returnValue('re-write-category-url'));

		return $catalogModelFactoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product_Type_Abstract class
	 *
	 * @return Mock_Mage_Catalog_Model_Product_Type_Abstract
	 */
	public function buildCatalogModelProductTypeAbstract()
	{
		$catalogModelProductTypeAbstractMock = $this->getMock(
			'Mage_Catalog_Model_Product_Type_Abstract',
			array()
		);

		return $catalogModelProductTypeAbstractMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product_Link class
	 *
	 * @return Mock_Mage_Catalog_Model_Product_Link
	 */
	public function buildCatalogModelProductLink()
	{
		$catalogModelProductLinkMock = $this->getMock(
			'Mage_Catalog_Model_Product_Link',
			array()
		);

		return $catalogModelProductLinkMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Category class
	 *
	 * @return Mock_Mage_Catalog_Model_Category
	 */
	public function buildCatalogModelCategory()
	{
		$catalogModelCategoryMock = $this->getMock(
			'Mage_Catalog_Model_Category',
			array()
		);

		return $catalogModelCategoryMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product_Type_Price class
	 *
	 * @return Mock_Mage_Catalog_Model_Product_Type_Price
	 */
	public function buildCatalogModelProductTypePrice()
	{
		$catalogModelProductTypePriceMock = $this->getMock(
			'Mage_Catalog_Model_Product_Type_Price',
			array()
		);

		return $catalogModelProductTypePriceMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection class
	 *
	 * @return Mock_Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
	 */
	public function buildCatalogModelResourceEavMysql4ProductLinkCollection()
	{
		$catalogModelResourceEavMysql4ProductLinkCollectionMock = $this->getMock(
			'Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection',
			array()
		);

		return $catalogModelResourceEavMysql4ProductLinkCollectionMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Resource_Product_Collection class
	 *
	 * @return Mock_Mage_Catalog_Model_Resource_Product_Collection
	 */
	public function buildCatalogModelResourceProductCollection()
	{
		$catalogModelResourceProductCollectionMock = $this->getMock(
			'Mage_Catalog_Model_Resource_Product_Collection',
			array(
				'__construct', 'getTable', 'getResource', 'getCatalogPreparedSelect', '_preparePriceExpressionParameters', 'getPriceExpression',
				'getAdditionalPriceExpression', 'getCurrencyRate', 'getFlatHelper'
			)
		);

		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('__construct')
			->will($this->returnSelf());
		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('getTable')
			->will($this->returnSelf());
		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('getResource')
			->will($this->returnSelf());
		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('getCatalogPreparedSelect')
			->will($this->returnSelf());
		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('_preparePriceExpressionParameters')
			->will($this->returnSelf());
		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('getPriceExpression')
			->will($this->returnSelf());
		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('getAdditionalPriceExpression')
			->will($this->returnSelf());
		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('getCurrencyRate')
			->will($this->returnSelf());
		$catalogModelResourceProductCollectionMock->expects($this->any())
			->method('getFlatHelper')
			->will($this->returnSelf());

		return $catalogModelResourceProductCollectionMock;
	}


	/**
	 * return a mock of the Mage_Catalog_Model_Product_Media_Config class
	 *
	 * @return Mock_Mage_Catalog_Model_Product_Media_Config
	 */
	public function buildCatalogModelProductMediaConfig()
	{
		$catalogModelProductMediaConfigMock = $this->getMock(
			'Mage_Catalog_Model_Product_Media_Config',
			array()
		);

		return $catalogModelProductMediaConfigMock;
	}

	/**
	 * return a mock of the Mage_Catalog_Model_Product class
	 *
	 * @return Mock_Mage_Catalog_Model_Product
	 */
	public function buildCatalogModelProduct()
	{
		$catalogModelProductMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array(
				'_initOldFieldsMap', 'getStoreId', 'getResourceCollection', 'getUrlModel', 'validate', 'getName',
				'getPrice', 'setPriceCalculation', 'getTypeId', 'setTypeId', 'getStatus', 'setStatus', 'getTypeInstance',
				'setTypeInstance', 'getLinkInstance', 'getIdBySku', 'getCategoryId', 'getCategory',
				'setCategoryIds', 'getCategoryIds', 'getCategoryCollection', 'getWebsiteIds',
				'getStoreIds', 'getAttributes', 'canAffectOptions', 'cleanCache', 'getPriceModel',
				'getGroupPrice', 'getTierPrice', 'getTierPriceCount', 'getFormatedTierPrice',
				'getFormatedPrice', 'setFinalPrice', 'getFinalPrice', 'getCalculatedFinalPrice',
				'getMinimalPrice', 'getSpecialPrice', 'getSpecialFromDate', 'getSpecialToDate',
				'getRelatedProducts', 'getRelatedProductIds', 'getRelatedProductCollection',
				'getRelatedLinkCollection', 'getUpSellProducts', 'getUpSellProductIds', 'getUpSellProductCollection',
				'getUpSellLinkCollection', 'getCrossSellProducts', 'getCrossSellProductIds',
				'getCrossSellProductCollection', 'getCrossSellLinkCollection', 'getGroupedLinkCollection', 'getMediaAttributes',
				'getMediaGalleryImages', 'addImageToMediaGallery', 'getMediaConfig', 'duplicate', 'isSuperGroup',
				'isSuperConfig', 'isGrouped', 'isConfigurable', 'isSuper', 'getVisibleInCatalogStatuses',
				'getVisibleStatuses', 'isVisibleInCatalog', 'getVisibleInSiteVisibilities', 'isVisibleInSiteVisibility',
				'isDuplicable', 'setIsDuplicable', 'isSalable', 'isAvailable', 'getIsSalable',
				'isVirtual', 'isRecurring', 'isSaleable', 'isInStock', 'getAttributeText', 'getCustomDesignDate',
				'getProductUrl', 'getUrlInStore', 'formatUrlKey', 'getUrlPath', 'addAttributeUpdate',
				'toArray', 'fromArray', 'loadParentProductIds', 'delete', 'getRequestPath', 'getGiftMessageAvailable',
				'getRatingSummary', 'isComposite', 'canConfigure', 'getSku', 'getWeight', 'getOptionInstance',
				'getProductOptionsCollection', 'addOption', 'getOptionById', 'getOptions', 'getIsVirtual', 'addCustomOption',
				'setCustomOptions', 'getCustomOptions', 'getCustomOption', 'hasCustomOptions',
				'canBeShowInCategory', 'getAvailableInCategories', 'getDefaultAttributeSetId', 'getImageUrl', 'getSmallImageUrl',
				'getThumbnailUrl', 'getReservedAttributes', 'isReservedAttribute', 'setOrigData',
				'reset', 'getCacheIdTags', 'isProductsHasSku', 'processBuyRequest', 'getPreconfiguredValues',
				'prepareCustomOptions', 'getProductEntitiesInfo', 'isDisabled', 'lockAttribute', 'unlockAttribute',
				'unlockAttributes', 'getLockedAttributes', 'hasLockedAttributes', 'isLockedAttribute', 'setData',
				'unsetData', 'loadByAttribute', 'getStore', 'getWebsiteStoreIds', 'setAttributeDefaultValue',
				'getAttributeDefaultValue', 'setExistsStoreValueFlag', 'getExistsStoreValueFlag', 'isDeleteable',
				'setIsDeleteable', 'isReadonly', 'setIsReadonly', 'getIdFieldName', 'getId',
				'setId', 'getResourceName', 'getCollection', 'load', 'afterLoad', 'save', 'afterCommitCallback',
				'isObjectNew', 'getCacheTags', 'cleanModelCache', 'getResource', 'getEntityId', 'clearInstance',
				'__construct', 'isDeleted', 'hasDataChanges', 'setIdFieldName', 'addData', 'unsetOldData', 'getData',
				'setDataUsingMethod', 'getDataUsingMethod', 'getDataSetDefault', 'hasData', '__toArray',
				'toXml', 'toJson', 'toString', '__call', '__get', '__set', 'isEmpty', 'serialize', 'getOrigData',
				'dataHasChangedFor', 'setDataChanges', 'debug', 'offsetSet', 'offsetExists',
				'offsetUnset', 'offsetGet', 'isDirty', 'flagDirty', 'setWeight', 'setVisibility', 'setAttributeSetId',
				'setShortDescription'
			)
		);
		$catalogModelProductMock->expects($this->any())
			->method('_initOldFieldsMap')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getStoreId')
			->will($this->returnValue(0));
		$catalogModelProductMock->expects($this->any())
			->method('getResourceCollection')
			->will($this->returnValue(array($this->buildCatalogModelResourceProduct())));
		$catalogModelProductMock->expects($this->any())
			->method('getUrlModel')
			->will($this->returnValue($this->buildCatalogModelFactory()));
		$catalogModelProductMock->expects($this->any())
			->method('validate')
			->will($this->returnValue(true));
		$catalogModelProductMock->expects($this->any())
			->method('getName')
			->will($this->returnValue('Product A'));
		$catalogModelProductMock->expects($this->any())
			->method('getPrice')
			->will($this->returnValue(99.99));
		$catalogModelProductMock->expects($this->any())
			->method('setPriceCalculation')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue('simple'));
		$catalogModelProductMock->expects($this->any())
			->method('setTypeId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getStatus')
			->will($this->returnValue(1));
		$catalogModelProductMock->expects($this->any())
			->method('setStatus')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getTypeInstance')
			->will($this->returnValue($this->buildCatalogModelProductTypeAbstract()));
		$catalogModelProductMock->expects($this->any())
			->method('setTypeInstance')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getLinkInstance')
			->will($this->returnValue($this->buildCatalogModelProductLink()));
		$catalogModelProductMock->expects($this->any())
			->method('getIdBySku')
			->will($this->returnValue(1));
		$catalogModelProductMock->expects($this->any())
			->method('getCategoryId')
			->will($this->returnValue(1));
		$catalogModelProductMock->expects($this->any())
			->method('getCategory')
			->will($this->returnValue($this->buildCatalogModelCategory()));
		$catalogModelProductMock->expects($this->any())
			->method('setCategoryIds')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getCategoryIds')
			->will($this->returnValue(array(1, 2, 3, 4)));
		$catalogModelProductMock->expects($this->any())
			->method('getCategoryCollection')
			->will($this->returnValue(new Varien_Data_Collection()));
		$catalogModelProductMock->expects($this->any())
			->method('getWebsiteIds')
			->will($this->returnValue(array(1, 2, 3, 4)));
		$catalogModelProductMock->expects($this->any())
			->method('getStoreIds')
			->will($this->returnValue(array(1, 2, 3, 4)));
		$catalogModelProductMock->expects($this->any())
			->method('getAttributes')
			->will($this->returnValue(array(1, 2, 3, 4)));
		$catalogModelProductMock->expects($this->any())
			->method('canAffectOptions')
			->will($this->returnValue(true));
		$catalogModelProductMock->expects($this->any())
			->method('cleanCache')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getPriceModel')
			->will($this->returnValue($this->buildCatalogModelProductTypePrice()));
		$catalogModelProductMock->expects($this->any())
			->method('getGroupPrice')
			->will($this->returnValue(99.99));
		$catalogModelProductMock->expects($this->any())
			->method('getTierPrice')
			->will($this->returnValue(99.99));
		$catalogModelProductMock->expects($this->any())
			->method('getTierPriceCount')
			->will($this->returnValue(1));
		$catalogModelProductMock->expects($this->any())
			->method('getFormatedTierPrice')
			->will($this->returnValue(array('$99.99')));



		$catalogModelProductMock->expects($this->any())
			->method('getFormatedPrice')
			->will($this->returnValue(array('$99.99')));
		$catalogModelProductMock->expects($this->any())
			->method('setFinalPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getFinalPrice')
			->will($this->returnValue(99.99));
		$catalogModelProductMock->expects($this->any())
			->method('getCalculatedFinalPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getMinimalPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getSpecialPrice')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getSpecialFromDate')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getSpecialToDate')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getRelatedProducts')
			->will($this->returnValue(array(1, 2, 3)));
		$catalogModelProductMock->expects($this->any())
			->method('getRelatedProductIds')
			->will($this->returnValue(array(1, 2, 3)));
		$catalogModelProductMock->expects($this->any())
			->method('getRelatedProductCollection')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getRelatedLinkCollection')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getUpSellProducts')
			->will($this->returnValue(array(1, 2, 3)));
		$catalogModelProductMock->expects($this->any())
			->method('getUpSellProductIds')
			->will($this->returnValue(1, 2, 3));
		$catalogModelProductMock->expects($this->any())
			->method('getUpSellProductCollection')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getUpSellLinkCollection')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getCrossSellProducts')
			->will($this->returnValue(array(1, 2, 3)));
		$catalogModelProductMock->expects($this->any())
			->method('getCrossSellProductIds')
			->will($this->returnValue(array(1, 2, 3)));
		$catalogModelProductMock->expects($this->any())
			->method('getCrossSellProductCollection')
			->will($this->returnValue($this->buildCatalogModelResourceEavMysql4ProductLinkCollection()));
		$catalogModelProductMock->expects($this->any())
			->method('getCrossSellLinkCollection')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getGroupedLinkCollection')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getMediaAttributes')
			->will($this->returnValue(array()));
		$catalogModelProductMock->expects($this->any())
			->method('getMediaGalleryImages')
			->will($this->returnValue(new Varien_Data_Collection()));
		$catalogModelProductMock->expects($this->any())
			->method('addImageToMediaGallery')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getMediaConfig')
			->will($this->returnValue($this->buildCatalogModelProductMediaConfig()));
		$catalogModelProductMock->expects($this->any())
			->method('duplicate')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isSuperGroup')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isSuperConfig')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isGrouped')
			->will($this->returnValue(true));
		$catalogModelProductMock->expects($this->any())
			->method('isConfigurable')
			->will($this->returnValue(true));



		// TODO: finish mock
		$catalogModelProductMock->expects($this->any())
			->method('isSuper')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getVisibleInCatalogStatuses')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getVisibleStatuses')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isVisibleInCatalog')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getVisibleInSiteVisibilities')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isVisibleInSiteVisibility')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isDuplicable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsDuplicable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isSalable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getIsSalable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isVirtual')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isRecurring')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isSaleable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isInStock')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getAttributeText')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getCustomDesignDate')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getProductUrl')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getUrlInStore')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('formatUrlKey')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getUrlPath')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addAttributeUpdate')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('toArray')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('fromArray')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('loadParentProductIds')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('delete')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getRequestPath')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getGiftMessageAvailable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getRatingSummary')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isComposite')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('canConfigure')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getWeight')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getOptionInstance')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getProductOptionsCollection')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addOption')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getOptionById')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getOptions')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getIsVirtual')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addCustomOption')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCustomOptions')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getCustomOptions')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getCustomOption')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('hasCustomOptions')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('canBeShowInCategory')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getAvailableInCategories')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getDefaultAttributeSetId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getImageUrl')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getSmallImageUrl')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getThumbnailUrl')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getReservedAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isReservedAttribute')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setOrigData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('reset')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getCacheIdTags')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isProductsHasSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('processBuyRequest')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getPreconfiguredValues')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('prepareCustomOptions')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getProductEntitiesInfo')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isDisabled')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('lockAttribute')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('unlockAttribute')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('unlockAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getLockedAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('hasLockedAttributes')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isLockedAttribute')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('unsetData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('loadByAttribute')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getStore')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getWebsiteStoreIds')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setAttributeDefaultValue')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getAttributeDefaultValue')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setExistsStoreValueFlag')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getExistsStoreValueFlag')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isDeleteable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsDeleteable')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isReadonly')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIsReadonly')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getIdFieldName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getResourceName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($this->buildCatalogModelResourceProductCollection()));
		$catalogModelProductMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('afterLoad')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('afterCommitCallback')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isObjectNew')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getCacheTags')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('cleanModelCache')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getResource')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('clearInstance')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('__construct')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isDeleted')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('hasDataChanges')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setIdFieldName')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('addData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('unsetOldData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDataUsingMethod')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getDataUsingMethod')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getDataSetDefault')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('hasData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('__toArray')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('toXml')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('toJson')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('toString')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('__call')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('__get')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('__set')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isEmpty')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('serialize')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('getOrigData')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('dataHasChangedFor')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setDataChanges')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('debug')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('offsetSet')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('offsetExists')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('offsetUnset')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('offsetGet')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('isDirty')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('flagDirty')
			->will($this->returnSelf());


		$catalogModelProductMock->expects($this->any())
			->method('setSku')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setCreatedAt')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setWeight')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setVisibility')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setAttributeSetId')
			->will($this->returnSelf());
		$catalogModelProductMock->expects($this->any())
			->method('setShortDescription')
			->will($this->returnSelf());

		return $catalogModelProductMock;
	}
}
