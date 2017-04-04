<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProduct;

use Magestore\InventorySuccess\Model\ResourceModel\AbstractCollection;
use Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Product as WarehouseProductResource;
use Magestore\InventorySuccess\Api\Data\Warehouse\ProductInterface as WarehouseProductInterface;


class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'transferstock_product_id';



    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magestore\InventorySuccess\Model\TransferStock\TransferStockProduct', 'Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProduct');
    }


    public function getTransferStockProduct($transferstock_id, $warehouse_id)
    {
        $this->getSelect()->joinLeft(array('warehouse_product' => $this->getTable(WarehouseProductResource::MAIN_TABLE))
            , 'main_table.product_id = warehouse_product.product_id',
            array(
                'total_qty' => 'total_qty',
                'available_qty' => 'qty',
            )
        );

        //$this->getSelect()->columns('(total_qty-qty_to_ship) as available_qty');

        $this->getSelect()->columns('main_table.qty as qty_requested');
        if($transferstock_id){
            $this->getSelect()->where("main_table.transferstock_id=$transferstock_id");
        }

        if($warehouse_id){
            $this->getSelect()->where("warehouse_product.". WarehouseProductInterface::WAREHOUSE_ID ."=$warehouse_id");
        }

        return $this;
    }

   


}
