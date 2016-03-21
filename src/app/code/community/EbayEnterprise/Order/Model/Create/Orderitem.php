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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IPriceGroup;

/**
 * Builds out order items for order create request
 */
class EbayEnterprise_Order_Model_Create_Orderitem
{
    /** @var EbayEnterprise_Eb2cCore_Helper_Shipping */
    protected $shippingHelper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Discount */
    protected $discountHelper;

    /**
     * inject dependencies on construction
     * @param array
     */
    public function __construct(array $args = [])
    {
        list($this->shippingHelper, $this->discountHelper) =
            $this->checkTypes(
                $this->nullCoalesce('shipping_helper', $args, Mage::helper('eb2ccore/shipping')),
                $this->nullCoalesce('discount_helper', $args, Mage::helper('eb2ccore/discount'))
            );
    }

    /**
     * ensure correct types are being injected
     * @param  EbayEnterprise_Eb2cCore_Helper_Shipping
     * @param  EbayEnterprise_Eb2cCore_Helper_Discount
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Eb2cCore_Helper_Shipping $shippingHelper,
        EbayEnterprise_Eb2cCore_Helper_Discount $discountHelper
    ) {
        return func_get_args();
    }

    /**
     * return $ar[$key] if it exists otherwise return $default
     * @param  string
     * @param  array
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce($key, array $ar, $default)
    {
        return isset($ar[$key]) ? $ar[$key] : $default;
    }

    /**
     * build out the order item payload
     * @param  IOrderItem
     * @param  Mage_Sales_Model_Order_Item
     * @param  Mage_Sales_Model_Order_Address
     * @param  int
     * @param  string
     * @param  bool
     * @return IOrderItem
     */
    public function buildOrderItem(
        IOrderItem $payload,
        Mage_Sales_Model_Order_Item $item,
        Mage_Sales_Model_Order_Address $address,
        $lineNumber,
        $includeShipping = false
    ) {
        $merch = $payload->getMerchandisePricing();
        $this->prepareMerchandisePricing($item, $merch);
        if ($includeShipping) {
            $this->prepareShippingPriceGroup($address, $payload);
        }
        list($itemSize, $itemSizeId) = $this->getItemSizeInfo($item);
        list($itemColor, $itemColorId) = $this->getItemColorInfo($item);
        $payload
            ->setLineNumber($lineNumber)
            ->setItemId($item->getSku())
            ->setQuantity($item->getQtyOrdered())
            ->setDescription($item->getName())
            ->setMerchandisePricing($merch)
            ->setColor($itemColor)
            ->setColorId($itemColorId)
            ->setSize($itemSize)
            ->setSizeId($itemSizeId)
            ->setDepartment($item->getProduct()->getDepartment())
            ->setShippingMethod($this->getItemShippingMethod($item, $address))
            ->setShippingMethodDisplayText($this->getItemShippingDescription($item, $address))
            ->setVendorId($item->getProduct()->getDropShipSupplierNumber())
            ->setVendorName($item->getProduct()->getDropShipSupplierName())
            // this is set here as a default; it is expected that the ISPU/STS module
            // will update this value through the order item event
            ->setFulfillmentChannel($payload::FULFILLMENT_CHANNEL_SHIP_TO_HOME);
        return $this->addGiftCardDataToPayload($payload, $item);
    }

    /**
     * Supply gift card fields
     *
     * @param IOrderItem
     * @param Mage_Sales_Model_Order_Item
     * @return $payload
     */
    protected function addGiftCardDataToPayload(IOrderItem $payload, Mage_Sales_Model_Order_Item $item)
    {
        if ($item->getProductType() === 'giftcard') {
            // Gift card fields are XML escaped by the SDK, so they do not need
            // to be escaped here.
            $buyRequest = $item->getBuyRequest();
            $from = $this->formatGiftCardName($buyRequest->getGiftcardSenderName(), $buyRequest->getGiftcardSenderEmail());
            $to = $this->formatGiftCardName($buyRequest->getGiftcardRecipientName(), $buyRequest->getGiftcardRecipientEmail());
            $message = $buyRequest->getGiftcardMessage();
            $payload
                ->setGiftCardFrom($from)
                ->setGiftCardTo($to)
                ->setGiftCardMessage($message);
        }
        return $payload;
    }

    /**
     * Format the sender/receiver name and possibly email address.
     *
     * Email address may not be provided in the case of physical or combined
     * gift cards. Formatting needs to handle cases where there is no email.
     *
     * @param string
     * @param string|null
     * @return string
     */
    protected function formatGiftCardName($name, $email = null)
    {
        $format = $email ? '"%s" <%s>' : '"%s"';
        return sprintf($format, $name, $email);
    }

