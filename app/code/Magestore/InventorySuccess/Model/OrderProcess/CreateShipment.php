<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\OrderProcess;

use Magestore\InventorySuccess\Api\OrderProcess\CreateShipmentInterface;
use Magestore\InventorySuccess\Api\Db\QueryProcessorInterface;
use Magestore\InventorySuccess\Model\OrderProcess\StockMovementActivity\SalesShipment as StockActivitySalesShipment;
use Magestore\InventorySuccess\Api\Data\Warehouse\ProductInterface as WarehouseProductInterface;

class CreateShipment extends OrderProcess implements CreateShipmentInterface
{
    /**
     * @var string
     */
    protected $process = 'create_shipment';    
    
    /**
     * @var array 
     */
    protected $shipWarehouses = [];
    
    /**
     * @array
     */
    protected $simpleOrderItems = [];
    
    /**
     * execute the process
     * 
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     * @return bool
     */        
    public function execute($item)
    {   
        if(!$this->canProcessItem($item)){
            return;
        }

        $this->processShipItem($item);
        
        $this->markItemProcessed($item);
        
        return true;        
    }
    
    /**
     * Process to ship item from Warehouse
     * 
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     */
    public function processShipItem($item)
    {
        $this->queryProcess->start('shipment');
        
        /* add shipped item to the Warehouse */
        $this->_addWarehouseShipmentItem($item);
        
        /* subtract qty_to_ship in ordered Warehouse by shipped qty*/
        $this->_subtractQtyToShipInOrderWarehouse($item);
        
        /* issue ship item from Warehouse */
        $this->_issueItemFromWarehouse($item);

        $this->queryProcess->process('shipment');
    }

   /**
    * Add shipment item to Warehouse
    * 
    * @param \Magento\Sales\Model\Order\Shipment\Item $item
    */
    protected function _addWarehouseShipmentItem($item)
    {
        /** @var \Magento\Sales\Model\Order\Item $simpleItem */
        $simpleItem = $this->_getSimpleOrderItem($item);
        $shipWarehouse = $this->getShipmentWarehouse($item);
        if (!$shipWarehouse)
            return $this;
        $warehouseShipModel = $this->warehouseShipmentItemFactory->create();
        $warehouseShipData = [
            'warehouse_id' => $shipWarehouse->getId(),
            'shipment_id' => $item->getParentId(),
            'item_id' => $item->getId(),            
            'order_id' => $item->getOrderItem()->getOrderId(),
            'order_item_id' => $item->getOrderItemId(),
            'product_id' => $simpleItem->getProductId(),
            'qty_shipped' => $this->_getShippedQty($item),
            'subtotal' => $item->getPrice(),
            'created_at' => $item->getShipment()->getCreatedAt(),
            'updated_at' => $item->getShipment()->getUpdatedAt(),            
        ];
        $this->queryProcess->addQuery([
            'type' => QueryProcessorInterface::QUERY_TYPE_INSERT,
            'values' =>  [$warehouseShipData], 
            'table' => $warehouseShipModel->getResource()->getMainTable(),
        ], 'shipment');        
              
    }
    
    /**
     * Get simple item from ship item
     * 
     * @param \Magento\Sales\Model\Order\Shipment\Item $shipItem
     * @return \Magento\Sales\Model\Order\Item
     */
    protected function _getSimpleOrderItem($shipItem)
    {
        if(!isset($this->simpleOrderItems[$shipItem->getId()])) {
            $simpleItem = $shipItem->getOrderItem();
            $orderItem = $shipItem->getOrderItem();
            if ($orderItem->getProduct()->isComposite()) {
                if($orderItem->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    foreach($orderItem->getChildrenItems() as $childItem) {
                        $simpleItem = $childItem;
                        break;
                    }
                }
            }   
     
            $this->simpleOrderItems[$shipItem->getId()] = $simpleItem;
        }
        return $this->simpleOrderItems[$shipItem->getId()];
    }
    
