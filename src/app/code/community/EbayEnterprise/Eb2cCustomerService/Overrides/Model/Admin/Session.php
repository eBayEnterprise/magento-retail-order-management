<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class EbayEnterprise_Eb2cCustomerService_Overrides_Model_Admin_Session extends Mage_Admin_Model_Session
{
    /** @var EbayEnterprise_MageLog_Helper_Data $_logger */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /** @var Mage_Adminhtml_Model_Url */
    protected $url;

    public function __construct(array $initParams = [])
    {
        list($this->logger, $this->context, $this->url) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce($initParams, 'url', Mage::getSingleton('adminhtml/url'))
        );
        parent::__construct($this->removeKnownKeys($initParams));
    }

    /**
     * Populate a new array with keys that not in the array of known keys.
     *
     * @param  array
     * @return array
     */
    protected function removeKnownKeys(array $initParams)
    {
        $newParams = [];
        $knownKeys = ['logger', 'context', 'url'];
        foreach ($initParams as $key => $value) {
            if (!in_array($key, $knownKeys)) {
                $newParams[$key] = $value;
            }
        }
        return $newParams;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @param  Mage_Adminhtml_Model_Url
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context,
        Mage_Adminhtml_Model_Url $url
    )
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Attempt to authenticate and login the CSR user using the provided token.
     * This method mimics the login method except it will only attempt to login
     * the configured CSR user and will use the provided token to auth via the
     * EB2C service call. If the user is successfully authenticated, the logged
     * in user will be returned. If the auth fails, return an empty user object.
     * @see Mage_Admin_Model_Session::login
     * @param string $token
     * @param Mage_Core_Controller_Request_Http
     * @return null
     * @codeCoverageIgnore All side-effects taken from Magento auth/login process
     */
    public function loginCSRWithToken($token, Mage_Core_Controller_Request_Http $request = null)
    {
        // An empty token should never validate so don't bother trying and just
        // return nothing.
        /** @var EbayEnterprise_Eb2cCustomerService_Helper_Data $helper */
        $helper = Mage::helper('eb2ccsr');
        /** @var Mage_Admin_Model_User $user */
        $user = Mage::getModel('admin/user');
        $user->load($helper->getConfigModel()->csrUser);
        try {
            $this->_validateUser($user);
            $helper->validateToken($token);
            // Event observers within this method may throw exceptions to cause the
            // login to fail.
            $this->_passValidation($user, $request);
        } catch (EbayEnterprise_Eb2cCustomerService_Exception_Authentication $e) {
            $this->_failValidation($user, $request, $e);
        }
    }

    /**
     * Validate the CSR user to ensure it is active and has a role assigned.
     *
     * @param Mage_Admin_Model_User $user
     * @throws EbayEnterprise_Eb2cCustomerService_Exception_Authentication
     * @return self
     */
    protected function _validateUser(Mage_Admin_Model_User $user)
    {
        if (!$user->getIsActive()) {
            throw new EbayEnterprise_Eb2cCustomerService_Exception_Authentication(
                'This account is inactive.'
            );
        }
        if (!$user->hasAssigned2Role($user->getId())) {
            throw new EbayEnterprise_Eb2cCustomerService_Exception_Authentication(
                'Access denied.'
            );
        }
        return $this;
    }
    /**
     * Update all the related session pieces after successfully validating the
     * user and if the request contains custom request URI logic, redirect to
     * the URI (which will set a location header and kill the process) or
     * return the updated user object.
     * The dispatched even may result in exceptions which will trigger the
     * login to fail, triggering self::_failValidation
     * @param  Mage_Admin_Model_User $user
     * @param  Mage_Core_Controller_Request_Http $request
     * @return null
     * @codeCoverageIgnore All side-effects taken from Magento auth/login process
     */
    protected function _passValidation(Mage_Admin_Model_User $user, Mage_Core_Controller_Request_Http $request = null)
    {
        $logMessage = 'Successfully authenticated using token.';
        $this->logger->info($logMessage, $this->context->getMetaData(__CLASS__));
        // This may potentially cause some issues as the user password is not
        // included since we never receive it when loggin in with the token. So far
        // it doesn't seem to be causing any issues but may have some impact on the
        // Mage_Enterprise_Pci_Model_Observer::adminAuthenticate method.
        Mage::dispatchEvent('admin_user_authenticate_after', array(
            'username' => $user->getUsername(),
            'password' => '',
            'user' => $user,
            'result' => true,
        ));
        // add a record of the login to the admin log
        $user->getResource()->recordLogin($user);

        // Renew session and url keys to make sure the session doesn't die right
        // after the auth.
        $this->renewSession();
        $adminhtmlUrl = Mage::getSingleton('adminhtml/url');
        if ($adminhtmlUrl->useSecretKey()) {
            $adminhtmlUrl->renewSecretUrls();
        }

        // setIsFirstPageAfterLogin is more than a "magic" setter so needs to be
        // called separately
        $this->setIsFirstPageAfterLogin(true)
            ->addData(array(
                'user' => $user, 'acl' => Mage::getResourceModel('admin/acl')->loadAcl()))
            // This appears to be necessary based on what Magento does normally
            // with login/pass authentication although I'm not entirely sure what
            // is is expected to do as the ACL was just set.
            ->refreshAcl($user);

        Mage::dispatchEvent('admin_session_user_login_success', array('user' => $user));

        // Get the page to redirect the newly logged in user to, in this case the
        // user's configured start page and redirect the user.
        $startpageUri = $this->_getStartpageUri($request);
        // This method will set a Location header and exit, allowing the HTTP
        // redirect to take over.
        $this->_postAuthCheckRedirect($startpageUri);
    }
    /**
     * Clear out user and session data when validation fails. Dispatch an event,
     * set session messages and unset user data before returning the empty
     * user object.
     * @param  Mage_Admin_Model_User $user
     * @param  Mage_Core_Controller_Request_Http $request
     * @param  Mage_Core_Exception $authException
     * @return null
     * @codeCoverageIgnore All side-effects taken from Magento auth/login process
     */
    protected function _failValidation(
        Mage_Admin_Model_User $user,
        Mage_Core_Controller_Request_Http $request = null,
        Mage_Core_Exception $authException
    ) {
        $logMessage = 'Failed to authenticate using token.';
        $this->logger->info($logMessage, $this->context->getMetaData(__CLASS__));
        // This may be problematic due to the missing user password. It is never
        // given while doing the token auth so we don't have one to pass. So far
        // it doesn't seem to be causing any issues but may have some impact on the
        // Mage_Enterprise_Pci_Model_Observer::adminAuthenticate method.
        Mage::dispatchEvent('admin_user_authenticate_after', array(
            'username' => $user->getUsername(),
            'password' => '',
            'user' => $user,
            'result' => false,
        ));
        Mage::dispatchEvent(
            'admin_session_user_login_failed',
            array('user_name' => $user->getUsername(), 'exception' => $authException)
        );
        if ($request && !$request->getParam('messageSent')) {
            Mage::getSingleton('adminhtml/session')->addError($authException->getMessage());
            $request->setParam('messageSent', true);
        }
        $user->unsetData();
        $this->_postAuthCheckRedirect(Mage::helper('adminhtml')->getUrl('*'));
    }
    /**
     * After logging the user in, set the location header and exit, allowing
     * the HTTP redirect to take over.
     * @see Mage_Admin_Model_Session::login
     * @param  string $requestUri
     * @return null
     * @codeCoverageIgnore All side-effects taken from Magento auth/login process
     */
    protected function _postAuthCheckRedirect($requestUri)
    {
        header('Location: ' . $requestUri);
        exit;
    }
    /**
     * Get the startup page URL for the current user set in the session.
     * @return string
     */
    protected function _getStartpageUri()
    {
        /** @var string */
        $startpageUri = $this->getUser()->getStartupPageUrl();
        return $this->url->getUrl($startpageUri);
    }
}
