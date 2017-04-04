<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Model\ResourceModel\AdjustStock\Product;

/**
 * Class Collection
 * @package Magestore\InventorySuccess\Model\ResourceModel\AdjustStock\Product
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct() {
        $this->_init('Magestore\InventorySuccess\Model\AdjustStock\Product', 'Magestore\InventorySuccess\Model\ResourceModel\AdjustStock\Product');
    }

    /**
     * get adjusted product
     *
     * @return void
     */
    public function getAdjustedProducts($adjustStockId){
        $collection = $this->addFieldToFilter('adjuststock_id', $adjustStockId)
                           ->setOrder('product_id', 'DESC');
        return $collection;
    }
}
