<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\Warehouse;

use Magestore\InventorySuccess\Api\Warehouse\ShipmentItemManagementInterface;

class ShipmentItemManagement implements ShipmentItemManagementInterface
{

    /**
     * @var \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Shipment\Item\CollectionFactory
     */
    protected $warehouseShipmentItemCollectionFactory; 
    
    /**
     * 
     * @param \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Shipment\Item\CollectionFactory $warehouseShipmentItemCollectionFactory
     */
    public function __construct(
        \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Shipment\Item\CollectionFactory $warehouseShipmentItemCollectionFactory
    )
    {
        $this->warehouseShipmentItemCollectionFactory = $warehouseShipmentItemCollectionFactory;
    }

    /**
     * Get Warehouse by shipment id
     * 
     * @param int $shipmentId
     * @return \Magestore\InventorySuccess\Api\Data\Warehouse\WarehouseInterface
     */    
    public function getWarehouseByShipmentId($shipmentId)
    {
        $item = $this->warehouseShipmentItemCollectionFactory->create()
                        ->addFieldToFilter('shipment_id', $shipmentId)
                        ->setPageSize(1)->setCurPage(1)
                        ->getFirstItem();

        return $item->getWarehouseId();
    }

}