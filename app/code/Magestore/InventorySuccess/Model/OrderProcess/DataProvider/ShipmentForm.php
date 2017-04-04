<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\OrderProcess\DataProvider;


class ShipmentForm
{
    
    /**
     * @var \Magestore\InventorySuccess\Model\WarehouseFactory
     */
    protected $warehouseFactory;
    
    /**
     * @var \Magestore\InventorySuccess\Api\Warehouse\WarehouseManagementInterface
     */
    protected $warehouseManagement;
    
    /**
     * @var \Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface 
     */
    protected $warehouseStockRegistry;
    
    /**
     * @var \Magestore\InventorySuccess\Api\Warehouse\OrderItemManagementInterface 
     */
    protected $orderItemManagement;    
    

    public function __construct(
        \Magestore\InventorySuccess\Model\WarehouseFactory $warehouseFactory,    
        \Magestore\InventorySuccess\Api\Warehouse\WarehouseManagementInterface $warehouseManagement,
        \Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface $warehouseStockRegistry,
        \Magestore\InventorySuccess\Api\Warehouse\OrderItemManagementInterface $orderItemManagement
    )
    {
        $this->warehouseFactory = $warehouseFactory;
        $this->warehouseManagement = $warehouseManagement;
        $this->warehouseStockRegistry = $warehouseStockRegistry;
        $this->orderItemManagement = $orderItemManagement;        
    }
    
    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getAvailableWarehouses($order)
    {
        /* prepare list of items to ship */
        $needToShipItems = $this->_prepareNeedToShipItems($order);
        
        /* get orderred Warehouses from items */
        $orderWarehouses = $this->_loadOrderWarehouses($needToShipItems);

        /* get products of items in all warehouses */
        $whProducts = $this->warehouseStockRegistry->getStocksFromEnableWarehouses(array_keys($needToShipItems));
        
        /* load information of warehouses */
        $warehouseIds = [];
        foreach ($whProducts as $whProduct) {
            $warehouseIds[$whProduct->getWarehouseId()] = $whProduct->getWarehouseId();
        }
        $warehouseList = $this->warehouseManagement->getWarehouses($warehouseIds);
        
        /* prepare list of available warehouses */
        $warehouses = $this->_prepareAvailableWarehouses($needToShipItems, $whProducts, $orderWarehouses, $warehouseList);
        
        /* scan need-to-ship items before returning */
        $warehouses = $this->_scanShipItemsInWarehouseList($warehouses, $needToShipItems);

        return $this->_sortWarehouses($warehouses);
    }
    
    /**
     * prepare list of available warehouses
     * 
     * @param array $needToShipItems
     * @param \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Product\Collection $whProducts
     * @param array $orderWarehouses
     * @param array $warehouseList
     * @return array
     */
    protected function _prepareAvailableWarehouses($needToShipItems, $whProducts, $orderWarehouses, $warehouseList)
    {
        $warehouses = [];       
        foreach ($whProducts as $whProduct) {
            $items = $needToShipItems[$whProduct->getProductId()];
            foreach($items as $item) {
                $warehouseId = $whProduct->getWarehouseId();
                /* get orderred warehouseId */
                $orderWarehouseId = isset($orderWarehouses[$item->getItemId()]) ? $orderWarehouses[$item->getItemId()] : null;

                if($warehouseId == $orderWarehouseId) {
                    /* create shipment from orderred Warehouse */
                    $qtyInWarehouse = floatval($whProduct->getTotalQty());
                } else {
                    /* create shipment from other warehouse */
                    $qtyInWarehouse = floatval($whProduct->getTotalQty() - $whProduct->getQtyToShip());
                }
                
                if($qtyInWarehouse <= 0)
                    continue;
                
                $qtyToShip = $this->_getQtyToShip($item);       
                $itemId = $this->_getItemIdToShip($item);        

                $lackQty = max($qtyToShip - $qtyInWarehouse, 0);

                $warehouses[$warehouseId]['items'][$itemId] = [
                            'qty_in_warehouse' => $qtyInWarehouse,
                            'lack_qty' => $lackQty,
                        ];
                /* insert warehouse data to array */
                if(isset($warehouses[$warehouseId]['lack_qty'])) {
                    $warehouses[$warehouseId]['lack_qty'] += $lackQty;
                } else {
                    $warehouses[$warehouseId]['lack_qty'] = $lackQty;
                }
                $warehouses[$warehouseId]['info'] = $warehouseList[$warehouseId];
            }
        }     
        
        return $warehouses;
    }
    
