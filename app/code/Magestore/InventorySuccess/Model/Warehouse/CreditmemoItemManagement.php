<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\Warehouse;

use Magestore\InventorySuccess\Api\Warehouse\CreditmemoItemManagementInterface;

class CreditmemoItemManagement implements CreditmemoItemManagementInterface
{

    /**
     * @var \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Creditmemo\Item\CollectionFactory
     */
    protected $warehouseCreditmemoItemCollectionFactory; 
    
    /**
     * 
     * @param \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Shipment\Item\CollectionFactory $warehouseShipmentItemCollectionFactory
     */
    public function __construct(
        \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Creditmemo\Item\CollectionFactory $warehouseCreditmemoItemCollectionFactory
    )
    {
        $this->warehouseCreditmemoItemCollectionFactory = $warehouseCreditmemoItemCollectionFactory;
    }

    /**
     * Get Warehouse by creditmemo item id
     * 
     * @param int $itemId
     * @return \Magestore\InventorySuccess\Api\Data\Warehouse\WarehouseInterface
     */    
    public function getWarehouseByCreditmemoItemId($itemId)
    {
        $item = $this->warehouseCreditmemoItemCollectionFactory->create()
                        ->addFieldToFilter('item_id', $itemId)
                        ->setPageSize(1)->setCurPage(1)
                        ->getFirstItem();

        return $item->getWarehouseId();
    }

}