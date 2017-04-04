<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Controller\Adminhtml\Product\NoneInWarehouse;

use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class InlineEdit
 * @package Magestore\InventorySuccess\Controller\Adminhtml\Product\NoneInWarehouse
 */
class MassWarehouse extends \Magestore\InventorySuccess\Controller\Adminhtml\AbstractAction
{
    /**
     * @var \Magestore\InventorySuccess\Model\ResourceModel\Product\NoneInWarehouse\CollectionFactory
     */
    protected $collectionFactory;

    /** @var JsonFactory  */
    protected $jsonFactory;

    /**
     * MassWarehouse constructor.
     * @param \Magestore\InventorySuccess\Controller\Adminhtml\Context $context
     * @param \Magestore\InventorySuccess\Model\ResourceModel\Product\NoneInWarehouse\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magestore\InventorySuccess\Controller\Adminhtml\Context $context, 
        \Magestore\InventorySuccess\Model\ResourceModel\Product\NoneInWarehouse\CollectionFactory $collectionFactory,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $warehouseId = $this->getRequest()->getParam('id', false);
        $selectedProduct = $this->getRequest()->getParam('selected', []);
        $excluded = $this->getRequest()->getParam('excluded');
        if(!$warehouseId){
            return $this->addResult(true, 'Please select a warehouse to add products');
        }
        if(count($selectedProduct)==0 && !isset($excluded)){
            return $this->addResult(true, 'Please select product(s) for the warehouse');
        }
        if(isset($excluded)){
            $collection = $this->collectionFactory->create();
            if(is_array($excluded)){
                $collection->addFieldToFilter('entity_id', ['nin' => $excluded]);
            }
            $selectedProduct = $collection->getColumnValues('entity_id');
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $postItems[$warehouseId] = $selectedProduct;
        $this->_warehouseFactory->create()->addProductsInline($postItems);

        $this->_warehouseFactory->create()->addProductsInlineToCatalog($postItems);


//        if(count($results)<count($selectedProduct)){
        $this->messageManager->addSuccessMessage(__('Add Product to Warehouse Successfully!'));
//        }
//        if(!$this->getRequest()->getParam('isAjax', false)){
//            if(count($results)>0){
//                foreach ($results as $id){
//                    $this->addErrorProductId($id);
//                }
//            }
//        }
        return $this->addResult(false,'true');
        
    }
    
    protected function addResult($error, $message){
        if($error)
            $this->messageManager->addErrorMessage($message);
        $isAjax = $this->getRequest()->getParam('isAjax', false);
        if($isAjax){
            $resultJson = $this->jsonFactory->create();
            return $resultJson->setData([
                'messages' => __($message),
                'error' => $error
            ]);
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index');
    }

    /**
     * Add product id to error message
     *
     * @param int $id
     * @return string
     */
    protected function addErrorProductId($id)
    {
        return $this->messageManager->addErrorMessage(__('[Product ID: %1] Cannot add this product to the warehouse',$id));
    }

}