    /**
     * fillout the merchandise price group payload for the order item
     * @param  Mage_Sales_Model_Order_Item
     * @param  IPriceGroup
     * @return self
     */
    protected function prepareMerchandisePricing(Mage_Sales_Model_Order_Item $item, IPriceGroup $merch)
    {
        if (!$this->canIncludeAmounts($item)) {
            return $this;
        }
        $merch
            ->setAmount($item->getRowTotal())
            ->setUnitPrice($item->getPrice());
        $this->discountHelper->transferDiscounts($item, $merch);
        return $this;
    }

    /**
     * determine if the item's amounts should be put into the request.
     *
     * @param Mage_Sales_Model_Order_Item
     * @return bool
     */
    protected function canIncludeAmounts(Mage_Sales_Model_Order_Item $item)
    {
        return !(
            // only the parent item will have the bundle product type
            $item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
            && $item->isChildrenCalculated()
        );
    }

    /**
     * fillout the shipping price group payload for the order item
     * @param  Mage_Sales_Model_Order_Address
     * @param  IOrderItem
     * @return self
     */
    protected function prepareShippingPriceGroup(Mage_Sales_Model_Order_Address $address, IOrderItem $payload)
    {
        $shippingPriceGroup = $payload->getEmptyPriceGroup();
        $shippingPriceGroup->setAmount((float) $address->getShippingAmount());
        $this->discountHelper->transferDiscounts($address, $shippingPriceGroup);
        $payload->setShippingPricing($shippingPriceGroup);
        return $this;
    }

    /**
     * load option data for $item
     *
     * @param  Mage_Sales_Model_Order_Item
     * @return Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection
     */
    protected function loadOrderItemOptions(Mage_Sales_Model_Order_Item $item)
    {
        $buyRequest = $item->getProductOptionByCode('info_buyRequest');
        $attrs = isset($buyRequest['super_attribute']) ? $buyRequest['super_attribute'] : [];
        $attrTable = ['attribute_table' => Mage::getSingleton('core/resource')->getTableName('eav/attribute')];
        $options = Mage::getResourceModel('eav/entity_attribute_option_collection');
        // join with the attribute table to get the attribute code.
        $options->getSelect()->join(
            $attrTable,
            'main_table.attribute_id=attribute_table.attribute_id',
            ['attribute_code']
        );
        $options->setStoreFilter($item->getStoreId());
        $options->addFieldToFilter('main_table.option_id', ['in' => array_values($attrs)]);
        return $options;
    }

    /**
     * get the selected option's default and localized values of the item's
     * attribute
     * @param  string
     * @param  Mage_Sales_Model_Order_Item
     * @return array
     */
    protected function getOptionInfo($attributeCode, Mage_Sales_Model_Order_Item $item)
    {
        $options = $this->loadOrderItemOptions($item);
        $option = $options->getItemByColumnValue('attribute_code', $attributeCode);
        if (!$option || ($option->getValue() && !$option->getDefaultValue())) {
            return [null, null];
        }
        return [$option->getValue(), $option->getDefaultValue()];
    }

    /**
     * get the selected default and localized value for the color attribute
     * @param  Mage_Sales_Model_Order_Item
     * @return array
     */
    protected function getItemColorInfo(Mage_Sales_Model_Order_Item $item)
    {
        // use the code to get the right data.
        return $this->getOptionInfo('color', $item);
    }

    /**
     * get the selected default and localized value for the size attribute
     * @param  Mage_Sales_Model_Order_Item
     * @return array
     */
    protected function getItemSizeInfo(Mage_Sales_Model_Order_Item $item)
    {
        return $this->getOptionInfo('size', $item);
    }

    /**
     * Get the shipping method id to use for an item. Takes into account if the
     * item is a physical or virtual item.
     *
     * @param Mage_Sales_Model_Order_Item
     * @param Mage_Sales_Model_Order_Address
     * @return string
     */
    protected function getItemShippingMethod(
        Mage_Sales_Model_Order_Item $item,
        Mage_Sales_Model_Order_Address $address
    ) {
        return $item->getIsVirtual()
            ? $this->shippingHelper->getVirtualMethodSdkId()
            : $this->shippingHelper->getMethodSdkId($address->getShippingMethod());
    }

    /**
     * Get the shipping description to use for an item. Takes into account if
     * the item is a physical or virtual item.
     *
     * @param Mage_Sales_Model_Order_Item
     * @param Mage_Sales_Model_Order_Address
     * @return string
     */
    protected function getItemShippingDescription(
        Mage_Sales_Model_Order_Item $item,
        Mage_Sales_Model_Order_Address $address
    ) {
        return $item->getIsVirtual()
            ? $this->shippingHelper->getVirtualMethodDescription()
            : $address->getShippingDescription();
    }
}
