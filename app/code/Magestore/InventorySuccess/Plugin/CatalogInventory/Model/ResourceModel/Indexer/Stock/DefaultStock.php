<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Plugin\CatalogInventory\Model\ResourceModel\Indexer\Stock;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseManagementInterface;
use \Magento\Store\Model\StoreManagerInterface;

class DefaultStock extends \Magento\CatalogInventory\Model\ResourceModel\Indexer\Stock\DefaultStock
{
    
    /**
     * @var WarehouseManagementInterface 
     */
    protected $warehouseManagement;
    
    /**
     * @var StoreManagerInterface 
     */
    protected $storeManagement;
    
    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Indexer\Table\StrategyInterface $tableStrategy
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Indexer\Table\StrategyInterface $tableStrategy,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        WarehouseManagementInterface $warehouseManagement,
        StoreManagerInterface $storeManagement,
        $connectionName = null
    ) {
        parent::__construct($context, $tableStrategy, $eavConfig, $scopeConfig, $connectionName);
        $this->warehouseManagement = $warehouseManagement;
        $this->storeManagement = $storeManagement;
    }
    
    /**
     * Get the select object for get stock status by product ids
     *
     * @param int|array $entityIds
     * @param bool $usePrimaryTable use primary or temporary index table
     * @return \Magento\Framework\DB\Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getStockStatusSelect($entityIds = null, $usePrimaryTable = false)
    {
        $connection = $this->getConnection();
        $qtyExpr = $connection->getCheckSql('cisi.qty > 0', 'cisi.qty', 0);
        $select = $connection->select()->from(
            ['e' => $this->getTable('catalog_product_entity')],
            ['entity_id']
        );
        $select->joinInner(
            ['cisi' => $this->getTable('cataloginventory_stock_item')],
            'cisi.product_id = e.entity_id',
            ['website_id', 'stock_id']
        )->columns(
            ['qty' => $qtyExpr]
        )->where('e.type_id = ?', $this->getTypeId())
            ->group(['e.entity_id', 'cisi.website_id', 'cisi.stock_id']);
        $select->columns(['status' => $this->getStatusExpression($connection, true)]);
        if ($entityIds !== null) {
            $select->where('e.entity_id IN(?)', $entityIds);
        }

        return $select;
    }
}