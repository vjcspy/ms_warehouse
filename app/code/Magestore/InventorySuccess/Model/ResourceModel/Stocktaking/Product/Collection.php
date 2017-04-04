<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\ResourceModel\Stocktaking\Product;

/**
 * Class Collection
 * @package Magestore\InventorySuccess\Model\ResourceModel\Stocktaking\Product
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct() {
        $this->_init('Magestore\InventorySuccess\Model\Stocktaking\Product', 'Magestore\InventorySuccess\Model\ResourceModel\Stocktaking\Product');
    }

    /**
     * get stocktaking products
     *
     * @return void
     */
    public function getStocktakingProducts($stocktakingId){
        $collection = $this->addFieldToFilter('stocktaking_id', $stocktakingId)
                           ->setOrder('product_id', 'DESC');
        return $collection;
    }

    /**
     * get stocktaking different products
     *
     * @return void
     */
    public function getStocktakingDifferentProducts($stocktakingId){
        $collection = $this->addFieldToFilter('stocktaking_id', $stocktakingId);
        $collection->getSelect()->columns(array(
                         'different_qty' => 'ABS(main_table.old_qty - main_table.stocktaking_qty)'))
                    ->where('ABS(main_table.old_qty - main_table.stocktaking_qty) != 0');
        return $collection;
    }
}
