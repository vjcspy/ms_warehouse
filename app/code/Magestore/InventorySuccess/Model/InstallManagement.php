<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\InventorySuccess\Model;

use Magestore\InventorySuccess\Api\InstallManagementInterface;

class InstallManagement implements InstallManagementInterface
{

    /**
     *
     * @var \Magestore\InventorySuccess\Model\ResourceModel\InstallManagement
     */
    protected $_resource;

    /**
     * 
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor
     */
    protected $_stockIndexerProcessor;    

    public function __construct(
        \Magestore\InventorySuccess\Model\ResourceModel\InstallManagement $installManagementResource,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $_stockIndexerProcessor
    )
    {
        $this->_resource = $installManagementResource;
        $this->_stockIndexerProcessor = $_stockIndexerProcessor;
    }    
    
    /**
     * @inheritdoc
     */    
    public function calculateQtyToShip()
    {
        $this->getResource()->calculateQtyToShip();
        return $this;
    }

    /**
     * @inheritdoc
     */    
    public function transferProductsToDefaultWarehouse()
    {
        $this->getResource()->transferProductsToDefaultWarehouse();
        return $this;        
    }
    
    /**
     * @inheritdoc
     */ 
    public function createDefaultWarehouse()
    {
        $this->getResource()->createDefaultWarehouse();
        return $this;          
    }    
    
    /**
     * 
     * @return \Magestore\InventorySuccess\Model\ResourceModel\InstallManagement
     */
    public function getResource()
    {
        return $this->_resource;
    }

    /**
     * create default notification rule
     *
     * @return \Magestore\InventorySuccess\Model\ResourceModel\InstallManagement
     */
    public function createDefaultNotificationRule()
    {
        /** @var \Magestore\InventorySuccess\Api\LowStockNotification\RuleManagementInterface $ruleManagement */
        $ruleManagement = \Magento\Framework\App\ObjectManager::getInstance()->create(
            '\Magestore\InventorySuccess\Api\LowStockNotification\RuleManagementInterface'
        );
        $ruleManagement->createDefaultNotificationRule();
    }
    
    /**
     * @inheritdoc
     */     
    public function transferWarehouseProductToMagentoStockItem()
    {
        $this->getResource()->transferWarehouseProductToMagentoStockItem();
    }

}