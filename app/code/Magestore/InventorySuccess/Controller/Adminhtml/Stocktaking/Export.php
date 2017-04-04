<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Controller\Adminhtml\Stocktaking;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magestore\InventorySuccess\Model\Stocktaking as StocktakingModel; 
/**
 * Class Import
 * @package Magestore\InventorySuccess\Controller\Adminhtml\Stocktaking
 */
class Export extends \Magestore\InventorySuccess\Controller\Adminhtml\Stocktaking\Stocktaking
{
    const SAMPLE_QTY = 1;
    
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $this->getBaseDirMedia()->create('magestore/inventory/stocktaking');
        $filename = $this->getBaseDirMedia()->getAbsolutePath('magestore/inventory/stocktaking/stocktaking_products.csv');
        $data = $this->getHeadersFile();
        $different = $this->getRequest()->getParam('different');
        $data = array_merge($data, $this->getProductCollection($different));
        $this->csvProcessor->saveData($filename, $data);
        return $this->fileFactory->create(
            'stocktaking_products.csv',
            file_get_contents($filename),
            DirectoryList::VAR_DIR
        );
    }

    /**
     * get headers file
     *
     * @return array
     */
    public function getHeadersFile()
    {
        $data = array(
            array(__('No'), __('Product Name'), __('SKU'), __('Stocktaking Qty'))
        );
        if($this->getCurrentStocktakingStatus() == StocktakingModel::STATUS_VERIFIED)
            $data = array(
                array(__('No'), __('Product Name'), __('SKU'), __('Stocktaking Qty'))
            );
        if($this->getCurrentStocktakingStatus() == StocktakingModel::STATUS_COMPLETED)
            $data = array(
                array(__('No'), __('Product Name'), __('SKU'), __('Qty in Warehouse'), __('Stocktaking Qty'))
            );

        return $data;
    }

    /**
     * get csv url
     *
     * @return string
     */
    public function getCsvLink()
    {
        $path = 'magestore/inventory/stocktaking/stocktaking_products.csv';
        $url =  $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . $path;
        return $url;
    }

    /**
     * get base dir media
     *
     * @return string
     */
    public function getBaseDirMedia()
    {
        return $this->filesystem->getDirectoryWrite('media');
    }

    /**
     * prepare stocktaking data
     *
     * @param
     * @return array
     */
    public function prepareData($productCollection)
    {
        $number = 1;
        $data = array();
        if($this->getCurrentStocktakingStatus() == StocktakingModel::STATUS_PENDING)
            foreach ($productCollection as $productModel) {
                $data[]= array(
                    $number,
                    $productModel->getData('product_name'),
                    $productModel->getData('product_sku'),
                    ''
                );
                $number ++;
            }
        if($this->getCurrentStocktakingStatus() == StocktakingModel::STATUS_VERIFIED)
            foreach ($productCollection as $productModel) {
                $data[]= array(
                    $number,
                    $productModel->getData('product_name'),
                    $productModel->getData('product_sku'),
                    $productModel->getData('stocktaking_qty')
                );
                $number ++;
            }
        if($this->getCurrentStocktakingStatus() == StocktakingModel::STATUS_COMPLETED)
            foreach ($productCollection as $productModel) {
                $data[]= array(
                    $number,
                    $productModel->getData('product_name'),
                    $productModel->getData('product_sku'),
                    $productModel->getData('old_qty'),
                    $productModel->getData('stocktaking_qty')
                );
                $number ++;
            }
        return $data;
    }

    /**
     * get stocktaking product collection
     *
     * @param
     * @return array
     */
    public function getProductCollection($different = false)
    {
        $stocktakingId = $this->getRequest()->getParam('id');
        $data = array();
        if(isset($stocktakingId)){
            $stocktakingkManagement = $this->stocktakingManagement;
            $stocktaking = $this->stocktakingFactory->create();
            $stocktaking->setId($stocktakingId);
            if($different)
                $productCollection = $stocktakingkManagement->getDifferentProducts($stocktaking);
            else
                $productCollection = $stocktakingkManagement->getProducts($stocktaking);
            $data = $this->prepareData($productCollection);
        }
        return $data;
    }

    /**
     * get current stocktaking
     *
     * @param
     * @return array
     */
    public function getCurrentStocktakingStatus()
    {
        if($this->getRequest()->getParam('status'))
            return $this->getRequest()->getParam('status');
        return 0;
    }
}
