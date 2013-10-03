<?php
/**
 * Test Suite for the Order_Cancel
 */
class TrueAction_Eb2cOrder_Test_Model_CancelTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	const SAMPLE_CANCELLED_XML = <<<CANCELLED_XML
<OrderCancelResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
  <ResponseStatus>CANCELLED</ResponseStatus>
</OrderCancelResponse>
CANCELLED_XML;

	const SAMPLE_FAILED_XML = <<<FAILED_XML
<OrderCancelResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
  <ResponseStatus>WHOOPS THAT DIDNT WORK</ResponseStatus>
</OrderCancelResponse>
FAILED_XML;

	const SAMPLE_INVALID_XML = <<<INVALID_XML
  <OrderCancelResponse>
Sorry, this is some invalid stuff right here.
INVALID_XML;

	/**
	 * @test
	 */
	public function testCancel()
	{

		$cancelor = Mage::getModel('eb2corder/cancel')
			->buildRequest(
				array(
					'order_id'    => '12345',
					'order_type'  => 'RETURN',
					'reason_code' => 'TestReasonCode',
					'reason'      => 'Testing out a longish reason text right here',
				)
			);

		// Test that we can receive CANCELLED message succesfully:
		$this->replaceModel( 'eb2ccore/api',
			array (
				'request' => self::SAMPLE_CANCELLED_XML
			),
			false
		);
		$this->assertSame(true, $cancelor->sendRequest());

		// Test that we can receive !CANCELLED message succesfully
		$this->replaceModel( 'eb2ccore/api',
			array (
				'request' => self::SAMPLE_FAILED_XML
			),
			false
		);
		$this->assertSame(false, $cancelor->sendRequest());

		// Test that we can receive invalid XML without ill effect:
		$this->replaceModel( 'eb2ccore/api',
			array (
				'request' => self::SAMPLE_INVALID_XML
			),
			false
		);
		$this->assertSame(false, $cancelor->sendRequest());
	}
}
