<?php
/**
 *
 */
class TrueAction_Eb2cFraud_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case_Config
{
	/**
	 * Is this observer first of all defined?
	 * @test
	 */
	public function testEventObserverDefined()
	{
		$areas = array( 'frontend', 'test' );	// Testing that it's in frontend so it will actually do something live.
		foreach( $areas as $area ) {			// Testing that it's in test so it will actually get called.
			$this->assertEventObserverDefined(
				$area,
				'eb2c_onepage_save_order_before',
				'eb2cfraud/observer',
				'captureOrderContext',
				'capture_order_context'
			);
		}
	}

	/**
	 * @test
	 */
	public function testObserverMethod()
	{
		$orderContext = Mage::getModel('eb2cfraud/context');
		$mockOrderContext = $this->getMock(get_class($orderContext),
			array( 'getCharSet', 'getContentTypes', 'getEncoding', 'getHostName',
					'getIpAddress', 'getLanguage', 'getReferrer', 'getSessionId', 'getUserAgent',)
		);
		$this->replaceByMock('model', 'eb2cfraud/context', $mockOrderContext);

		// Get a phony quote ...
		$mockQuote = $this->getModelMockBuilder('sales/quote')
				->disableOriginalConstructor()
				->getMock();

		// Get a phony request ...
		$mockRequest = $this->getModelMockBuilder('varien/object')
				->disableOriginalConstructor()
				->setMethods(
					array(
						'getPost',
					)
				)
				->getMock();

		// From request, we'll need a getPost Method:
		$mockRequest->expects($this->any())
				->method('getPost')
				->will($this->returnValue('sample_js_data'));

		Mage::dispatchEvent(
			'eb2c_onepage_save_order_before',
			array(
				'quote' => $mockQuote,
				'request' => $mockRequest
			)
		);
		$this->assertEventDispatched('eb2c_onepage_save_order_before');
	}
}
