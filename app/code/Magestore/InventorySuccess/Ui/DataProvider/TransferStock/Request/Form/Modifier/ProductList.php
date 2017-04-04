<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\InventorySuccess\Ui\DataProvider\TransferStock\Request\Form\Modifier;

use Magento\Ui\Component\Form;
use Magestore\InventorySuccess\Model\TransferStock;
use Magento\Ui\Component\DynamicRows;
use Magestore\InventorySuccess\Api\Data\TransferStock\TransferPermission;

/**
 * Class Related
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductList extends \Magestore\InventorySuccess\Ui\DataProvider\TransferStock\Request\Form\Modifier\RequestStock
{
    protected $_sortOrder = '4';

    protected $_dataLinks = 'request_products';
    protected $_groupContainer = 'requeststock';


    protected $_groupLabel = 'Product List';


    protected $_fieldsetContent = 'Please add or import products to request stock';
    

    protected $_buttonTitle = 'Select Products';


    protected $_modalTitle = 'Add Products to Request Stock';


    protected $_modalButtonTitle = 'Add Selected Products';

    protected $_modifierConfig = [
        'button_set' => 'product_stock_button_set',
        'modal' => 'product_stock_modal',
        'listing' => 'os_transferstock_warehouse_product_stock_listing',
        'form' => 'transferstock_request_form',
        'columns_ids' => 'product_columns.ids'
    ];

    protected $_mapFields = [
        'id' => 'entity_id',
        'sku' => 'sku',
        'name' => 'name',
        'qty' => 'available_qty',
        'request_qty' => 'request_qty'
    ];

    protected $_modalDataId = 'transferstock_id';


    public function getVisible(){

        $transferstock_id = $this->request->getParam('id');
        if($transferstock_id){
            $transferStock = $this->_transferStockFactory->create()->load($transferstock_id);
            if($transferStock->getStatus() == TransferStock::STATUS_PENDING ){
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        if(!$this->getVisible()){
            return $data;
        }
        $locator = $this->_locatorFactory->create();
        $transferstockId = $this->request->getParam('id');
        if ($transferstockId) {

            $transferStock = $this->_transferStockFactory->create()->load($transferstockId);
            $warehouseId = $transferStock->getSourceWarehouseId();
            /** \Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProduct\CollectionFactory */
            $request_products =   $locator->getSesionByKey("request_products");
            if(count($request_products)){
                $data[$transferstockId]['links'][$this->_dataLinks] = $request_products;

            }
            else{
                $collection = $this->_collectionFactory->create();
                $products = $collection->getTransferStockProduct($transferstockId,$warehouseId);

                $data[$transferstockId]['links'][$this->_dataLinks] = [];
                foreach ($products as $product) {
                    $data[$transferstockId]['links'][$this->_dataLinks][] = $this->fillDynamicData($product);
                }
            }
        }
        $locator->refreshSessionByKey("request_products");
        return $data;
    }

    public function getTransferStockProducts(){
        $transferstockId = $this->request->getParam('id');
        $transferStock = $this->_transferStockFactory->create()->load($transferstockId);
        $warehouseId = $transferStock->getSourceWarehouseId();
        $collection = $this->_collectionFactory->create();
        $products = $collection->getTransferStockProduct($transferstockId,$warehouseId);

        return $products;
    }

    public function isShowColumnHeader(){
        $transferstock_id = $this->request->getParam('id');
        if ($transferstock_id) {
            $locator = $this->_locatorFactory->create();
            $transferStock = $this->_transferStockFactory->create()->load($transferstock_id);
            $warehouseId = $transferStock->getSourceWarehouseId();
            /** \Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProduct\CollectionFactory */
            $request_products =   $locator->getSesionByKey("request_products");
            
            if(count($request_products)){
                return true;
            }
            else{
                $products = $this->getTransferStockProducts();
                if(count($products)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns dynamic rows configuration
     *
     * @return array
     */
    protected function getDynamicGrid()
    {
        $grid = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__field-wide',
                        'componentType' => DynamicRows::NAME,
                        'label' => null,
                        'renderDefaultRecord' => false,
                        'template' => 'ui/dynamic-rows/templates/grid',
                        'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
                        'addButton' => false,
                        'itemTemplate' => 'record',
                        'dataScope' => 'data.links',
                        'deleteButtonLabel' => __('Remove'),
                        'dataProvider' => $this->_modifierConfig['listing'],
                        'map' => $this->_mapFields,
                        'links' => ['insertData' => '${ $.provider }:${ $.dataProvider }'],
                        'sortOrder' => 20,
                        'columnsHeader' => $this->isShowColumnHeader(),
                        'columnsHeaderAfterRender' => true,
                    ],
                ],
            ],
            'children' => $this->getRows(),
        ];
        return $grid;
    }

    /**
     * Retrieve child meta configuration
     *
     * @return array
     */
    protected function getModifierChildren()
    {
        if($this->_permissionManagement->checkPermission(TransferPermission::REQUEST_STOCK_ADD_PRODUCT)){
            $children = [
                $this->_modifierConfig['button_set'] => $this->getCustomButtons(),
                $this->_modifierConfig['modal'] => $this->getCustomModal(),
                $this->_dataLinks => $this->getDynamicGrid(),
            ];
            /**
             * @var \Magento\Framework\Module\Manager $moduleManager
             */
            $moduleManager = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Magento\Framework\Module\Manager');
            if($moduleManager->isEnabled('Magestore_BarcodeSuccess')){
                $children['product_barcode_scan_input'] = $this->getProductScanBarcodeInput();
            }
        }
        else{
            $children = [
                $this->_modifierConfig['modal'] => $this->getCustomModal(),
                $this->_dataLinks => $this->getDynamicGrid(),
            ];
        }

        return $children;
    }

    /**
     * Return scan barcode input
     *
     * @return array
     */
    public function getProductScanBarcodeInput(){
        $transferstockId = $this->request->getParam('id');
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => \Magento\Ui\Component\Container::NAME,
                        'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                        'component' => 'Magestore_InventorySuccess/js/form/element/barcode',
                        'label' => false,
                        'sortOrder' => 15,
                        'placeholder' => __('Scan product barcode here'),
                        'barcodeJson' => $this->_transferStockManagement->getSelectProductListJson($transferstockId),
                        'sourceElement' => 'index = ' . $this->_modifierConfig['listing'],
                        'destinationElement' => $this->_modifierConfig['form'] . '.' .
                            $this->_modifierConfig['form'] . '.' .
                            $this->_groupContainer . '.' .
                            $this->_dataLinks,
                        'selectionsProvider' =>
                            $this->_modifierConfig['listing']
                            . '.' . $this->_modifierConfig['listing']
                            . '.product_columns.ids',
                        'qtyElement' => $this->_modifierConfig['form'] . '.' .
                            $this->_modifierConfig['form'] . '.' .
                            $this->_groupContainer . '.' .
                            $this->_dataLinks . '.%s.request_qty',
                        'inputElementName' => 'request_qty'
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns Buttons Set configuration
     *
     * @return array
     */
    protected function getCustomButtons()
    {
        $moduleManager = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Framework\Module\Manager');        
        $showScanBarcodeButton = $moduleManager->isEnabled('Magestore_BarcodeSuccess') ? true : false;
        
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'label' => false,
                        'content' => __($this->_fieldsetContent),
                        'template' => 'ui/form/components/complex',
                    ],
                ],
            ],
            'children' => [
                'scan_button' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magestore_InventorySuccess/js/transferstock/scan-barcode-button',
                                'actions' => [],
                                'title' => __('Scan Barcode'),
                                'provider' => null,
                                'visible' => $showScanBarcodeButton,
                            ],
                        ],
                    ],
                ],                  
                'import_button' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magestore_InventorySuccess/js/transferstock/import-product-button',
                                'actions' => [],
                                'title' => __('Import'),
                                'provider' => null,
                            ],
                        ],
                    ],
                ],
                'select_product_button' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'actions' => [
                                    [
                                        'targetName' =>
                                            $this->_modifierConfig['form'] . '.' . $this->_modifierConfig['form']
                                            . '.'
                                            . $this->_groupContainer
                                            . '.'
                                            . $this->_modifierConfig['modal'],
                                        'actionName' => 'openModal',
                                    ],
                                    [
                                        'targetName' =>
                                            $this->_modifierConfig['form'] . '.' . $this->_modifierConfig['form']
                                            . '.'
                                            . $this->_groupContainer
                                            . '.'
                                            . $this->_modifierConfig['modal']
                                            . '.'
                                            . $this->_modifierConfig['listing'],
                                        'actionName' => 'render',
                                    ],
                                ],
                                'title' => __($this->_buttonTitle),
                                'provider' => null,
                            ],
                        ],
                    ],
                ],

                'save_product_button' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'actions' => [
                                    [
                                        'targetName' =>
                                            $this->_modifierConfig['form'] . '.' . $this->_modifierConfig['form'],

                                        'actionName' => 'save',
                                        'params' => [
                                            true,
                                            [
                                                'id' => $this->request->getParam('id'),
                                                'action' => 'save_product',
                                            ],
                                        ]
                                    ]
                                ],
                                'title' => __('Save Products'),
                                'provider' => null,
                            ],
                        ],
                    ],
                ],

            ],
        ];
    }

    

    /**
     * Fill data column
     *
     * @param ProductModel
     * @return array
     */
    protected function fillDynamicData($product)
    {
        return [
            'id' => $product->getProductId(),
            'sku' => $product->getProductSku(),
            'name' => $product->getProductName(),
            'qty' => $product->getAvailableQty(),
            //'position' => $product->getPosition(),
            'request_qty' => $product->getQty()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if(!$this->getVisible()){
            return $meta;
        }

        return parent::modifyMeta($meta);

    }

    /**
     * Fill meta columns
     *
     * @return array
     */
    protected function fillModifierMeta()
    {
        return [
            'id' => $this->getTextColumn('id', true, __('ID'), 10),
            'sku' => $this->getTextColumn('sku', false, __('SKU'), 20),
            'name' => $this->getTextColumn('name', false, __('Name'), 30),
            'qty' => $this->getTextColumn('qty', false, __('Qty in Warehouse'), 40),
            'request_qty' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'dataType' => Form\Element\DataType\Number::NAME,
                            'formElement' => Form\Element\Input::NAME,
                            'componentType' => Form\Field::NAME,
                            'dataScope' => 'request_qty',
                            'label' => __('Qty'),
                            'fit' => true,
                            'additionalClasses' => 'admin__field-small',
                            'sortOrder' => 50,
                            'validation' => [
                                'validate-number' => true,
                                'validate-greater-than-zero' => true,
                                'required-entry' => true,
                            ],
                        ],
                    ],
                ],
            ],

            'actionDelete' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType' => 'actionDelete',
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'label' => __('Actions'),
                            'sortOrder' => 60,
                            'fit' => true,
                        ],
                    ],
                ],
            ],
            'position' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'dataType' => Form\Element\DataType\Number::NAME,
                            'formElement' => Form\Element\Input::NAME,
                            'componentType' => Form\Field::NAME,
                            'dataScope' => 'position',
                            'sortOrder' => 70,
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
}
