<?php
/**
 * Test Suite for the Order_Cancel
 */
class TrueAction_Eb2cOrder_Test_Model_CancelTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 * @loadFixture
	 * TODO: This guy needs a LOT of help crap test really. 
	 */
	public function testCancel()
	{
		$cancelor = Mage::getModel('eb2corder/cancel');
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Cancel', $cancelor );

		$status = null;
		$cancelor->buildRequest( array(
			'order_id'=>'12345',
			'order_type'=>'RETURN',
			'reason_code'=>'TestReasonCode',
			'reason'=>'Testing out a longish reason text right here',
			)
		);
		$status = $cancelor->sendRequest();
		$this->assertSame(false,$status);
	}
}
