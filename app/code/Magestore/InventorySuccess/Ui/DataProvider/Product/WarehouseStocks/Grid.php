<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Ui\DataProvider\Product\WarehouseStocks;


class Grid extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magento\Framework\View\Element\UiComponent\ContextInterface
     */
    protected $context;
    
    /**
     * @var \Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface 
     */
    protected $_warehouseStockRegistry;    
    
    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[] $addFieldStrategies
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface $warehouseStockRegistry,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->context = $context;
        $this->_warehouseStockRegistry = $warehouseStockRegistry;
        $this->collection = $this->_warehouseStockRegistry->getStockWarehouses(1);
    } 
      
}
