<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Block\Adminhtml\SupplyNeeds\Edit;

use \Magento\Framework\App\Helper\AbstractHelper;

class Productgrid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magestore\InventorySuccess\Model\SupplyNeeds\SupplyNeedsManagement
     */
    protected $_supplyNeedsManagement;

    /**
     * @var \Magestore\InventorySuccess\Model\ResourceModel\SupplyNeeds
     */
    protected $_supplyNeedsResourceModel;
    /**
     * Productgrid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magestore\InventorySuccess\Model\SupplyNeeds\SupplyNeedsManagement $supplyNeedsManagement,
        \Magestore\InventorySuccess\Model\ResourceModel\SupplyNeeds $supplyNeedsResourceModel,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->_productFactory = $productFactory;
        $this->_supplyNeedsManagement = $supplyNeedsManagement;
        $this->_supplyNeedsResourceModel = $supplyNeedsResourceModel;
    }


    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('product_grid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
    }

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
//    protected function _addColumnFilterToCollection($column)
//    {
//        // Set custom filters for in product flag
//        if ($column->getId() == 'in_products') {
//            $productIds = $this->_getSelectedProducts();
//            if (empty($productIds)) {
//                $productIds = 0;
//            }
//            if ($column->getFilter()->getValue()) {
//                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
//            } else {
//                if ($productIds) {
//                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
//                }
//            }
//        } else {
//            parent::_addColumnFilterToCollection($column);
//        }
//        return $this;
//    }

    /**
     * @return $this
     */
    protected function _prepareCollection() {
        if (!$this->getRequest()->getParam('top_filter')) {
            $collection = $this->_productFactory->create()->getCollection()->addAttributeToFilter('entity_id', null);
        } else {
            $topFilter = $this->getRequest()->getParam('top_filter');
            $sort = $this->getRequest()->getParam('sort');
            $dir = $this->getRequest()->getParam('dir');
            $collection = $this->_supplyNeedsManagement->getProductSupplyNeedsCollection($topFilter, $sort, $dir);
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }


    /**
     * @throws \Exception
     */
    protected function _prepareColumns() {

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );


        $this->addColumn('name',
            [
                'header' => __('Name'),
                'align' => 'left',
                'index' => 'name'
            ]
        );

        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku'
            ]
        );

        $this->addColumn(
            'avg_qty_ordered',
            [
                'header' => __('Qty. Sold/day'),
                'type' => 'number',
                'index' => 'avg_qty_ordered',
                'renderer'  => '\Magestore\InventorySuccess\Block\Adminhtml\SupplyNeeds\Edit\Renderer\FormatNumber',
                'filter_condition_callback' => [$this, '_filterNumberCallback']
            ]
        );

        $this->addColumn(
            'total_sold',
            [
                'header' => __('Total Sold'),
                'type' => 'number',
                'index' => 'total_sold',
                'filter_condition_callback' => [$this, '_filterNumberCallback']
            ]
        );

        $this->addColumn(
            'current_qty',
            [
                'header' => __('Current Qty'),
                'type' => 'number',
                'index' => 'current_qty',
                'filter_condition_callback' => [$this, '_filterNumberCallback']
            ]
        );

        $this->addColumn(
            'availability_date',
            [
                'header' => __('Availability Date'),
                'type' => 'date',
                'index' => 'availability_date',
                'filter_condition_callback' => [$this, '_filterDateCallback']
            ]
        );

        $this->addColumn(
            'supply_needs',
            [
                'header' => __('Supply Needs'),
                'type' => 'number',
                'index' => 'supply_needs',
                'filter_condition_callback' => [$this, '_filterNumberCallback']
            ]
        );

        $this->addExportType('*/*/exportSupplyNeedsCsv', __('CSV'));
        $this->addExportType('*/*/exportSupplyNeedsExcel', __('Excel XML'));
    }

    /**
     * @return mixed|string
     */
    public function getGridUrl() {
        return $this->getData(
            'grid_url'
        ) ? $this->getData(
            'grid_url'
        ) : $this->getUrl(
            '*/*/grid',
            ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
        );
    }


    protected function _getSelectedProducts() {
        $products = '';
        return $products;
    }

    public function getSelectedRelatedProducts() {
        $products = [];
        return $products;
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return bool
     */
    public function getRowUrl($row) {
        return false;
    }

    /**
     * @param $collection
     * @param $column
     * @return mixed
     */
    protected function _filterDateCallback($collection, $column) {
        if ($topFilter = $this->getRequest()->getParam('top_filter')) {
            return $this->_supplyNeedsResourceModel->filterDateCallback($collection, $column, $topFilter);
        }
    }

    /**
     * @param $collection
     * @param $column
     * @return mixed
     */
    protected function _filterNumberCallback($collection, $column) {
        if ($topFilter = $this->getRequest()->getParam('top_filter')) {
            return $this->_supplyNeedsResourceModel->filterNumberCallback($collection, $column, $topFilter);
        }
    }

    /**
     * Retrieve a file container array by grid data as CSV
     *
     * Return array with keys type and value
     *
     * @return array
     */
//    public function getCsvFile()
//    {
//        $this->_isExport = true;
//        $this->_prepareGrid();
//
//        $name = md5(microtime());
//        $file = $this->_path . '/' . $name . '.csv';
//
//        $this->_directory->create($this->_path);
//        $stream = $this->_directory->openFile($file, 'w+');
//
//        $stream->lock();
//        $topFilter = $this->getRequest()->getParam('top_filter');
//        if ($topFilter) {
//            $moreInformation = $this->_supplyNeedsManagement->getMoreInformationToExport($topFilter);
//            foreach ($moreInformation as $info) {
//                $stream->writeCsv($info);
//            }
//        }
//        $stream->writeCsv($this->_getExportHeaders());
//        $this->_exportIterateCollection('_exportCsvItem', [$stream]);
//
//        if ($this->getCountTotals()) {
//            $stream->writeCsv($this->_getExportTotals());
//        }
//
//        $stream->unlock();
//        $stream->close();
//
//        return [
//            'type' => 'filename',
//            'value' => $file,
//            'rm' => true  // can delete file after use
//        ];
//    }

}