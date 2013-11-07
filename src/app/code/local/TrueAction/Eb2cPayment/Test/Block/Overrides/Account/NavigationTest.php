<?php
class TrueAction_Eb2cPayment_Test_Block_Overrides_Account_NavigationTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_navigation;
	protected $_links;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_navigation = new TrueAction_Eb2cPayment_Overrides_Block_Account_Navigation();
		$navigationReflector = new ReflectionObject($this->_navigation);
		$linksProperty = $navigationReflector->getProperty('_links');
		$linksProperty->setAccessible(true);
		$linksProperty->setValue(
			$this->_navigation,
			array(
				'account' => new Varien_Object(),
				'account_edit' => new Varien_Object(),
				'address_book' => new Varien_Object(),
				'orders' => new Varien_Object(),
				'billing_agreements' => new Varien_Object(),
				'recurring_profiles' => new Varien_Object(),
				'reviews' => new Varien_Object(),
				'tags' => new Varien_Object(),
				'wishlist' => new Varien_Object(),
				'newsletter' => new Varien_Object(),
				'downloadable_products' => new Varien_Object(),
				'enterprise_customerbalance' => new Varien_Object(),
				'enterprise_giftcardaccount' => new Varien_Object(),
				'giftregistry' => new Varien_Object(),
				'enterprise_reward' => new Varien_Object(),
				'invitations' => new Varien_Object(),
			)
		);
		$this->_links = $linksProperty->getValue($this->_navigation);
	}

	public function providerRemoveLinkByName()
	{
		return array(
			array('enterprise_giftcardaccount')
		);
	}

	/**
	 * testing removeLinkByName method
	 *
	 * @test
	 * @dataProvider providerRemoveLinkByName
	 */
	public function testRemoveLinkByName($name)
	{
		// First test, let make sure the links has the key 'enterprise_giftcardaccount'
		$this->assertArrayHasKey(
			'enterprise_giftcardaccount',
			$this->_links
		);

		$navigationReflector = new ReflectionObject($this->_navigation);
		$linksProperty = $navigationReflector->getProperty('_links');
		$linksProperty->setAccessible(true);
		$linksProperty->setValue($this->_navigation, $this->_links);

		// Second test, let make sure after calling the new method 'removeLinkByName',
		// the 'enterprise_giftcardaccount' key is removed from the links array

		$this->assertNull(
			$this->_navigation->removeLinkByName($name)
		);

		// confirming, the array 'enterprise_giftcardaccount' has been removed.
		$this->assertArrayNotHasKey(
			'enterprise_giftcardaccount',
			$linksProperty->getValue($this->_navigation)
		);
	}
}
