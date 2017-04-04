<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\OrderProcess\DataProvider;

class CreditmemoForm
{

    /**
     * @var \Magestore\InventorySuccess\Api\Warehouse\WarehouseManagementInterface
     */
    protected $warehouseManagement;


    public function __construct(
        \Magestore\InventorySuccess\Api\Warehouse\WarehouseManagementInterface $warehouseManagement
    )
    {
        $this->warehouseManagement = $warehouseManagement;
    }

    /**
     * Get list of available warehouses to return items
     * 
     * @return array
     */
    public function getAvailableWarehouses()
    {   
        $availableWarehouses = [];
        $warehouses = $this->warehouseManagement->getEnableWarehouses();
        if($warehouses->getSize()) {
            foreach($warehouses as $warehouse) {
                $availableWarehouses[$warehouse->getId()] = $warehouse->getWarehouseName() . ' ('.$warehouse->getWarehouseCode().')';
            }
        }
        return $availableWarehouses;
    }

}
