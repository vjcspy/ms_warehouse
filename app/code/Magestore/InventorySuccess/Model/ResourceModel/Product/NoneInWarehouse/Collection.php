<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\ResourceModel\Product\NoneInWarehouse;

use Magento\Catalog\Model\Product\Type as SimpleProductType;

/**
 * Class Collection
 * @package Magestore\InventorySuccess\Model\ResourceModel\Product\NoneInWarehouse
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    const MAPPING_FIELD = [
        'sum_total_qty' => 'SUM(warehouse_product.total_qty)',
        'sum_qty_to_ship' => 'SUM(warehouse_product.qty_to_ship)',
        'available_qty' => '(SUM(warehouse_product.total_qty) - SUM(warehouse_product.qty_to_ship))'
    ];
    
    /**
     * Init select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        $warehouseProductIds = $this->getWarehouseProductIds();

        $this->getSelect()->from(['e' => $this->getEntity()->getEntityTable()]);
        $entity = $this->getEntity();
        if ($entity->getTypeId() && $entity->getEntityTable() == \Magento\Eav\Model\Entity::DEFAULT_ENTITY_TABLE) {
            $this->addAttributeToFilter('entity_type_id', $this->getEntity()->getTypeId());
        }
        $this->addFieldToFilter(
            'type_id', SimpleProductType::DEFAULT_TYPE
        )->addAttributeToSelect([
            "name",
            "sku",
            "price",
            "status",
            "qty"
        ]);
        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $this->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1 AND {{table}}.website_id=0',
                'left'
            );
        }
        if(count($warehouseProductIds))
            $this->getSelect()->where('e.entity_id NOT IN (?)', array_merge($warehouseProductIds['in_stock'], $warehouseProductIds['out_stock']))
                            ->orWhere('qty > 0 AND e.entity_id IN (?)', $warehouseProductIds['out_stock']);
        
        return $this;
    }
    
    /**
     * Return ['in_stock' => [], 'out_stock' => []]
     * 
     * @return array
     */
    public function getWarehouseProductIds(){
        $ids = ['in_stock' => [], 'out_stock' => []];
        $warehouseProductCollection = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Product\Collection');
        $warehouseProductCollection->getSelect()->group('product_id');
        $warehouseProductCollection->getSelect()->columns('sum(total_qty) as  sum_total_qty');
        
        foreach($warehouseProductCollection as $warehouseProduct) {
            if($warehouseProduct->getData('sum_total_qty') > 0) {
                $ids['in_stock'][] = $warehouseProduct->getProductId();
            } else {
                $ids['out_stock'][] = $warehouseProduct->getProductId();
            }
        }

        return $ids;
    }

    /**
     * Get count sql
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = clone $this->getSelect();
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);

        if (!count($this->getSelect()->getPart(\Magento\Framework\DB\Select::GROUP))) {
            $countSelect->columns(new \Zend_Db_Expr('COUNT(*)'));
            return $countSelect;
        }
        $countSelect->reset(\Magento\Framework\DB\Select::HAVING);
        $countSelect->reset(\Magento\Framework\DB\Select::GROUP);
        $group = $this->getSelect()->getPart(\Magento\Framework\DB\Select::GROUP);
        $countSelect->columns(new \Zend_Db_Expr(("COUNT(DISTINCT ".implode(", ", $group).")")));
        return $countSelect;
    }

    /**
     * @param string $columnName
     * @param array $filterValue
     * @return $this
     */
    public function addQtyToFilter($columnName, $filterValue){
        if(isset($filterValue['from'])) {
            $this->getSelect()->having(self::MAPPING_FIELD[$columnName]. ' >= ?', $filterValue['from']);
        }
        if(isset($filterValue['to'])) {
            $this->getSelect()->having(self::MAPPING_FIELD[$columnName]. ' <= ?', $filterValue['to']);
        }
        return $this;
    }
}