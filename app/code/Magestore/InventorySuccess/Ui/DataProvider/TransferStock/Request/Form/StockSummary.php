<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Ui\DataProvider\TransferStock\Request\Form;
use Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProduct\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form\Field;
use Magestore\InventorySuccess\Model\TransferStock\TransferStockProductFactory;


class StockSummary extends AbstractDataProvider
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var use Magestore\InventorySuccess\Model\TransferStock\TransferStockProductFactory
     */
    protected $_transferStockProductFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /** @var  \Magestore\InventorySuccess\Model\TransferStockFactory $_transferStockFactory */
    protected $_transferStockFactory;

    /**
     * @var \Magestore\InventorySuccess\Model\Source\Adminhtml\Warehouse
     */
    protected $_warehouseSource;

    private $_registry;

    /** @var  \Magestore\InventorySuccess\Model\Locator\LocatorFactory $_locatorFactory */
    protected $_locatorFactory;

    /**
     * Generate constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param UrlInterface $urlBuilder
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProduct\CollectionFactory $collectionFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magestore\InventorySuccess\Model\TransferStock\TransferStockProductFactory $transferStockProductFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magestore\InventorySuccess\Model\Source\Adminhtml\Warehouse $warehouseSource,
        \Magento\Framework\Registry $registry,
        \Magestore\InventorySuccess\Model\TransferStockFactory $transferStockFactory,
        \Magestore\InventorySuccess\Model\Locator\LocatorFactory $locatorFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->_request = $request;
        $this->_transferStockFactory = $transferStockFactory;
        $this->collection = $collectionFactory->create();

        $this->urlBuilder = $urlBuilder;
        $this->_transferStockProductFactory = $transferStockProductFactory;
        $this->_warehouseSource = $warehouseSource;
        $this->_locatorFactory = $locatorFactory;

        $this->prepareCollection();
    }

    public function prepareCollection()
    {
        /** @var \Magestore\InventorySuccess\Model\Locator\Locator $locator */
        $locator = $this->_locatorFactory->create();

        $transferstock_id = $locator->getCurrentTransferstockId();
        if($transferstock_id)
        {
          
            /** @var \Magestore\InventorySuccess\Model\TransferStock $transferstock */
            $transferstock = $this->_transferStockFactory->create()->load($transferstock_id);

            //\Zend_Debug::dump($transferstock->getData());die();
            $source_warehouse_id = $transferstock->getSourceWarehouseId();
            $collection = $this->collection->getTransferStockProduct($transferstock_id, $source_warehouse_id);
            $this->collection = $collection;
        }

    }
}