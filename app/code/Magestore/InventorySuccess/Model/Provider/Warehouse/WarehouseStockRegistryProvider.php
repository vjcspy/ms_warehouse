<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\Provider\Warehouse;

use Magestore\InventorySuccess\Model\ProviderInterface\Warehouse\WarehouseStockRegistryProviderInterface;

class WarehouseStockRegistryProvider implements WarehouseStockRegistryProviderInterface
{
    protected $warehouseProductCollectionFactory;

    public function __construct(
        \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Product\CollectionFactory $warehouseProductCollectionFactory
    )
    {
        $this->warehouseProductCollectionFactory = $warehouseProductCollectionFactory;
    }

    /**
     * @param int $warehouseId
     * @param int $productId
     * @return \Magestore\InventorySuccess\Api\Data\Warehouse\ProductInterface
     */
    public function getWarehouseStock($warehouseId, $productId)
    {
        $warehouseProductCollection = $this->warehouseProductCollectionFactory->create();
        $warehouseProduct = $warehouseProductCollection->addFieldToFilter('warehouse_id', $warehouseId)
            ->addFieldToFilter('product_id', $productId)
            ->setPageSize(1)
            ->setCurPage(1)
            ->getFirstItem();
        return $warehouseProduct;
    }
}