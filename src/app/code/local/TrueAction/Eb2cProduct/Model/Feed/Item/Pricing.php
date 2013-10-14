<?php
class TrueAction_Eb2cProduct_Model_Feed_Item_Pricing
	extends TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	/**
	 * mapping of field name to xpath strings
	 * @var array
	 */
	protected $_extractMap = array(
		'client_item_id' => 'ClientItemId/text()',
		'event_number' => 'Event[last()]/EventNumber/text()',
		'catalog_id' => './@catalog_id',
		'gsi_client_id' => './@gsi_client_id',
		'gsi_store_id' => './@gsi_store_id',
		'price' => 'Event[last()]/Price/text()',
		'msrp' => 'Event[last()]/MSRP/text()',
		'alternate_price' => 'Event[last()]/AlternatePrice1/text()',
		'start_date' => 'Event[last()]/StartDate/text()',
		'end_date' => 'Event[last()]/EndDate/text()',
		'price_vat_inclusive' => 'Event[last()]/PriceVatInclusive/text()',
	);

	/**
	 * list of extractor models to run on a PricePerItem node.
	 * @var ArrayObject
	 */
	protected $_extractors;

	/**
	 * prepare the data to be set to the product
	 * @param Varien_Object $dataObject, the object with data needed to add the product
	 * @return self
	 */
	public function transformData(Varien_Object $dataObject)
	{
		$priceIsVatInclusive = $dataObject->getPriceVatInclusive();
		$priceIsVatInclusive = strtoupper($priceIsVatInclusive) === 'TRUE' ? true : false;
		$data = array(
			'sku' => $dataObject->getClientItemId(),
			'price' => $dataObject->getPrice(),
			'special_price' => null,
			'special_from_date' => null,
			'special_to_date' => null,
			'msrp' => $dataObject->getMsrp(),
			'price_is_vat_inclusive' => $priceIsVatInclusive,
		);
		if ($dataObject->getEventNumber()) {
			$data['price'] = $dataObject->getAlternatePrice();
			$data['special_price'] = $dataObject->getPrice();
			$data['special_from_date'] = $dataObject->getStartDate();
			$data['special_to_date'] = $dataObject->getEndDate();
		}
		$dataObject->setData($data);
		return $this;
	}

	/**
	 * Initialize model
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_baseXpath = '/Prices/PricePerItem';
		$this->_feedLocalPath = $this->_config->pricingFeedLocalPath;
		$this->_feedRemotePath = $this->_config->pricingFeedRemotePath;
		$this->_feedFilePattern = $this->_config->pricingFeedFilePattern;
		$this->_feedEventType = $this->_config->pricingFeedEventType;

		$this->_extractors = array(
			Mage::getModel('eb2cproduct/feed_extractor_xpath', array($this->_extractMap)),
		);
	}
}
