<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Ui\DataProvider\Warehouse\DeleteProduct;

/**
 * 
 */
use Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Stock\CollectionFactory;

/**
 * Class DataProvider
 * @package Magestore\InventorySuccess\Ui\DataProvider\Product\NoneInWarehouse\Page
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Warehouse collection
     *
     * @var \Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Framework\View\Element\UiComponent\ContextInterface
     */
    protected $context;
    
    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[] $addFieldStrategies
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $requestInterface,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->context = $context;
        $warehouseId = $requestInterface->getParam('warehouse_id', false);
        $this->collection = $collectionFactory->create();
        if($warehouseId){
            $this->collection->addWarehouseToFilter($warehouseId);
        }
        $this->collection->getNoneWarehouseProduct();
//        \Zend_Debug::Dump($this->collection->getData());
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        $items = $this->getCollection()->toArray();

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];
    }
}