    /**
     * subtract qty_to_ship of product in ordered warehouse
     * 
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     */
    protected function _subtractQtyToShipInOrderWarehouse($item)
    {
        $orderItem = $this->_getSimpleOrderItem($item);
        $orderWarehouseId = $this->getOrderWarehouse($orderItem->getItemId());
        $qtyChanges = [WarehouseProductInterface::QTY_TO_SHIP => -$this->_getShippedQty($item)];

        /* increase available_qty in ordered warehouse  */
        $query = $this->warehouseStockRegistry
                        ->prepareChangeProductQty($orderWarehouseId, $orderItem->getProductId(), $qtyChanges);
        $this->queryProcess->addQuery($query, 'shipment');
        
        /* increase available_qty in global stock */
        $query = $this->warehouseStockRegistry
                        ->prepareChangeProductQty(WarehouseProductInterface::DEFAULT_SCOPE_ID, $orderItem->getProductId(), $qtyChanges);        
        $this->queryProcess->addQuery($query, 'shipment');        
    }
    
    /**
     * issue item from ship warehouse
     * 
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     */      
    protected function _issueItemFromWarehouse($item)     
    {
        $orderItem = $this->_getSimpleOrderItem($item);
        if (!$this->getShipmentWarehouse($item))
            return $this;
        $shipWarehouseId = $this->getShipmentWarehouse($item)->getId();
        $products = [$orderItem->getProductId() => $this->_getShippedQty($item)];
        /* issue item for shipment from Warehouse, also update global stock */
        $this->stockChange->issue($shipWarehouseId, $products, StockActivitySalesShipment::STOCK_MOVEMENT_ACTION_CODE, $item->getShipment()->getId(), true);
    }
    
    /**
     * 
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     * @return boolean
     */
    public function canProcessItem($item)
    {
        /* check processed item */
        if($this->isProcessedItem($item)) {
            return false;
        }
        
        /* check manage stock or not */
        if(!$this->isManageStock($item)) {
            return false;
        }        
        
        $orderItem = $item->getOrderItem();
        if($orderItem->getParentItem() 
            && $orderItem->getParentItem()->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return false;
        }
            
        /* check added item */
        $warehouseShipmentItems = $this->warehouseShipmentItemFactory->create()
                                    ->getCollection()
                                    ->addFieldToFilter('item_id', $item->getId());
        
        if($warehouseShipmentItems->getSize() > 0) {
            return false;
        }
        
        return true;
    }      
    
    /**
     * Get warehouse to ship item
     * 
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     * @return \Magestore\InventorySuccess\Api\Data\Warehouse\WarehouseInterface
     */
    public function getShipmentWarehouse($item)
    {
        if(!isset($this->shipWarehouses[$item->getId()])) {
            /* get posted warehouse_id */
            $postData = $this->request->getParam('shipment');
            $shipWarehouseId = isset($postData['warehouse']) ? $postData['warehouse'] : null;
            /* get ordered warehouse_id */
            $orderItem = $this->_getSimpleOrderItem($item);
            $shipWarehouseId = $shipWarehouseId 
                                ? $shipWarehouseId 
                                : $this->getOrderWarehouse($orderItem->getItemId());
            /* get primary warehouse_id */                    
            if(!$shipWarehouseId) {
                $shipWarehouse = $this->warehouseManagement->getPrimaryWarehouse();
            } else {
                $shipWarehouse = $this->warehouseFactory->create()->load($shipWarehouseId);
            }
            $skipWarehouse = false;
            /* allow to change the Warehouse by other extension */
            $this->eventManager->dispatch('inventorysuccess_create_shipment_warehouse', [
                                            'warehouse' => $shipWarehouse,
                                            'item' => $item,
                                            'skip_warehouse' => &$skipWarehouse
                    ]);
            if ($skipWarehouse)
                return null;
            $this->shipWarehouses[$item->getId()] = $shipWarehouse;
        }
        return $this->shipWarehouses[$item->getId()];                
    }

}
