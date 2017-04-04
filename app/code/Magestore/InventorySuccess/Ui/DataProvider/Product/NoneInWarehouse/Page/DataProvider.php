<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Ui\DataProvider\Product\NoneInWarehouse\Page;

/**
 * 
 */
use Magestore\InventorySuccess\Model\ResourceModel\Product\NoneInWarehouse\CollectionFactory;

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
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->context = $context;
        $this->collection = $collectionFactory->create();
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
