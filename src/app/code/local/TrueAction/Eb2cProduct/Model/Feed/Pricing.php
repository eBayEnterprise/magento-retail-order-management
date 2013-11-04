<?php
class TrueAction_Eb2cProduct_Model_Feed_Pricing
	extends TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	/**
	 * mapping of field name to xpath strings
	 * @var array
	 */
	protected $_extractMap = array(
		'client_item_id' => 'ClientItemId/text()',
		'ebc_pricing_event_number' => 'Event[last()]/EventNumber/text()',
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
	 * Initialize model
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_baseXpath = '/Prices/PricePerItem';
		$this->_feedLocalPath = $this->_config->pricingFeedLocalPath;
		$this->_feedRemotePath = str_replace('{storeid}', $this->_config->storeId, $this->_config->pricingFeedRemoteReceivedPath);
		$this->_feedFilePattern = $this->_config->pricingFeedFilePattern;
		$this->_feedEventType = $this->_config->pricingFeedEventType;

		$this->_extractors = array(
			Mage::getModel('eb2cproduct/feed_extractor_xpath', array($this->_extractMap)),
		);
	}
}
