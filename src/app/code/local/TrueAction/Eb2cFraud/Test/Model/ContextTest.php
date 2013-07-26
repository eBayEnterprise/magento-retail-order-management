<?php
/**
 *
 *
 */
class TrueAction_Eb2cFraud_Test_Model_ContextTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_contextor;			// This has my mocks in it, so it will exercise some return values.
	protected $_mockHttp;			// Mocked-up Http
	protected $_mockSession;		// Mocked-up Mage Session
	protected $_mageContextor;		// This is a not-mocked instance, but will return nothing useful. Used to complete code coverage.

	const TEST_HTTP_ACCEPT_CHAR_SET = 'iso-8859-5, unicode-1-1;q=0.8';
	const TEST_HTTP_ACCEPT_LANGUAGE = 'da, en-gb;q=0.8, en;';
	const TEST_HTTP_HOST = 'www.w3.org';
	const TEST_HTTP_REFERER = 'http://www.w3.org/hypertext/DataSources/Overview.html';
	const TEST_HTTP_USER_AGENT = 'CERN-LineMode/2.15 libwww/2.17b3';
	const TEST_REMOTE_ADDR = '192.168.113.139';

	const TEST_ENCRYPTED_SESSION_ID = 'mecm7b5kma4qv06sfoqe49ejl1';
	const TEST_JAVASCRIPT_DATA = 'TF1;015;;;;;;;;;;;;;;;;;;;;;;Mozilla;Netscape;5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20Safari/536.30.1;20030107;undefined;true;;true;MacIntel;undefined;Mozilla/5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_8_4%29%20AppleWebKit/536.30.1%20%28KHTML%2C%20like%20Gecko%29%20Version/6.0.5%20Safari/536.30.1;en-us;iso-8859-1;;undefined;undefined;undefined;undefined;true;true;1374873016593;-5;June%207%2C%202005%209%3A33%3A44%20PM%20EDT;1920;1080;;11.8;7.7.1;;;;2;300;240;July%2026%2C%202013%205%3A10%3A16%20PM%20EDT;24;1920;1054;0;22;;;;;;Shockwave%20Flash%7CShockwave%20Flash%2011.8%20r800;;;;QuickTime%20Plug-in%207.7.1%7CThe%20QuickTime%20Plugin%20allows%20you%20to%20view%20a%20wide%20variety%20of%20multimedia%20content%20in%20web%20pages.%20For%20more%20information%2C%20visit%20the%20%3CA%20HREF%3Dhttp%3A//www.apple.com/quicktime%3EQuickTime%3C/A%3E%20Web%20site.;;;;;Silverlight%20Plug-In%7C5.1.20125.0;;;;18;';

	const TEST_HTTP_ACCEPT_ENCODING = 'gzip, deflate';
	const TEST_HTTP_ACCEPT = 'text/plain';


	/**
	 * Testing grabbing a session, we need a base url
	 */
	protected function _setupBaseUrl()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
	}

	/**
	 * We mock up a Mage_Core_Helper_Http so we can flex Context objects getters:
	 */
	public function setUp()
	{
		$this->_setupBaseUrl();
		$this->_contextor = Mage::getModel('eb2cfraud/context');			// This will exercise with mocks, so we have values
		$this->_mageContextor = Mage::getModel('eb2cfraud/context');		// This will exercise without mocks, so we have coverage

		// Mock up a session:
		$sessModel = Mage::getModel('customer/session');
		$this->_mockSession = $this->getMock( get_class($sessModel),
			array(
				'getEncryptedSessionId',
				'getJavascriptData',
			)
		);
		$this->_mockSession->expects($this->any())
			->method('getEncryptedSessionId')
			->will($this->returnValue(self::TEST_ENCRYPTED_SESSION_ID));

		$this->_mockSession->expects($this->any())
			->method('getJavascriptData')
			->will($this->returnValue(self::TEST_JAVASCRIPT_DATA));

		// Mock up a helper:
		$httpHelper = Mage::helper('eb2cfraud/http');
		$this->_mockHttp = $this->getMock( get_class($httpHelper),
			array(
				'getHttpAcceptCharSet',
				'getHttpAcceptLanguage',
				'getHttpAcceptEncoding',
				'getHttpAccept',
				'getHttpHost',
				'getHttpReferer',
				'getHttpUserAgent',
				'getRemoteAddr',
			)
		);

		$this->_mockHttp->expects($this->any())
			->method('getHttpAcceptCharset')
			->will($this->returnValue(self::TEST_HTTP_ACCEPT_CHAR_SET));

		$this->_mockHttp->expects($this->any())
			->method('getHttpAcceptLanguage')
			->will($this->returnValue(self::TEST_HTTP_ACCEPT_LANGUAGE));

		$this->_mockHttp->expects($this->any())
			->method('getHttpAcceptEncoding')
			->will($this->returnValue(self::TEST_HTTP_ACCEPT_ENCODING));

		$this->_mockHttp->expects($this->any())
			->method('getHttpAccept')
			->will($this->returnValue(self::TEST_HTTP_ACCEPT));

		$this->_mockHttp->expects($this->any())
			->method('getHttpReferer')
			->will($this->returnValue(self::TEST_HTTP_REFERER));

		$this->_mockHttp->expects($this->any())
			->method('getHttpUserAgent')
			->will($this->returnValue(self::TEST_HTTP_USER_AGENT));

		$this->_mockHttp->expects($this->any())
			->method('getRemoteAddr')
			->will($this->returnValue(self::TEST_REMOTE_ADDR));

		$this->_mockHttp->expects($this->any())
			->method('getHttpHost')
			->will($this->returnValue(self::TEST_HTTP_HOST));

		// Now install our mock helper and mock session:
        $contextorReflection = new ReflectionObject($this->_contextor);
        $mockMageHttpHelper = $contextorReflection->getProperty('_httpHelper');
		$mockMageHttpHelper->setAccessible(true);
        $mockMageHttpHelper->setValue($this->_contextor, $this->_mockHttp);

		$mockMageSession = $contextorReflection->getProperty('_mageSession');
		$mockMageSession->setAccessible(true);
        $mockMageSession->setValue($this->_contextor, $this->_mockSession);
	}

	/**
	 * Flexing the Context Getters.
	 * @test
	 */
	public function testContextGetters()
	{
		// Most basic test - can and did we get the correct model we requested
		$testFactoryModel = Mage::getModel('eb2cfraud/context');
		$this->assertInstanceOf('TrueAction_Eb2cFraud_Model_Context', $testFactoryModel);

		/*
		 * This section happens to be about some HTTP type stuff, but the caller of our Context object really doesn't care. Split
		 * out here because developers may find that interesting. (See also the Session-y stuff after these http bits...)
		 */
		$this->assertEmpty($this->_mageContextor->getCharSet());	// Just for code coverage, it can't return anything meaningful
		$this->assertEmpty($this->_mageContextor->getContentTypes());	// Forces coverage of http helper; it can't return anything meaningful
		$this->assertEmpty($this->_mageContextor->getEncoding());		// Forces coverage of http helper; it can't return anything meaningful

		$this->assertEquals(self::TEST_HTTP_ACCEPT_CHAR_SET,	$this->_contextor->getCharSet());
		$this->assertEquals(self::TEST_HTTP_ACCEPT_LANGUAGE,	$this->_contextor->getLanguage());
		$this->assertEquals(self::TEST_HTTP_REFERER, 			$this->_contextor->getReferer());
		$this->assertEquals(self::TEST_HTTP_USER_AGENT, 		$this->_contextor->getUserAgent());
		$this->assertEquals(self::TEST_REMOTE_ADDR, 			$this->_contextor->getIpAddress());
		$this->assertEquals(self::TEST_HTTP_HOST, 				$this->_contextor->getHostName());
		$this->assertEquals(self::TEST_HTTP_ACCEPT_ENCODING,	$this->_contextor->getEncoding());
		$this->assertEquals(self::TEST_HTTP_ACCEPT,				$this->_contextor->getContentTypes());

		/*
		 * This section happens to be about some Sesssion-y Magento-y type stuff, but the caller of our Context object really doesn't care.
		 */
		$this->assertEmpty($this->_mageContextor->getSessionId()); // Should be empty in our "dumb" mode here
		$this->assertEquals(self::TEST_ENCRYPTED_SESSION_ID,	$this->_contextor->getSessionId());
		$this->assertEquals(self::TEST_JAVASCRIPT_DATA,			$this->_contextor->getJavascriptData());
	}
}
