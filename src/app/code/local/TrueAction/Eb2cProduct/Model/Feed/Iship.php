<?php
class TrueAction_Eb2cProduct_Model_Feed_Iship
	extends TrueAction_Eb2cProduct_Model_Feed_Item
{
	protected $_extractMap = array(
		'gift_wrapping_available' => 'ExtendedAttributes/GiftWrap/text()', // bool
		'catalog_id' => './@catalog_id',
		'gsi_client_id' => './@gsi_client_id',
		'gsi_store_id' => './@gsi_store_id',
		'unique_id' => 'UniqueID/text()',
		'style_id' => 'StyleID/text()',
	);

	public function __construct()
	{
		parent::__construct();
		$this->_extractors = array(
			Mage::getModel('eb2cproduct/feed_extractor_xpath', array($this->_extractMap)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('hts_codes' => 'HTSCodes/HTSCode'),
				array(
					// The mfn_duty_rate attributes.
					'mfn_duty_rate' => './@mfn_duty_rate',
					// The destination_country attributes
					'destination_country' => './@destination_country',
					// The restricted attributes
					'restricted' => './@restricted', // (bool)
					// The HTSCode node value
					'hts_code' => '.',
				)
			)),
		);

		$this->_baseXpath = '/iShip/Item';
		$this->_feedLocalPath = $this->_config->iShipFeedLocalPath;
		$this->_feedRemotePath = $this->_config->iShipFeedRemoteReceivedPath;
		$this->_feedFilePattern = $this->_config->iShipFeedFilePattern;
		$this->_feedEventType = $this->_config->iShipFeedEventType;
	}
}
