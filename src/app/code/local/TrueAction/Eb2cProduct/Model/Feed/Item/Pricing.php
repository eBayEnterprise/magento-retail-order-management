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
		$data = array(
			'sku' => $dataObject->getClientItemId(),
			'price' => $dataObject->getPrice(),
			'special_price' => null,
			'special_from_date' => null,
			'special_to_date' => null,
			'msrp' => $dataObject->getMsrp(),
			'price_is_vat_inclusive' => $dataObject->getPriceVatInclusive(),
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
	 * @return Iterable list of extractor models
	 */
	public function getExtractors()
	{
		return $this->_extractors;
	}

	/**
	 * get the xpath used to split a feed document into processable units
	 * @return string xpath
	 */
	public function getBaseXpath()
	{
		return '/Prices/PricePerItem';
	}

	public function getFeedLocalPath()
	{
		return $this->_config->pricingFeedLocalPath;
	}

	public function getFeedRemotePath()
	{
		return $this->_config->pricingFeedRemotePath;
	}

	public function getFeedFilePattern()
	{
		return $this->_config->pricingFeedFilePattern;
	}

	public function getFeedEventType()
	{
		return $this->_config->pricingFeedEventType;
	}

	/**
	 * Initialize model
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_extractors = array(
			Mage::getModel('eb2cproduct/feed_extractor_xpath', array($this->_extractMap));
		);
	}
}
