<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Controller\Adminhtml\TransferStock\Request;

use Magestore\InventorySuccess\Api\Data\TransferStock\TransferPermission;

class Edit extends \Magestore\InventorySuccess\Controller\Adminhtml\TransferStock\AbstractRequest
{
    const ADMIN_RESOURCE = TransferPermission::REQUEST_STOCK_VIEW;
    /**
     * @var \Magestore\InventorySuccess\Model\TransferStockFactory
     */
    protected $_transferStockFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;

    /**
     * @var \Magestore\InventorySuccess\Model\Warehouse\PermissionFactory
     */
    protected $_warehousePermissionFactory;

    /** @var  \Magestore\InventorySuccess\Model\Locator\LocatorFactory $_locatorFactory */
    protected $_locatorFactory;

    public function __construct(
        \Magestore\InventorySuccess\Controller\Adminhtml\Context $context,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magestore\InventorySuccess\Model\Locator\LocatorFactory $locatorFactory

    ){
        parent::__construct($context);
        $this->_transferStockFactory = $context->getTransferStockFactory();
        $this->_adminSession = $adminSession;
        $this->_locatorFactory = $locatorFactory;

    }
    public function execute()
    {

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        $this->_coreRegistry->register('transferstock_id', 1);
       
        /** @var \Magestore\InventorySuccess\Model\TransferStock $model */
        $model = $this->_transferStockFactory->create();
        if ($id) {
            /** @var  \Magestore\InventorySuccess\Model\Locator\Locator $locator */
            $locator = $this->_locatorFactory->create();
            $locator->setSessionByKey("current_transferstock_id", $id);
            //$locator->setCurrentTransferstockId($id);

            $model->load($id);
            if (!$model->getTransferstockId()) {
                $this->messageManager->addError(__('This transferstock is no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
            
            if($model->getSourceWarehouseId()) {
                /* register current warehouse */
                $warehouse = $this->_warehouseFactory->create()->load($model->getSourceWarehouseId());
                $this->_coreRegistry->register('current_warehouse', $warehouse);
            }                
        }
        
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('current_request_stock', $model);


        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Transfer Stock') : __('Add a New Request Stock'),
            $id ? __('Transfer Stock') : __('Add a New Request Stock')
        );

        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ?
                __('Request Stock "%1"', $model->getTransferstockCode()) : __('Add a New Request Stock')
        );

        return $resultPage;
    }

    /**
     * Init page.
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Magestore_InventorySuccess::request_stock_create')
            ->addBreadcrumb(__('InventorySuccess'), __('InventorySuccess'))
            ->addBreadcrumb(__('Manage Transfer Stock'), __('Manage Transfer Stock'));

        return $resultPage;
    }

}


