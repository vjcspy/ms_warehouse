<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface {

    /**
     *
     * @var \Magestore\InventorySuccess\Api\InstallManagementInterface
     */
    protected $_installManagement;

    /**
     *
     * @param \Magestore\InventorySuccess\Api\InstallManagementInterface $installManagement
     */
    public function __construct(
        \Magestore\InventorySuccess\Api\InstallManagementInterface $installManagement
    ) {
        $this->_installManagement = $installManagement;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();

        /* create default warehouse */
        /*
         * Cái này làm 2 việc:
         * 1. tạo 1 row trong os_warehouse là default warehouse
         * 2. tạo thêm 1 stock trong bảng cataloginventory_stock nhưng khó hiểu là sao nó lại lưu warehouse_id vào cột website_id
         * */
        $this->_installManagement->createDefaultWarehouse();

        /* transfer products to default warehouse */
        /*
         * Bọn này dùng field website_id để làm warehouse_id, nó duplicate các row có sẵn và chuyển website_id thành warehouse_id primary
         * */
        $this->_installManagement->transferProductsToDefaultWarehouse();

        /* calculate qty-to-ship of all products, save to database, update to default warehouse */
        /*
         * Để xem là order sẽ bị trừ vào warehouse nào
         * */
        $this->_installManagement->calculateQtyToShip();

        /** create default notification rule */
        $this->_installManagement->createDefaultNotificationRule();

        $setup->endSetup();
    }
}
