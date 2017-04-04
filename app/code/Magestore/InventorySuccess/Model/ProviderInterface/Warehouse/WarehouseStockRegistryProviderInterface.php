<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\InventorySuccess\Model\ProviderInterface\Warehouse;

/**
 * Interface WarehouseStockRegistryProviderInterface
 */
interface WarehouseStockRegistryProviderInterface
{
    /**
     * @param int $warehouseId
     * @param int $productId
     * @return \Magestore\InventorySuccess\Api\Data\Warehouse\ProductInterface
     */
    public function getWarehouseStock($warehouseId, $productId);
}
