<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\OrderProcess;

use Magestore\InventorySuccess\Api\OrderProcess\PlaceNewOrderInterface;
use Magestore\InventorySuccess\Api\Db\QueryProcessorInterface;
use Magestore\InventorySuccess\Api\Data\Warehouse\ProductInterface as WarehouseProductInterface;

class PlaceNewOrder extends OrderProcess implements PlaceNewOrderInterface
{
    /**
     * @var string
     */
    protected $process = 'place_new_order';
    
    /**
     * @var array
     */
    private $orderWarehouses = [];
    
    /**
     * execute the process
     * 
     * @param \Magento\Sales\Model\Order\Item $item
     * @param \Magento\Sales\Model\Order\Item $itemBefore
     * @return bool
     */    
    public function execute($item, $itemBefore)
    {
        if(!$this->canProcessItem($item, $itemBefore)){
            return;
        }

        $this->assignOrderItemToWarehouse($item);
        
        $this->markItemProcessed($item);
        
        return true;
    }    
    
    /**
     * Assign order item to Warehouse
     * 
     * @param \Magento\Sales\Model\Order\Item $item
     */    
    public function assignOrderItemToWarehouse($item)
    {
        $this->queryProcess->start();
        
        $this->_addWarehouseOrderItem($item); 
        $this->_increaseQtyToShipInOrderWarehouse($item);

        $this->queryProcess->process();        
        
    }
    
    /**
     * 
     * @param \Magento\Sales\Model\Order\Item $orderItem
     */
    protected function _addWarehouseOrderItem($item)
    {
        $warehouse = $this->getOrderWarehouse($item->getOrder());
        $warehouseOrderModel = $this->warehouseOrderItemFactory->create();
        $warehouseOrderData = [
            'warehouse_id' => $warehouse->getId(),
            'order_id' => $item->getOrderId(),
            'item_id' => $item->getId(),
            'product_id' => $item->getProductId(),
            'qty_ordered' => $this->_getOrderedQty($item),
            'subtotal' => $item->getRowTotal(),
            'created_at' => $item->getOrder()->getCreatedAt(),
            'updated_at' => $item->getOrder()->getUpdatedAt(),            
        ];
        $this->queryProcess->addQuery([
            'type' => QueryProcessorInterface::QUERY_TYPE_INSERT,
            'values' =>  [$warehouseOrderData], 
            'table' => $warehouseOrderModel->getResource()->getMainTable(),
        ]);        
    }
    
    /**
     * 
     * @param \Magento\Sales\Model\Order\Item $item
     */    
    protected function _increaseQtyToShipInOrderWarehouse($item)
    {
        $orderWarehouse = $this->getOrderWarehouse($item->getOrder());
        if($this->catalogInventoryConfiguration->getDefaultScopeId() == WarehouseProductInterface::DEFAULT_SCOPE_ID) {
            /* place order from global stock */
            $updateWarehouseId = $orderWarehouse->getWarehouseId();
        } else {
            /* place order from warehouse stock  */
            $updateWarehouseId = WarehouseProductInterface::DEFAULT_SCOPE_ID;         
        }
        
        $qtyChanges = array(WarehouseProductInterface::QTY_TO_SHIP => $this->_getOrderedQty($item));
        $query = $this->warehouseStockRegistry->prepareChangeProductQty($updateWarehouseId, $item->getProductId(), $qtyChanges);
        $this->queryProcess->addQuery($query);           
    }
    
    
    /**
     * 
     * @param \Magento\Sales\Model\Order\Item $item
     * @param \Magento\Sales\Model\Order\Item $itemBefore
     * @return boolean
     */
    public function canProcessItem($item, $itemBefore)
    {
        /* check new item */
        if($itemBefore->getId()) {
            return false;
        }
        
        /* check processed item */
        if($this->isProcessedItem($item)) {
            return false;
        }
        
        /* check manage stock or not */
        if(!$this->isManageStock($item)) {
            return false;
        }
        
        /* check added item */
        $warehouseOrderItems = $this->warehouseOrderItemFactory->create()
                                    ->getCollection()
                                    ->addFieldToFilter('item_id', $item->getItemId());
        
        if($warehouseOrderItems->getSize() > 0) {
            return false;
        }
        return true;
    }    
    
    /**
     * 
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magestore\InventorySuccess\Api\Data\Warehouse\WarehouseInterface $warehouse
     */
    public function assignOrderToWarehouse($order, $warehouse)
    {
        
    }
    
    
    /**
     * Get warehouse which responds to the order
     * 
     * @param \Magento\Sales\Model\Order $order
     * @return \Magestore\InventorySuccess\Api\Data\Warehouse\WarehouseInterface
     */
    public function getOrderWarehouse($order)
    {
        if(!isset($this->orderWarehouses[$order->getId()])) {
            if($this->inventoryHelper->getLinkWarehouseStoreConfig()) {
                $orderWarehouse = $this->warehouseManagement->getCurrentWarehouseByStore();
            } else {
                $orderWarehouse = $this->warehouseManagement->getPrimaryWarehouse();
            }
            /* allow to change the order Warehouse by other extension */
            $this->eventManager->dispatch('inventorysuccess_new_order_warehouse', [
                        'order' => $order,
                        'warehouse' => $orderWarehouse
                    ]);
            $this->orderWarehouses[$order->getId()] = $orderWarehouse;
        }
        return $this->orderWarehouses[$order->getId()];
    }

}

