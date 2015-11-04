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

require_once 'EbayEnterprise/Order/controllers/OrderController.php';

class EbayEnterprise_Order_Test_Controller_OrderControllerTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const API_CLASS = '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';

    // @var Mock_EbayEnterprise_Order_GuestController
    protected $_controller;
    // @var Mock_Mage_Customer_Model_Session
    protected $_session;
    // @var Mock_Mage_Core_Controller_Request_Http
    protected $_request;
    /** @var Mock_Zend_Controller_Response_Abstract */
    protected $_response;
    /** @var Mock_HttpApi */
    protected $_api;

    /**
     * mock the request, customer session, and controller instance to test with.
     */
    public function setUp()
    {
        $this->_session = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();

        $this->_request = $this->getMockBuilder('Mage_Core_Controller_Request_Http')
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMock();

        $this->_controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->disableOriginalConstructor()
            ->setMethods(['_loadValidOrder', '_canViewOrder', 'loadLayout', 'renderLayout', 'getRequest', '_redirect', '_getRomReturnPath'])
            ->getMock();
        $this->_controller->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->_request));

        /** @var Mock_Zend_Controller_Response_Abstract */
        $this->_response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        $this->_api = $this->getMockBuilder(static::API_CLASS)
            // Disabling the constructor because it requires the IHttpConfig parameter to be passed in.
            ->disableOriginalConstructor()
            ->getMock();
    }
    /**
     * verify the order and shipment are setup correctly.
     * verify the request is handled properly.
     */
    public function testPrintOrderShipmentAction()
    {
        $this->_request->expects($this->any())
            ->method('getParam')
            ->will($this->returnValue('theid'));
        $this->_controller->expects($this->any())
            ->method('_loadValidOrder')
            ->will($this->returnValue(true));
        $this->_controller->expects($this->any())
            ->method('_canViewOrder')
            ->will($this->returnValue(true));
        $this->_controller->expects($this->once())
            ->method('loadLayout')
            ->with($this->isType('string'))
            ->will($this->returnValue($this->getModelMock('core/layout')));
        // if all went well, a call to renderLayout should
        // be observed.
        $this->_controller->expects($this->once())
            ->method('renderLayout')
            ->will($this->returnSelf());
        $this->_controller->printOrderShipmentAction();
    }
    /**
     * provide datasets so the printOrderShipmentAction method does not attempt
     * to render any content.
     * @return array
     */
    public function providePrintOrderFailData()
    {
        return [
            [false, true, 2, true],
            [true, false, '2', false],
            [true, true, null, true],
        ];
    }
    /**
     * if any of the following is false redirect to either to the order history page or the guest form.
     *  - _loadValidOrder
     *  - _canViewOrder
     *  - no shipment id is given
     * @param bool  $loaded   whether the order was loaded or not
     * @param bool  $viewable whether the order should be viewable
     * @param mixed $shipId   shipment id
     * @dataProvider providePrintOrderFailData
     */
    public function testPrintOrderShipmentActionFailure($loaded, $viewable, $shipId, $isLoggedIn)
    {
        $this->_session->expects($loaded && !is_null($shipId) ? $this->never() : $this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue($isLoggedIn));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getCustomerSession']);
        $factory->expects($loaded && !is_null($shipId) ? $this->never() : $this->once())
            ->method('getCustomerSession')
            ->will($this->returnValue($this->_session));

        $this->_request->expects($this->any())
            ->method('getParam')
            ->will($this->returnValue($shipId));
        $this->_controller->expects($this->any())
            ->method('_loadValidOrder')
            ->will($this->returnValue($loaded));
        $this->_controller->expects($this->any())
            ->method('_canViewOrder')
            ->will($this->returnValue($viewable));
        $this->_controller->expects($loaded && !is_null($shipId) ? $this->never() : $this->once())
            ->method('_redirect')
            ->with($this->isType('string'));

        EcomDev_Utils_Reflection::setRestrictedPropertyValues($this->_controller, [
            '_orderFactory' => $factory,
        ]);
        $this->_controller->printOrderShipmentAction();
    }

    /**
     * @return array
     */
    public function providerLoadValidOrder()
    {
        return [
            [false, null, true],
            [true, Mage::exception('EbayEnterprise_Order_Exception_Order_Detail_Notfound'), false],
        ];
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_loadValidOrder()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::getRequest()
     * which will return an instance of an object that extends Zend_Controller_Request_Abstract class.
     * Then, the method Zend_Controller_Request_Abstract::getParam() will be invoked, passed in
     * the string literal 'order_id' as its parameter and then it will return the order id.
     * Then, the method ebayenterprise_order/factory::getNewRomOrderDetailModel() will be invoked and
     * return an object of type ebayenterprise_order/detail. Then, the method ebayenterprise_order/detail::requestOrderDetail() will be
     * called passing in the return value from calling the method Zend_Controller_Request_Abstract::getParam().
     * If no exception is thrown the method EbayEnterprise_Order_OrderController::_handleOrderDetailException().
     * Finally, the method EbayEnterprise_Order_OrderController::_loadValidOrder() will return null.
     *
     * @param bool
     * @param EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception | null
     * @param bool
     * @dataProvider providerLoadValidOrder
     */
    public function testLoadValidOrder($isException, $exception, $result)
    {
        /** @var string */
        $param = 'order_id';
        /** @var string */
        $orderId = '10000093888341';

        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract', [], '', true, true, true, ['getParam']);
        $request->expects($this->once())
            ->method('getParam')
            ->with($this->identicalTo($param))
            ->will($this->returnValue($orderId));

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response */
        $response = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Mock_EbayEnterprise_Order_Model_Detail */
        $orderDetail = $this->getModelMockBuilder('ebayenterprise_order/detail')
            ->disableOriginalConstructor()
            ->getMock();
        $orderDetail->expects($this->once())
            ->method('process')
            ->will($isException ? $this->throwException($exception) : $this->returnValue($response));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewRomOrderDetailModel']);
        $factory->expects($this->once())
            ->method('getNewRomOrderDetailModel')
            ->will($this->returnValue($orderDetail));

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['getRequest', '_handleOrderDetailException'])
            ->setConstructorArgs([$request, $this->_response])
            ->getMock();
        EcomDev_Utils_Reflection::setRestrictedPropertyValues($controller, [
            '_orderFactory' => $factory,
        ]);
        $controller->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $controller->expects($isException ? $this->once() : $this->never())
            // Proving that this method will never be called if the method ebayenterprise_order/detail::requestOrderDetail()
            // don't throw an exception of type bayEnterprise_Order_Exception_Order_Detail_Notfound.
            // However, when exception of type bayEnterprise_Order_Exception_Order_Detail_Notfound is thrown
            // the this method expected to be invoked once.
            ->method('_handleOrderDetailException')
            ->with($this->identicalTo($exception))
            ->will($this->returnSelf());

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_loadValidOrder', []));
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_handleOrderDetailException()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::_getCustomerSession(),
     * which will return an instance of type customer/session. Then, the method customer/session::addError()
     * will be called and passed as its parameter the value from calling the method
     * EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception::getMessage() from the passed in exception object.
     * Then, the method EbayEnterprise_Order_OrderController::_redirect() is invoked, and passed in the return
     * value from calling the method EbayEnterprise_Order_OrderController::_getRomReturnPath().
     * Finally, the controller method EbayEnterprise_Order_OrderController::_handleOrderDetailException() will
     * return itself.
     */
    public function testHandleOrderDetailException()
    {
        /** @var string */
        $path = EbayEnterprise_Order_OrderController::LOGGED_IN_ORDER_HISTORY_PATH;
        /** @var string */
        $message = 'Exception test message';
        /** @var EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception */
        $exception = Mage::exception('EbayEnterprise_Order_Exception_Order_Detail_Notfound', $message);
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');
        /** @var Mock_Mage_Core_Model_Session */
        $coreSession = $this->getModelMockBuilder('core/session')
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->setMethods(['addError'])
            ->getMock();
        $coreSession->expects($this->once())
            ->method('addError')
            ->with($this->identicalTo($message))
            ->will($this->returnSelf());

        /** @var Mock_Mage_Customer_Model_Session */
        $customerSession = $this->getModelMockBuilder('customer/session')
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->getMock();
        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getCustomerSession', 'getCoreSessionModel']);
        $factory->expects($this->once())
            ->method('getCustomerSession')
            ->will($this->returnValue($customerSession));
        $factory->expects($this->once())
            ->method('getCoreSessionModel')
            ->will($this->returnValue($coreSession));

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_getRomReturnPath', '_redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        EcomDev_Utils_Reflection::setRestrictedPropertyValues($controller, [
            '_orderFactory' => $factory,
        ]);
        $controller->expects($this->once())
            ->method('_getRomReturnPath')
            ->with($this->identicalTo($customerSession))
            ->will($this->returnValue($path));
        $controller->expects($this->once())
            ->method('_redirect')
            ->with($this->identicalTo($path))
            ->will($this->returnSelf());

        $this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_handleOrderDetailException', [$exception]));
    }

    /**
     * @return array
     */
    public function providerGetOrderDetailReturnPath()
    {
        return [
            [true, EbayEnterprise_Order_OrderController::LOGGED_IN_ORDER_HISTORY_PATH],
            [false, EbayEnterprise_Order_OrderController::GUEST_ORDER_FORM_PATH],
        ];
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_getRomReturnPath()
     * is invoked and it will call the method customer/session::isLoggedIn() and if it returns the boolean value
     * true then the controller method EbayEnterprise_Order_OrderController::_getRomReturnPath() will
     * return the class constant EbayEnterprise_Order_OrderController::LOGGED_IN_ORDER_HISTORY_PATH.
     * Otherwise, it will return class constant value EbayEnterprise_Order_OrderController::GUEST_ORDER_FORM_PATH.
     *
     * @param bool
     * @param string
     * @dataProvider providerGetOrderDetailReturnPath
     */
    public function testGetOrderDetailReturnPath($isLoggedIn, $path)
    {
        /** @var Mock_Mage_Customer_Model_Session */
        $session = $this->getModelMockBuilder('customer/session')
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();
        $session->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue($isLoggedIn));

        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setConstructorArgs([$request, $response])
            ->getMock();

        $this->assertSame($path, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getRomReturnPath', [$session]));
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::romViewAction()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::_viewAction().
     * Then, the method EbayEnterprise_Order_OrderController::loadLayout() will be invoked. Then,
     * the method EbayEnterprise_Order_OrderController::_initLayoutMessages() will be called and passed
     * in as parameter the string literal 'catalog/session'. Then, the method EbayEnterprise_Order_OrderController::getLayout()
     * will be called and return an instance of the class Mage_Core_Model_Layout. Then,
     * the method Mage_Core_Model_Layout::getBlock() will be called and passed in as parameter the string
     * literal 'customer_account_navigation' and it will return an instance object that extend the abstract class
     * Mage_Core_Block_Abstract. Then, the method Mage_Core_Block_Abstract::setActive() will be called. Then, the method
     * EbayEnterprise_Order_OrderController::renderLayout() will be called. Finally, the method
     * EbayEnterprise_Order_OrderController::romViewAction() will return null.
     */
    public function testRomViewAction()
    {
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_viewAction'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('_viewAction')
            ->will($this->returnValue(null));

        $this->assertNull($controller->romViewAction());
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::romGuestViewAction()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::_viewAction().
     * Then, the method EbayEnterprise_Order_OrderController::loadLayout() will be invoked. Then,
     * the method EbayEnterprise_Order_OrderController::_initLayoutMessages() will be called and passed
     * in as parameter the string literal 'catalog/session'. Then, the method
     * EbayEnterprise_Order_OrderController::renderLayout() will be called. Finally, the method
     * EbayEnterprise_Order_OrderController::romGuestViewAction() will return null.
     */
    public function testRomGuestViewAction()
    {
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_viewAction'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('_viewAction')
            ->will($this->returnValue(null));

        $this->assertNull($controller->romGuestViewAction());
    }

    /**
     * @return array
     */
    public function providerRomCancelAction()
    {
        return [
            [true, true, true],
            [false, true, true],
            [false, false, true],
            [false, false, false],
        ];
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::romCancelAction()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::_canShowOrderCancelForm()
     * and if it returns the boolean value true then these controller methods
     * EbayEnterprise_Order_OrderController::_setRefererUrlInSession() and
     * EbayEnterprise_Order_OrderController::_showOrderCancelPage() will be called. However,
     * the method EbayEnterprise_Order_OrderController::_processOrderCancelAction() will never be called.
     * Otherwise, if the method EbayEnterprise_Order_OrderController::_canShowOrderCancelForm() return the boolean
     * value false, then these controller methods EbayEnterprise_Order_OrderController::_setRefererUrlInSession() and
     * EbayEnterprise_Order_OrderController::_showOrderCancelPage() will never be called. However, the method
     * EbayEnterprise_Order_OrderController::_processOrderCancelAction() will be called once.
     * Finally, the method EbayEnterprise_Order_OrderController::romCancelAction() will return null.
     *
     * @param bool
     * @param bool
     * @param bool
     * @dataProvider providerRomCancelAction
     */
    public function testRomCancelAction($canShow, $isValidOperation, $isLoggedIn)
    {
        $this->_session->expects($isValidOperation ? $this->never() : $this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue($isLoggedIn));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getCustomerSession']);
        $factory->expects($isValidOperation ? $this->never() : $this->once())
            ->method('getCustomerSession')
            ->will($this->returnValue($this->_session));

        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_canShowOrderCancelForm', '_setRefererUrlInSession', '_showOrderCancelPage', '_processOrderCancelAction', '_isValidOperation', '_redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        EcomDev_Utils_Reflection::setRestrictedPropertyValues($controller, [
            '_orderFactory' => $factory,
        ]);
        $controller->expects($isValidOperation ? $this->once() : $this->never())
            ->method('_canShowOrderCancelForm')
            ->will($this->returnValue($canShow));
        $controller->expects($canShow ? $this->once() : $this->never())
            // when the method EbayEnterprise_Order_OrderController::_canShowOrderCancelForm() return true
            // this method will be invoked once, otherwise it will never be called.
            ->method('_setRefererUrlInSession')
            ->will($this->returnSelf());
        $controller->expects($canShow ? $this->once() : $this->never())
            // when the method EbayEnterprise_Order_OrderController::_canShowOrderCancelForm() return true
            // this method will be invoked once, otherwise it will never be called.
            ->method('_showOrderCancelPage')
            ->will($this->returnSelf());
        $controller->expects($isValidOperation && !$canShow ? $this->once() : $this->never())
            // when the method EbayEnterprise_Order_OrderController::_canShowOrderCancelForm() return true
            // this method will never be invoked, otherwise it will be called once.
            ->method('_processOrderCancelAction')
            ->will($this->returnSelf());
        $controller->expects($this->once())
            ->method('_isValidOperation')
            ->will($this->returnValue($isValidOperation));
        $controller->expects($isValidOperation ? $this->never() : $this->once())
            ->method('_redirect')
            ->will($this->returnValueMap([
                ['*/*/history', [], $controller],
                ['sales/guest/form', [], $controller],
            ]));

        $this->assertNull($controller->romCancelAction());
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::romGuestCancelAction()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::romCancelAction().
     * Finally, the method EbayEnterprise_Order_OrderController::romGuestCancelAction() will return null.
     */
    public function testromGuestCancelAction()
    {
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['romCancelAction'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('romCancelAction')
            ->will($this->returnValue(null));

        $this->assertNull($controller->romGuestCancelAction());
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_setRefererUrlInSession()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::_getRomCancelSession(),
     * which will return an instance of type core/session. Then, the method core/session::setCancelActionRefererUrl()
     * will be called and passed as its parameter the return value from calling the method
     * EbayEnterprise_Order_OrderController::_getRefererUrl(). finally, the controller method
     * EbayEnterprise_Order_OrderController::_setRefererUrlInSession() will return itself.
     */
    public function testSetRefererUrlInSession()
    {
        /** @var string */
        $path = EbayEnterprise_Order_OrderController::LOGGED_IN_ORDER_HISTORY_PATH;
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_Mage_Core_Model_Session */
        $session = $this->getModelMockBuilder('core/session')
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->setMethods(['setCancelActionRefererUrl'])
            ->getMock();
        $session->expects($this->once())
            ->method('setCancelActionRefererUrl')
            ->will($this->returnSelf());

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_getRomCancelSession', '_getRefererUrl'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('_getRomCancelSession')
            ->will($this->returnValue($session));
        $controller->expects($this->once())
            ->method('_getRefererUrl')
            ->will($this->returnValue($path));

        $this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_setRefererUrlInSession', []));
    }

    /**
     * @return array
     */
    public function providerProcessOrderCancelAction()
    {
        return [
            [false, false, null],
            [true, true, Mage::exception('Mage_Core')],
        ];
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_processOrderCancelAction()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::_getRomOrderToBeCanceled(),
     * which will return an instance of type sales/order. Then, the method
     * EbayEnterprise_Order_OrderController::_getRomOrderCancelModel() will be called and passed as its parameter the
     * sales/order object, which in term will return an instance of type ebayenterprise_order/cancel.
     * Then, the method EbayEnterprise_Order_OrderController::_getRomCancelSession() will be invoked
     * and return an instance of type core/session. Then, the method core/session::getCancelActionRefererUrl()
     * will be called and if it return a non-empty string value then, the method EbayEnterprise_Order_OrderController::_getRefererUrl().
     * However, if the return value from core/session::getCancelActionRefererUrl() is null, then the method
     * EbayEnterprise_Order_OrderController::_getRefererUrl() will be invoked once. Then, the method
     * ebayenterprise_order/cancel::process() will be invoked and if it doesn't throw an exception the method
     * EbayEnterprise_Order_OrderController::_handleRomCancelResponse() invoked once passed in as first parameter
     * a sales/order object, as second parameter core/session object and as turn parameter a string literal representing
     * the redirect URL. However, if the method ebayenterprise_order/cancel::process() throw an exception, then the method
     * EbayEnterprise_Order_OrderController::_handleRomCancelResponse() will never be invoked, but instead the method
     * EbayEnterprise_Order_OrderController::_handleRomCancelException() will be invoked and passed in as first parameter
     * and object of type Exception, as second parameter an object of type core/session, and as third parameter string literal
     * representing the redirect URL. Finally, the method EbayEnterprise_Order_OrderController::_processOrderCancelAction()
     * will return itself.
     *
     * @param bool
     * @param bool
     * @param Exception | null
     * @dataProvider providerProcessOrderCancelAction
     */
    public function testProcessOrderCancelAction($isException, $isRedirectSession, $exception)
    {
        /** @var string */
        $redirect = 'http://test.example.com/sales/order/romview/order_id/10008832231/';
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');

        /** @var EbayEnterprise_Eb2cCore_Helper_Data */
        $coreHelper = $this->getHelperMock('eb2ccore/data', ['getSdkApi']);
        $coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->will($this->returnValue($this->_api));

        /** @var Mock_EbayEnterprise_Order_Model_Cancel */
        $cancel = $this->getModelMock('ebayenterprise_order/cancel', ['process'], false, [[
            // This key is required
            'order' => $order,
            // This key is optional
            'core_helper' => $coreHelper,
        ]]);
        $cancel->expects($this->once())
            ->method('process')
            ->will($isException ? $this->throwException($exception) : $this->returnSelf());

        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_Mage_Core_Model_Session */
        $session = $this->getModelMockBuilder('core/session')
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->setMethods(['getCancelActionRefererUrl'])
            ->getMock();
        $session->expects($this->once())
            ->method('getCancelActionRefererUrl')
            ->will($this->returnValue($isRedirectSession ? $redirect : null));

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_getRomOrderToBeCanceled', '_getRomOrderCancelModel', '_getRomCancelSession', '_getRefererUrl', '_handleRomCancelException', '_handleRomCancelResponse'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('_getRomOrderToBeCanceled')
            ->will($this->returnValue($order));
        $controller->expects($this->once())
            ->method('_getRomOrderCancelModel')
            ->with($this->identicalTo($order))
            ->will($this->returnValue($cancel));
        $controller->expects($this->once())
            ->method('_getRomCancelSession')
            ->will($this->returnValue($session));
        $controller->expects($isRedirectSession? $this->never() : $this->once())
            // Proving that this method will never be called when the varien magic method
            // core/session::getCancelActionRefererUrl() returns a non-empty string.
            // When the method core/session::getCancelActionRefererUrl() return null
            // this method will be invoked once.
            ->method('_getRefererUrl')
            ->will($this->returnValue($redirect));
        $controller->expects($isException ? $this->once() : $this->never())
            // Proving that this method will never be invoked unless the method
            // ebayenterprise_order/cancel::process() thrown an exception, then it will be invoked once.
            ->method('_handleRomCancelException')
            ->with($this->identicalTo($exception), $this->identicalTo($session), $this->identicalTo($redirect))
            ->will($this->returnSelf());
        $controller->expects($isException ? $this->never() : $this->once())
            // Proving that this method will be invoked once unless the method
            // ebayenterprise_order/cancel::process() thrown an exception, then it will never be invoked.
            ->method('_handleRomCancelResponse')
            ->with($this->identicalTo($order), $this->identicalTo($session), $this->identicalTo($redirect))
            ->will($this->returnSelf());

        $this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_processOrderCancelAction', []));
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_showOrderCancelPage()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::_viewAction().
     * Then, the method EbayEnterprise_Order_OrderController::loadLayout() will be invoked. Then,
     * the method EbayEnterprise_Order_OrderController::_initLayoutMessages() will be called and passed
     * in as parameter the string literal 'catalog/session'. Then, the method
     * EbayEnterprise_Order_OrderController::renderLayout() will be called. Finally, the method
     * EbayEnterprise_Order_OrderController::_showOrderCancelPage() will return itself.
     */
    public function testShowOrderCancelPage()
    {
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_viewAction'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('_viewAction')
            ->will($this->returnValue(null));

        $this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_showOrderCancelPage', []));
    }

    /**
     * @return array
     */
    public function providerCanShowOrderCancelForm()
    {
        return [
            [true, true, false],
            [false, true, true],
        ];
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_canShowOrderCancelForm()
     * is invoked and when the method ebayenterprise_order/data::hasOrderCancelReason() is called and return true
     * and the method EbayEnterprise_Order_OrderController::_isOrderCancelPostAction() and return false will
     * the method EbayEnterprise_Order_OrderController::_canShowOrderCancelForm() return true. Otherwise, it
     * will return false.
     *
     * @param bool
     * @param bool
     * @param string
     * @dataProvider providerCanShowOrderCancelForm
     */
    public function testCanShowOrderCancelForm($isPost, $hasReason, $result)
    {
        /** @var EbayEnterprise_Order_Helper_Data */
        $orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['hasOrderCancelReason']);
        $orderHelper->expects($this->once())
            ->method('hasOrderCancelReason')
            ->will($this->returnValue($hasReason));

        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_isOrderCancelPostAction'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('_isOrderCancelPostAction')
            ->will($this->returnValue($isPost));

        // Replacing the protected class property EbayEnterprise_Order_OrderController::$_orderHelper with a mock.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($controller, '_orderHelper', $orderHelper);

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_canShowOrderCancelForm', []));
    }

    /**
     * @return array
     */
    public function providerIsOrderCancelPostAction()
    {
        return [
            [true, true, true],
            [true, false, false],
            [false, true, false],
            [false, false, false],
        ];
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_isOrderCancelPostAction()
     * is invoked and the method EbayEnterprise_Order_OrderController::getRequest() will be called and return
     * an instance object that extend the class Zend_Controller_Request_Abstract. Then, the controller method
     * EbayEnterprise_Order_OrderController::_isOrderCancelPostAction() will only return true when both
     * Zend_Controller_Request_Abstract::isPost() and Zend_Controller_Request_Abstract::getPost() passing in a string
     * literal 'cancel_action' return boolean true. Otherwise, the EbayEnterprise_Order_OrderController::_isOrderCancelPostAction()
     * method will always return false. Also, when the method Zend_Controller_Request_Abstract::isPost() return false
     * the method Zend_Controller_Request_Abstract::getPost() will never be called.
     *
     * @param bool
     * @param bool
     * @param bool
     * @dataProvider providerIsOrderCancelPostAction
     */
    public function testIsOrderCancelPostAction($isPost, $cancelAction, $result)
    {
        /** @var string */
        $param = 'cancel_action';

        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract', [], '', true, true, true, ['isPost', 'getPost']);
        $request->expects($this->once())
            ->method('isPost')
            ->will($this->returnValue($isPost));
        $request->expects($isPost? $this->once() : $this->never())
            // Proving that once the method Zend_Controller_Request_Abstract::isPost() return
            // false the method Zend_Controller_Request_Abstract::getPost() will never be invoked
            // since we are using logical AND operation for the condition.
            ->method('getPost')
            ->with($this->identicalTo($param))
            ->will($this->returnValue($cancelAction));

        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['getRequest'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_isOrderCancelPostAction', []));
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_getRomOrderToBeCanceled()
     * is invoked and the method EbayEnterprise_Order_OrderController::getRequest() will be called and return
     * an instance object that extend the class Zend_Controller_Request_Abstract. Then, an instance of type sales/order
     * will be instantiated. Then, the method Zend_Controller_Request_Abstract::getParam() will be called, passed in the string literal 'order_id'
     * and return the order id. This order id will be passed in sales/order::loadByIncrementId() method. Then, the method
     * Zend_Controller_Request_Abstract::getParam() will be called, passed in string the literal 'cancel_reason'
     * to the method sales/order::setCancelReasonCode(). Finally, the method EbayEnterprise_Order_OrderController::_getRomOrderToBeCanceled()
     * will return the sales/order object.
     */
    public function testGetRomOrderToBeCanceled()
    {
        /** @var null */
        $default = null;
        /** @var string */
        $orderId = '1000000083787831';
        /** @var string */
        $cancelReason = 'reason_code_001';

        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract', [], '', true, true, true, ['getParam']);
        $request->expects($this->exactly(2))
            ->method('getParam')
            ->will($this->returnValueMap([
                ['order_id', $default, $orderId],
                ['cancel_reason', $default, $cancelReason],
            ]));

        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_Mage_Sales_Model_Order */
        $order = $this->getModelMock('sales/order', ['loadByIncrementId', 'setCancelReasonCode']);
        $order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($this->identicalTo($orderId))
            ->will($this->returnSelf());
        $order->expects($this->once())
            ->method('setCancelReasonCode')
            ->with($this->identicalTo($cancelReason))
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'sales/order', $order);

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['getRequest'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->assertSame($order, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getRomOrderToBeCanceled', []));
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_getRomOrderCancelModel()
     * is invoked, it will be passed an instance of type sales/order and it will instantiate an instance of
     * type ebayenterprise_order/cancel, passed in the required array with key 'order' mapped to a sales/order
     * object to the constructor method and then return the ebayenterprise_order/cancel object.
     */
    public function testGetRomOrderCancelModel()
    {
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_Model_Cancel */
        $cancel = $this->getModelMock('ebayenterprise_order/cancel', [], false, [[
            // This key is required
            'order' => $order,
        ]]);
        $this->replaceByMock('model', 'ebayenterprise_order/cancel', $cancel);

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setConstructorArgs([$request, $response])
            ->getMock();

        $this->assertSame($cancel, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getRomOrderCancelModel', [$order]));
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_getRomCancelSession()
     * is invoked, it will instantiate an instance of type core/session and then return the core/session object.
     */
    public function testGetRomCancelSession()
    {
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_Mage_Core_Model_Session */
        $session = $this->getModelMockBuilder('core/session')
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setConstructorArgs([$request, $response])
            ->getMock();

        $this->assertSame($session, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_getRomCancelSession', []));
    }

    /**
     * @return array
     */
    public function providerHandleRomCancelResponse()
    {
        return [
            [Mage_Sales_Model_Order::STATE_CANCELED],
            [null],
        ];
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_handleRomCancelResponse()
     * is invoked, it will be passed as first parameter a sales/order object, as second parameter core/session
     * object, and as third parameter a string literal representing the redirect URL. Then, it will check if the
     * sales/order:getState() matched the class constant Mage_Sales_Model_Order::STATE_CANCELED, if it does, then
     * the method core/session::addSuccess() will be called passing in the translated and sprintf return value from
     * calling the method ebayenterprise_order/data::__() with class constant EbayEnterprise_Order_OrderController::CANCEL_SUCCESS_MESSAGE
     * pass its parameter. However, if the method sales/order:getState() doesn't matched the class constant
     * Mage_Sales_Model_Order::STATE_CANCELED, then, the method core/session::addError() will be called and passed in the translated
     * and sprintf return value from calling the method ebayenterprise_order/data::__() with class constant
     * EbayEnterprise_Order_OrderController::CANCEL_FAIL_MESSAGE. Then, the method
     * EbayEnterprise_Order_OrderController::_redirectUrl() will be invoked and passed in the parameter string
     * literal representing the redirect URL. Finally, the method EbayEnterprise_Order_OrderController::_handleRomCancelResponse()
     * will return itself.
     *
     *
     * @param string | null
     * @dataProvider providerHandleRomCancelResponse
     */
    public function testHandleRomCancelResponse($state)
    {
        /** @var bool */
        $isSuccess = !is_null($state);
        /** @var string */
        $incrementId = '11999383838111991';
        /** @var string */
        $translatedSuccessMessage = EbayEnterprise_Order_OrderController::CANCEL_SUCCESS_MESSAGE;
        /** @var string */
        $succesMessage = sprintf($translatedSuccessMessage, $incrementId);
        /** @var string */
        $translatedFailMessage = EbayEnterprise_Order_OrderController::CANCEL_FAIL_MESSAGE;
        /** @var string */
        $failMessage = sprintf($translatedFailMessage, $incrementId);
        /** @var string */
        $redirectUrl = "http://test.example.com/sales/order/order_id/{$incrementId}";
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order', [
            'increment_id' => $incrementId,
            'state' => $state,
        ]);
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var EbayEnterprise_Order_Helper_Data */
        $orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['__']);
        $orderHelper->expects($this->once())
            ->method('__')
            ->will($this->returnValueMap([
                [EbayEnterprise_Order_OrderController::CANCEL_SUCCESS_MESSAGE, $translatedSuccessMessage],
                [EbayEnterprise_Order_OrderController::CANCEL_FAIL_MESSAGE, $translatedFailMessage]
            ]));

        /** @var Mock_Mage_Core_Model_Session */
        $session = $this->getModelMockBuilder('core/session')
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->setMethods(['addSuccess', 'addError'])
            ->getMock();
        $session->expects($isSuccess ? $this->once() : $this->never())
            ->method('addSuccess')
            ->with($this->identicalTo($succesMessage))
            ->will($this->returnSelf());
        $session->expects($isSuccess ? $this->never() : $this->once())
            ->method('addError')
            ->with($this->identicalTo($failMessage))
            ->will($this->returnSelf());

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_redirectUrl'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('_redirectUrl')
            ->with($this->identicalTo($redirectUrl))
            ->will($this->returnSelf());
        // Replacing the protected class property EbayEnterprise_Order_OrderController::$_orderHelper with a mock.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($controller, '_orderHelper', $orderHelper);

        $this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_handleRomCancelResponse', [$order, $session, $redirectUrl]));
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_handleRomCancelException()
     * is invoked, it will be passed as first parameter an object of type Exception, as second parameter core/session
     * object, and as third parameter a string literal representing the redirect URL. Then, the method core/session::addError()
     * will be invoked and passed in as its parameter a return value from calling the method
     * ebayenterprise_order/data::__(), this method will be passed in the return value from calling the Exception::getMessage()
     * method. Then, the method EbayEnterprise_Order_OrderController::_redirectUrl() will be invoked and passed in the
     * parameter string literal representing the redirect URL. Finally, the method EbayEnterprise_Order_OrderController::_handleRomCancelResponse()
     * will return itself.
     */
    public function testHandleRomCancelException()
    {
        /** @var string */
        $message = 'Exception test message.';
        /** @var string */
        $translatedMessage = $message;
        /** @var Mage_Core_Exception */
        $exception = Mage::exception('Mage_Core', $message);
        /** @var string */
        $redirectUrl = "http://test.example.com/sales/order/order_id/1000001";
        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var EbayEnterprise_Order_Helper_Data */
        $orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['__']);
        $orderHelper->expects($this->once())
            ->method('__')
            ->with($this->identicalTo($message))
            ->will($this->returnValue($translatedMessage));

        /** @var Mock_Mage_Core_Model_Session */
        $session = $this->getModelMockBuilder('core/session')
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->setMethods(['addError'])
            ->getMock();
        $session->expects($this->once())
            ->method('addError')
            ->with($this->identicalTo($translatedMessage))
            ->will($this->returnSelf());

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['_redirectUrl'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $controller->expects($this->once())
            ->method('_redirectUrl')
            ->with($this->identicalTo($redirectUrl))
            ->will($this->returnSelf());
        // Replacing the protected class property EbayEnterprise_Order_OrderController::$_orderHelper with a mock.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($controller, '_orderHelper', $orderHelper);

        $this->assertSame($controller, EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_handleRomCancelException', [$exception, $session, $redirectUrl]));
    }

    /**
     * Test that the controller method EbayEnterprise_Order_OrderController::_displayPage()
     * is invoked and it will call the method EbayEnterprise_Order_OrderController::loadLayout() will be invoked. Then,
     * the method EbayEnterprise_Order_OrderController::_initLayoutMessages() will be called and passed
     * in as parameter the string literal 'catalog/session'. Then, the method EbayEnterprise_Order_OrderController::getLayout()
     * will be called and return an instance of the class Mage_Core_Model_Layout. Then,
     * the method Mage_Core_Model_Layout::getBlock() will be called and passed in as parameter the string
     * literal 'customer_account_navigation' and it will return an instance object that extend the abstract class
     * Mage_Core_Block_Abstract. Then, the method Mage_Core_Block_Abstract::setActive() will be called. Then, the method
     * EbayEnterprise_Order_OrderController::renderLayout() will be called. Finally, the method
     * EbayEnterprise_Order_OrderController::romViewAction() will return null.
     */
    public function testDisplayPage()
    {
        /** @var bool */
        $isLoggedIn = true;
        /** @var string */
        $messages = 'catalog/session';
        /** @var string */
        $blockName = 'customer_account_navigation';
        /** @var string */
        $path = 'sales/order/history';

        $this->_session->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue($isLoggedIn));

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getCustomerSession']);
        $factory->expects($this->once())
            ->method('getCustomerSession')
            ->will($this->returnValue($this->_session));

        /** @var Mage_Core_Block_Abstract */
        $block = $this->getMockForAbstractClass('Mage_Core_Block_Abstract', [], '', true, true, true, ['setActive']);
        $block->expects($this->once())
            ->method('setActive')
            ->with($this->identicalTo($path))
            ->will($this->returnSelf());

        /** @var Mage_Core_Model_Layout */
        $layout = $this->getModelMock('core/layout', ['getBlock']);
        $layout->expects($this->once())
            ->method('getBlock')
            ->with($this->identicalTo($blockName))
            ->will($this->returnValue($block));

        /** @var Mock_Zend_Controller_Request_Abstract */
        $request = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        /** @var Mock_Zend_Controller_Response_Abstract */
        $response = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

        /** @var Mock_EbayEnterprise_Order_OrderController */
        $controller = $this->getMockBuilder('EbayEnterprise_Order_OrderController')
            ->setMethods(['loadLayout', '_initLayoutMessages', 'getLayout', 'renderLayout'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        EcomDev_Utils_Reflection::setRestrictedPropertyValues($controller, [
            '_orderFactory' => $factory,
        ]);
        $controller->expects($this->once())
            ->method('loadLayout')
            ->will($this->returnSelf());
        $controller->expects($this->once())
            ->method('_initLayoutMessages')
            ->with($this->identicalTo($messages))
            ->will($this->returnSelf());
        $controller->expects($this->once())
            ->method('getLayout')
            ->will($this->returnValue($layout));
        $controller->expects($this->once())
            ->method('renderLayout')
            ->will($this->returnSelf());

        $this->assertNull(EcomDev_Utils_Reflection::invokeRestrictedMethod($controller, '_displayPage', []));
    }

    /**
     * @return array
     */
    public function providerIsValidOperation()
    {
        return [
            [null, false],
            ['10000938382811', true],
        ];
    }

    /**
     * @param string | null
     * @param bool
     * @dataProvider providerIsValidOperation
     */
    public function testIsValidOperation($orderId, $isValid)
    {
        $this->_request->expects($this->any())
            ->method('getParam')
            ->with($this->identicalTo('order_id'))
            ->will($this->returnValue($orderId));
        $this->assertSame($isValid, EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_controller, '_isValidOperation', []));
    }

    /**
     * Scenario: Display shipment tracking information
     * Given ROM order id
     * And shipment id
     * And tracking number
     * When displaying shipment tracking information
     * Then get ROM order detail in the registry
     * And store a ebayenterprise_order/tracking instance in the registry
     * And load and render the layout.
     */
    public function testRomtrackingpopupAction()
    {
        /** @var string */
        $shipmentId = 's0-000399399991111';
        /** @var string */
        $trackingNumber = '993999222';
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        Mage::unregister('rom_order');
        Mage::register('rom_order', $order);
        /** @var EbayEnterprise_Order_Model_Tracking */
        $trackingModel = $this->getModelMockBuilder('ebayenterprise_order/tracking')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EbayEnterprise_Order_Helper_Factory */
        $factory = $this->getHelperMock('ebayenterprise_order/factory', ['getNewTrackingModel']);
        $factory->expects($this->once())
            ->method('getNewTrackingModel')
            ->with($this->identicalTo($order), $this->identicalTo($shipmentId), $this->identicalTo($trackingNumber))
            ->will($this->returnValue($trackingModel));

        $this->_request->expects($this->exactly(2))
            ->method('getParam')
            ->will($this->returnValueMap([
                ['shipment_id', null, $shipmentId],
                ['tracking_number', null, $trackingNumber],
            ]));
        $this->_controller->expects($this->once())
            ->method('_loadValidOrder')
            ->will($this->returnValue(true));
        $this->_controller->expects($this->once())
            ->method('loadLayout')
            ->will($this->returnValue($this->getModelMock('core/layout')));
        $this->_controller->expects($this->once())
            ->method('renderLayout')
            ->will($this->returnSelf());
        EcomDev_Utils_Reflection::setRestrictedPropertyValues($this->_controller, [
            '_orderFactory' => $factory,
        ]);

        $this->assertNull($this->_controller->romtrackingpopupAction());
    }
}
