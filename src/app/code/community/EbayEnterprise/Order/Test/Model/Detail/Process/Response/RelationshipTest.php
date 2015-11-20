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

class EbayEnterprise_Order_Test_Model_Detail_Process_Response_RelationshipTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Scenario: Process bundle items using ROM Order Detail Relationship data
     * Given an ebayenterprise_order/detail_process_response instance
     * When processing bundle items for ROM Order Detail
     * Then get the relationship data from the ROM Order Detail response
     * And get all relationship data
     * And for each parent relation data group all children under the parent.
     */
    public function testRelationshipProcess()
    {
        /** @var array */
        $data = ['parent_bundle_id', 'child_item_1_id', 'child_item_2_id'];
        /** @var array */
        $bundles = [
            $data[0] => [
                $data[1],
                $data[2],
            ]
        ];
        /** @var Varien_Data_Collection */
        $items = new Varien_Data_Collection();
        foreach ($data as $refId) {
            $items->addItem(Mage::getModel('ebayenterprise_order/detail_process_response_item', ['ref_id' => $refId]));
        }

        /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationshipIterable */
        $itemRelationships = $this->getMockForAbstractClass('eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationshipIterable');

        /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IOrderResponse */
        $responseOrder = $this->getMockForAbstractClass('eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IOrderDetailResponse', [], '', true, true, true, ['getItemRelationships']);
        $responseOrder->expects($this->once())
            ->method('getItemRelationships')
            ->will($this->returnValue($itemRelationships));

        /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IOrderDetailResponse */
        $response = $this->getMockForAbstractClass('eBayEnterprise\RetailOrderManagement\Payload\Order\Detail\IOrderDetailResponse', [], '', true, true, true, ['getOrder']);
        $response->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($responseOrder));

        /** @var EbayEnterprise_Order_Model_Detail_Process_IResponse */
        $order = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response')
            ->disableOriginalConstructor()
            ->setMethods(['getItemsCollection', 'getResponse'])
            ->getMock();
        $order->expects($this->once())
            ->method('getItemsCollection')
            ->will($this->returnValue($items));
        $order->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMock('ebayenterprise_order/detail_process_response_relationship', ['extractBundleItems', 'groupBundle'], false, [[
            'order' => $order,
        ]]);
        $relationship->expects($this->once())
            ->method('extractBundleItems')
            ->with($this->identicalTo($itemRelationships))
            ->will($this->returnValue($bundles));
        $relationship->expects($this->once())
            ->method('groupBundle')
            ->with($this->identicalTo($items->setPageSize(1)->getFirstItem()), $this->identicalTo([$data[1], $data[2]]))
            ->will($this->returnSelf());

        $this->assertSame($relationship, $relationship->process());
    }

    /**
     * Scenario: Extract bundle items from ROM Order Detail
     * Given an eBayEnterprise\RetailOrderManagement\Payload\Order\ItemRelationshipIterable instance
     * When extracting bundle items from ROM Order Detail
     * Then build an array of parent bundle items mapped to bundle child items.
     */
    public function testExtractBundleItems()
    {
        /** @var array */
        $data = ['parent_bundle_id', 'child_item_1_id', 'child_item_2_id'];
        /** @var array */
        $bundles = [
            $data[0] => [
                $data[1],
                $data[2],
            ]
        ];
        /** @var eBayEnterprise\RetailOrderManagement\Payload\IValidator */
        $stubValidator = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\IValidator');
        /** @var eBayEnterprise\RetailOrderManagement\Payload\ValidatorIterator */
        $validatorIterator = new eBayEnterprise\RetailOrderManagement\Payload\ValidatorIterator([$stubValidator]);
        /** @var eBayEnterprise\RetailOrderManagement\Payload\ISchemaValidator */
        $stubSchemaValidator = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\ISchemaValidator');
        /** @var eBayEnterprise\RetailOrderManagement\Payload\PayloadMap */
        $payloadMap = new eBayEnterprise\RetailOrderManagement\Payload\PayloadMap;
        /** @var Psr\Log\NullLogger */
        $logger = new Psr\Log\NullLogger;
        /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationshipIterable */
        $itemRelationships = new eBayEnterprise\RetailOrderManagement\Payload\Order\ItemRelationshipIterable($validatorIterator, $stubSchemaValidator, $payloadMap, $logger);

        /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReferenceIterable */
        $itemReferences = $this->getMockForAbstractClass('eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReferenceIterable');

        /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationship */
        $itemRelationship = $this->getMockForAbstractClass('eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationship', [], '', true, true, true, [
            'getParentItemId', 'getItemReferences'
        ]);
        $itemRelationships[$itemRelationship] = null;
        $itemRelationship->expects($this->once())
            ->method('getParentItemId')
            ->will($this->returnValue($data[0]));
        $itemRelationship->expects($this->once())
            ->method('getItemReferences')
            ->will($this->returnValue($itemReferences));

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(['extractBundleChildItems'])
            ->getMock();
        $relationship->expects($this->once())
            ->method('extractBundleChildItems')
            ->with($this->identicalTo($itemReferences))
            ->will($this->returnValue([$data[1], $data[2]]));

        $this->assertSame($bundles, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'extractBundleItems', [$itemRelationships]));
    }

    /**
     * Scenario: Extract bundle child items from Order Detail
     * Given an eBayEnterprise\RetailOrderManagement\Payload\Order\OrderItemReferenceIterable instance
     * When extracting bundle child items from Order Detail
     * Then build an array of child bundle items
     */
    public function testExtractBundleChildItems()
    {
        /** @var array */
        $childBundleItems = [];
        /** @var array */
        $data = ['child_item_1_id', 'child_item_2_id'];
        /** @var eBayEnterprise\RetailOrderManagement\Payload\IValidator */
        $stubValidator = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\IValidator');
        /** @var eBayEnterprise\RetailOrderManagement\Payload\ValidatorIterator */
        $validatorIterator = new eBayEnterprise\RetailOrderManagement\Payload\ValidatorIterator([$stubValidator]);
        /** @var eBayEnterprise\RetailOrderManagement\Payload\ISchemaValidator */
        $stubSchemaValidator = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\ISchemaValidator');
        /** @var eBayEnterprise\RetailOrderManagement\Payload\PayloadMap */
        $payloadMap = new eBayEnterprise\RetailOrderManagement\Payload\PayloadMap;
        /** @var Psr\Log\NullLogger */
        $logger = new Psr\Log\NullLogger;
        /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReferenceIterable */
        $itemReferences = new eBayEnterprise\RetailOrderManagement\Payload\Order\OrderItemReferenceIterable($validatorIterator, $stubSchemaValidator, $payloadMap, $logger);

        /** @var Varien_Data_Collection */
        $items = new Varien_Data_Collection();
        foreach ($data as $refId) {
            $item = Mage::getModel('ebayenterprise_order/detail_process_response_item', ['ref_id' => $refId]);
            $childBundleItems[$refId] = $item;
            $items->addItem($item);
            /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReference */
            $itemReference = $this->getMockForAbstractClass('eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReference', [], '', true, true, true, [
                'getReferencedItemId'
            ]);
            $itemReference->expects($this->once())
                ->method('getReferencedItemId')
                ->will($this->returnValue($refId));
            $itemReferences[$itemReference] = null;
        }

        /** @var EbayEnterprise_Order_Model_Detail_Process_IResponse */
        $order = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response')
            ->disableOriginalConstructor()
            ->setMethods(['getItemsCollection'])
            ->getMock();
        $order->expects($this->once())
            ->method('getItemsCollection')
            ->will($this->returnValue($items));

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMock('ebayenterprise_order/detail_process_response_relationship', ['foo'], false, [[
            'order' => $order,
        ]]);

        $this->assertSame($childBundleItems, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'extractBundleChildItems', [$itemReferences]));
    }

    /**
     * Scenario: Group bundle items under its parent
     * Given an ebayenterprise_order/detail_process_response_item instance
     * And an array of extracted bundle child data
     * When grouping bundle items under its parent
     * Then get bundle configuration data from the bundle product
     * And build bundle option using the extracted bundle child data and bundle product configurable data
     * And add bundle option data to the parent bundle item
     * And, finally, remove child bundle items
     */
    public function testGroupBundle()
    {
        /** @var array */
        $data = ['child_item_1_id', 'child_item_2_id'];
        /** @var array */
        $bundleData = [
            'Option Label 1' => [
                'sku1' => 'Item Title 1',
                'sku2' => 'Item Title 2',
            ],
            'Option Label 2' => [
                'sku3' => 'Item Title 3',
            ]
        ];
        /** @var array */
        $optionData = [
            'options' => [
                [
                    'label' => 'Option Label 1',
                    'value' => "3 x Item Title 1\n2 x Item Title 2\n",
                ],
                [
                    'label' => 'Option Label 2',
                    'value' => "1 x Item Title 3\n",
                ],
            ],
        ];
        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item */
        $item = $this->getModelMock('ebayenterprise_order/detail_process_response_item', ['setProductOptions']);
        $item->expects($this->once())
            ->method('setProductOptions')
            ->with($this->identicalTo($optionData))
            ->will($this->returnSelf());

        /** @var Mage_Bundle_Model_Product_Type */
        $bundleProductType = Mage::getModel('bundle/product_type');
        /** @var Mage_Catalog_Model_Product */
        $product = $this->getModelMock('catalog/product', ['getTypeInstance']);
        $product->expects($this->once())
            ->method('getTypeInstance')
            ->will($this->returnValue($bundleProductType));

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(['getBundleProduct', 'getBundleParentData', 'buildBundleOptions', 'removeBundleChildItems'])
            ->getMock();
        $relationship->expects($this->once())
            ->method('getBundleProduct')
            ->with($this->identicalTo($item))
            ->will($this->returnValue($product));
        $relationship->expects($this->once())
            ->method('getBundleParentData')
            ->with($this->identicalTo($bundleProductType))
            ->will($this->returnValue($bundleData));
        $relationship->expects($this->once())
            ->method('buildBundleOptions')
            ->with($this->identicalTo($data), $this->identicalTo($bundleData))
            ->will($this->returnValue($optionData));
        $relationship->expects($this->once())
            ->method('removeBundleChildItems')
            ->with($this->identicalTo($data))
            ->will($this->returnSelf());

        $this->assertSame($relationship, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'groupBundle', [$item, $data]));
    }

    /**
     * Scenario: Get bundle product
     * Given an ebayenterprise_order/detail_process_response_item instance
     * When getting the bundle product
     * Then load the product by sku using the sku in the item.
     */
    public function testGetBundleProduct()
    {
        /** @var string */
        $sku = 'ABC123';
        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item */
        $item = Mage::getModel('ebayenterprise_order/detail_process_response_item', ['sku' => $sku]);
        /** @var Mage_Catalog_Model_Product */
        $product = $this->getModelMock('catalog/product', ['loadByAttribute']);
        $product->expects($this->once())
            ->method('loadByAttribute')
            ->with($this->identicalTo('sku'), $this->identicalTo($sku))
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'catalog/product', $product);

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->assertSame($product, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'getBundleProduct', [$item]));
    }

    /**
     * Scenario: Get parent bundle data
     * Given a bundle/product_type instance
     * When getting parent bundle data
     * Then get all configurable option data as an array of data
     */
    public function testGetBundleParentData()
    {
        /** @var array */
        $ids = [1];
        /** @var string */
        $optionTitle = 'Option Label 1';
        /** @var array */
        $optionData = [
            'sku1' => 'Item Title 1',
        ];
        /** @var array */
        $bundleData = [
            $optionTitle => $optionData,
        ];

        /** @var Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product', ['sku' => 'sku1', 'name' => 'Item Title 1']);

        /** @var Mage_Bundle_Model_Resource_Selection_Collection */
        $selections = $this->getResourceModelMockBuilder('bundle/selection_collection')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        /** @var Mage_Bundle_Model_Option */
        $option = $this->getModelMock('bundle/option', ['getSelections', 'getDefaultTitle']);
        $option->expects($this->once())
            ->method('getSelections')
            ->will($this->returnValue([$product]));
        $option->expects($this->once())
            ->method('getDefaultTitle')
            ->will($this->returnValue($optionTitle));

        /** @var Mage_Bundle_Model_Resource_Option_Collection*/
        $options = $this->getResourceModelMock('bundle/option_collection', ['appendSelections']);
        $options->expects($this->once())
            ->method('appendSelections')
            ->with($this->identicalTo($selections))
            ->will($this->returnValue([$option]));

        /** @var Mage_Bundle_Model_Product_Type */
        $bundleProductType = $this->getModelMock('bundle/product_type', ['getOptionsCollection', 'getOptionsIds', 'getSelectionsCollection']);
        $bundleProductType->expects($this->once())
            ->method('getOptionsCollection')
            ->will($this->returnValue($options));
        $bundleProductType->expects($this->once())
            ->method('getOptionsIds')
            ->will($this->returnValue($ids));
        $bundleProductType->expects($this->once())
            ->method('getSelectionsCollection')
            ->with($this->identicalTo($ids))
            ->will($this->returnValue($selections));

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(['getBundleItemData'])
            ->getMock();
        $relationship->expects($this->once())
            ->method('getBundleItemData')
            ->with($this->identicalTo([$product]))
            ->will($this->returnValue($optionData));

        $this->assertSame($bundleData, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'getBundleParentData', [$bundleProductType]));
    }

    /**
     * Scenario: Get item bundle data
     * Given an array of products
     * When getting item bundle data
     * Then return an array of key sku mapped to product name
     */
    public function testGetBundleItemData()
    {
        /** @var array */
        $result = [
            'sku1' => 'Item Title 1',
        ];

        /** @var Mage_Catalog_Model_Product[] */
        $products = [Mage::getModel('catalog/product', ['sku' => 'sku1', 'name' => 'Item Title 1'])];

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'getBundleItemData', [$products]));
    }

    /**
     * Scenario: Build bundle options
     * Given an array of bundle items
     * And an array of parent bundle data
     * When building bundle options
     * Then return an array with key options map to an array of key labels and key values.
     */
    public function testBuildBundleOptions()
    {
        /** @var array */
        $options = [
            [
                'label' => 'Option Label 1',
                'value' => "Option Value 1\n",
            ],
        ];

        /** @var array */
        $bundleParentData = [];

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item[] */
        $data = [Mage::getModel('ebayenterprise_order/detail_process_response_item')];

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(['extractBundleOptions'])
            ->getMock();
        $relationship->expects($this->once())
            ->method('extractBundleOptions')
            ->with($this->identicalTo([]), $this->identicalTo($data[0]), $this->identicalTo($bundleParentData))
            ->will($this->returnValue($options));

        $this->assertSame(['options' => $options], EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'buildBundleOptions', [$data, $bundleParentData]));
    }

    /**
     * Scenario: Remove child bundle items
     * Given an array that contains keys of child bundle items ids
     * When removing child bundle items
     * Then for each child item ids in the item collection remove the item from the collection
     */
    public function testRemoveBundleChildItems()
    {
        /** @var array */
        $data = [
            'child_item_1_id' => null,
            'child_item_2_id' => null,
        ];

        /** @var Varien_Data_Collection */
        $items = new Varien_Data_Collection();
        foreach (array_keys($data) as $refId) {
            $items->addItem(Mage::getModel('ebayenterprise_order/detail_process_response_item', ['ref_id' => $refId]));
        }

        /** @var EbayEnterprise_Order_Model_Detail_Process_IResponse */
        $order = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response')
            ->disableOriginalConstructor()
            ->setMethods(['getItemsCollection'])
            ->getMock();
        $order->expects($this->once())
            ->method('getItemsCollection')
            ->will($this->returnValue($items));

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMock('ebayenterprise_order/detail_process_response_relationship', ['foo'], false, [[
            'order' => $order,
        ]]);

        // Proving that before invoking the method ebayenterprise_order/detail_process_response_relationship::removeBundleChildItems()
        // The collection have two element.
        $this->assertCount(2, $items);

        $this->assertSame($relationship, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'removeBundleChildItems', [$data]));

        // Proving that after invoking the method ebayenterprise_order/detail_process_response_relationship::removeBundleChildItems()
        // The collection have zero element.
        $this->assertCount(0, $items);
    }

    /**
     * Scenario: Extract bundle options
     * Given options array
     * And an ebayenterprise_order/detail_process_response_item instance
     * And an array of parent bundle data
     * When extracting bundle options
     * Then return an array of bundle options
     */
    public function testExtractBundleOptions()
    {
        /** @var string */
        $label = 'Option Label 1';
        /** @var array */
        $options = [
            [
                'label' => $label,
                'value' => "Option Value 1\n",
            ],
        ];
        /** @var array */
        $data = [
            'sku1' => 'Option Value 1',
        ];

        /** @var array */
        $bundleParentData = [
            $label => $data,
        ];

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item[] */
        $childItem = Mage::getModel('ebayenterprise_order/detail_process_response_item');

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(['extractBundleOption'])
            ->getMock();
        $relationship->expects($this->once())
            ->method('extractBundleOption')
            ->with($this->identicalTo([]), $this->identicalTo($childItem), $this->identicalTo($label), $this->identicalTo($data))
            ->will($this->returnValue($options));

        $this->assertSame($options, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'extractBundleOptions', [[], $childItem, $bundleParentData]));
    }

    /**
     * @return array
     */
    public function providerExtractBundleOption()
    {
        return [
            [
                [
                    [
                        'label' => 'Option Label 1',
                        'value' => "Option Value 1\n",
                    ],
                ],
                "Option Value 2\n",
                [
                    [
                        'label' => 'Option Label 1',
                        'value' => "Option Value 1\nOption Value 2\n",
                    ],
                ]

            ],
            [
                [],
                "Option Value 1\n",
                [
                    [
                        'label' => 'Option Label 1',
                        'value' => "Option Value 1\n",
                    ],
                ]
            ]
        ];
    }

    /**
     * Scenario: Extract bundle option
     * Given options array
     * And an ebayenterprise_order/detail_process_response_item instance
     * And an array of parent bundle data
     * And an option label
     * When extracting bundle option
     * Then return an array of bundle options
     *
     * @param array
     * @param string
     * @param array
     * @dataProvider providerExtractBundleOption
     */
    public function testExtractBundleOption(array $options, $newValue, array $result)
    {
        /** @var string */
        $label = 'Option Label 1';
        /** @var array */
        $data = [
            'sku1' => 'Option Value 1',
            'sku2' => 'Option Value 2',
        ];

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item[] */
        $childItem = Mage::getModel('ebayenterprise_order/detail_process_response_item');

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(['getBundleOptionValue'])
            ->getMock();
        $relationship->expects($this->once())
            ->method('getBundleOptionValue')
            ->with($this->identicalTo($childItem), $this->identicalTo($data))
            ->will($this->returnValue($newValue));

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'extractBundleOption', [$options, $childItem, $label, $data]));
    }

    /**
     * @return array
     */
    public function providerGetBundleOptionValue()
    {
        return [
            [
                [
                    'sku1' => null,
                    'sku2' => null,
                ],
                "3 x Item Title\n",
            ],
            [
                [],
                null,
            ],
        ];
    }

    /**
     * Scenario: Get bundle option value
     * Given an ebayenterprise_order/detail_process_response_item instance
     * And an array of data
     * When getting bundle option value
     * Then return a string contains the number of item ordered and child bundle item name
     *
     * @param array
     * @param string
     * @dataProvider providerGetBundleOptionValue
     */
    public function testGetBundleOptionValue(array $data, $result)
    {
        /** @var string */
        $result = "3 x Item Title\n";
        /** @var array */
        $data = [
            'sku1' => null,
            'sku2' => null,
        ];

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Item */
        $childItem = Mage::getModel('ebayenterprise_order/detail_process_response_item', [
            'qty_ordered' => 3,
            'sku' => 'sku1',
            'name' => 'Item Title'
        ]);

        /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Relationship */
        $relationship = $this->getModelMockBuilder('ebayenterprise_order/detail_process_response_relationship')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($relationship, 'getBundleOptionValue', [$childItem, $data]));
    }
}
