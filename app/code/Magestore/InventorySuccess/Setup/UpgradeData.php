<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    
    /**
     * @var \Magestore\InventorySuccess\Api\InstallManagementInterface
     */
    protected $installManagement;
    
    
    public function __construct(
        \Magestore\InventorySuccess\Api\InstallManagementInterface $installManagement
    )
    {
        $this->installManagement = $installManagement;
    }
    
    /**
     * {@inheritdoc}
     */    
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->installManagement->transferWarehouseProductToMagentoStockItem();
        }          
    }

}