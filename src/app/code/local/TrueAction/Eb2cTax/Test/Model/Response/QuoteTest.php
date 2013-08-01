<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * tests the tax response quote class.
 */
class TrueAction_Eb2cTax_Test_Model_Response_QuoteTest extends TrueAction_Eb2cTax_Test_Base
{
	public static function setUpBeforeClass()
	{
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$write->query("truncate table eb2ctax_response_quote");
	}

	public function test()
	{
		$data = array(
			'code' => 'The taxquote code',
			'quote_item_id' => 1,
			'type'          => 0,
			'situs'         => 'the situs',
			'calculated_tax'=> 4.38,
			'effective_rate'=> 0.0625,
			'taxable_amount'=> 20.95,
		);
		$q = Mage::getModel('eb2ctax/response_quote');
		$q->setData($data);
		$q->save();

		$qColl = $q->getCollection()->load();
		$this->assertSame(1, $qColl->count());

		$q = $qColl->getFirstItem();
		$q->setSitus('the new situs');
		$q->save();

		$qColl = $q->getCollection()->load();
		$this->assertSame(1, $qColl->count());
		$q = $qColl->getFirstItem();
		$this->assertSame('the new situs', $q->getSitus());
	}
}
