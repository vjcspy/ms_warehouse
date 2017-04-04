<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Api;


interface InstallManagementInterface
{
    
    /**
     * Create default Warehouse 
     * 
     * @return \Magestore\InventorySuccess\Api\InstallManagementInterface
     */
    public function createDefaultWarehouse();
    
    /**
     * Transfer all products to the default Warehouse
     * 
     * @return \Magestore\InventorySuccess\Api\InstallManagementInterface
     */
    public function transferProductsToDefaultWarehouse();
    
    /**
     * Calculate qty-to-ship of all products
     * 
     * @return \Magestore\InventorySuccess\Api\InstallManagementInterface
     */
    public function calculateQtyToShip();

    /**
     * create default notification rule
     *
     * @return \Magestore\InventorySuccess\Api\InstallManagementInterface
     */
    public function createDefaultNotificationRule();
    
    
    /**
     * transfer data in os_warehouse_product to cataloginventory_stock_item
     * use to upgrade Magestore_InventorySuccess from v1.0.0 to v1.1.0 and higher
     * 
     * @return \Magestore\InventorySuccess\Api\InstallManagementInterface
     */
    public function transferWarehouseProductToMagentoStockItem();

}