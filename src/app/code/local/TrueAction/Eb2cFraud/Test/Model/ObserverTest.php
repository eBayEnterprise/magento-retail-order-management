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
		$areas = array( 'frontend' );
		foreach( $areas as $area ) {
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
		// Get a phony quote ...
		$mockQuote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save'))
			->getMock();

		$mockQuote->expects($this->any())
			->method('addData')
			->will($this->returnSelf());

		$mockQuote->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		// Get a phony request ...
		$mockRequest = $this->getModelMockBuilder('varien/object')
			->disableOriginalConstructor()
			->setMethods(array('getPost'))
			->getMock();

		// From request, we'll need a getPost Method:
		$mockRequest->expects($this->any())
			->method('getPost')
			->will($this->returnValue('sample_js_data'));

		$fraudHelper = $this->getHelperMock('eb2cfraud/data', array('getJavaScriptFraudData'));
		$fraudHelper->expects($this->once())
			->method('getJavaScriptFraudData')
			->with($this->identicalTo($mockRequest))
			->will($this->returnValue('javascript data'));
		$this->replaceByMock('helper', 'eb2cfraud', $fraudHelper);

		$this->replaceSingleton(
			'customer/session',
			array(
				'getEncryptedSessionId' => '123456'
			)
		);

		Mage::getModel('eb2cfraud/observer')->captureOrderContext(
			$this->replaceModel(
				'varien/event_observer',
				array(
					'getEvent' =>
					$this->replaceModel(
						'varien/event',
						array(
							'getQuote' => $mockQuote,
							'getRequest' => $mockRequest
						)
					)
				)
			)
		);
	}

	/**
	 * Returns a mocked object
	 * @todo: Merge this into test base class?
	 * @param a Magento Class Alias
	 * @param array of key / value pairs; key is the method name, value is value returned by that method
	 *
	 * @return mocked-object
	 */
	private function _getFullMocker($classAlias, $mockedMethodSet, $disableConstructor=true)
	{
		$justMethodNames = array_keys($mockedMethodSet);
		$mock = null;

		if( $disableConstructor ) {
			$mock = $this->getModelMockBuilder($classAlias)
				->disableOriginalConstructor()
				->setMethods($justMethodNames)
				->getMock();
		} else {
			$mock = $this->getModelMockBuilder($classAlias)
				->setMethods($justMethodNames)
				->getMock();
		}

		reset($mockedMethodSet);
		foreach($mockedMethodSet as $method => $returnSet ) {
			$mock->expects($this->any())
				->method($method)
				->will($this->returnValue($returnSet));
		}
		return $mock;
	}

	/**
	 * @todo: Merge this into test base class?
	 */
	public function replaceModel($classAlias, $mockedMethodSet, $disableOriginalConstructor=true)
	{
		$mock = $this->_getFullMocker($classAlias, $mockedMethodSet, $disableOriginalConstructor);
		$this->replaceByMock('model', $classAlias, $mock);
		return $mock;
	}

	public function replaceSingleton($classAlias, $mockedMethodSet, $disableOriginalConstructor=true)
	{
		$mock = $this->_getFullMocker($classAlias, $mockedMethodSet, $disableOriginalConstructor);
		$this->replaceByMock('singleton', $classAlias, $mock);
		return $mock;
	}
}