    /**
     * prepare list of items to ship 
     * 
     * @param \Magento\Sales\Model\Order $order
     */
    protected function _prepareNeedToShipItems($order)
    {
        /* prepare list of items to ship */
        $needToShipItems = [];
        foreach($order->getAllItems() as $item) {
            if($item->getRealProductType() !== null) {
                /* composite product */
                continue;
            }
            $needToShip = true;
            if($item->getQtyToShip() == 0) {
                if($item->getParentItemId()) {
                    if(!$item->getParentItem()->getQtyToShip()) {
                        $needToShip = false;
                    }
                } else {
                    $needToShip = false;
                }
            }
            if(!$needToShip) {
                continue;
            }
            if(!isset($needToShipItems[$item->getProductId()])) {
                $needToShipItems[$item->getProductId()] = [$item];
            } else {
                $needToShipItems[$item->getProductId()][] = $item;
            }
        }     
        return $needToShipItems;
    }
    
    /**
     * 
     * @param array $needToShipItems
     * @return array
     */
    protected function _loadOrderWarehouses($needToShipItems)
    {
        $orderItemIds = [];
        foreach($needToShipItems as $items) {
            foreach($items as $item)
                $orderItemIds[] = $item->getItemId();
        }
        
        return $this->orderItemManagement->getWarehousesByItemIds($orderItemIds);   
    }
    
    /**
     * 
     * @param \Magento\Sales\Model\Order\Item $item
     * @return boolean
     */
    protected function _isUsedParentItem($item)
    {
        if($item->getParentItemId()) {
            if($item->getParentItem()->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
                return false;
            }
            return true;
        }
        return false;
    }
    
    /**
     * scan need-to-ship items before returning
     * 
     * @param array $warehouses
     * @param array $needToShipItems
     * @return array
     */
    protected function _scanShipItemsInWarehouseList($warehouses, $needToShipItems)
    {
        foreach($warehouses as $warehouseId => &$warehouseData) {
            foreach($needToShipItems as $items) {
                foreach($items as $item) {
                    $qtyToShip = $this->_getQtyToShip($item);
                    $itemId = $this->_getItemIdToShip($item);                
                    if(!isset($warehouseData['items'][$itemId])) {
                        $warehouseData['items'][$itemId] = [
                            'qty_in_warehouse' => 0,
                            'lack_qty' => $qtyToShip,
                        ];
                        $warehouses[$warehouseId]['lack_qty'] += $qtyToShip;
                    }
                }
            }
        }
        return $warehouses;
    }
    
    /**
     * Sort warehouses by lack_qty ASC
     * 
     * @param array $warehouses
     * @return array
     */
    protected function _sortWarehouses($warehouses)
    {
        $sortedWarehouses = [];
        usort($warehouses, [$this, "sortShipmentWarehouses"]);    
        foreach($warehouses as $warehouse){
            $warehouseId = $warehouse['info']['warehouse_id'];
            $sortedWarehouses[$warehouseId] = $warehouse;
        }
        return $sortedWarehouses;
    }
    
    /**
     * Compare lack_qty of warehouses
     * 
     * @param array $warehouseA
     * @param array $warehouseB
     * @return int
     */
    public function sortShipmentWarehouses($warehouseA, $warehouseB)
    {
        if($warehouseA['lack_qty'] == $warehouseB['lack_qty'])
            return 0;
        if($warehouseA['lack_qty'] < $warehouseB['lack_qty'])
            return -1;
        return 1;
    }
    
    /**
     * Get Qty to Ship of Item
     * 
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float
     */
    protected function _getQtyToShip($item)
    {
        if($this->_isUsedParentItem($item)) {
            return $item->getParentItem()->getQtyToShip();
        }
        return $item->getQtyToShip();
    }
    
    /**
     * Get ItemId of need-to-ship item
     * 
     * @param \Magento\Sales\Model\Order\Item $item
     * @return int
     */
    protected function _getItemIdToShip($item)
    {
        if($this->_isUsedParentItem($item)) {
            return $item->getParentItemId();
        }
        return $item->getItemId();        
    }    
    
}